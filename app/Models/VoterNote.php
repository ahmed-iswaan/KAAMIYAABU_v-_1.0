<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class VoterNote extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['directory_id','election_id','note','created_by'];

    public function voter(){ return $this->belongsTo(Directory::class,'directory_id'); }
    public function election(){ return $this->belongsTo(Election::class); }
    public function author(){ return $this->belongsTo(User::class,'created_by'); }
}
