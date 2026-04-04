<?php

namespace App\Livewire\Election;

use App\Models\Directory;
use App\Models\Election;
use App\Models\VotingBox;
use App\Models\VotedRepresentative;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\EventLog;

class VotingBoxes extends Component
{
    use WithPagination, AuthorizesRequests;

    public string $search = '';
    public int $perPage = 25;

    public string $directoriesSearch = '';

    public ?string $selectedBoxId = null;
    public ?VotingBox $selectedBox = null;

    public ?string $electionId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'selectedBoxId' => ['except' => null, 'as' => 'box'],
        'electionId' => ['except' => null, 'as' => 'election'],
    ];

    public function mount(): void
    {
        // Pick active election by default (used only for final pledge join)
        $activeElectionId = Election::query()->where('status', Election::STATUS_ACTIVE)->value('id');
        $this->electionId = $this->electionId ?: $activeElectionId;

        if ($this->selectedBoxId) {
            $this->selectedBox = VotingBox::query()->find($this->selectedBoxId);
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function updatingDirectoriesSearch(): void
    {
        $this->resetPage('directoriesPage');
    }

    public function viewBox(string $boxId): void
    {
        $this->authorize('voting-boxes-render');

        $this->selectedBoxId = $boxId;
        $this->selectedBox = VotingBox::query()->find($boxId);
        $this->resetPage('directoriesPage');
    }

    public function clearBox(): void
    {
        $this->selectedBoxId = null;
        $this->selectedBox = null;
        $this->resetPage('directoriesPage');
    }

    public function getBoxesProperty()
    {
        $this->authorize('voting-boxes-render');

        $user = Auth::user();

        $q = VotingBox::query()
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where('name', 'like', $term);
            });

        // If the user has specific voting boxes assigned, restrict the list to those.
        // Otherwise (no assignments), keep current behavior and show all.
        if ($user && method_exists($user, 'votingBoxes')) {
            $assignedIds = $user->votingBoxes()->pluck('voting_boxes.id');
            if ($assignedIds->isNotEmpty()) {
                $q->whereIn('voting_boxes.id', $assignedIds);
            }
        }

        return $q
            ->withCount('directories')
            ->orderBy('name')
            ->paginate($this->perPage, ['*'], 'boxesPage');
    }

    public function getDirectoriesProperty()
    {
        if (! $this->selectedBoxId) {
            return collect();
        }

        $q = Directory::query()
            ->where('directories.voting_box_id', $this->selectedBoxId)
            ->leftJoin('parties', 'parties.id', '=', 'directories.party_id')
            ->leftJoin('sub_consites', 'sub_consites.id', '=', 'directories.sub_consite_id');

        // Directory search (NID / Serial / Name). Serial-only: prefix with "s" => "s 123" or "s123".
        $termRaw = trim((string) $this->directoriesSearch);
        if ($termRaw !== '') {
            $serialOnly = null;
            if (preg_match('/^s\s*(\d+)$/i', $termRaw, $m)) {
                $serialOnly = $m[1];
            }

            $like = '%'.$termRaw.'%';
            $q->where(function ($w) use ($like, $serialOnly) {
                $w->where('directories.name', 'like', $like)
                    ->orWhere('directories.id_card_number', 'like', $like)
                    ->orWhere('directories.serial', 'like', $like);

                if ($serialOnly !== null) {
                    $w->orWhere('directories.serial', $serialOnly);
                }
            });
        }

        // Determine which election to use for voted status
        $electionIdForVoted = $this->electionId;
        if (!$electionIdForVoted) {
            $latest = Election::orderBy('start_date', 'desc')->first(['id']);
            $electionIdForVoted = $latest?->id;
        }

        if ($electionIdForVoted) {
            $q->leftJoin('voted_representatives as vr', function ($join) use ($electionIdForVoted) {
                $join->on('vr.directory_id', '=', 'directories.id')
                    ->where('vr.election_id', '=', $electionIdForVoted);
            });
        }

        if ($this->electionId) {
            $q->leftJoin('voter_pledges as vp_final', function ($join) {
                $join->on('vp_final.directory_id', '=', 'directories.id')
                    ->where('vp_final.election_id', '=', $this->electionId)
                    ->where('vp_final.type', '=', \App\Models\VoterPledge::TYPE_FINAL);
            });
        }

        return $q->select([
                'directories.id',
                'directories.name',
                'directories.id_card_number',
                'directories.serial',
                'directories.phones',
                'directories.email',
                'directories.street_address',
                'directories.address',
                'parties.short_name as party_short',
                'sub_consites.code as sub_consite_code',
                DB::raw('COALESCE(vp_final.status, "pending") as final_pledge_status'),
                DB::raw('CASE WHEN vr.id IS NULL THEN 0 ELSE 1 END as is_voted'),
                'vr.voted_at as voted_at',
            ])
            ->orderBy('directories.name')
            ->paginate($this->perPage, ['*'], 'directoriesPage');
    }

    private function swal(string $icon, string $title, string $text = '', array $extra = []): void
    {
        $this->dispatch('swal', array_merge([
            'icon' => $icon,
            'title' => $title,
            'text' => $text,
            'showConfirmButton' => true,
        ], $extra));
    }

    public function markAsVoted(string $directoryId): void
    {
        $this->authorize('votedRepresentative-markAsVoted');

        // Use latest election (same behavior as voted-list)
        $electionId = $this->electionId;
        if (!$electionId) {
            $latest = Election::orderBy('start_date', 'desc')->first(['id']);
            $electionId = $latest?->id;
        }

        if (!$electionId) {
            $this->swal('error', 'No election', 'No election selected.');
            return;
        }

        $dir = Directory::query()
            ->select(['id', 'name', 'id_card_number', 'sub_consite_id', 'status'])
            ->where('id', $directoryId)
            ->first();

        if (!$dir || $dir->status !== 'Active') {
            $this->swal('warning', 'Not found', 'Directory not found or inactive.');
            return;
        }

        // Only allow voting for directories inside the selected box
        if ($this->selectedBoxId) {
            $inBox = Directory::query()->where('id', $dir->id)->where('voting_box_id', $this->selectedBoxId)->exists();
            if (!$inBox) {
                $this->swal('error', 'Invalid', 'This directory is not in the selected voting box.');
                return;
            }
        }

        if (VotedRepresentative::where('election_id', $electionId)->where('directory_id', $dir->id)->exists()) {
            $this->swal('info', 'Already voted', 'This directory is already marked as voted.');
            return;
        }

        DB::transaction(function () use ($dir, $electionId) {
            $vr = VotedRepresentative::create([
                'election_id' => $electionId,
                'directory_id' => $dir->id,
                'user_id' => Auth::id(),
                'voted_at' => now(),
            ]);

            EventLog::create([
                'user_id' => Auth::id(),
                'event_tab' => 'Election',
                'event_entry_id' => $dir->id,
                'event_type' => 'Representative Marked Voted',
                'description' => 'Marked representative as voted for election (voting boxes)',
                'event_data' => [
                    'election_id' => $electionId,
                    'directory_id' => $dir->id,
                    'voted_representative_id' => $vr->id,
                ],
                'ip_address' => request()->ip(),
            ]);
        });

        $this->swal('success', 'Saved', 'Marked as voted!', ['showConfirmButton' => false, 'timer' => 1200]);
        $this->resetPage();
    }

    public function render()
    {
        $directories = $this->directories;

        return view('livewire.election.voting-boxes', [
            'boxes' => $this->boxes,
            'directories' => $directories,
            'selectedBox' => $this->selectedBox,
        ]);
    }
}
