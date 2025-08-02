<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PaymentSlipApiController extends Controller
{
    public function parseSlip(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_slip' => 'required|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Save uploaded file temporarily
        $path = $request->file('payment_slip')->store('temp_slips', 'public');
        $fullPath = Storage::disk('public')->path($path);

        try {
            // Run OCR
            $extractedText = (new TesseractOCR($fullPath))
                ->executable('C:\Program Files\Tesseract-OCR\tesseract.exe') // ✅ your exe path
                ->lang('eng') // change to 'eng+div' if Dhivehi support added
                ->run();

            // Try to parse values (basic regex example)
            $amount = null;
            $ref = null;
            $date = null;

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

            return response()->json([
                'message' => 'Slip parsed successfully',
                'bank'    => $isMIB ? 'MIB' : ($isBML ? 'BML' : 'Unknown'),
                'extracted_text' => $extractedText,
                'parsed_data' => $parsedData,
            ]);



        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to parse slip',
                'details' => $e->getMessage(),
            ], 500);
        } finally {
            // Clean up temp file
            Storage::disk('public')->delete($path);
        }
    }
}
