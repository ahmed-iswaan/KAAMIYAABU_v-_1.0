<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Enums\InvoiceType;
use App\Enums\InvoiceStatus;
use App\Enums\FineInterval;
use App\Services\UniqueIdGenerator;
use App\Models\Property;
use App\Models\Directory;
use App\Models\InvoiceLine; // Make sure InvoiceLine is imported
use App\Models\Payment; // Make sure Payment is imported
use Illuminate\Support\Facades\Log; // Import the Log facade for debugging
use App\Models\PendingTelegramNotification;

class Invoice extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'number',
        'property_id',
        'directories_id',
        'date',
        'due_date',
        'invoice_type',
        'status',
        'fine_rate',
        'fine_interval',
        'fine_grace_period',
        'message_on_statement',
        'message_to_customer',
        'total_amount', // This field will be set by the 'saved' hook, but it's good to have it fillable if you might pass it directly
    ];

    protected $casts = [
        'date'              => 'date',
        'due_date'          => 'date',
        'fine_rate'         => 'integer',   // cast to integer MVR
        'total_amount'      => 'decimal:2',
        'invoice_type'      => InvoiceType::class,
        'status'            => InvoiceStatus::class,
        'fine_interval'     => FineInterval::class,
        'fine_grace_period' => 'integer',
    ];

    protected $appends = [
        'subtotal',
        'accrued_fine',
        'total_with_fine',
        'fine_detail',
    ];

    // Relations
    public function lines()
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function directory()
    {
        return $this->belongsTo(Directory::class, 'directories_id');
    }

    public function payments()
    {
        return $this->belongsToMany(
            Payment::class,
            'invoice_payments',
            'invoice_id',
            'payment_id'
        )->withPivot('applied_amount')->withTimestamps();
    }

    // Accessors
    public function getSubtotalAttribute(): float
    {
        // When creating an invoice, 'lines' might not be associated yet.
        // Ensure we gracefully handle the case where the lines relationship is not loaded
        // or is an empty collection.
        if (!$this->relationLoaded('lines') || $this->lines->isEmpty()) {
            return 0.0;
        }

        return $this->lines->sum(fn($line) => $line->quantity * $line->unit_price);
    }

    /**
     * Get the accrued fine amount for the invoice.
     * This accessor now uses the shared calculateFineDetails helper.
     */
    public function getAccruedFineAttribute(): int
    {
        $details = $this->calculateFineDetails();
        return $details['fine'];
    }

    /**
     * Get the total amount of the invoice including any accrued fine.
     * This accessor directly sums subtotal and accrued fine.
     */
    public function getTotalWithFineAttribute(): float
    {
        // Ensure accessors are called as properties, not methods.
        // This is the correct way to get the computed values.
        return $this->subtotal + $this->accrued_fine;
    }

    /**
     * Get a detailed string explanation of the accrued fine.
     * This accessor also uses the shared calculateFineDetails helper.
     */
    public function getFineDetailAttribute(): string
    {
        // Get all fine details from the shared helper method
        $details = $this->calculateFineDetails();
        $fine = $details['fine'];
        $units = $details['units'];
        $label_singular = $details['label_singular'];
        $label_plural = $details['label_plural'];

        if ($fine <= 0) {
            return 'No fine accrued';
        }

        // Determine the correct label (singular/plural) for the total units
        $label = $units === 1 ? $label_singular : $label_plural;

        // Use sprintf for formatting the detail string
        return sprintf(
            "This invoice has a fine for %d %s, fine rate is %d MVR per %s, so total fine is %d MVR.",
            $units,
            $label,
            $this->fine_rate, // fine_rate is already cast to integer
            $label_singular,  // Use singular for 'per X' in the rate description
            $fine
        );
    }

    /**
     * Helper: Calculates the fine start date based on due date and grace period.
     *
     * @return Carbon|null
     */
    protected function fineStartDate(): ?Carbon
    {
        // If no due date or fine rate is zero or less, no fine applies.
        if (! $this->due_date || $this->fine_rate <= 0) {
            return null;
        }

        // Add grace period based on fine interval.
        return match ($this->fine_interval) {
            FineInterval::HOURLY  => $this->due_date->copy()->addHours($this->fine_grace_period),
            FineInterval::DAILY   => $this->due_date->copy()->addDays($this->fine_grace_period),
            FineInterval::MONTHLY => $this->due_date->copy()->addMonths($this->fine_grace_period),
            default               => null, // Fallback for undefined intervals
        };
    }

    /**
     * Shared helper to calculate fine details (units and amount).
     * This centralizes the logic to ensure consistency across accessors.
     *
     * @return array Contains 'units', 'fine', 'label_singular', 'label_plural'.
     */
    protected function calculateFineDetails(): array
    {
        $start = $this->fineStartDate();
        // If no start date for fine or current time is before or at the start, no fine.
        // Also check fine_rate here to ensure consistency.
        if (! $start || now()->lte($start) || $this->fine_rate <= 0) {
            return [
                'units' => 0,
                'fine' => 0,
                'label_singular' => '',
                'label_plural' => '',
            ];
        }

        $units = 0;
        $label_singular = '';
        $label_plural = '';

        // Calculate the number of full intervals passed and set labels.
        switch ($this->fine_interval) {
            case FineInterval::HOURLY:
                $units = $start->diffInHours(now());
                $label_singular = 'hour';
                $label_plural = 'hours';
                break;
            case FineInterval::DAILY:
                $units = $start->diffInDays(now());
                $label_singular = 'day';
                $label_plural = 'days';
                break;
            case FineInterval::MONTHLY:
                $units = $start->diffInMonths(now());
                $label_singular = 'month';
                $label_plural = 'months';
                break;
            default:
                // Should not happen if fine_interval is properly cast, but for safety
                $units = 0;
                $label_singular = '';
                $label_plural = '';
                break;
        }

        // Calculate the fine amount. Ensure integer multiplication.
        $fine = intval($units) * intval($this->fine_rate);

        return [
            'units' => $units,
            'fine' => $fine,
            'label_singular' => $label_singular,
            'label_plural' => $label_plural,
        ];
    }

    // Events
    protected static function booted()
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->number)) {
                $invoice->number = UniqueIdGenerator::generate(
                    table:   'invoices',
                    column:  'number',
                    prefix:  'INV-'.now()->format('Y').'-',
                    padLength: 6
                );
            }

            // You might want to set default values for enums or nullable fields here
            // if they are not explicitly provided during creation and have a default.
            // For example:
            // $invoice->invoice_type = $invoice->invoice_type ?? InvoiceType::REGULAR;
            // $invoice->status = $invoice->status ?? InvoiceStatus::DRAFT;
            // $invoice->fine_rate = $invoice->fine_rate ?? 0;
            // $invoice->fine_grace_period = $invoice->fine_grace_period ?? 0;
        });

        // Use the 'saved' event instead of 'saving' to ensure related models (like InvoiceLines)
        // have been persisted before calculating total_amount that depends on them.
        static::saved(function (Invoice $invoice) {
            // Explicitly load the 'lines' relationship to ensure it's available
            // before accessing the 'subtotal' accessor.
            // This is crucial if lines are created/updated after the initial invoice save.
            $invoice->load('lines');

            // Log values for debugging
            // Log::info("Invoice saved event for ID: {$invoice->id}");
            // Log::info("  Subtotal (from accessor): {$invoice->subtotal}");
            // Log::info("  Accrued Fine (from accessor): {$invoice->accrued_fine}");
            // Log::info("  Current total_amount property on model (before update): {$invoice->total_amount}"); // Value on the model instance
            // Log::info("  Raw total_amount from DB (original): {$invoice->getRawOriginal('total_amount')}"); // Original value from DB

            // Recalculate total_amount using accessors.
            $newTotalAmount = $invoice->subtotal + $invoice->accrued_fine;

            // --- DEBUGGING: Explicit logging before comparison ---
            // Log::info("  DEBUG: Inside saved event comparison check for Invoice ID: {$invoice->id}");
            // Log::info("  DEBUG: \$invoice->total_amount (as float): " . (float)$invoice->total_amount);
            // Log::info("  DEBUG: \$newTotalAmount (as float): " . (float)$newTotalAmount);
            // Log::info("  DEBUG: Comparison result ((float)\$invoice->total_amount !== (float)\$newTotalAmount): " . (((float)$invoice->total_amount !== (float)$newTotalAmount) ? 'true' : 'false'));
            // --- END DEBUGGING ---

            // Only update if the total amount has actually changed to avoid unnecessary database writes.
            // This condition is now re-enabled.
            if ((float)$invoice->total_amount !== (float)$newTotalAmount) {
                // Log::info("  Updating total_amount from {$invoice->total_amount} to {$newTotalAmount}");
                // Changed from updateQuietly() to save() to ensure the update commits.
                // The 'if' condition prevents an infinite loop.
                $invoice->total_amount = $newTotalAmount; // Set the attribute
                $invoice->save(); // Persist the change, re-triggering 'saved' but stopped by 'if' condition.
            } else {
                // Log::info("  total_amount unchanged: {$invoice->total_amount}. No update needed.");
            }

            // After save(), refresh the model instance to reflect the latest state from the database.
            // This is crucial for subsequent operations or logs to show the correct, persisted value.
            $invoice->refresh();
            // Log::info("  Model refreshed. New total_amount on model instance (after refresh): {$invoice->total_amount}");
        });
    }
}
