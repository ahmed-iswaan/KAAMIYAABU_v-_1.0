<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VotingBox extends Model
{
    use HasUuids;

    protected $table = 'voting_boxes';

    protected $fillable = [
        'name',
        'sub_consite_id',
    ];

    public function directories(): HasMany
    {
        return $this->hasMany(Directory::class, 'voting_box_id');
    }

    public function subConsite(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SubConsite::class, 'sub_consite_id');
    }
}
