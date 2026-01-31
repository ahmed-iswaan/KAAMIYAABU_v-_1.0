<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElectionDirectoryCallSubStatus extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'election_directory_call_sub_statuses';

    protected $keyType = 'string';
    public $incrementing = false;

    public const ATTEMPTS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

    protected $fillable = [
        'election_id',
        'directory_id',
        'phone_number',
        'attempt',
        'sub_status_id',
        'notes',
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

    public function subStatus(): BelongsTo
    {
        return $this->belongsTo(SubStatus::class, 'sub_status_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
