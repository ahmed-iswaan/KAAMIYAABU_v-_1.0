<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // use immediate broadcast
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoterDataChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $changeType;
    public string $voter_id; // changed from int to string to support UUID
    public ?string $election_id; // changed from ?int to ?string to support UUID
    public array $extra;

    public function __construct(string $changeType, $voterId, $electionId = null, array $extra = [])
    {
        $this->changeType = $changeType;
        $this->voter_id = (string)$voterId; // cast to string
        $this->election_id = $electionId !== null ? (string)$electionId : null;
        $this->extra = $extra;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('elections.voters');
    }

    public function broadcastAs(): string
    {
        return 'VoterDataChanged';
    }

    public function broadcastWith(): array
    {
        return [
            'voter_id' => $this->voter_id,
            'election_id' => $this->election_id,
            'change_type' => $this->changeType,
            'extra' => $this->extra,
        ];
    }
}
