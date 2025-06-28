<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class WasteType extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'unit',
        'unit_quantity',
        'index',
        'total_collection',
    ];

    protected $casts = [
        'unit_quantity' => 'decimal:2',
        'total_collection' => 'decimal:2',
    ];

    public function addToTotal($amount)
    {
        $this->increment('total_collection', $amount);
    }

    public function subtractFromTotal($amount)
    {
        $this->decrement('total_collection', $amount);
    }
}