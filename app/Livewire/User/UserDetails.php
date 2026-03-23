<?php

namespace App\Livewire\User;

use App\Models\Directory;
use App\Models\Election;
use App\Models\ElectionDirectoryCallStatus;
use App\Models\ElectionDirectoryCallSubStatus;
use App\Models\SubStatus;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class UserDetails extends Component
{
    use WithPagination, AuthorizesRequests;

    protected $paginationTheme = 'bootstrap';

    public int $userId;

    public ?string $activeElectionId = null;
    public ?string $activeElectionName = null;

    public int $attemptsPerPage = 20;
    public int $completedPerPage = 20;

    /**
     * Active Sub Status options for mapping UUID => name.
     * Format: [id => name]
     */
    public array $activeSubStatuses = [];

    public function mount(int $user): void
    {
        $this->authorize('user-render');

        $this->userId = $user;

        $activeElection = Election::query()
            ->where('status', Election::STATUS_ACTIVE)
            ->first(['id', 'name']);

        $this->activeElectionId = $activeElection?->id ? (string) $activeElection->id : null;
        $this->activeElectionName = $activeElection?->name ? (string) $activeElection->name : null;

        $this->activeSubStatuses = SubStatus::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->mapWithKeys(fn($s) => [(string) $s->id => (string) $s->name])
            ->all();
    }

    public function render()
    {
        $this->authorize('user-render');

        $user = User::query()->findOrFail($this->userId);

        $attempts = collect();
        $completed = collect();

        if ($this->activeElectionId) {
            $attempts = ElectionDirectoryCallSubStatus::query()
                ->where('election_id', (string) $this->activeElectionId)
                ->where('updated_by', $this->userId)
                ->latest('updated_at')
                ->paginate($this->attemptsPerPage, ['*'], 'attemptsPage');

            $completed = ElectionDirectoryCallStatus::query()
                ->where('election_id', (string) $this->activeElectionId)
                ->where('updated_by', $this->userId)
                ->where('status', ElectionDirectoryCallStatus::STATUS_COMPLETED)
                ->latest(DB::raw('COALESCE(completed_at, updated_at)'))
                ->paginate($this->completedPerPage, ['*'], 'completedPage');
        }

        // preload directories referenced in current page (to show name/serial)
        $dirIds = collect($attempts->items() ?? [])->pluck('directory_id')
            ->merge(collect($completed->items() ?? [])->pluck('directory_id'))
            ->map(fn($v) => (string) $v)
            ->unique()
            ->values()
            ->all();

        $directories = count($dirIds)
            ? Directory::query()->whereIn('id', $dirIds)->get(['id', 'name', 'serial', 'id_card_number'])
                ->keyBy(fn($d) => (string) $d->id)
            : collect();

        return view('livewire.user.user-details', [
            'user' => $user,
            'attempts' => $attempts,
            'completed' => $completed,
            'directories' => $directories,
            'activeElectionId' => $this->activeElectionId,
            'activeElectionName' => $this->activeElectionName,
        ])->layout('layouts.master');
    }
}
