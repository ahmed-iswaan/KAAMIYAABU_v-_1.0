<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\DhiraaguSmsService;

class SendDhiraaguSmsJob implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    public string $to;
    public string $message;

    public function __construct(string $to, string $message)
    {
        $this->to      = $to;
        $this->message = $message;
    }

    public function handle(DhiraaguSmsService $sms)
    {
        $sms->send($this->to, $this->message);
    }

    public function retryAfter(): int
    {
        return 60; // retry after 60 seconds
    }
}