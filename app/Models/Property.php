<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Property extends Model
{
    use HasFactory;
     use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'square_feet',
        'register_number',
        'number',
        'island_id',
        'ward_id',
        'property_type_id',
        'street_address',
    ];

    public function island(): BelongsTo
    {
        return $this->belongsTo(Island::class);
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Wards::class);
    }

    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(PropertyTypes::class);
    }
}
