<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Atoll extends Model
{
    use HasFactory;
    use HasUuids;

    // UUID primary key
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'code', 
        'status',
    ];

}
