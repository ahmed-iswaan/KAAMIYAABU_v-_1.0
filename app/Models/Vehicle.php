<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Vehicle extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'registration_number',
        'driver_id',
        'model',
        'device_id',
        'status',
    ];

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
