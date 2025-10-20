<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FormQuestionOption extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'form_question_id','value','label','position','meta'
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function($model){
            if(empty($model->id)) { $model->id = (string) Str::uuid(); }
        });
    }

    public function question()
    {
        return $this->belongsTo(FormQuestion::class,'form_question_id');
    }
}
