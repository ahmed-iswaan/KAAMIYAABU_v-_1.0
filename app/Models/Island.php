<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Island extends Model
{
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'atoll_id',
        'name',
        'code',
        'status',
    ];

    /**
     * An island belongs to an atoll.
     */
    public function atoll()
    {
        return $this->belongsTo(Atoll::class, 'atoll_id', 'id');
    }
}
