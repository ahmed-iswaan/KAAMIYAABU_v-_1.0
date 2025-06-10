<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingTelegramNotification extends Model
{
    use HasFactory;

        protected $fillable = [
        'chat_id',
        'message',
        'is_sent',
        'attempted_at',
    ];
}
