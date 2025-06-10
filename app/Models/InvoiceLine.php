<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class InvoiceLine extends Model
{
    use HasFactory;
    use HasUuids;

    public $incrementing = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'invoice_id','category_id',
        'description','quantity','unit_price',
    ];


    protected $casts = [
        'quantity'   => 'integer',
        'unit_price' => 'decimal:2',
    ];

    protected static function booted()
    {
        foreach (['created','updated','deleted'] as $evt) {
            static::$evt(function (InvoiceLine $line) {
                $line->invoice->save();  // fires Invoice::saving → recalculates
            });
        }
    }

    /**
     * The invoice this line belongs to.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    /**
     * The category of this line item.
     */
    public function category()
    {
        return $this->belongsTo(InvoiceCategory::class, 'category_id');
    }
}
