<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Services\UniqueIdGenerator;

class Payment extends Model
{
  use HasUuids;

    public $incrementing = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'directories_id',
        'date', 'amount', 'method','status',
        'bank', 'ref','payment_slip','note',
    ];

    protected $casts = [
        'date'   => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * The directory this payment applies to.
     */
    public function directory()
    {
        return $this->belongsTo(Directory::class, 'directories_id');
    }

    /**
     * Invoices this payment is applied against.
     */
    public function invoices()
    {
        return $this->belongsToMany(Invoice::class)
                    ->withPivot('applied_amount')
                    ->withTimestamps();
    }

    protected static function booted()
    {
        static::creating(function ($payment) {
            if (empty($payment->number)) {
                $year   = now()->format('Y');
                $prefix = "PMT-{$year}-";
                $payment->number = UniqueIdGenerator::generate(
                    table:     'payments',
                    column:    'number',
                    prefix:    $prefix,
                    padLength: 6
                );
            }
        });
    }
}
