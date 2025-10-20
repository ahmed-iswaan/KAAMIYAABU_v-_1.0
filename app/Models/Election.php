<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Election extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name', 'start_date', 'end_date', 'status'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    public const STATUS_UPCOMING = 'Upcoming';
    public const STATUS_ACTIVE = 'Active';
    public const STATUS_COMPLETED = 'Completed';

    /**
     * Get the participants for the election.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }
}
