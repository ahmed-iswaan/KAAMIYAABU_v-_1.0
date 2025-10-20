<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class VoterOpinion extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = [
        'directory_id','election_id','opinion_type_id','rating','note','taken_by',
        'status'
    ];

    public function voter(){ return $this->belongsTo(Directory::class,'directory_id'); }
    public function election(){ return $this->belongsTo(Election::class); }
    public function type(){ return $this->belongsTo(OpinionType::class,'opinion_type_id'); }
    public function takenBy(){ return $this->belongsTo(User::class,'taken_by'); }
}
