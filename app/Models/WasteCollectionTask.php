<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class WasteCollectionTask extends Model
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
        'completed_latitude',
        'completed_longitude',
        'status',
        'note',
        'index',
        'total_collected',
        'waste_data',
        'scheduled_at',
        'completed_at',
    ];

    protected $casts = [
        'completed_latitude' => 'decimal:7',
        'completed_longitude' => 'decimal:7',
        'total_collected' => 'decimal:2',
        'waste_data' => 'array',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

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

    public function calculateTotalCollected(): float
    {
        if (!$this->waste_data) return 0;

        return collect($this->waste_data)
            ->pluck('amount')
            ->filter()
            ->sum();
    }
}
