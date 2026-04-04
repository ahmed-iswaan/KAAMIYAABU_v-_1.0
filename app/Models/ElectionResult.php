<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElectionResult extends Model
{
    use HasFactory;

    protected $table = 'election_results';

    protected $fillable = [
        'election_id',
        'voting_box_id',
        'candidate_1_votes',
        'candidate_2_votes',
        'candidate_3_votes',
        'candidate_4_votes',
        'candidate_5_votes',
        'invalid_votes',
        'result_datetime',
    ];

    protected $casts = [
        'result_datetime' => 'datetime',
    ];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function votingBox(): BelongsTo
    {
        return $this->belongsTo(VotingBox::class);
    }
}
