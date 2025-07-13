<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Country extends Model
{
    use HasFactory;
    use HasUuids;

    // UUID primary key
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'iso_codes',
        'country_code',
        'dialing_code',
        'status',
    ];
}
