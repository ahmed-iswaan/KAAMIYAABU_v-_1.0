<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Services\UniqueIdGenerator; // added

class VoterRequest extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = [
        'directory_id','election_id','request_type_id','amount','note','status','created_by','request_number'
    ];

    // Automatically assign a sequential human-friendly request_number if not provided
    protected static function booted()
    {
        static::creating(function($model){
            if(empty($model->request_number)){
                $model->request_number = UniqueIdGenerator::generate('voter_requests','request_number','VRQ-');
            }
        });
    }

    public function voter(){ return $this->belongsTo(Directory::class,'directory_id'); }
    public function election(){ return $this->belongsTo(Election::class); }
    public function type(){ return $this->belongsTo(RequestType::class,'request_type_id'); }
    public function author(){ return $this->belongsTo(User::class,'created_by'); }
    public function responses(){ return $this->hasMany(VoterRequestResponse::class,'voter_request_id'); }
}
