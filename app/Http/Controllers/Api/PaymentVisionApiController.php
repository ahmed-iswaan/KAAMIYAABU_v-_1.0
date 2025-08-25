<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Directory;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Enums\InvoiceStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class PaymentVisionApiController extends Controller
{
    public function uploadSlip(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'directories_id' => 'required|uuid|exists:directories,id',
            'payment_slip'   => 'required|file|mimes:jpg,jpeg,png|max:4096',
            'invoices'       => 'required|array|min:1',
            'invoices.*.id'  => 'required|uuid|exists:invoices,id',
            'credit_used'    => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Save slip
            $slipPath = $request->file('payment_slip')->store('payment_slips', 'public');
            $fullPath = Storage::disk('public')->path($slipPath);
            $base64Image = base64_encode(file_get_contents($fullPath));


$apiKey = env('GEMINI_API_KEY');

// Convert the image to base64
$imagePath = Storage::disk('public')->path($slipPath);
$base64Image = base64_encode(file_get_contents($imagePath));

// Correct model and endpoint
$endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro-vision:generateContent?key={$apiKey}";

$response = Http::withHeaders([
    'Content-Type' => 'application/json',
])->post($endpoint, [
    'contents' => [[
        'parts' => [
            [
                'text' => 'Extract the amount, date, bank name, and reference number from this payment slip. Respond only as JSON with keys: amount, date, bank, reference.'
            ],
            [
                'inlineData' => [
                    'mimeType' => 'image/jpeg',
                    'data' => $base64Image
                ]
            ]
        ]
    ]],
    'generationConfig' => [
        'temperature' => 0.2,
        'topK' => 1,
        'topP' => 1,
        'maxOutputTokens' => 1024
    ]
]);

if (!$response->ok()) {
    return response()->json([
        'error' => 'Gemini API error',
        'details' => $response->body()
    ], 500);
}

$text = $response->json('candidates.0.content.parts.0.text');
$parsedData = json_decode($text, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    return response()->json([
        'error' => 'Failed to parse JSON response',
        'raw_text' => $text
    ], 500);
}


            $content = json_decode($response->body(), true);
            $text = $content['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $parsedData = json_decode($text, true);

            if (!is_array($parsedData) || !isset($parsedData['amount'])) {
                return response()->json(['error' => 'Unable to parse slip data from Gemini response'], 400);
            }

            $amount = floatval(str_replace(',', '', $parsedData['amount'] ?? $request->input('amount')));
            $ref = $parsedData['reference'] ?? null;
            $dateString = $parsedData['date'] ?? null;
            $bank = $parsedData['bank'] ?? 'Unknown';

            $date = now()->format('Y-m-d H:i:s');
            if ($dateString) {
                try {
                    $date = Carbon::parse($dateString)->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    \Log::warning("Invalid date format from Gemini: {$dateString}");
                }
            }

            DB::beginTransaction();

            $payerDirectory = Directory::findOrFail($request->directories_id);
            $availableCredit = floatval($payerDirectory->credit_balance ?? 0);
            $creditUsed = floatval($request->credit_used ?? 0);

            if ($creditUsed > $availableCredit) {
                return response()->json(['error' => 'Insufficient credit balance'], 400);
            }

            $totalFunds = $amount + $creditUsed;
            $remainingFunds = $totalFunds;

            $appliedInvoices = [];

            $invoices = Invoice::whereIn('id', collect($request->invoices)->pluck('id'))
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($invoices as $invoice) {
                if (!in_array($invoice->status, [InvoiceStatus::PENDING, InvoiceStatus::PARTIAL])) {
                    return response()->json([
                        'error' => "Invoice {$invoice->number} is not eligible for payment."
                    ], 400);
                }

                if ($remainingFunds <= 0) break;

                $invoiceBalance = $invoice->total_amount - $invoice->paid_amount;
                $applyAmount = min($invoiceBalance, $remainingFunds);

                if ($applyAmount <= 0) continue;

                $appliedInvoices[] = [
                    'invoice' => $invoice,
                    'applied' => round($applyAmount, 2),
                ];

                $remainingFunds -= $applyAmount;
            }

            $overpaid = max(0, $remainingFunds);

            $payment = Payment::create([
                'directories_id' => $payerDirectory->id,
                'amount' => $amount,
                'method' => $request->input('method', 'bank transfer'),
                'bank' => $bank,
                'ref' => $ref,
                'payment_slip' => $slipPath,
                'date' => $date,
                'credit_used' => $creditUsed,
                'overpaid_amount' => $overpaid,
                'status' => 'Pending Approval',
                'collection_point' => $request->header('X-Client-Name'),
                'total_applied_to_invoices' => collect($appliedInvoices)->sum('applied'),
            ]);

            foreach ($appliedInvoices as $applied) {
                InvoicePayment::create([
                    'invoice_id' => $applied['invoice']->id,
                    'payment_id' => $payment->id,
                    'applied_amount' => $applied['applied'],
                ]);

                $applied['invoice']->status = InvoiceStatus::PAYMENTONREVIEW;
                $applied['invoice']->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Payment created successfully (Pending Approval)',
                'payment' => $payment->load('invoices'),
                'applied_invoices' => collect($appliedInvoices)->map(fn($a) => [
                    'invoice_number' => $a['invoice']->number,
                    'applied_amount' => $a['applied'],
                ]),
                'parsed_data' => $parsedData,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to create payment',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
