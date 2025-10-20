<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Services\UniqueIdGenerator; // added

class Task extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'number', // added
        'title','notes','type','status','priority','form_id','directory_id','election_id','due_at','completed_at','created_by','updated_by','meta'
    ];

    protected $casts = [
        'meta' => 'array',
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function($model){
            if(empty($model->id)) { $model->id = (string) Str::uuid(); }
            if(empty($model->priority)) { $model->priority = 'normal'; }
            // generate sequential human readable number if missing
            if(empty($model->number)) {
                $model->number = UniqueIdGenerator::generate('tasks','number','TSK-',6);
            }
        });
    }

    /* Relationships */
    public function users(){ return $this->belongsToMany(User::class,'task_user')->withTimestamps(); }
    // Backward compatibility alias
    public function assignees(){ return $this->users(); }
    public function form(){ return $this->belongsTo(Form::class); }
    public function directory(){ return $this->belongsTo(Directory::class); }
    public function election(){ return $this->belongsTo(Election::class); }
    public function creator(){ return $this->belongsTo(User::class,'created_by'); }
    public function updater(){ return $this->belongsTo(User::class,'updated_by'); }
    public function submission(){ return $this->hasOne(FormSubmission::class,'task_id'); }

    /* Helpers */
    public function scopeStatus($q,$status){ if($status) $q->where('status',$status); }
    public function markCompleted(){ $this->update(['status'=>'completed','completed_at'=>now()]); }
    public function isOverdue(): bool { return $this->status !== 'completed' && $this->due_at && $this->due_at->isPast(); }
    public function priorityBadge(): string {
        return match($this->priority){
            'urgent' => 'badge-light-danger',
            'high' => 'badge-light-warning',
            'low' => 'badge-light-secondary',
            default => 'badge-light-info'
        };
    }
}
