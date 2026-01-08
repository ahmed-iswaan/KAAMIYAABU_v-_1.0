<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RepresentativeVotedChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $changeType;
    public string $directoryId;
    public ?string $electionId;
    public array $extra;

    public function __construct(string $changeType, $directoryId, $electionId = null, array $extra = [])
    {
        $this->changeType = $changeType;
        $this->directoryId = (string) $directoryId;
        $this->electionId = $electionId !== null ? (string) $electionId : null;
        $this->extra = $extra;
    }

    public function broadcastOn(): Channel
    {
        // Public channel: clients decide whether to refresh based on sub consite filtering.
        return new Channel('elections.representatives');
    }

    public function broadcastAs(): string
    {
        return 'RepresentativeVotedChanged';
    }

    public function broadcastWith(): array
    {
        return [
            'change_type' => $this->changeType,
            'directory_id' => $this->directoryId,
            'election_id' => $this->electionId,
            'extra' => $this->extra,
        ];
    }
}
