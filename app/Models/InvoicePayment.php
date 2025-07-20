<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class InvoicePayment extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table       = 'invoice_payments';
    public $incrementing  = false;
    protected $keyType     = 'string';

    protected $fillable = [
        'payment_id', 'invoice_id', 'applied_amount',
    ];

   protected $casts = [
        'applied_amount' => 'decimal:2',
    ];

    /**
     * The payment record.
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * The invoice record.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
