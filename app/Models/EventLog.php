<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EventLog extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = ['user_id', 'event_type', 'event_tab', 'event_entry_id','description', 'event_data', 'ip_address','task_id'];

    protected $casts = [
        'event_data' => 'array', // Cast JSON to array
    ];

    public function task(){ return $this->belongsTo(Task::class,'task_id'); }
}
