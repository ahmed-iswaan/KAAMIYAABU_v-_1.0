<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consite extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'code', 'name', 'status'
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    public function subConsites(): HasMany
    {
        return $this->hasMany(SubConsite::class);
    }
}
