<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class WasteCollectionSchedule extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'property_id',
        'directories_id',
        'waste_management_register_id',
        'driver_id',
        'vehicle_id',
        'start_date',
        'next_collection_date',
        'recurrence',
        'total_cycles',
        'generated_count',
        'is_active',
        'waste_data',
        'note',
    ];

    protected $casts = [
        'start_date'            => 'date',
        'next_collection_date' => 'date',
        'waste_data'            => 'array',
        'is_active'             => 'boolean',
    ];

    // Relationships
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id')->withDefault();
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function directory()
    {
        return $this->belongsTo(Directory::class, 'directories_id');
    }

    public function register()
        {
            return $this->belongsTo(WasteManagementRegister::class, 'waste_management_register_id');
        }
}
