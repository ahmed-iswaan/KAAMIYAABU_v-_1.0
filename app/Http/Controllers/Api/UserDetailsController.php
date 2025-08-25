<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Directory;
use App\Models\DirectoryType;
use App\Models\Invoice;
use App\Models\WasteManagementRegister;
use App\Models\Payment;
use App\Enums\InvoiceStatus;
use Illuminate\Http\Request;

class UserDetailsController extends Controller
{
    /**
     * GET /api/users/by-phone?phone=+96077xxxxxx
     * Headers: X-Client-Name / X-Client-Secret
     */
    public function byIdCard(Request $request)
    {
        $data = $request->validate([
            'id_card' => ['required','string','min:2','max:64'],
        ]);

        $idCard = trim($data['id_card']);

        $typeId = DirectoryType::query()->where('name', 'Individual')->value('id');
        if (!$typeId) {
            return response()->json(['ok' => false, 'message' => "DirectoryType 'Individual' not found"], 500);
        }

      $directory = Directory::query()
            ->with([
                'type', // Load the directory type relationship
                'relatedAs' => function ($q) {
                    $q->with([
                        // origin directory (the one whose directory_id points at this directory)
                        'directory:id,name,registration_number,phone,email,profile_picture,directory_type_id',
                        'directory.type' // Load the type relationship for the origin directory as well
                    ]);
                }
            ])
            ->where('directory_type_id', $typeId)
            ->whereRaw('LOWER(registration_number) = ?', [mb_strtolower($idCard)])
            ->first(['id','name','registration_number','phone','email','profile_picture','directory_type_id']);

        if (!$directory) {
            return response()->json(['ok' => false, 'message' => 'Not found'], 404);
        }

        $payload = [
            'default' => [
                'is_default'          => true,
                'id'                  => $directory->id,
                'name'                => $directory->name,
                'registration_number' => $directory->registration_number,
                'phone'               => $directory->phone,
                'email'               => $directory->email,
                'directory_type_name' => $directory->type->name ?? null,
            ],

            // INCOMING relations: rows where linked_directory_id == $directory->id
            // Include relationship meta + the origin (from) directory details.
            'related_as' => $directory->relatedAs->map(function ($rel) {
                $from = $rel->directory; // origin directory (has directory_id)
                return [
                    // 'relationship' => [
                    //     'id'                  => $rel->id,
                    //     'directory_id'        => $rel->directory_id,        // origin
                    //     'linked_directory_id' => $rel->linked_directory_id, // should equal default.id
                    //     'link_type'           => $rel->link_type,
                    //     'designation'         => $rel->designation,
                    //     'permissions'         => $rel->permissions,
                    //     'status'              => $rel->status,
                    //     'start_date'          => optional($rel->start_date)->toDateString(),
                    //     'end_date'            => optional($rel->end_date)->toDateString(),
                    //     'remark'              => $rel->remark,
                    // ],
                    'from_directory' => $from ? [
                        'is_default'          => false,
                        'id'                  => $from->id,
                        'name'                => $from->name,
                        'registration_number' => $from->registration_number,
                        'phone'               => $from->phone,
                        'email'               => $from->email,
                        'link_type'           => $rel->link_type,
                        'designation'         => $rel->designation,
                        'directory_type_name' => $from->type->name ?? null,
                    ] : null,
                ];
            })->values(),

            'related_as_count' => $directory->relatedAs->count(),
        ];

        return response()->json(['ok' => true, 'data' => $payload]);
    }

    /**
     * GET /api/users/profile-photo?id_card=A372300
     * Headers: X-Client-Name / X-Client-Secret
     * Returns profile photo for the given ID card
     */
    public function getProfilePhoto(Request $request)
    {
        $data = $request->validate([
            'id_card' => ['required', 'string', 'min:2', 'max:64'],
        ]);

        $idCard = trim($data['id_card']);

        $typeId = DirectoryType::query()->where('name', 'Individual')->value('id');
        if (!$typeId) {
            return response()->json(['ok' => false, 'message' => "DirectoryType 'Individual' not found"], 500);
        }

        $directory = Directory::query()
            ->where('directory_type_id', $typeId)
            ->whereRaw('LOWER(registration_number) = ?', [mb_strtolower($idCard)])
            ->first(['id', 'name', 'registration_number', 'profile_picture']);

        if (!$directory) {
            return response()->json(['ok' => false, 'message' => 'User not found'], 404);
        }

        if (!$directory->profile_picture) {
            return response()->json(['ok' => false, 'message' => 'Profile photo not available'], 404);
        }

        $payload = [
            'id' => $directory->id,
            'name' => $directory->name,
            'registration_number' => $directory->registration_number,
            'profile_picture' => $directory->profile_picture,
            'profile_picture_url' => $directory->profile_picture ? asset('storage/' . $directory->profile_picture) : null,
        ];

        return response()->json(['ok' => true, 'data' => $payload]);
    }

