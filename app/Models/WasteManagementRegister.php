<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Services\UniqueIdGenerator;
use Illuminate\Support\Carbon;

class WasteManagementRegister extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'number',
        'register_number',
        'property_id',
        'directories_id',
        'block_count',
        'fk_waste_price_list',
        'floor',
        'applicant_is',
        'status',
        'status_change_note',
    ];

    protected static function booted()
    {
        static::creating(function (WasteManagementRegister $register) {
            // Generate unique 'number' in format WM-MUL/2025/0001
            $islandCode = 'MUL'; // You can make this dynamic later
            $year = Carbon::now()->format('Y');
            $prefix = "WM-{$islandCode}/{$year}/";

            $register->number = UniqueIdGenerator::generate(
                table: 'waste_management_registers',
                column: 'number',
                prefix: $prefix,
                padLength: 4
            );

            // Generate unique 'register_number' in format WC-0001
            $register->register_number = UniqueIdGenerator::generate(
                table: 'waste_management_registers',
                column: 'register_number',
                prefix: 'WC-',
                padLength: 4
            );
        });
    }

    // Relationships
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function wasteCollectionSchedule()
    {
        return $this->hasOne(WasteCollectionSchedule::class,'waste_management_register_id');
    }

    public function invoiceSchedule()
    {
        return $this->hasOne(InvoiceSchedule::class, 'ref_id');
    }


    public function directory()
    {
        return $this->belongsTo(Directory::class, 'directories_id');
    }

    public function priceList()
    {
        return $this->belongsTo(WasteCollectionPriceList::class, 'fk_waste_price_list');
    }
}
