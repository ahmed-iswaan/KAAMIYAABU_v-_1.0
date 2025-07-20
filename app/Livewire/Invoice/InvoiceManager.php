<?php

namespace App\Livewire\Invoice;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\Directory;
use App\Models\InvoiceCategory;
use App\Models\Payment;
use App\Enums\InvoiceType;
use App\Enums\InvoiceStatus;
use App\Models\InvoicePayment;
use App\Enums\FineInterval;
use Illuminate\Validation\Rules\Enum as EnumRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use App\Models\EventLog;
use App\Models\PendingTelegramNotification;

class InvoiceManager extends Component
{
    use WithPagination;

    public string $pageTitle = 'Invoices';

    public ?string $selectedInvoiceId = null;
    public ?Invoice $selectedInvoice  = null;

    public Collection $invoicesToPay;
    public float $totalInvoiceAmount = 0;

    public array $selectedInvoices = []; // ✅ for payment selection

    public float $paymentAmount = 0;
    public array $paymentPreview = [];
    public array $unpayableInvoices = [];

    public string $paymentMethod = 'cash';
    public string $paymentBank = '';
    public string $paymentRef = '';
    public string $paymentNote = '';
    public $paymentDate;
    public $paymentSlip;

    public float $creditUsed = 0;
    public float $overpaidAmount = 0;
    public float $availableCredit = 0;

    public float $totalApplied = 0;
    public bool $hasZeroApplied = false;

    public ?string $selectedCustomerId = null;


    public array $invoice;
    public array $lines;

    public $properties;
    public $directories;
    public $categories;
    public $types;
    public $statuses;
    public $intervals;
    public int $perPage = 10;
    public $search = '';

    public function mount()
    {
        $this->invoicesToPay = collect();
        $this->paymentAmount = 0;
        $this->paymentMethod = 'cash';
        $this->paymentBank = '';
        $this->paymentRef = '';
        $this->paymentNote = '';
        $this->paymentDate = now()->toDateString();
        $this->creditUsed = 0;
        $this->overpaidAmount = 0;
        $this->availableCredit = 0;

        $this->properties  = Property::pluck('name', 'id');
        $this->directories = Directory::pluck('name', 'id');
        $this->categories  = InvoiceCategory::pluck('name', 'id');
        $this->types       = InvoiceType::cases();
        $this->statuses    = InvoiceStatus::cases();
        $this->intervals   = FineInterval::cases();


        $this->resetForm();

        $first = Invoice::orderBy('number', 'desc')->first();
        if ($first) {
            $this->selectInvoice($first->id);
        }
    }

        public function updatingSearch()
    {
        $this->resetPage();
        $this->dispatch('TableUpdated'); 
    }

    protected function rules(): array
    {
        return [
            'invoice.property_id'          => 'required|uuid|exists:properties,id',
            'invoice.directories_id'       => 'required|uuid|exists:directories,id',
            'invoice.date'                 => 'required|date',
            'invoice.due_date'             => 'nullable|date|after_or_equal:invoice.date',
            'invoice.invoice_type'         => ['required', new EnumRule(InvoiceType::class)],
            'invoice.status'               => ['required', new EnumRule(InvoiceStatus::class)],
            'invoice.fine_rate'            => 'nullable|numeric|min:0',
            'invoice.fine_interval'        => ['nullable', new EnumRule(FineInterval::class)],
            'invoice.fine_grace_period'    => 'nullable|integer|min:0',
            'invoice.message_on_statement' => 'nullable|string',
            'invoice.message_to_customer'  => 'nullable|string',
            'invoice.invoice_tag'          => 'nullable|string|max:255',
            'invoice.ref_id'               => 'nullable|string|max:255',
            'lines'                        => 'required|array|min:1',
            'lines.*.category_id'          => 'nullable|uuid|exists:invoice_categories,id',
            'lines.*.description'          => 'required|string',
            'lines.*.quantity'             => 'required|numeric|min:1',
            'lines.*.unit_price'           => 'required|numeric|min:0',
        ];
    }

    public function resetForm()
    {
        $this->invoice = [
            'property_id'          => '',
            'directories_id'       => '',
            'date'                 => today()->toDateString(),
            'due_date'             => '',
            'invoice_type'         => InvoiceType::STANDARD->value,
            'status'               => InvoiceStatus::DRAFT->value,
            'fine_rate'            => 0,
            'fine_interval'        => FineInterval::DAILY->value,
            'fine_grace_period'    => 0,
            'message_on_statement' => '',
            'message_to_customer'  => '',
            'invoice_tag'          => '',
            'ref_id'               => '',
        ];

        $this->lines = [
            ['category_id' => null, 'description' => '', 'quantity' => 1, 'unit_price' => 0]
        ];
    }

    public function showCreateModal()
    {
        $this->resetForm();
        $this->dispatch('showInvoiceModal');
    }

    public function updatedPaymentAmount($value)
    {
        $this->paymentAmount = floatval($value);
        $this->preparePaymentPreview();
    }

