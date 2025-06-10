<?php

namespace App\Livewire\Invoice;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\Directory;
use App\Models\InvoiceCategory;
use App\Enums\InvoiceType;
use App\Enums\InvoiceStatus;
use App\Enums\FineInterval;
use Illuminate\Validation\Rules\Enum as EnumRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\EventLog; 
use App\Models\PendingTelegramNotification; 

class InvoiceManager extends Component
{
    use WithPagination;

    public string $pageTitle = 'Invoices';

    public ?string $selectedInvoiceId = null;
    public ?Invoice $selectedInvoice  = null;

    public array $invoice;
    public array $lines;

    public $properties;
    public $directories;
    public $categories;
    public $types;
    public $statuses;
    public $intervals;
    public int $perPage = 10; 

    public function mount()
    {
        $this->properties  = Property::pluck('name','id');
        $this->directories = Directory::pluck('name','id');
        $this->categories  = InvoiceCategory::pluck('name','id');
        $this->types       = InvoiceType::cases();
        $this->statuses    = InvoiceStatus::cases();
        $this->intervals   = FineInterval::cases();

        $this->resetForm();

        $first = Invoice::orderBy('number','desc')->first();
        if ($first) {
            $this->selectInvoice($first->id);
        }
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
        ];

        $this->lines = [
            ['category_id'=>null,'description'=>'','quantity'=>1,'unit_price'=>0]
        ];
    }

    public function showCreateModal()
    {
        $this->resetForm();
        $this->dispatch('showInvoiceModal');
    }

        /**
     * Increments the number of items per page to load more invoices.
     */
    public function loadMore()
    {
        $this->perPage += 10; // Load 10 more items each time
    }

    public function addLine()
    {
        $this->lines[] = ['category_id'=>null,'description'=>'','quantity'=>1,'unit_price'=>0];
    }

    public function removeLine(int $index)
    {
        array_splice($this->lines, $index, 1);
    }

  public function save()
    {
        // 1. Validate the incoming data using Livewire's validation
        $this->validate();

        $inv = null; // Initialize $inv to null

        try {
            // 2. Use a database transaction to ensure atomicity.
            // If any part of the invoice or line creation fails, everything is rolled back.
            DB::transaction(function () use (&$inv) { // Pass $inv by reference
                // Create the Invoice record
                $inv = Invoice::create($this->invoice);

                // Create associated InvoiceLine records
                foreach ($this->lines as $lineData) {
                    $inv->lines()->create($lineData);
                }

                // Important: The Invoice model's `static::saved` event listener will automatically:
                // a) Recalculate and update the `total_amount` based on the new lines and fine.
                // b) Enqueue a Telegram notification.
                // These actions happen *after* the invoice and its lines are committed to the DB.
            });

            // After the transaction, refresh the invoice model to get the latest `total_amount`
            // that was updated by the Invoice model's `static::saved` event.
            if ($inv) {
                $inv->refresh(); // Crucial for getting the updated total_amount
            }

            // 3. Flash a success message to the session
            session()->flash('success', 'Invoice created successfully.');
            
                $this->dispatch('swal', [
                    'title' => 'Invoice created',
                    'text' => 'Invoice created successfully.',
                    'icon' => 'success',
                    'buttonsStyling' => false,
                    'confirmButtonText' => 'Ok, got it!',
                    'confirmButton' => 'btn btn-primary',
                ]);

            // --- Event Log for Invoice Creation ---
            if ($inv) { // Ensure invoice was successfully created and refreshed
                try {
                    EventLog::create([
                        'user_id'        => auth()->id(), // Get the ID of the authenticated user
                        'event_tab'      => 'Invoices',
                        'event_entry_id' => $inv->id, // ID of the newly created invoice
                        'event_type'     => 'Invoice Created',
                        'description'    => 'New invoice created successfully.',
                        'event_data'     => [
                            'invoice_number' => $inv->number,
                            'total_amount'   => $inv->total_amount, // Will now be the correct, updated total
                            'property_id'    => $inv->property_id,
                            'directory_id'   => $inv->directories_id, // Note: your column name is directories_id
                        ],
                        'ip_address'     => request()->ip(),
                    ]);
                    Log::info("EventLog entry created for Invoice ID: {$inv->id}");
                } catch (\Exception $e) {
                    Log::error("Failed to create EventLog entry for Invoice ID: {$inv->id}. Error: {$e->getMessage()}");
                }

                // --- Telegram Notification Logic (Manual Trigger from Component) ---
                // This section will trigger a Telegram notification directly from the component.
                // IMPORTANT: Your Invoice model's 'saved' event already handles a similar notification.
                // You will receive TWO notifications if both are active. Consider centralizing this.
                try {
                    // Load relationships needed for the notification message
                    // These should already be loaded by the model's refresh() if they are part of Invoice model.
                    // However, if refresh() doesn't load them, you might need $inv->load(['property', 'directory']);
                    // For safety and to ensure we have fresh data for the notification
                    $inv->load(['property', 'directory']); // Load relationships needed for notification

                    $creatorName = \Auth::user()->name ?? 'Unknown User';
                    $msg = "<b>📢 New Invoice Created Successfully</b>\n\n" .
                           "<b>Invoice Number:</b> {$inv->number}\n" .
                           "<b>Property:</b> " . ($inv->property->name ?? 'N/A') . "\n" .
                           "<b>Customer:</b> " . ($inv->directory->name ?? 'N/A') . "\n" .
                           "<b>Due Date:</b> " . ($inv->due_date ? $inv->due_date->format('d M Y') : 'N/A') . "\n" .
                           "<b>Total Amount:</b> " . number_format($inv->total_amount, 2) . " MVR\n" . // This will now be the correct total
                           "<b>Status:</b> " . ($inv->status ? $inv->status->value : 'N/A') . "\n" .
                           "<b>Created By:</b> {$creatorName}\n" .
                           "<b>Created At:</b> " . now()->format('d M Y H:i');

                    // Enqueue the notification—don’t call sendMessage() here
                    PendingTelegramNotification::create([
                        'chat_id' => env('TELEGRAM_INVOICES_CHAT_ID'), // Ensure this env variable is set
                        'message' => $msg,
                    ]);
                    Log::info("Telegram notification enqueued by InvoiceManager for Invoice ID: {$inv->id}");

                } catch (\Exception $e) {
                    Log::error("Failed to enqueue Telegram notification from InvoiceManager for Invoice ID: {$inv->id}. Error: {$e->getMessage()}");
                }
                // --- End Telegram Notification Logic ---
            }
            // --- End Event Log (and Notification) ---


            // 4. Dispatch events to close the modal and reset component state
            $this->dispatch('closeInvoiceModal');
            $this->resetPage(); // Resets pagination
            $this->resetForm(); // Resets form fields for next creation

        } catch (\Exception $e) {
            // Log the error for debugging purposes
            Log::error("Invoice creation failed in Livewire component: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'invoice_data' => $this->invoice,
                'lines_data' => $this->lines,
            ]);

            // Flash an error message to the session
            session()->flash('error', 'Failed to create invoice. Please try again.');

            // You might want to dispatch an event to show an error message in the UI
            // $this->dispatch('errorCreatingInvoice');
        }
    }

    public function selectInvoice(string $id)
    {
        $this->selectedInvoiceId = $id;
        $this->selectedInvoice = Invoice::with([
            'property',          // for name/logo/etc
            'directory',         // for address/contact/etc
            'lines.category',    // for line‐item categories
            'payments',          // for transaction #
        ])->findOrFail($id);
    }

    public function render()
    {
        $invoices = Invoice::with('property','directory')
                           ->orderBy('number','desc')
                           ->paginate($this->perPage);

        return view('livewire.invoice.invoice-manager', [
            'invoices'  => $invoices,
            'pageTitle' => $this->pageTitle,
            'selectedInvoice' => $this->selectedInvoice,
        ])->layout('layouts.master');
    }
}
