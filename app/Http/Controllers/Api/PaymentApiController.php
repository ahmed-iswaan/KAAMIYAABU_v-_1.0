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
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Carbon\Carbon;

class PaymentApiController extends Controller
{

    private function rasterizeFirstPageIfPdf(string $fullPath): string
{
    if (strtolower(pathinfo($fullPath, PATHINFO_EXTENSION)) !== 'pdf') {
        return $fullPath;
    }
    try {
        $im = new \Imagick();
        $im->setResolution(220, 220);
        $im->readImage($fullPath.'[0]');
        $im->setImageFormat('jpg');
        $jpg = $fullPath.'.jpg';
        $im->writeImage($jpg);
        $im->clear(); $im->destroy();
        return $jpg;
    } catch (\Throwable $e) {
        \Log::warning('PDF rasterize failed: '.$e->getMessage());
        return $fullPath;
    }
}

private function ocrWithGoogle(string $path): string
{
    $client = new ImageAnnotatorClient(); // uses GOOGLE_APPLICATION_CREDENTIALS
    try {
        $imageData = file_get_contents($path);
        // documentTextDetection is better for receipts/slips
        $response = $client->documentTextDetection($imageData);
        if ($response->getError() && $response->getError()->getMessage()) {
            \Log::error('Vision OCR error: '.$response->getError()->getMessage());
            return '';
        }
        $ann = $response->getFullTextAnnotation();
        return $ann ? $ann->getText() : '';
    } finally {
        $client->close();
    }
}


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

        // --- Google OCR instead of Tesseract ---
        $rasterPath    = $this->rasterizeFirstPageIfPdf($fullPath);
        $extractedText = $this->ocrWithGoogle($rasterPath);

        // Run OCR
        // $extractedText = (new TesseractOCR($fullPath))
        //     ->executable('C:\Program Files\Tesseract-OCR\tesseract.exe')
        //     ->lang('eng')
        //     ->run();

// Normalize once
        $t = preg_replace("/\r\n|\r/", "\n", $extractedText);

        // Make detection case-insensitive and include Favara/ATM cues
        $isMIB = preg_match('/\bMaldives\s+Islamic\s+Bank\b/i', $t)              // "MALDIVES ISLAMIC BANK"
            || preg_match('/\b(Id#|Favara request transfer completed|Created Date|Status Date|ATM Withdrawal|Transaction#)\b/i', $t);

        $isBML = preg_match('/\bBank\s+of\s+Maldives\b/i', $t)
            || preg_match('/\b(Transaction date|Post date)\b/i', $t);


