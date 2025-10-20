<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Participant extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'election_id', 'sub_consite_id'
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function subConsite(): BelongsTo
    {
        return $this->belongsTo(SubConsite::class, 'sub_consite_id');
    }
}
