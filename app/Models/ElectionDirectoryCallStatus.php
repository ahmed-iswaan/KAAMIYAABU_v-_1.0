<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElectionDirectoryCallStatus extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'election_directory_call_statuses';

    protected $keyType = 'string';
    public $incrementing = false;

    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_UNREACHABLE = 'unreachable';
    public const STATUS_CALLBACK = 'callback';
    public const STATUS_WRONG_NUMBER = 'wrong_number';
    public const STATUS_DO_NOT_CALL = 'do_not_call';

    public const STATUSES = [
        self::STATUS_NOT_STARTED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_UNREACHABLE,
        self::STATUS_CALLBACK,
        self::STATUS_WRONG_NUMBER,
        self::STATUS_DO_NOT_CALL,
    ];

    protected $fillable = [
        'election_id',
        'directory_id',
        'status',
        'notes',
        'updated_by',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class, 'election_id');
    }

    public function directory(): BelongsTo
    {
        return $this->belongsTo(Directory::class, 'directory_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
