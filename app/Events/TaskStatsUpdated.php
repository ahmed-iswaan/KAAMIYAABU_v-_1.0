<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class TaskStatsUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $taskId;
    public string $changeType;
    public array $userIds; // users affected (assigned)
    public array $extra;

    public function __construct(string $taskId, string $changeType, array $userIds = [], array $extra = [])
    {
        $this->taskId = $taskId;
        $this->changeType = $changeType;
        $this->userIds = $userIds;
        $this->extra = $extra;
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('tasks.global')];
    }

    public function broadcastAs(): string
    {
        return 'TaskStatsUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'task_id' => $this->taskId,
            'change_type' => $this->changeType,
            'user_ids' => $this->userIds,
            'extra' => $this->extra,
        ];
    }
}
