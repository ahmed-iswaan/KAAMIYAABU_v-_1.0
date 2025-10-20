<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FormSubmission extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'form_id','submission_uuid','submitted_by','assigned_agent_id','status','meta','directory_id','election_id','task_id'
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function($model){
            if(empty($model->id)) { $model->id = (string) Str::uuid(); }
            if(empty($model->submission_uuid)){
                $model->submission_uuid = (string) Str::uuid();
            }
        });
    }

    public function form(){ return $this->belongsTo(Form::class); }
    public function answers(){ return $this->hasMany(FormSubmissionAnswer::class); }
    public function submitter(){ return $this->belongsTo(User::class,'submitted_by'); }
    public function agent(){ return $this->belongsTo(User::class,'assigned_agent_id'); }
    public function task(){ return $this->belongsTo(Task::class,'task_id'); }
}
