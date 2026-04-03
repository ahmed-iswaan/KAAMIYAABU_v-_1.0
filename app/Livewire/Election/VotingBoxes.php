<?php

namespace App\Livewire\Election;

use App\Models\Directory;
use App\Models\Election;
use App\Models\VotingBox;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class VotingBoxes extends Component
{
    use WithPagination, AuthorizesRequests;

    public string $search = '';
    public int $perPage = 25;

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

    public function viewBox(string $boxId): void
    {
        $this->authorize('voting-boxes-render');

        $this->selectedBoxId = $boxId;
        $this->selectedBox = VotingBox::query()->find($boxId);
        $this->resetPage();
    }

    public function clearBox(): void
    {
        $this->selectedBoxId = null;
        $this->selectedBox = null;
        $this->resetPage();
    }

    public function getBoxesProperty()
    {
        $this->authorize('voting-boxes-render');

        return VotingBox::query()
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where('name', 'like', $term);
            })
            ->withCount('directories')
            ->orderBy('name')
            ->paginate($this->perPage);
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
                'directories.phones',
                'directories.email',
                'directories.street_address',
                'directories.address',
                'parties.short_name as party_short',
                'sub_consites.code as sub_consite_code',
                DB::raw('COALESCE(vp_final.status, "pending") as final_pledge_status'),
            ])
            ->orderBy('directories.name')
            ->paginate($this->perPage);
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
