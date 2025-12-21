<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoterProvisionalUserPledge extends Model
{
    use HasFactory;

    protected $table = 'voter_provisional_user_pledges';

    protected $fillable = [
        'election_id',
        'directory_id',
        'user_id',
        'status',
    ];

    public const STATUSES = ['yes','no','neutral']; // pending = no row

    public function election(){ return $this->belongsTo(Election::class); }
    public function directory(){ return $this->belongsTo(Directory::class); }
    public function user(){ return $this->belongsTo(User::class); }
}
