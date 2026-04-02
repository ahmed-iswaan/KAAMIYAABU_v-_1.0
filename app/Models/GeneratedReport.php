<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class GeneratedReport extends Model
{
    use HasUuids;

    protected $table = 'generated_reports';

    protected $fillable = [
        'type',
        'status',
        'user_id',
        'filename',
        'disk',
        'path',
        'params',
        'error',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'params' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
}
