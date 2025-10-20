<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FormQuestion extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'form_id','form_section_id','type','code','question_text','help_text','is_required','validation_rules','meta','position'
    ];

    protected $casts = [
        'validation_rules' => 'array',
        'meta' => 'array',
        'is_required' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function($model){
            if(empty($model->id)) { $model->id = (string) Str::uuid(); }
        });
    }

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function section()
    {
        return $this->belongsTo(FormSection::class,'form_section_id');
    }

    public function options()
    {
        return $this->hasMany(FormQuestionOption::class)->orderBy('position');
    }
}
