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
use App\Models\InvoiceLine;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
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
        'subtotal',
        'total_fine',
        'total_amount',
        'invoice_tag',
        'ref_id',
        'discount',
        'paid_amount',
    ];

    protected $casts = [
        'date'              => 'date',
        'due_date'          => 'date',
        'fine_rate'         => 'integer',
        'subtotal'          => 'decimal:2',
        'total_fine'        => 'decimal:2',
        'total_amount'      => 'decimal:2',
        'invoice_tag'       => 'string',
        'ref_id'            => 'string',
        'invoice_type'      => InvoiceType::class,
        'status'            => InvoiceStatus::class,
        'fine_interval'     => FineInterval::class,
        'fine_grace_period' => 'integer',
        'discount'          => 'decimal:2',
        'paid_amount'       => 'decimal:2',
    ];

    protected $appends = [
        'subtotal',
        'accrued_fine',
        'total_with_fine',
        'fine_detail',
        'balance_due',
    ];

    // Relationships
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
        if (!$this->relationLoaded('lines') || $this->lines->isEmpty()) {
            return 0.0;
        }

        return $this->lines->sum(fn($line) => $line->quantity * $line->unit_price);
    }

    public function getAccruedFineAttribute(): int
    {
        return $this->calculateFineDetails()['fine'];
    }

    public function getTotalWithFineAttribute(): float
    {
         return max(0, ($this->subtotal - $this->discount)) + $this->accrued_fine;
    }

    public function getFineDetailAttribute(): string
    {
        $details = $this->calculateFineDetails();

        if ($details['fine'] <= 0) {
            return 'No fine accrued';
        }

        $label = $details['units'] === 1 ? $details['label_singular'] : $details['label_plural'];

        return sprintf(
            "This invoice has a fine for %d %s, fine rate is %d MVR per %s, so total fine is %d MVR.",
            $details['units'],
            $label,
            $this->fine_rate,
            $details['label_singular'],
            $details['fine']
        );
    }

    public function getBalanceDueAttribute(): float
    {
        return max(0, $this->total_amount - $this->paid_amount);
    }

    protected function fineStartDate(): ?Carbon
    {
        if (! $this->due_date || $this->fine_rate <= 0) {
            return null;
        }

        return match ($this->fine_interval) {
            FineInterval::HOURLY  => $this->due_date->copy()->addHours($this->fine_grace_period),
            FineInterval::DAILY   => $this->due_date->copy()->addDays($this->fine_grace_period),
            FineInterval::MONTHLY => $this->due_date->copy()->addMonths($this->fine_grace_period),
            default               => null,
        };
    }

    protected function calculateFineDetails(): array
    {
        $start = $this->fineStartDate();

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
        }

        $fine = intval($units) * intval($this->fine_rate);

        return [
            'units' => $units,
            'fine' => $fine,
            'label_singular' => $label_singular,
            'label_plural' => $label_plural,
        ];
    }

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
        });

        static::saved(function (Invoice $invoice) {
            $invoice->load('lines');

            $newSubtotal = $invoice->subtotal;
            $newFine = $invoice->accrued_fine;
            $newTotal = max(0, $newSubtotal - $invoice->discount) + $newFine;

            if (
                (float)$invoice->total_amount !== (float)$newTotal ||
                (float)$invoice->subtotal !== (float)$newSubtotal ||
                (float)$invoice->total_fine !== (float)$newFine
            ) {
                $invoice->updateQuietly([
                    'subtotal'     => $newSubtotal,
                    'total_fine'   => $newFine,
                    'total_amount' => $newTotal,
                ]);
            }

            $invoice->refresh();
        });
    }
}
