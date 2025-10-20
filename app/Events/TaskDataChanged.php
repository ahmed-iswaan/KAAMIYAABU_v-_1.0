<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskDataChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $taskId;
    public string $userId;
    public string $changeType;
    public array $extra;

    public function __construct(string $taskId, string $userId, string $changeType, array $extra = [])
    {
        $this->taskId = $taskId;
        $this->userId = $userId;
        $this->changeType = $changeType;
        $this->extra = $extra;
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('agent.tasks.' . $this->userId);
    }

    public function broadcastAs(): string
    {
        return 'TaskDataChanged';
    }

    public function broadcastWith(): array
    {
        return [
            'task_id' => $this->taskId,
            'change_type' => $this->changeType,
            'extra' => $this->extra,
        ];
    }
}
