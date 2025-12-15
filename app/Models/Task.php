<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Services\UniqueIdGenerator; // added
use Illuminate\Database\Eloquent\Builder;

class Task extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'number',
        'title','notes','type','status','sub_status_id','priority','form_id','directory_id','election_id','due_at','follow_up_date','followup_at','completed_at','completed_by','follow_up_by','created_by','updated_by','meta',
        'deleted','deleted_at','deleted_by',
    ];

    protected $casts = [
        'meta' => 'array',
        'due_at' => 'datetime',
        'follow_up_date' => 'datetime',
        'followup_at' => 'datetime',
        'completed_at' => 'datetime',
        'deleted' => 'boolean',
        'deleted_at' => 'datetime',
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
            // Ensure followup_at set if creating with follow_up status
            if(($model->status ?? null) === 'follow_up' && empty($model->followup_at)){
                $model->followup_at = now();
            }
        });
        static::saving(function($model){
            // If status is follow_up and followup_at is empty, set today
            if(($model->status ?? null) === 'follow_up' && empty($model->followup_at)){
                $model->followup_at = now();
            }
        });
        static::addGlobalScope('not_deleted', function(Builder $builder){
            $builder->where('deleted', false);
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
    public function completedBy(){ return $this->belongsTo(User::class,'completed_by'); }
    public function followUpBy(){ return $this->belongsTo(User::class,'follow_up_by'); }
    public function subStatus(){ return $this->belongsTo(SubStatus::class,'sub_status_id'); }

    /* Helpers */
    public function scopeStatus($q,$status){ if($status) $q->where('status',$status); }
    public function markCompleted(){ $this->update(['status'=>'completed','completed_at'=>now(),'completed_by'=>auth()->id()]); }
    public function markFollowUp($date = null): void
    {
        $dt = $date ? now()->parse($date) : now();
        $this->update([
            'status' => 'follow_up',
            'follow_up_by' => auth()->id(),
            'follow_up_date' => $dt,
            'followup_at' => now(),
        ]);
    }
    public function isOverdue(): bool { return $this->status !== 'completed' && $this->due_at && $this->due_at->isPast(); }
    public function priorityBadge(): string {
        return match($this->priority){
            'urgent' => 'badge-light-danger',
            'high' => 'badge-light-warning',
            'low' => 'badge-light-secondary',
            default => 'badge-light-info'
        };
    }
    public function scopeWithDeleted(Builder $q): Builder { return $q->withoutGlobalScope('not_deleted'); }
    public function markDeleted(): void {
        $this->deleted = true;
        $this->deleted_at = now();
        $this->deleted_by = auth()->id();
        $this->save();
        \App\Models\EventLog::create([
            'user_id'=>auth()->id(),
            'event_type'=>'task_mark_deleted',
            'event_tab'=>'tasks',
            'event_entry_id'=>$this->id,
            'task_id'=>$this->id,
            'description'=>'Task marked deleted',
            'event_data'=>['task_id'=>$this->id,'deleted_at'=>$this->deleted_at,'deleted_by'=>$this->deleted_by],
            'ip_address'=>request()->ip(),
        ]);
    }
}
