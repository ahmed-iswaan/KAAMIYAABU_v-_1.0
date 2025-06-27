<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class InvoiceSchedule extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'start_date' => 'date',
        'next_invoice_date' => 'date',
        'lines' => 'array',
        'is_active' => 'boolean',
    ];

    protected $fillable = [
        'property_id',
        'directories_id',
        'invoice_tag',
        'ref_id',
        'fine_rate',
        'fine_interval',
        'fine_grace_period',
        'due_days',
        'start_date',
        'next_invoice_date',
        'recurrence',
        'total_cycles',
        'generated_count',
        'is_active',
        'lines',
    ];
}
