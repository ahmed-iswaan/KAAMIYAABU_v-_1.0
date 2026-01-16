<?php

namespace App\Livewire\Election;

use App\Models\Election;
use App\Models\ElectionSubConsiteResult;
use App\Models\SubConsite;
use App\Models\EventLog;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class VoteResultsEntry extends Component
{
    use AuthorizesRequests;

    public ?string $electionId = null;
    public string $subConsiteId = '';

    public int $totalEligibleVoters = 0;
    public int $yesVotes = 0;
    public int $noVotes = 0;
    public int $invalidVotes = 0;

    public function mount(): void
    {
        $latest = Election::orderBy('start_date', 'desc')->first(['id']);
        $this->electionId = $latest?->id ? (string) $latest->id : null;
    }

    protected function allowedSubConsiteIds(): array
    {
        return Auth::user()?->subConsites()->pluck('sub_consites.id')->all() ?? [];
    }

    protected function rules(): array
    {
        return [
            'subConsiteId' => 'required|string',
            'totalEligibleVoters' => 'required|integer|min:0',
            'yesVotes' => 'required|integer|min:0',
            'noVotes' => 'required|integer|min:0',
            'invalidVotes' => 'required|integer|min:0',
        ];
    }

    public function updatedSubConsiteId(): void
    {
        $this->resetErrorBag();

        if (!$this->electionId || !$this->subConsiteId) {
            return;
        }

        $allowed = $this->allowedSubConsiteIds();
        if (!in_array($this->subConsiteId, $allowed, true)) {
            $this->reset(['subConsiteId']);
            return;
        }

        $row = ElectionSubConsiteResult::query()
            ->where('election_id', $this->electionId)
            ->where('sub_consite_id', $this->subConsiteId)
            ->first();

        // Auto-fill eligible voters from system (Active directories in sub consite)
        $eligibleFromSystem = (int) \App\Models\Directory::query()
            ->where('status', 'Active')
            ->where('sub_consite_id', $this->subConsiteId)
            ->count();

        // Prefer saved value if it exists; otherwise use system-derived eligible
        $this->totalEligibleVoters = (int) ($row->total_eligible_voters ?? 0);
        if ($this->totalEligibleVoters <= 0) {
            $this->totalEligibleVoters = $eligibleFromSystem;
        }

        $this->yesVotes = (int) ($row->yes_votes ?? 0);
        $this->noVotes = (int) ($row->no_votes ?? 0);
        $this->invalidVotes = (int) ($row->invalid_votes ?? 0);
    }

    public function save(): void
    {
        $this->authorize('vote-results-entry-save');

        if (!$this->electionId) {
            $this->addError('electionId', 'Election is required.');
            return;
        }

        $allowed = $this->allowedSubConsiteIds();
        if (!in_array($this->subConsiteId, $allowed, true)) {
            $this->addError('subConsiteId', 'Not permitted.');
            return;
        }

        $this->validate();

        $result = ElectionSubConsiteResult::query()->updateOrCreate(
            [
                'election_id' => $this->electionId,
                'sub_consite_id' => $this->subConsiteId,
            ],
            [
                'total_eligible_voters' => $this->totalEligibleVoters,
                'yes_votes' => $this->yesVotes,
                'no_votes' => $this->noVotes,
                'invalid_votes' => $this->invalidVotes,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]
        );

        EventLog::create([
            'user_id' => Auth::id(),
            'event_tab' => 'Election',
            'event_entry_id' => $result->id,
            'event_type' => 'Vote Results Saved',
            'description' => 'Saved vote results by sub consite',
            'event_data' => [
                'election_id' => (string) $this->electionId,
                'sub_consite_id' => (string) $this->subConsiteId,
                'total_eligible_voters' => (int) $this->totalEligibleVoters,
                'yes_votes' => (int) $this->yesVotes,
                'no_votes' => (int) $this->noVotes,
                'invalid_votes' => (int) $this->invalidVotes,
                'turnout' => (int) $this->yesVotes + (int) $this->noVotes + (int) $this->invalidVotes,
            ],
            'ip_address' => request()->ip(),
        ]);

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Saved',
            'text' => 'Results saved.',
            'showConfirmButton' => false,
            'timer' => 1200,
        ]);

        // Trigger charts rebuild on the client after Livewire updates the DOM
        $this->dispatch('vote-results-updated');
    }

    public function render()
    {
        $this->authorize('vote-results-entry-render');

        $allowed = $this->allowedSubConsiteIds();

        $subConsites = SubConsite::query()
            ->whereIn('id', $allowed)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $turnout = (int) $this->yesVotes + (int) $this->noVotes + (int) $this->invalidVotes;

        // Chart: aggregate by sub consite
        $chartLabels = [];
        $chartEligible = [];
        $chartYes = [];
        $chartNo = [];
        $chartInvalid = [];
        $chartTurnout = [];

        // Pie Totals (overall)
        $totals = [
            'yes' => 0,
            'no' => 0,
            'invalid' => 0,
        ];

        if ($this->electionId && !empty($allowed)) {
            $rows = ElectionSubConsiteResult::query()
                ->join('sub_consites', 'sub_consites.id', '=', 'election_sub_consite_results.sub_consite_id')
                ->where('election_sub_consite_results.election_id', $this->electionId)
                ->whereIn('election_sub_consite_results.sub_consite_id', $allowed)
                ->groupBy('sub_consites.id', 'sub_consites.code')
                ->orderBy('sub_consites.code')
                ->selectRaw('sub_consites.code as label')
                ->selectRaw('SUM(election_sub_consite_results.total_eligible_voters) as eligible')
                ->selectRaw('SUM(election_sub_consite_results.yes_votes) as yes_votes')
                ->selectRaw('SUM(election_sub_consite_results.no_votes) as no_votes')
                ->selectRaw('SUM(election_sub_consite_results.invalid_votes) as invalid_votes')
                ->get();

            $chartLabels = $rows->pluck('label')->values()->all();
            $chartEligible = $rows->pluck('eligible')->map(fn($v) => (int) $v)->values()->all();
            $chartYes = $rows->pluck('yes_votes')->map(fn($v) => (int) $v)->values()->all();
            $chartNo = $rows->pluck('no_votes')->map(fn($v) => (int) $v)->values()->all();
            $chartInvalid = $rows->pluck('invalid_votes')->map(fn($v) => (int) $v)->values()->all();
            $chartTurnout = $rows->map(fn($r) => (int) $r->yes_votes + (int) $r->no_votes + (int) $r->invalid_votes)->values()->all();

            $totals = [
                'yes' => (int) $rows->sum('yes_votes'),
                'no' => (int) $rows->sum('no_votes'),
                'invalid' => (int) $rows->sum('invalid_votes'),
            ];
        }

        return view('livewire.election.vote-results-entry', [
            'subConsites' => $subConsites,
            'turnout' => $turnout,
            'turnoutOk' => $turnout <= (int) $this->totalEligibleVoters,
            'chart' => [
                'labels' => $chartLabels,
                'eligible' => $chartEligible,
                'yes' => $chartYes,
                'no' => $chartNo,
                'invalid' => $chartInvalid,
                'turnout' => $chartTurnout,
            ],
            'totals' => $totals,
        ]);
    }
}
