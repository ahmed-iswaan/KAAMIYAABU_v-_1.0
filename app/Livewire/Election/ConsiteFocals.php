<?php

namespace App\Livewire\Election;

use App\Models\Directory;
use App\Models\Election;
use App\Models\VoterPledge;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ConsiteFocals extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $electionId;

    public $search = '';
    public $subConsiteId = '';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'representative-voted-changed' => 'handleRepresentativeVotedChanged',
    ];

    protected function swal(string $icon, string $title, string $text = '', array $extra = []): void
    {
        $this->dispatch('swal', array_merge([
            'icon' => $icon,
            'title' => $title,
            'text' => $text,
            'showConfirmButton' => true,
        ], $extra));
    }

    public function mount(): void
    {
        $latest = Election::orderBy('start_date', 'desc')->first(['id']);
        $this->electionId = $latest?->id;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSubConsiteId(): void
    {
        $this->resetPage();
    }

    public function updatedElectionId(): void
    {
        // election is fixed to latest; ignore user changes
    }

    protected function allowedSubConsiteIds(): array
    {
        return Auth::user()?->subConsites()->pluck('sub_consites.id')->all() ?? [];
    }

    // View-only page: no actions.

    public function handleRepresentativeVotedChanged(array $payload = []): void
    {
        // If an election filter is active, only refresh for that election
        $electionId = $payload['election_id'] ?? null;
        if ($this->electionId && $electionId && (string)$this->electionId !== (string)$electionId) {
            return;
        }

        // Refresh the not-voted list
        $this->resetPage();
    }

    public function render()
    {
        $this->authorize('consites-focals-render');

        $allowed = $this->allowedSubConsiteIds();

        $subConsites = Auth::user()?->subConsites()->orderBy('code')->get(['sub_consites.id', 'code', 'name']);

        $directories = Directory::query()
            ->select([
                'id',
                'name',
                'profile_picture',
                'id_card_number',
                'sub_consite_id',
                'address','street_address',
                'current_address','current_street_address',
                'phones',
            ])
            ->addSelect([
                'final_pledge_status' => VoterPledge::select('status')
                    ->whereColumn('directory_id', 'directories.id')
                    ->where('election_id', $this->electionId)
                    ->where('type', VoterPledge::TYPE_FINAL)
                    ->limit(1),
            ])
            ->with(['subConsite:id,code,name'])
            ->where('status', 'Active')
            ->whereIn('sub_consite_id', $allowed)
            ->when($this->subConsiteId, fn($q) => $q->where('sub_consite_id', $this->subConsiteId))
            ->when($this->search, function ($q) {
                $s = trim($this->search);
                $q->where(function ($qq) use ($s) {
                    $qq->where('name', 'like', "%{$s}%")
                       ->orWhere('id_card_number', 'like', "%{$s}%")
                       ->orWhere('address', 'like', "%{$s}%")
                       ->orWhere('street_address', 'like', "%{$s}%")
                       ->orWhere('current_address', 'like', "%{$s}%")
                       ->orWhere('current_street_address', 'like', "%{$s}%")
                       // phones is JSON/array cast; use JSON search (MySQL) and fallback LIKE
                       ->orWhereRaw("JSON_SEARCH(phones, 'one', ?) IS NOT NULL", [$s])
                       ->orWhere('phones', 'like', "%{$s}%");
                });
            })
            ->whereNotExists(function ($q) {
                $q->selectRaw(1)
                  ->from('voted_representatives')
                  ->whereColumn('voted_representatives.directory_id', 'directories.id')
                  ->where('voted_representatives.election_id', $this->electionId);
            })
            ->orderByRaw("CASE
                WHEN final_pledge_status IN ('strong_yes','yes') THEN 0
                WHEN final_pledge_status = 'neutral' THEN 1
                WHEN final_pledge_status IS NULL OR final_pledge_status = '' THEN 2
                ELSE 3
            END")
            ->orderBy('name')
            ->paginate(25);

        return view('livewire.election.consite-focals', [
            'directories' => $directories,
            'subConsites' => $subConsites,
        ]);
    }
}
