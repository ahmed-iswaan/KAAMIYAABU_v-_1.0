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
use thiagoalessio\TesseractOCR\TesseractOCR;
use Carbon\Carbon;

class PaymentApiController extends Controller
{
public function uploadSlip(Request $request)
{
    $validator = Validator::make($request->all(), [
        'directories_id' => 'required|uuid|exists:directories,id',
        'payment_slip'   => 'required|file|mimes:jpg,jpeg,png,pdf|max:4096',
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

        // Run OCR
        $extractedText = (new TesseractOCR($fullPath))
            ->executable('C:\Program Files\Tesseract-OCR\tesseract.exe')
            ->lang('eng')
            ->run();

          // Detect slip type
            $isMIB = str_contains($extractedText, 'Maldives Islamic Bank') || str_contains($extractedText, 'Transaction Date');
            $isBML = str_contains($extractedText, 'Bank of Maldives') || str_contains($extractedText, 'Transaction date');

            $parsedData = [];

            // MIB Slip Parsing
            if ($isMIB) {
                if (preg_match('/TOTAL AMOUNT\s+MVR\s+([\d,.]+)/i', $extractedText, $match)) {
                    $parsedData['amount'] = $match[1];
                }

                if (preg_match('/Reference\s*#?\s*([A-Z0-9]+)/i', $extractedText, $match)) {
                    $parsedData['reference'] = $match[1];
                }

                if (preg_match('/Transaction Date\s+([\d\/\-]{10}\s+\d{2}:\d{2}:\d{2})/i', $extractedText, $match)) {
                    $parsedData['date'] = $match[1];
                }

                if (preg_match('/To Account\s+([\d]+)/i', $extractedText, $match)) {
                    $parsedData['to_account'] = $match[1];
                }

                if (preg_match('/To Bank\s+([A-Z]+)/i', $extractedText, $match)) {
                    $parsedData['to_bank'] = $match[1];
                }

                if (preg_match('/Purpose\s+([A-Za-z ]+)/i', $extractedText, $match)) {
                    $parsedData['purpose'] = trim($match[1]);
                }
            }

            // BML Slip Parsing
            elseif ($isBML) {
                if (preg_match('/([\d,.]+)\s*MVR/i', $extractedText, $match)) {
                    $parsedData['amount'] = $match[1];
                }

                if (preg_match('/Reference\s*[:#]?\s*([A-Z0-9]+)/i', $extractedText, $match)) {
                    $parsedData['reference'] = $match[1];
                }

                if (preg_match('/Transaction date\s+([\d\/\-]{2,10}\s+\d{2}:\d{2})/i', $extractedText, $match)) {
                    $parsedData['date'] = $match[1];
                }

                if (preg_match('/From\s+([A-Z\s]+[A-Z])/i', $extractedText, $match)) {
                    $parsedData['from'] = trim($match[1]);
                }
            }

        $amount   = floatval(str_replace(',', '', $parsedData['amount'] ?? $request->input('amount')));
        $ref      = $parsedData['reference'] ?? null;
        $dateString = $parsedData['date'] ?? null;
        $date = now()->format('Y-m-d H:i:s');

        if ($dateString) {
            try {
                $date = Carbon::createFromFormat('d/m/Y H:i', $dateString)->format('Y-m-d H:i:s');
            } catch (\Exception $e1) {
                try {
                    $date = Carbon::createFromFormat('d-m-Y H:i', $dateString)->format('Y-m-d H:i:s');
                } catch (\Exception $e2) {
                    \Log::warning("Invalid date format from OCR: {$dateString}");
                }
            }
        }

        DB::beginTransaction();

        $payerDirectory = Directory::findOrFail($request->directories_id);
        $availableCredit = floatval($payerDirectory->credit_balance ?? 0);
        $creditUsed = floatval($request->credit_used ?? 0);

        if ($creditUsed > $availableCredit) {
            return response()->json(['error' => 'Insufficient credit balance'], 400);
        }

        // ✅ Calculate total available funds (cash + credit)
        $totalFunds = $amount + $creditUsed;
        $remainingFunds = $totalFunds; // <-- Define properly here

        $appliedInvoices = [];

        // ✅ Order invoices FIFO (oldest first)
        $invoices = Invoice::whereIn('id', collect($request->invoices)->pluck('id'))
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($invoices as $invoice) {
            $invoiceBalance = $invoice->total_amount - $invoice->paid_amount;

            if ($remainingFunds <= 0) {
                return response()->json([
                    'error' => "Insufficient funds: Could not apply to invoice {$invoice->number}."
                ], 400);
            }

            // Apply as much as possible (up to balance)
            $applyAmount = min($invoiceBalance, $remainingFunds);

            if ($applyAmount <= 0) {
                return response()->json([
                    'error' => "Invoice {$invoice->number} cannot receive a valid payment."
                ], 400);
            }

            $appliedInvoices[] = [
                'invoice' => $invoice,
                'applied' => round($applyAmount, 2),
            ];

            $remainingFunds -= $applyAmount;
        }


        $overpaid = max(0, $remainingFunds);

        $payment = Payment::create([
            'directories_id' => $payerDirectory->id,
            'amount'         => $amount,
            'method'         => $request->input('method', 'bank transfer'),
            'bank'           => $isMIB ? 'MIB' : ($isBML ? 'BML' : 'Unknown'),
            'ref'            => $ref,
            'payment_slip'   => $slipPath,
            'date'           => $date,
            'credit_used'    => $creditUsed,
            'overpaid_amount'=> $overpaid,
            'status'         => 'Pending Approval',
            'collection_point' => $request->header('X-Client-Name'),
            'total_applied_to_invoices' => collect($appliedInvoices)->sum('applied'),
        ]);

        foreach ($appliedInvoices as $applied) {
            InvoicePayment::create([
                'invoice_id'     => $applied['invoice']->id,
                'payment_id'     => $payment->id,
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
            'extracted_text' => $extractedText,
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
