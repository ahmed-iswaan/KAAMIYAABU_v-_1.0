<?php

namespace App\Livewire\Election;

use App\Models\Election;
use App\Models\ElectionResult;
use App\Models\EventLog;
use App\Models\VotingBox;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class VoteResultsEntry extends Component
{
    use AuthorizesRequests;

    public ?string $electionId = null;
    public string $votingBoxId = '';

    public int $candidate1Votes = 0;
    public int $candidate2Votes = 0;
    public int $candidate3Votes = 0;
    public int $candidate4Votes = 0;
    public int $candidate5Votes = 0;
    public int $invalidVotes = 0;

    public int $eligibleCount = 0;

    public ?string $resultDatetime = null; // bound to datetime-local input

    public function mount(): void
    {
        $latest = Election::orderBy('start_date', 'desc')->first(['id']);
        $this->electionId = $latest?->id ? (string) $latest->id : null;
    }

    protected function allowedVotingBoxIds(): array
    {
        return Auth::user()?->votingBoxes()->pluck('users_voting_boxes.voting_box_id')->all() ?? [];
    }

    protected function rules(): array
    {
        return [
            'votingBoxId' => 'required|string',
            'candidate1Votes' => 'required|integer|min:0',
            'candidate2Votes' => 'required|integer|min:0',
            'candidate3Votes' => 'required|integer|min:0',
            'candidate4Votes' => 'required|integer|min:0',
            'candidate5Votes' => 'required|integer|min:0',
            'invalidVotes' => 'required|integer|min:0',
            'resultDatetime' => 'nullable|date',
        ];
    }

    public function updatedVotingBoxId(): void
    {
        $this->resetErrorBag();

        if (!$this->electionId || !$this->votingBoxId) {
            $this->eligibleCount = 0;
            return;
        }

        $allowed = $this->allowedVotingBoxIds();
        if (!in_array($this->votingBoxId, $allowed, true)) {
            $this->reset(['votingBoxId']);
            $this->eligibleCount = 0;
            return;
        }

        // Eligible voters derived from system directories in this voting box
        $this->eligibleCount = (int) \App\Models\Directory::query()
            ->where('status', 'Active')
            ->where('voting_box_id', $this->votingBoxId)
            ->count();

        $row = ElectionResult::query()
            ->where('election_id', $this->electionId)
            ->where('voting_box_id', $this->votingBoxId)
            ->first();

        $this->candidate1Votes = (int) ($row->candidate_1_votes ?? 0);
        $this->candidate2Votes = (int) ($row->candidate_2_votes ?? 0);
        $this->candidate3Votes = (int) ($row->candidate_3_votes ?? 0);
        $this->candidate4Votes = (int) ($row->candidate_4_votes ?? 0);
        $this->candidate5Votes = (int) ($row->candidate_5_votes ?? 0);
        $this->invalidVotes = (int) ($row->invalid_votes ?? 0);

        $this->resultDatetime = $row?->result_datetime?->format('Y-m-d\TH:i');
    }

    public function save(): void
    {
        $this->authorize('vote-results-entry-save');

        if (!$this->electionId) {
            $this->addError('electionId', 'Election is required.');
            return;
        }

        $allowed = $this->allowedVotingBoxIds();
        if (!in_array($this->votingBoxId, $allowed, true)) {
            $this->addError('votingBoxId', 'Not permitted.');
            return;
        }

        $this->validate();

        $result = ElectionResult::query()->updateOrCreate(
            [
                'election_id' => $this->electionId,
                'voting_box_id' => $this->votingBoxId,
            ],
            [
                'candidate_1_votes' => $this->candidate1Votes,
                'candidate_2_votes' => $this->candidate2Votes,
                'candidate_3_votes' => $this->candidate3Votes,
                'candidate_4_votes' => $this->candidate4Votes,
                'candidate_5_votes' => $this->candidate5Votes,
                'invalid_votes' => $this->invalidVotes,
                'result_datetime' => $this->resultDatetime ? \Illuminate\Support\Carbon::parse($this->resultDatetime) : null,
            ]
        );

        EventLog::create([
            'user_id' => Auth::id(),
            'event_tab' => 'Election',
            'event_entry_id' => $result->id,
            'event_type' => 'Election Results Saved',
            'description' => 'Saved election results by voting box',
            'event_data' => [
                'election_id' => (string) $this->electionId,
                'voting_box_id' => (string) $this->votingBoxId,
                'candidate_1_votes' => (int) $this->candidate1Votes,
                'candidate_2_votes' => (int) $this->candidate2Votes,
                'candidate_3_votes' => (int) $this->candidate3Votes,
                'candidate_4_votes' => (int) $this->candidate4Votes,
                'candidate_5_votes' => (int) $this->candidate5Votes,
                'invalid_votes' => (int) $this->invalidVotes,
                'total' => (int) $this->candidate1Votes + (int) $this->candidate2Votes + (int) $this->candidate3Votes + (int) $this->candidate4Votes + (int) $this->candidate5Votes + (int) $this->invalidVotes,
                'result_datetime' => (string) ($this->resultDatetime ?? ''),
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

        $this->dispatch('vote-results-updated');
    }

    public function render()
    {
        $this->authorize('vote-results-entry-render');

        $allowed = $this->allowedVotingBoxIds();

        $votingBoxes = VotingBox::query()
            ->whereIn('id', $allowed)
            ->orderBy('name')
            ->get(['id', 'name']);

        $totalVotes = (int) $this->candidate1Votes
            + (int) $this->candidate2Votes
            + (int) $this->candidate3Votes
            + (int) $this->candidate4Votes
            + (int) $this->candidate5Votes
            + (int) $this->invalidVotes;

        $chartLabels = [];
        $chartC1 = [];
        $chartC2 = [];
        $chartC3 = [];
        $chartC4 = [];
        $chartC5 = [];
        $chartInvalid = [];
        $chartTotal = [];

        $totals = [
            'c1' => 0,
            'c2' => 0,
            'c3' => 0,
            'c4' => 0,
            'c5' => 0,
            'invalid' => 0,
        ];

        if ($this->electionId && !empty($allowed)) {
            $rows = ElectionResult::query()
                ->join('voting_boxes', 'voting_boxes.id', '=', 'election_results.voting_box_id')
                ->where('election_results.election_id', $this->electionId)
                ->whereIn('election_results.voting_box_id', $allowed)
                ->groupBy('voting_boxes.id', 'voting_boxes.name')
                ->orderBy('voting_boxes.name')
                ->selectRaw('voting_boxes.name as label')
                ->selectRaw('SUM(election_results.candidate_1_votes) as c1')
                ->selectRaw('SUM(election_results.candidate_2_votes) as c2')
                ->selectRaw('SUM(election_results.candidate_3_votes) as c3')
                ->selectRaw('SUM(election_results.candidate_4_votes) as c4')
                ->selectRaw('SUM(election_results.candidate_5_votes) as c5')
                ->selectRaw('SUM(election_results.invalid_votes) as invalid_votes')
                ->get();

            $chartLabels = $rows->pluck('label')->values()->all();
            $chartC1 = $rows->pluck('c1')->map(fn($v) => (int) $v)->values()->all();
            $chartC2 = $rows->pluck('c2')->map(fn($v) => (int) $v)->values()->all();
            $chartC3 = $rows->pluck('c3')->map(fn($v) => (int) $v)->values()->all();
            $chartC4 = $rows->pluck('c4')->map(fn($v) => (int) $v)->values()->all();
            $chartC5 = $rows->pluck('c5')->map(fn($v) => (int) $v)->values()->all();
            $chartInvalid = $rows->pluck('invalid_votes')->map(fn($v) => (int) $v)->values()->all();
            $chartTotal = $rows->map(fn($r) => (int) $r->c1 + (int) $r->c2 + (int) $r->c3 + (int) $r->c4 + (int) $r->c5 + (int) $r->invalid_votes)->values()->all();

            $totals = [
                'c1' => (int) $rows->sum('c1'),
                'c2' => (int) $rows->sum('c2'),
                'c3' => (int) $rows->sum('c3'),
                'c4' => (int) $rows->sum('c4'),
                'c5' => (int) $rows->sum('c5'),
                'invalid' => (int) $rows->sum('invalid_votes'),
            ];
        }

        return view('livewire.election.vote-results-entry', [
            'votingBoxes' => $votingBoxes,
            'eligibleCount' => $this->eligibleCount,
            'totalVotes' => $totalVotes,
            'chart' => [
                'labels' => $chartLabels,
                'c1' => $chartC1,
                'c2' => $chartC2,
                'c3' => $chartC3,
                'c4' => $chartC4,
                'c5' => $chartC5,
                'invalid' => $chartInvalid,
                'total' => $chartTotal,
            ],
            'totals' => $totals,
        ]);
    }
}
