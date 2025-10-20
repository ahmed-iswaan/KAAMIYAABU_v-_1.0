<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FormSubmissionAnswer extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'form_submission_id','form_question_id','value_text','value_text_dv','value_json','value_number','value_date','meta'
    ];

    protected $casts = [
        'value_json' => 'array',
        'meta' => 'array',
        'value_date' => 'date',
    ];

    protected static function booted()
    {
        static::creating(function($model){
            if(empty($model->id)) { $model->id = (string) Str::uuid(); }
        });
    }

    public function submission(){ return $this->belongsTo(FormSubmission::class,'form_submission_id'); }
    public function question(){ return $this->belongsTo(FormQuestion::class,'form_question_id'); }
}
