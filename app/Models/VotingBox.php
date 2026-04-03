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
    ];

    public function directories(): HasMany
    {
        return $this->hasMany(Directory::class, 'voting_box_id');
    }
}
