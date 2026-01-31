<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallCenterForm extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'call_center_forms';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'election_id',
        'directory_id',
        'q1_performance',
        'q2_reason',
        'q3_support',
        'q4_voting_area',
        'q4_other_text',
        'q5_help_needed',
        'q6_message_to_mayor',
        'created_by',
        'updated_by',
    ];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class, 'election_id');
    }

    public function directory(): BelongsTo
    {
        return $this->belongsTo(Directory::class, 'directory_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
