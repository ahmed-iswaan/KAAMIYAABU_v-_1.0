<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectoryPhoneStatus extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    public const STATUS_NOT_CALLED = 'not_called';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_WRONG_NUMBER = 'wrong_number';
    public const STATUS_NO_ANSWER = 'no_answer';
    public const STATUS_BUSY = 'busy';
    public const STATUS_SWITCHED_OFF = 'switched_off';
    public const STATUS_CALLBACK = 'callback';

    public const STATUSES = [
        self::STATUS_NOT_CALLED,
        self::STATUS_COMPLETED,
        self::STATUS_WRONG_NUMBER,
        self::STATUS_NO_ANSWER,
        self::STATUS_BUSY,
        self::STATUS_SWITCHED_OFF,
        self::STATUS_CALLBACK,
    ];

    protected $fillable = [
        'directory_id',
        'phone',
        'status',
        'sub_status_id',
        'notes',
        'last_called_at',
        'last_called_by',
    ];

    protected $casts = [
        'last_called_at' => 'datetime',
    ];

    public function directory(): BelongsTo
    {
        return $this->belongsTo(Directory::class, 'directory_id');
    }

    public function lastCalledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_called_by');
    }

    public static function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        return $digits ?: '';
    }
}
