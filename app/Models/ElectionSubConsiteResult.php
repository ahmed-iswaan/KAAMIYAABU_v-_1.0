<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElectionSubConsiteResult extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'election_sub_consite_results';

    protected $fillable = [
        'election_id',
        'sub_consite_id',
        'total_eligible_voters',
        'yes_votes',
        'no_votes',
        'invalid_votes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'total_eligible_voters' => 'integer',
        'yes_votes' => 'integer',
        'no_votes' => 'integer',
        'invalid_votes' => 'integer',
    ];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function subConsite(): BelongsTo
    {
        return $this->belongsTo(SubConsite::class, 'sub_consite_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
