<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Form extends Model
{
    use HasFactory;

    public $incrementing = false; // UUID primary key
    protected $keyType = 'string';

    protected $fillable = [
        'slug','language','title','description','status','version','created_by','updated_by','meta'
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

    public function sections()
    {
        return $this->hasMany(FormSection::class)->orderBy('position');
    }

    public function questions()
    {
        return $this->hasMany(FormQuestion::class)->orderBy('position');
    }
}
