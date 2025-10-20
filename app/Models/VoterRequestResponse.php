<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class VoterRequestResponse extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = [
        'voter_request_id','responded_by','response','status_after'
    ];

    public function request(){ return $this->belongsTo(VoterRequest::class,'voter_request_id'); }
    public function responder(){ return $this->belongsTo(User::class,'responded_by'); }
}
