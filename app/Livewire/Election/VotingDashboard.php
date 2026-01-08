<?php

namespace App\Livewire\Election;

use App\Models\Election;
use App\Models\VotedRepresentative;
use App\Models\VoterPledge;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class VotingDashboard extends Component
{
    use AuthorizesRequests;

    public ?string $electionId = null;

    /**
     * If you later want to allow selecting election from UI.
     */
    public function mount(): void
    {
        $latest = Election::orderBy('start_date', 'desc')->first(['id']);
        $this->electionId = $latest?->id ? (string) $latest->id : null;
    }

    protected $listeners = [
        'representative-voted-changed' => 'handleRepresentativeVotedChanged',
    ];

    protected function allowedSubConsiteIds(): array
    {
        // Ensure fresh read (avoid any stale in-memory relation cache on long-lived Livewire component)
        return Auth::user()?->subConsites()
            ->newQuery()
            ->pluck('sub_consites.id')
            ->all() ?? [];
    }

    public function handleRepresentativeVotedChanged(array $payload = []): void
    {
        // if dashboard is locked to latest election, still keep safe check
        $incomingElectionId = $payload['election_id'] ?? null;
        if ($this->electionId && $incomingElectionId && (string) $this->electionId !== (string) $incomingElectionId) {
            return;
        }

        // Rebuild stats and push to browser so charts can update without relying on DOM-morph timing
        $stats = $this->buildStats();

        // Optional debug
        if (config('app.debug') && ($payload['extra']['debug'] ?? false)) {
            logger()->debug('VotingDashboard stats rebuilt', ['stats' => $stats, 'payload' => $payload]);
        }

        $this->dispatch('voting-dashboard:stats-updated', stats: $stats);
        $this->dispatch('$refresh');
    }

    protected function buildStats(): array
    {
        if (!$this->electionId) {
            return [
                'totalVoted' => 0,
                'totalNotVoted' => 0,
                'pledgeLabels' => [],
                'pledgeVotedCounts' => [],
                'pledgeNotVotedCounts' => [],
                'subConsiteLabels' => [],
                'subConsiteVotedCounts' => [],
                'subConsiteNotVotedCounts' => [],
            ];
        }

        $allowed = $this->allowedSubConsiteIds();
        if (empty($allowed)) {
            return [
                'totalVoted' => 0,
                'totalNotVoted' => 0,
                'pledgeLabels' => [],
                'pledgeVotedCounts' => [],
                'pledgeNotVotedCounts' => [],
                'subConsiteLabels' => [],
                'subConsiteVotedCounts' => [],
                'subConsiteNotVotedCounts' => [],
            ];
        }

        // Voted (Active directories in allowed subConsites)
        $votedBase = VotedRepresentative::query()
            ->join('directories', 'directories.id', '=', 'voted_representatives.directory_id')
            ->where('voted_representatives.election_id', $this->electionId)
            ->where('directories.status', 'Active')
            ->whereIn('directories.sub_consite_id', $allowed);

        $totalVoted = (clone $votedBase)->distinct('voted_representatives.directory_id')->count('voted_representatives.directory_id');

        // Not voted = Active directories in allowed subConsites that do not appear in voted_representatives for election
        $totalNotVoted = DB::table('directories')
            ->where('directories.status', 'Active')
            ->whereIn('directories.sub_consite_id', $allowed)
            ->whereNotExists(function ($q) {
                $q->selectRaw(1)
                    ->from('voted_representatives')
                    ->whereColumn('voted_representatives.directory_id', 'directories.id')
                    ->where('voted_representatives.election_id', $this->electionId);
            })
            ->count();

        // Final pledge buckets (shared labels)
        $pledgeOrder = ['yes', 'neutral', 'no', 'pending'];
        $pledgeLabels = ['Yes', 'Neutral', 'No', 'Pending'];

        // Voted by final pledge status
        $pledgeVotedRaw = DB::table('voted_representatives')
            ->join('directories', 'directories.id', '=', 'voted_representatives.directory_id')
            ->leftJoin('voter_pledges as vp', function ($join) {
                $join->on('vp.directory_id', '=', 'directories.id')
                    ->where('vp.election_id', '=', $this->electionId)
                    ->where('vp.type', '=', VoterPledge::TYPE_FINAL);
            })
            ->where('voted_representatives.election_id', $this->electionId)
            ->where('directories.status', 'Active')
            ->whereIn('directories.sub_consite_id', $allowed)
            ->selectRaw(
                "CASE\n" .
                "  WHEN vp.status IN ('yes', 'strong_yes') THEN 'yes'\n" .
                "  WHEN vp.status IN ('no', 'strong_no') THEN 'no'\n" .
                "  WHEN vp.status = 'neutral' THEN 'neutral'\n" .
                "  ELSE 'pending'\n" .
                "END as status, COUNT(DISTINCT voted_representatives.directory_id) as cnt"
            )
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $pledgeVotedCounts = [];
        foreach ($pledgeOrder as $key) {
            $pledgeVotedCounts[] = (int) ($pledgeVotedRaw[$key] ?? 0);
        }

        // Not voted by final pledge status
        $pledgeNotVotedRaw = DB::table('directories')
            ->leftJoin('voter_pledges as vp', function ($join) {
                $join->on('vp.directory_id', '=', 'directories.id')
                    ->where('vp.election_id', '=', $this->electionId)
                    ->where('vp.type', '=', VoterPledge::TYPE_FINAL);
            })
            ->where('directories.status', 'Active')
            ->whereIn('directories.sub_consite_id', $allowed)
            ->whereNotExists(function ($q) {
                $q->selectRaw(1)
                    ->from('voted_representatives')
                    ->whereColumn('voted_representatives.directory_id', 'directories.id')
                    ->where('voted_representatives.election_id', $this->electionId);
            })
            ->selectRaw(
                "CASE\n" .
                "  WHEN vp.status IN ('yes', 'strong_yes') THEN 'yes'\n" .
                "  WHEN vp.status IN ('no', 'strong_no') THEN 'no'\n" .
                "  WHEN vp.status = 'neutral' THEN 'neutral'\n" .
                "  ELSE 'pending'\n" .
                "END as status, COUNT(DISTINCT directories.id) as cnt"
            )
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $pledgeNotVotedCounts = [];
        foreach ($pledgeOrder as $key) {
            $pledgeNotVotedCounts[] = (int) ($pledgeNotVotedRaw[$key] ?? 0);
        }

        // Voted by sub consite
        $subConsiteRows = DB::table('voted_representatives')
            ->join('directories', 'directories.id', '=', 'voted_representatives.directory_id')
            ->join('sub_consites', 'sub_consites.id', '=', 'directories.sub_consite_id')
            ->where('voted_representatives.election_id', $this->electionId)
            ->where('directories.status', 'Active')
            ->whereIn('directories.sub_consite_id', $allowed)
            ->groupBy('sub_consites.id', 'sub_consites.code', 'sub_consites.name')
            ->orderBy('sub_consites.code')
            ->selectRaw("CONCAT(sub_consites.code, ' - ', sub_consites.name) as label, COUNT(DISTINCT voted_representatives.directory_id) as cnt")
            ->get();

        $subConsiteLabels = $subConsiteRows->pluck('label')->values()->all();
        $subConsiteCounts = $subConsiteRows->pluck('cnt')->map(fn ($v) => (int) $v)->values()->all();

        // Voted by sub consite (include all allowed sub consites, even if counts are 0)
        $subConsiteBase = DB::table('sub_consites')
            ->whereIn('sub_consites.id', $allowed)
            ->orderBy('sub_consites.code')
            ->selectRaw("sub_consites.id, CONCAT(sub_consites.code, ' - ', sub_consites.name) as label")
            ->get();

        $votedBySub = DB::table('voted_representatives')
            ->join('directories', 'directories.id', '=', 'voted_representatives.directory_id')
            ->where('voted_representatives.election_id', $this->electionId)
            ->where('directories.status', 'Active')
            ->whereIn('directories.sub_consite_id', $allowed)
            ->groupBy('directories.sub_consite_id')
            ->pluck(DB::raw('COUNT(DISTINCT voted_representatives.directory_id) as cnt'), 'directories.sub_consite_id');

        $notVotedBySub = DB::table('directories')
            ->where('directories.status', 'Active')
            ->whereIn('directories.sub_consite_id', $allowed)
            ->whereNotExists(function ($q) {
                $q->selectRaw(1)
                    ->from('voted_representatives')
                    ->whereColumn('voted_representatives.directory_id', 'directories.id')
                    ->where('voted_representatives.election_id', $this->electionId);
            })
            ->groupBy('directories.sub_consite_id')
            ->pluck(DB::raw('COUNT(DISTINCT directories.id) as cnt'), 'directories.sub_consite_id');

        $subConsiteVotedCounts = [];
        $subConsiteNotVotedCounts = [];

        foreach ($subConsiteBase as $row) {
            $subConsiteVotedCounts[] = (int) ($votedBySub[$row->id] ?? 0);
            $subConsiteNotVotedCounts[] = (int) ($notVotedBySub[$row->id] ?? 0);
        }

        return [
            'totalVoted' => (int) $totalVoted,
            'totalNotVoted' => (int) $totalNotVoted,
            'pledgeLabels' => $pledgeLabels,
            'pledgeVotedCounts' => $pledgeVotedCounts,
            'pledgeNotVotedCounts' => $pledgeNotVotedCounts,
            'subConsiteLabels' => $subConsiteLabels,
            'subConsiteVotedCounts' => $subConsiteVotedCounts,
            'subConsiteNotVotedCounts' => $subConsiteNotVotedCounts,
        ];
    }

    public function render()
    {
        $this->authorize('voting-dashboard-render');

        $stats = $this->buildStats();

        return view('livewire.election.voting-dashboard', [
            'electionId' => $this->electionId,
            'stats' => $stats,
        ]);
    }
}
