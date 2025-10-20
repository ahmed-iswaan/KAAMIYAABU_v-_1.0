<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubConsite extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'consite_id', 'code', 'name', 'status'
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    public function consite(): BelongsTo
    {
        return $this->belongsTo(Consite::class);
    }

    public function directories(): HasMany
    {
        return $this->hasMany(Directory::class, 'sub_consite_id');
    }
}
