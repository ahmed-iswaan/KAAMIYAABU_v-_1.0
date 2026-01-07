<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VotedRepresentative extends Model
{
    use HasFactory;

    protected $table = 'voted_representatives';

    protected $fillable = [
        'election_id',
        'directory_id',
        'user_id',
        'voted_at',
    ];

    public function directory() { return $this->belongsTo(Directory::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function election() { return $this->belongsTo(Election::class); }
}