    public function preparePaymentPreview()
    {
        $remaining = $this->paymentAmount;
        $this->paymentPreview = [];
        $this->unpayableInvoices = [];

        foreach ($this->invoicesToPay as $invoice) {
           $due = $invoice->total_amount - $invoice->paid_amount;

            if ($remaining <= 0) {
                $this->unpayableInvoices[] = $invoice->number;
                $this->paymentPreview[] = [
                    'invoice' => $invoice,
                    'applied' => 0,
                ];
                continue;
            }

            $applied = min($remaining, $due);
            $remaining -= $applied;

            $this->paymentPreview[] = [
                'invoice' => $invoice,
                'applied' => $applied,
            ];
        }

        // ✅ These should all be $this->property
        $this->totalApplied   = collect($this->paymentPreview)->sum('applied');
        $this->hasZeroApplied = collect($this->paymentPreview)->contains(fn($p) => $p['applied'] == 0);

        $this->creditUsed     = max(0, $this->totalApplied - $this->paymentAmount);
        $this->overpaidAmount = max(0, $this->paymentAmount - $this->totalApplied);
    }

    public function updatedSelectedCustomerId($value)
    {

         logger('Customer selected: ' . $value);
        $this->availableCredit = Directory::find($value)?->credit_balance ?? 0;

        // Update preview if there's a payment amount entered already
        if ($this->paymentAmount > 0) {
            $this->preparePaymentPreview();
        }
    }


    public function submitPayment()
    {
    if ($this->paymentAmount <= 0) {
        $this->addError('paymentAmount', 'Please enter a valid amount.');
        return;
    }

    $this->preparePaymentPreview();

    $payables = collect($this->paymentPreview)->filter(fn($p) => $p['applied'] > 0);

    if ($payables->isEmpty()) {
        $this->addError('paymentAmount', 'Amount too small to apply to selected invoices.');
        return;
    }

    // Get the directory
    $payerDirectory = Directory::find($this->selectedCustomerId); // or however you're selecting the paying customer
    if (! $payerDirectory) {
        $this->addError('paymentAmount', 'Payer not found.');
        return;
    }

    $totalApplied = $payables->sum('applied');
    $creditUsed   = $this->creditUsed ?? 0;
    $overpaid     = max(0, $this->paymentAmount - $totalApplied);

    // 💳 Update only the selected payer's credit balance
    $payerDirectory->credit_balance = max(0, $payerDirectory->credit_balance - $creditUsed + $overpaid);
    $payerDirectory->save();


    // Create Payment record
    $payment = Payment::create([
        'directories_id'             => $payerDirectory->id,
        'amount'                     => $this->paymentAmount,
        'method'                     => $this->paymentMethod,
        'bank'                       => $this->paymentBank,
        'ref'                        => $this->paymentRef,
        'payment_slip'              => $this->paymentSlip ? $this->paymentSlip->store('payment_slips', 'public') : null,
        'note'                       => $this->paymentNote,
        'date'                       => $this->paymentDate,
        'credit_used'               => $creditUsed,
        'overpaid_amount'           => $overpaid,
        'total_applied_to_invoices' => $totalApplied,
    ]);

    // Apply payment to invoices
    foreach ($payables as $p) {
        $invoice = $p['invoice'];
        $applied = $p['applied'];

        InvoicePayment::create([
            'invoice_id'     => $invoice->id,
            'payment_id'     => $payment->id,
            'applied_amount' => $applied,
        ]);

        $invoice->paid_amount += $applied;

        if ($invoice->paid_amount < $invoice->total_amount) {
            $invoice->status = InvoiceStatus::PARTIAL;
        } else {
            $invoice->status = InvoiceStatus::PAID;
        }

        $invoice->save();
    }

    $this->dispatch('swal', ['title' => 'Payment recorded successfully', 'text' => 'Payment recorded successfully.', 'icon' => 'success','confirmButtonText' => 'Ok!','confirmButton'=> 'btn btn-primary']);
    $this->dispatch('closePayModal');
    $this->reset([
        'selectedInvoices', 'paymentAmount', 'paymentPreview',
        'unpayableInvoices', 'paymentDate', 'paymentMethod', 'paymentBank',
        'paymentRef', 'paymentNote', 'paymentSlip'
    ]);

    session()->flash('success', 'Payment recorded successfully.');
}





    public function makePayment()
    {
        $this->resetForm();

        $this->invoicesToPay = Invoice::with('property', 'directory')
        ->when($this->selectedCustomerId, fn($q) => $q->where('directories_id', $this->selectedCustomerId))
        ->whereIn('id', $this->selectedInvoices)
        ->get();

        $this->availableCredit = Directory::find($this->selectedCustomerId)?->credit_balance ?? 0;


        $this->paymentDate = now()->toDateString();

        $firstInvoice = $this->invoicesToPay->first();

        $this->invoicesToPay = Invoice::with('property', 'directory')
            ->whereIn('id', $this->selectedInvoices)
            ->get();

       $this->totalInvoiceAmount = $this->invoicesToPay->sum(fn($inv) => $inv->total_amount - $inv->paid_amount);


        $this->paymentAmount = $this->totalInvoiceAmount;

        // 🧾 Trigger the preview calculation (if used)
        $this->preparePaymentPreview();

        $this->dispatch('showPayModal');
    }

