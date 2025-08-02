<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Client extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['name', 'secret', 'domain'];

    protected static function booted()
    {
        static::creating(function ($client) {
            $client->id = Str::uuid();
            $client->secret = bcrypt($client->secret);
        });
    }
}
