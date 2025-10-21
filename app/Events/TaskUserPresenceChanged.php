<?php
namespace App\Events;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class TaskUserPresenceChanged implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels, Dispatchable;

    public $taskId;
    public $userId;
    public $isOnline;

    public function __construct($taskId, $userId, $isOnline)
    {
        $this->taskId = $taskId;
        $this->userId = $userId;
        $this->isOnline = $isOnline;
    }

    public function broadcastOn()
    {
        return new PresenceChannel('task.presence.' . $this->taskId);
    }

    public function broadcastWith()
    {
        return [
            'taskId' => $this->taskId,
            'userId' => $this->userId,
            'isOnline' => $this->isOnline,
        ];
    }

    public function broadcastAs()
    {
        return 'TaskUserPresenceChanged';
    }
}
