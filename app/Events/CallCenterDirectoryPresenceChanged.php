<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcasts presence/locking information for Call Center directory modal.
 *
 * This can be used to show "Being viewed by X" or to prevent multiple agents
 * from editing the same directory at the same time.
 */
class CallCenterDirectoryPresenceChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $directory_id;
    public ?string $election_id;
    public string $action; // opened|closed|ping
    public string $user_id;
    public ?string $user_name;

    public function __construct(
        $directoryId,
        $electionId,
        string $action,
        $userId,
        ?string $userName = null,
    ) {
        $this->directory_id = (string) $directoryId;
        $this->election_id = $electionId !== null ? (string) $electionId : null;
        $this->action = $action;
        $this->user_id = (string) $userId;
        $this->user_name = $userName;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('call-center.presence');
    }

    public function broadcastAs(): string
    {
        return 'CallCenterDirectoryPresenceChanged';
    }

    public function broadcastWith(): array
    {
        return [
            'directory_id' => (string) $this->directory_id,
            'election_id' => (string) ($this->election_id ?? ''),
            'action' => (string) $this->action,
            'user_id' => (string) $this->user_id,
            'user_name' => (string) ($this->user_name ?? ''),
        ];
    }
}