    public function loadMore()
    {
        $this->perPage += 10;
    }

    public function addLine()
    {
        $this->lines[] = ['category_id' => null, 'description' => '', 'quantity' => 1, 'unit_price' => 0];
    }

    public function removeLine(int $index)
    {
        array_splice($this->lines, $index, 1);
    }

    public function save()
    {
        $this->validate();

        $inv = null;

        try {
            DB::transaction(function () use (&$inv) {
                $inv = Invoice::create($this->invoice);

                foreach ($this->lines as $lineData) {
                    $inv->lines()->create($lineData);
                }
            });

            if ($inv) {
                $inv->refresh();
            }

            session()->flash('success', 'Invoice created successfully.');

            $this->dispatch('swal', [
                'title' => 'Invoice created',
                'text' => 'Invoice created successfully.',
                'icon' => 'success',
                'buttonsStyling' => false,
                'confirmButtonText' => 'Ok, got it!',
                'confirmButton' => 'btn btn-primary',
            ]);

            if ($inv) {
                try {
                    EventLog::create([
                        'user_id'        => auth()->id(),
                        'event_tab'      => 'Invoices',
                        'event_entry_id' => $inv->id,
                        'event_type'     => 'Invoice Created',
                        'description'    => 'New invoice created successfully.',
                        'event_data'     => [
                            'invoice_number' => $inv->number,
                            'total_amount'   => $inv->total_amount,
                            'property_id'    => $inv->property_id,
                            'directory_id'   => $inv->directories_id,
                        ],
                        'ip_address'     => request()->ip(),
                    ]);
                    Log::info("EventLog entry created for Invoice ID: {$inv->id}");
                } catch (\Exception $e) {
                    Log::error("Failed to create EventLog entry for Invoice ID: {$inv->id}. Error: {$e->getMessage()}");
                }

                try {
                    $inv->load(['property', 'directory']);
                    $creatorName = \Auth::user()->name ?? 'Unknown User';

                    $msg = "<b>📢 New Invoice Created Successfully</b>\n\n" .
                        "<b>Invoice Number:</b> {$inv->number}\n" .
                        "<b>Property:</b> " . ($inv->property->name ?? 'N/A') . "\n" .
                        "<b>Customer:</b> " . ($inv->directory->name ?? 'N/A') . "\n" .
                        "<b>Due Date:</b> " . ($inv->due_date ? $inv->due_date->format('d M Y') : 'N/A') . "\n" .
                        "<b>Total Amount:</b> " . number_format($inv->total_amount, 2) . " MVR\n" .
                        "<b>Status:</b> " . ($inv->status ? $inv->status->value : 'N/A') . "\n" .
                        "<b>Created By:</b> {$creatorName}\n" .
                        "<b>Created At:</b> " . now()->format('d M Y H:i');

                    PendingTelegramNotification::create([
                        'chat_id' => env('TELEGRAM_INVOICES_CHAT_ID'),
                        'message' => $msg,
                    ]);

                    Log::info("Telegram notification enqueued by InvoiceManager for Invoice ID: {$inv->id}");
                } catch (\Exception $e) {
                    Log::error("Failed to enqueue Telegram notification for Invoice ID: {$inv->id}. Error: {$e->getMessage()}");
                }
            }

            $this->dispatch('closeInvoiceModal');
            $this->resetPage();
            $this->resetForm();

        } catch (\Exception $e) {
            Log::error("Invoice creation failed: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'invoice_data' => $this->invoice,
                'lines_data' => $this->lines,
            ]);

            session()->flash('error', 'Failed to create invoice. Please try again.');
        }
    }

    public function selectInvoice(string $id)
    {
        $this->selectedInvoiceId = $id;
        $this->selectedInvoice = Invoice::with([
            'property',
            'directory',
            'lines.category',
            'payments',
        ])->findOrFail($id);
    }

    public function render()
    {
    $invoices = Invoice::with('property', 'directory')
        ->when($this->search, function ($query) {
            $query->where(function ($q) {
                $q->where('number', 'like', '%' . $this->search . '%')
                  ->orWhere('status', 'like', '%' . $this->search . '%')
                  ->orWhereHas('directory', function ($q) {
                      $q->where('name', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('property', function ($q) {
                      $q->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        })
        ->orderBy('number', 'desc')
        ->paginate($this->perPage); 

        return view('livewire.invoice.invoice-manager', [
            'invoices' => $invoices,
            'pageTitle' => $this->pageTitle,
            'selectedInvoice' => $this->selectedInvoice,
        ])->layout('layouts.master');
    }

}