    /**
     * GET /api/users/financial-summary?id_card=A372300
     * Headers: X-Client-Name / X-Client-Secret
     * Returns financial summary (credit available, total pending, total due) for the given ID card
     */
    public function getFinancialSummary(Request $request)
    {
        $data = $request->validate([
            'id_card' => ['required', 'string', 'min:2', 'max:64'],
        ]);

        $idCard = trim($data['id_card']);

        $typeId = DirectoryType::query()->where('name', 'Individual')->value('id');
        if (!$typeId) {
            return response()->json(['ok' => false, 'message' => "DirectoryType 'Individual' not found"], 500);
        }

        $directory = Directory::query()
            ->whereRaw('LOWER(registration_number) = ?', [mb_strtolower($idCard)])
            ->first(['id', 'name', 'registration_number', 'credit_balance']);

        if (!$directory) {
            return response()->json(['ok' => false, 'message' => 'User not found'], 404);
        }

        // Get all invoices for this directory (for calculations)
        $allInvoices = Invoice::where('directories_id', $directory->id)->get();

        // Get invoices for detailed view - exclude paid and cancelled
        $invoices = Invoice::with(['lines', 'payments', 'property'])
            ->where('directories_id', $directory->id)
            ->whereNotIn('status', [InvoiceStatus::PAID, InvoiceStatus::CANCELLED])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get all payments for calculations
        $allPayments = Payment::where('directories_id', $directory->id)->get();

        // Get latest 6 payments for detailed view
        $payments = Payment::with(['invoices'])
            ->where('directories_id', $directory->id)
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        // Get all waste management services for this directory
        $wasteServices = WasteManagementRegister::with(['property', 'priceList'])
            ->where('directories_id', $directory->id)
            ->get();

        // Calculate totals
        $totalCredit = $directory->credit_balance ?? 0;
        $totalPending = 0;
        $totalDue = 0;
        $totalPaymentOnReview = 0;
        $pendingPayments = 0;

        // Process invoices by status (using all invoices for accurate calculations)
        foreach ($allInvoices as $invoice) {
            $balanceDue = $invoice->balance_due ?? ($invoice->total_amount - $invoice->paid_amount);
            
            // Skip if invoice is fully paid or cancelled
            if ($balanceDue <= 0 || $invoice->status === InvoiceStatus::PAID || $invoice->status === InvoiceStatus::CANCELLED) {
                continue;
            }
            
            switch ($invoice->status) {
                case InvoiceStatus::PENDING:
                case InvoiceStatus::SENT:
                    // Check if invoice is overdue
                    if ($invoice->due_date && $invoice->due_date->isPast()) {
                        $totalDue += $balanceDue;
                    } else {
                        $totalPending += $balanceDue;
                    }
                    break;
                    
                case InvoiceStatus::PARTIAL:
                    // Partial payments are considered due (remaining balance)
                    $totalDue += $balanceDue;
                    break;
                    
                case InvoiceStatus::PAYMENTONREVIEW:
                    $totalPaymentOnReview += $balanceDue;
                    break;
            }
        }

        // Process payments to find pending ones (using all payments)
        foreach ($allPayments as $payment) {
            if ($payment->status === 'pending') {
                $pendingPayments += $payment->amount;
            }
        }

        // Get detailed invoice breakdown (using all invoices)
        $invoiceBreakdown = [
            'pending' => $allInvoices->where('status', InvoiceStatus::PENDING)->count(),
            'sent' => $allInvoices->where('status', InvoiceStatus::SENT)->count(),
            'partial' => $allInvoices->where('status', InvoiceStatus::PARTIAL)->count(),
            'payment_on_review' => $allInvoices->where('status', InvoiceStatus::PAYMENTONREVIEW)->count(),
            'paid' => $allInvoices->where('status', InvoiceStatus::PAID)->count(),
            'draft' => $allInvoices->where('status', InvoiceStatus::DRAFT)->count(),
            'cancelled' => $allInvoices->where('status', InvoiceStatus::CANCELLED)->count(),
        ];

        // Get payment breakdown (using all payments)
        $paymentBreakdown = [
            'pending' => $allPayments->where('status', 'pending')->count(),
            'approved' => $allPayments->where('status', 'approved')->count(),
            'rejected' => $allPayments->where('status', 'rejected')->count(),
            'total_pending_amount' => $pendingPayments,
        ];

        // Format services data
        $services = $wasteServices->map(function ($service) {
            return [
                'id' => $service->id,
                'number' => $service->number,
                'register_number' => $service->register_number,
                'status' => $service->status,
                'status_change_note' => $service->status_change_note,
                'block_count' => $service->block_count,
                'floor' => $service->floor,
                'applicant_is' => $service->applicant_is,
                'property_name' => $service->property->name ?? null,
                'price_list_name' => $service->priceList->name ?? null,
                'created_at' => $service->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $service->updated_at->format('Y-m-d H:i:s'),
            ];
        });

        // Format detailed invoice data
        $detailedInvoices = $invoices->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'date' => $invoice->date->format('Y-m-d'),
                'due_date' => $invoice->due_date ? $invoice->due_date->format('Y-m-d') : null,
                'status' => $invoice->status->value,
                'invoice_type' => $invoice->invoice_type->value ?? null,
                'subtotal' => number_format($invoice->subtotal, 2),
                'total_fine' => number_format($invoice->total_fine, 2),
                'total_amount' => number_format($invoice->total_amount, 2),
                'paid_amount' => number_format($invoice->paid_amount, 2),
                'balance_due' => number_format($invoice->balance_due ?? ($invoice->total_amount - $invoice->paid_amount), 2),
                'discount' => number_format($invoice->discount ?? 0, 2),
                'fine_rate' => $invoice->fine_rate,
                'fine_grace_period' => $invoice->fine_grace_period,
                'message_to_customer' => $invoice->message_to_customer,
                'property_name' => $invoice->property->name ?? null,
                'invoice_lines' => $invoice->lines->map(function ($line) {
                    return [
                        'id' => $line->id,
                        'description' => $line->description,
                        'quantity' => $line->quantity,
                        'unit_price' => number_format($line->unit_price, 2),
                        'amount' => number_format($line->amount, 2),
                    ];
                }),
                'applied_payments' => $invoice->payments->map(function ($payment) {
                    return [
                        'payment_id' => $payment->id,
                        'payment_number' => $payment->number ?? null,
                        'payment_date' => $payment->date->format('Y-m-d'),
                        'payment_method' => $payment->method,
                        'applied_amount' => number_format($payment->pivot->applied_amount, 2),
                        'payment_status' => $payment->status,
                    ];
                }),
                'created_at' => $invoice->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $invoice->updated_at->format('Y-m-d H:i:s'),
            ];
        });

        // Format detailed payment data - latest 6 payments
        $detailedPayments = $payments->map(function ($payment) {
            return [
                'id' => $payment->id,
                'number' => $payment->number ?? null,
                'date' => $payment->date->format('Y-m-d'),
                'amount' => number_format($payment->amount, 2),
                'method' => $payment->method,
                'status' => $payment->status,
                'collection_point' => $payment->collection_point,
                'bank' => $payment->bank,
                'ref' => $payment->ref,
                'payment_slip' => $payment->payment_slip,
                'note' => $payment->note,
                'cancel_note' => $payment->cancel_note,
                'credit_used' => number_format($payment->credit_used ?? 0, 2),
                'overpaid_amount' => number_format($payment->overpaid_amount ?? 0, 2),
                'total_applied_to_invoices' => number_format($payment->total_applied_to_invoices ?? 0, 2),
                'applied_invoices' => $payment->invoices->map(function ($invoice) {
                    return [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->number,
                        'invoice_total' => number_format($invoice->total_amount, 2),
                        'applied_amount' => number_format($invoice->pivot->applied_amount, 2),
                        'invoice_status' => $invoice->status->value,
                    ];
                }),
                'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $payment->updated_at->format('Y-m-d H:i:s'),
            ];
        });

        // Also create a separate array for pending approval payments from all payments
        $pendingApprovalPayments = $allPayments->filter(function ($payment) {
            return strtolower($payment->status) === 'pending approval';
        })->map(function ($payment) {
            return [
                'id' => $payment->id,
                'number' => $payment->number ?? null,
                'date' => $payment->date->format('Y-m-d'),
                'amount' => number_format($payment->amount, 2),
                'method' => $payment->method,
                'status' => $payment->status,
                'collection_point' => $payment->collection_point,
                'bank' => $payment->bank,
                'ref' => $payment->ref,
                'payment_slip' => $payment->payment_slip,
                'note' => $payment->note,
                'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
            ];
        })->values();

        $payload = [
            'id' => $directory->id,
            'name' => $directory->name,
            'registration_number' => $directory->registration_number,
            'financial_summary' => [
                'total_credit_available' => number_format($totalCredit, 2),
                'total_pending' => number_format($totalPending, 2),
                'total_due' => number_format($totalDue, 2),
                'total_payment_on_review' => number_format($totalPaymentOnReview, 2),
                'pending_payments' => number_format($pendingPayments, 2),
                'raw_values' => [
                    'total_credit_available' => $totalCredit,
                    'total_pending' => $totalPending,
                    'total_due' => $totalDue,
                    'total_payment_on_review' => $totalPaymentOnReview,
                    'pending_payments' => $pendingPayments,
                ]
            ],
            'invoice_breakdown' => $invoiceBreakdown,
            'payment_breakdown' => $paymentBreakdown,
            'detailed_invoices' => $detailedInvoices,
            'detailed_payments' => $detailedPayments,
            'pending_approval_payments' => $pendingApprovalPayments,
            'services' => [
                'waste_management' => $services,
                'waste_service_count' => $wasteServices->count(),
                'active_services' => $wasteServices->where('status', 'active')->count(),
                'pending_services' => $wasteServices->where('status', 'pending')->count(),
            ],
            'counts' => [
                'total_invoices' => $allInvoices->count(),
                'active_invoices' => $invoices->count(), // Excludes paid and cancelled
                'total_payments' => $allPayments->count(),
                'latest_payments_shown' => $payments->count(),
            ],
        ];

        return response()->json(['ok' => true, 'data' => $payload]);
    }
}