        $parsedData = [];

// ---------------- MIB Parsing (ATM + transfers + Favara) ----------------
    if ($isMIB) {
        $parsedData['bank'] = 'MIB';

        // Normalize text/lines once
        $t     = preg_replace("/\r\n|\r/", "\n", $extractedText);
        $lines = preg_split('/\n+/', $t);

        // 1) Amount (ALWAYS POSITIVE)
        $amountStr = null;
        // "- MVR 1,500.00" or "MVR 40,000.00"
        if (preg_match('/[-–]?\s*MVR\s*([\d.,]+)/i', $t, $m)) {
            $amountStr = $m[1];
        } elseif (preg_match('/\b([\d.,]+)\s*MVR\b/i', $t, $m)) { // "40,000.00 MVR"
            $amountStr = $m[1];
        } elseif (preg_match('/\bTOTAL\s+AMOUNT\b.*?MVR\s*([\d.,]+)/i', $t, $m)) {
            $amountStr = $m[1];
        }
        if ($amountStr !== null) {
            $parsedData['amount'] = (float) str_replace([',',' '], '', $amountStr);
        }

        // 2) Date (Created Date → Transaction Date → Status Date → any ISO)
        $dateStr = null;
        if (preg_match('/\bCreated\s*Date\b\s*[:#]?\s*([0-9]{4}-[0-9]{2}-[0-9]{2}\s+\d{2}:\d{2}:\d{2})/i', $t, $m)) {
            $dateStr = trim($m[1]);
        } elseif (preg_match('/\bTransaction\s*Date\b\s*[:#]?\s*([0-9:\-\/\s]+)/i', $t, $m)) {
            $dateStr = trim($m[1]);
        } elseif (preg_match('/\bStatus\s*Date\b\s*[:#]?\s*([0-9]{4}-[0-9]{2}-[0-9]{2}\s+\d{2}:\d{2}:\d{2})/i', $t, $m)) {
            $dateStr = trim($m[1]);
        } elseif (preg_match('/\b([0-9]{4}-[0-9]{2}-[0-9]{2}\s+\d{2}:\d{2}:\d{2})\b/', $t, $m)) {
            $dateStr = trim($m[1]);
        }
        if (!empty($dateStr)) {
            $parsedData['date'] = $dateStr;
        }

        // 3) Reference (prefer "Id#" / "Reference"; stitch ATM/location tails; fallback to Transaction#)
        $ref = null;

        // (a) Same-line after "Reference" or "Id#"
        foreach ($lines as $line) {
            if (preg_match('/^\s*Reference\b\s*#?\s*[:\-]?\s*([A-Z0-9@\/\\\\>._\-]+)\s*$/i', $line, $mm)) {
                $ref = trim($mm[1]);
                break;
            }
            if (preg_match('/^\s*Id#\s*[:\-]?\s*([A-Z0-9@\/\\\\>._\-]+)\s*$/i', $line, $mm)) {
                $ref = trim($mm[1]); // Favara: "Id# 748224280"
                break;
            }
        }

        // (b) Header-only "Reference" or "Id#" → scan next ~12 lines
        if (!$ref) {
            $findHeader = function(array $labels) use ($lines): ?int {
                foreach ($lines as $i => $ln) {
                    foreach ($labels as $lab) {
                        if (preg_match('/^\s*' . preg_quote($lab, '/') . '\s*#?\s*$/i', $ln)) return $i;
                    }
                }
                return null;
            };
            $hdrIdx = $findHeader(['Reference','Id#']);
            if ($hdrIdx !== null) {
                $badLabels = [
                    'TO ACCOUNT','TO BANK','TRANSACTION DATE','VALUE DATE','PURPOSE','TOTAL AMOUNT',
                    'AMOUNT','SUCCESS','SUCCESSFUL','BANK','MVR','MESSAGE','TRANSACTION TYPE',
                    'CREATED DATE','STATUS DATE','FROM','TO','ID#'
                ];
                $chosenIdx = null;

                for ($j = $hdrIdx + 1; $j < min(count($lines), $hdrIdx + 13); $j++) {
                    $cand = trim($lines[$j]);
                    if ($cand === '') continue;
                    if (in_array(strtoupper($cand), $badLabels, true)) continue;

                    // Skip obvious long account numbers
                    if (preg_match('/^\d{12,}$/', $cand)) continue;

                    // Accept: contains '@' OR alnum with letters+digits OR 6–12 digit id (Favara Id#)
                    if (str_contains($cand, '@')
                        || preg_match('/^(?=.*[A-Z])(?=.*\d)[A-Z0-9@\/\\\\>._\-]{6,}$/i', $cand)
                        || preg_match('/^\d{6,12}$/', $cand)) {
                        $ref = $cand; $chosenIdx = $j; break;
                    }
                }

                // Stitch next line if it looks like an ATM/location tail
                if ($ref && $chosenIdx !== null) {
                    $next = ($chosenIdx + 1 < count($lines)) ? trim($lines[$chosenIdx + 1]) : '';
                    if ($next !== '' && !in_array(strtoupper($next), $badLabels, true)
                        && preg_match('/\b(ATM|CENTRO|CENTRE|CENTER|HULHUMALE|MALE|BRANCH|COUNTER|MV|ATM\s*>?)\b/i', $next)) {
                        $ref .= ' ' . $next;
                    }
                }
            }
        }

        // (c) Fallback: Transaction# (may be split across lines; stitch trailing '-' + next numeric)
        if (!$ref) {
            $txIdx = null; $txVal = null;
            foreach ($lines as $i => $line) {
                if (preg_match('/^\s*Transaction#\s*$/i', $line)) { $txIdx = $i; break; }
                if (preg_match('/^\s*Transaction#\s*([0-9\-]+)\s*$/i', $line, $mm)) {
                    $txVal = trim($mm[1]); break;
                }
            }
            if ($txIdx !== null) {
                for ($k = $txIdx + 1; $k <= $txIdx + 2 && $k < count($lines); $k++) {
                    $s = trim($lines[$k]);
                    if ($s !== '') { $txVal = $s; break; }
                }
            }
            if ($txVal) {
                if (str_ends_with($txVal, '-') && isset($lines[$txIdx + 2])) {
                    $next = trim($lines[$txIdx + 2]);
                    if (preg_match('/^\d+$/', $next)) $txVal .= $next;
                }
                $ref = $txVal;
            }
        }

        if ($ref) {
            $parsedData['reference'] = preg_replace('/\s+>/', ' >', $ref);
        }
    }


// ---------------- BML Parsing ----------------
    elseif ($isBML) {
        if (preg_match('/MVR\s*-?([\d,.]+)/i', $extractedText, $m)) {
                    $parsedData['amount'] = $m[1];
                }

    // -------- Reference: prefer FT… (with optional \TAIL), then Reference section, then Transaction ID --------
        // 1) Global FT… first (e.g., FT25212LCG6X\B26)
        if (preg_match('/\bFT[A-Z0-9]{4,}(?:\\\\[A-Z0-9]+)?\b/i', $extractedText, $m)) {
            $parsedData['reference'] = $m[0];
        }

        // 2) If still empty, try "Reference ..." (same line or next lines), skipping labels like AMOUNT/MVR/etc.
        if (empty($parsedData['reference'])) {
            $lines = preg_split('/\R/', $extractedText);
            // same-line
            foreach ($lines as $line) {
                if (preg_match('/^\s*Reference\s*[:#]?\s*([A-Z0-9@\/\\\\\-]+)\s*$/i', $line, $mm)) {
                    $parsedData['reference'] = trim($mm[1]);
                    break;
                }
            }
            // header-only then value below
            if (empty($parsedData['reference'])) {
                $refIdx = null;
                foreach ($lines as $i => $line) {
                    if (preg_match('/^\s*Reference\s*$/i', $line)) { $refIdx = $i; break; }
                }
                if ($refIdx !== null) {
                    $isValidRef = function (string $s): bool {
                        $s = trim($s);
                        if ($s === '') return false;
                        $bad = ['AMOUNT','DESCRIPTION','STATUS','SUCCESS','FROM','POST','DATE','TRANSACTION','MVR','BANK','MESSAGE'];
                        if (in_array(strtoupper($s), $bad, true)) return false;
                        if (preg_match('/^\d+([.,]\d+)?$/', $s)) return false;
                        if (preg_match('/^\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}(\s+\d{2}:\d{2}(:\d{2})?)?$/', $s)) return false;
                        if (preg_match('/^(FT|RB|BLAZ)[A-Z0-9]{4,}(?:\\\\[A-Z0-9]+)?$/i', $s)) return true;
                        if (str_contains($s, '\\') && preg_match('/^[A-Z0-9@\/\\\\\-]{6,}$/i', $s)) return true;
                        if (preg_match('/^(?=.*[A-Z])(?=.*\d)[A-Z0-9@\/\\\\\-]{8,}$/i', $s)) return true;
                        return false;
                    };
                    for ($j = $refIdx + 1; $j < min(count($lines), $refIdx + 10); $j++) {
                        $candidate = trim($lines[$j]);
                        if ($isValidRef($candidate)) { $parsedData['reference'] = $candidate; break; }
                    }
                }
            }
        }

        // 3) Final fallback: Transaction ID
        if (empty($parsedData['reference']) &&
            preg_match('/\bTransaction\s*ID\b\s*[:\-]?\s*([A-Z0-9]+)/i', $extractedText, $m)
        ) {
            $parsedData['reference'] = trim($m[1]);
        }

        // -------- Date: handle next-line and general fallback --------
        // Try "Transaction date <on same line or next line>"
        if (preg_match('/\bTransaction\s*date\b\s*[:#]?\s*([0-9\/\-\s:]+)/i', $extractedText, $m)) {
            $parsedData['date'] = trim($m[1]);
        } else {
            // If header-only, take the next date-like token after that header
            $lines = isset($lines) ? $lines : preg_split('/\R/', $extractedText);
            $txIdx = null;
            foreach ($lines as $i => $line) {
                if (preg_match('/^\s*Transaction\s*date\s*$/i', $line)) { $txIdx = $i; break; }
            }
            if ($txIdx !== null) {
                for ($j = $txIdx + 1; $j < min(count($lines), $txIdx + 6); $j++) {
                    $candidate = trim($lines[$j]);
                    if (preg_match('/^\d{2}[\/\-]\d{2}[\/\-]\d{4}(?:\s+\d{2}:\d{2}(?::\d{2})?)?$/', $candidate)) {
                        $parsedData['date'] = $candidate; break;
                    }
                }
            }
        }
        // Fallback: first date-with-time anywhere, else first date
        if (empty($parsedData['date'])) {
            if (preg_match('/\b(\d{2}[\/\-]\d{2}[\/\-]\d{4}\s+\d{2}:\d{2}(?::\d{2})?)\b/', $extractedText, $m)) {
                $parsedData['date'] = $m[1];
            } elseif (preg_match('/\b(\d{2}[\/\-]\d{2}[\/\-]\d{4})\b/', $extractedText, $m)) {
                $parsedData['date'] = $m[1];
            }
        }

        // Optional: From / Description (unchanged)
        if (preg_match('/\bFrom\b\s+([0-9]+)/i', $extractedText, $m)) {
            $parsedData['from'] = $m[1];
        }
        if (preg_match('/\bDescription\b\s+([\s\S]+)/i', $extractedText, $m)) {
            $parsedData['description'] = trim($m[1]);
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

        // Amount as signed float
        $amount = isset($parsedData['amount'])
            ? (float) str_replace([','], '', $parsedData['amount'])
            : (float) $request->input('amount');

        // Prefer the FT... reference; it's already captured
        $ref = $parsedData['reference'] ?? null;

        // Normalize date -> Y-m-d H:i:s (try common formats, then parse)
        $date = now()->format('Y-m-d H:i:s');
        if (!empty($parsedData['date'])) {
            $ds = trim($parsedData['date']);
            $tried = false;

            // Try d/m/Y H:i or d/m/Y first
            try {
                $date = Carbon::createFromFormat('d/m/Y H:i', $ds)->format('Y-m-d H:i:s');
                $tried = true;
            } catch (\Exception $e) {}

            if (!$tried) {
                try {
                    $date = Carbon::createFromFormat('d/m/Y', $ds)->format('Y-m-d 00:00:00');
                    $tried = true;
                } catch (\Exception $e) {}
            }

            // Try d-m-Y (some BML slips show a secondary date like "30-07-2025 …")
            if (!$tried) {
                try {
                    $date = Carbon::createFromFormat('d-m-Y H:i', $ds)->format('Y-m-d H:i:s');
                    $tried = true;
                } catch (\Exception $e) {}
            }
            if (!$tried) {
                try {
                    $date = Carbon::createFromFormat('d-m-Y', $ds)->format('Y-m-d 00:00:00');
                    $tried = true;
                } catch (\Exception $e) {}
            }

            // Last resort
            if (!$tried) {
                try { $date = Carbon::parse($ds)->format('Y-m-d H:i:s'); } catch (\Exception $e) {}
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

        // ✅ Get invoices FIFO and check status
        $invoices = Invoice::whereIn('id', collect($request->invoices)->pluck('id'))
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($invoices as $invoice) {
            if (!in_array($invoice->status, [InvoiceStatus::PENDING, InvoiceStatus::PARTIAL])) {
                return response()->json([
                    'error' => "Invoice {$invoice->number} is not eligible for payment (Status: {$invoice->status->value})."
                ], 400);
            }

            if ($remainingFunds <= 0) {
                return response()->json([
                    'error' => "Insufficient funds: Could not apply to invoice {$invoice->number}. payment amount {$totalFunds} {$extractedText}"
                ], 400);
            }

            $invoiceBalance = $invoice->total_amount - $invoice->paid_amount;
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

        // ✅ Validate duplicate reference
// ✅ Normalize reference string (trim spaces & uppercase)
if ($ref) {
    // ✅ Remove all whitespace and invisible characters
    $cleanRef = preg_replace('/[^A-Z0-9]/i', '', strtoupper($ref));

    \Log::info('Checking Payment Reference: ' . $cleanRef);

    $existingPayment = Payment::whereRaw('UPPER(TRIM(REPLACE(ref, " ", ""))) = ?', [$cleanRef])
        ->whereIn('status', ['Approved', 'Pending Approval'])
        ->first();

    if ($existingPayment) {
        if ($existingPayment->status === 'Approved') {
            return response()->json([
                'error' => "This payment reference ({$cleanRef}) has already been approved."
            ], 400);
        }

        if ($existingPayment->status === 'Pending Approval') {
            return response()->json([
                'warning' => "A payment with reference {$cleanRef} is already pending approval."
            ], 202);
        }
    }

    // Replace original ref with cleaned one before saving
    $ref = $cleanRef;
}


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
