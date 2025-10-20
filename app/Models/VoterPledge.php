<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class VoterPledge extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'directory_id','election_id','type','status','note','created_by'
    ];

    public const TYPE_PROVISIONAL = 'provisional';
    public const TYPE_FINAL = 'final';

    public const STATUSES = [
        'strong_yes','yes','neutral','no','strong_no'
    ];

    public function voter(){ return $this->belongsTo(Directory::class,'directory_id'); }
    public function election(){ return $this->belongsTo(Election::class); }
    public function creator(){ return $this->belongsTo(User::class,'created_by'); }
}
