<?php

namespace App\Livewire\CallCenter;

use App\Models\Directory;
use App\Models\Election;
use App\Models\ElectionDirectoryCallStatus;
use App\Models\ElectionDirectoryCallSubStatus;
use App\Models\SubConsite;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class CallCenterBeta extends Component
{
    use WithPagination, AuthorizesRequests;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';
    public string $filterSubConsiteId = '';
    public string $filterStatus = 'pending'; // pending|completed|all
    public int $perPage = 10;

    public bool $hideWithoutPhone = true;

    public ?string $activeElectionId = null;

    /**
     * Active Sub Status options for mapping UUID => name.
     * Format: [id => name]
     */
    public array $activeSubStatuses = [];

    /**
     * Cache image urls per-directory for the current request to avoid repeat filesystem checks.
     * @var array<string, string|null>
     */
    private array $imageUrlMemo = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'filterSubConsiteId' => ['except' => ''],
        'filterStatus' => ['except' => 'pending'],
        'perPage' => ['except' => 25],
        'hideWithoutPhone' => ['except' => true],
    ];

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterSubConsiteId(): void { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }
    public function updatedPerPage(): void { $this->resetPage(); }
    public function updatedHideWithoutPhone(): void { $this->resetPage(); }

    public function mount(): void
    {
        $this->activeElectionId = Election::query()
            ->where('status', Election::STATUS_ACTIVE)
            ->value('id');

        $this->activeSubStatuses = \App\Models\SubStatus::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->mapWithKeys(fn($s) => [(string) $s->id => (string) $s->name])
            ->all();
    }

    protected function allowedSubConsiteIds(): array
    {
        return Auth::user()?->subConsites()->pluck('sub_consites.id')->all() ?? [];
    }

    /**
     * Resolve image URLs for a list of directories in bulk (prefer stored profile_picture, then nid-images).
     *
     * @param iterable<int, \App\Models\Directory> $dirs
     * @return array<string, string|null>
     */
    private function directoryImageUrlsFor(iterable $dirs): array
    {
        $out = [];

        $nidsToCheck = [];
        foreach ($dirs as $dir) {
            $id = (string) $dir->id;

            // 1) Stored profile picture
            if (!empty($dir->profile_picture)) {
                $out[$id] = asset('storage/' . ltrim($dir->profile_picture, '/'));
                continue;
            }

            // 2) Try memoized result
            if (array_key_exists($id, $this->imageUrlMemo)) {
                $out[$id] = $this->imageUrlMemo[$id];
                continue;
            }

            $nid = trim((string) ($dir->id_card_number ?? ''));
            if ($nid !== '') {
                $nidsToCheck[$id] = $nid;
            } else {
                $out[$id] = null;
                $this->imageUrlMemo[$id] = null;
            }
        }

        if (count($nidsToCheck)) {
            foreach ($nidsToCheck as $id => $nid) {
                $found = null;
                foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
                    $relative = "nid-images/{$nid}.{$ext}";
                    if (is_file(public_path($relative))) {
                        $found = asset($relative);
                        break;
                    }
                }
                $out[$id] = $found;
                $this->imageUrlMemo[$id] = $found;
            }
        }

        return $out;
    }

    public function render()
    {
        $this->authorize('call-center-render');

        $allowed = $this->allowedSubConsiteIds();

        $subConsites = SubConsite::query()
            ->whereIn('id', $allowed)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        // Build one base query used for both totals and list (keep it in sync with list filters)
        $baseQuery = Directory::query()
            ->select([
                'id',
                'name',
                'id_card_number',
                'serial',
                'phones',
                'profile_picture',
                'address',
                'street_address',
                'properties_id',
                'current_address',
                'current_street_address',
                'current_properties_id',
                'sub_consite_id',
            ])
            ->where('status', 'Active')
            ->whereIn('sub_consite_id', $allowed)
            ->when($this->filterSubConsiteId, fn($q) => $q->where('sub_consite_id', $this->filterSubConsiteId))
            ->when($this->hideWithoutPhone, function ($q) {
                $q->whereNotNull('phones')
                    ->whereRaw("TRIM(phones) <> ''")
                    ->whereRaw("TRIM(phones) <> '[]'")
                    ->whereRaw("TRIM(phones) <> '[ ]'")
                    ->whereRaw("TRIM(phones) <> '[null]'")
                    ->whereRaw("TRIM(phones) <> 'null'")
                    ->whereRaw("TRIM(phones) <> '{}' ")
                    ->whereRaw("phones REGEXP '[0-9]'");
            })
            ->when($this->search, function ($q) {
                $term = trim($this->search);
                $q->where(function ($qq) use ($term) {
                    $qq->where('name', 'like', '%' . $term . '%')
                        ->orWhere('id_card_number', 'like', '%' . $term . '%')
                        ->orWhere('serial', 'like', '%' . $term . '%')
                        ->orWhere('phones', 'like', '%' . $term . '%')
                        ->orWhere('address', 'like', '%' . $term . '%');
                });
            });

        // Summary totals (do NOT apply filterStatus; keep pending/completed counts always visible)
        $totalsPending = 0;
        $totalsCompleted = 0;
        $totalsCompletedByMe = 0;
        $totalsCompletedToday = 0;
        $totalsAttemptsToday = 0;
        $totalsAttemptsTotal = 0;

        if ($this->activeElectionId && count($allowed)) {
            $completedExists = ElectionDirectoryCallStatus::query()
                ->where('election_id', (string) $this->activeElectionId)
                ->where('status', ElectionDirectoryCallStatus::STATUS_COMPLETED)
                ->whereColumn('directory_id', 'directories.id');

            $totalsCompleted = (clone $baseQuery)->whereExists($completedExists)->count();
            $totalsPending = (clone $baseQuery)->whereNotExists($completedExists)->count();

            $totalsCompletedByMe = (clone $baseQuery)->whereExists(
                ElectionDirectoryCallStatus::query()
                    ->where('election_id', (string) $this->activeElectionId)
                    ->where('status', ElectionDirectoryCallStatus::STATUS_COMPLETED)
                    ->where('updated_by', (string) auth()->id())
                    ->whereColumn('directory_id', 'directories.id')
            )->count();

            $totalsCompletedToday = (clone $baseQuery)->whereExists(
                ElectionDirectoryCallStatus::query()
                    ->where('election_id', (string) $this->activeElectionId)
                    ->where('status', ElectionDirectoryCallStatus::STATUS_COMPLETED)
                    ->whereRaw('DATE(COALESCE(completed_at, updated_at)) = ?', [now()->toDateString()])
                    ->whereColumn('directory_id', 'directories.id')
            )->count();

            // Attempts totals: avoid plucking all directory IDs (slow on large datasets).
            // Instead, count attempts by joining filtered directories.
            $attemptsBase = (clone $baseQuery)
                ->join('election_directory_call_sub_statuses as edcss', 'edcss.directory_id', '=', 'directories.id')
                ->where('edcss.election_id', (string) $this->activeElectionId);

            $totalsAttemptsTotal = (clone $attemptsBase)->count('edcss.id');
            $totalsAttemptsToday = (clone $attemptsBase)
                ->whereDate('edcss.updated_at', now()->toDateString())
                ->count('edcss.id');
        }

        // Apply status filter to LIST (totals intentionally ignore filterStatus)
        if ($this->activeElectionId) {
            if ($this->filterStatus === 'completed') {
                $baseQuery->whereExists(
                    ElectionDirectoryCallStatus::query()
                        ->where('election_id', (string) $this->activeElectionId)
                        ->where('status', ElectionDirectoryCallStatus::STATUS_COMPLETED)
                        ->whereColumn('directory_id', 'directories.id')
                );
            } elseif ($this->filterStatus === 'pending') {
                $baseQuery->whereNotExists(
                    ElectionDirectoryCallStatus::query()
                        ->where('election_id', (string) $this->activeElectionId)
                        ->where('status', ElectionDirectoryCallStatus::STATUS_COMPLETED)
                        ->whereColumn('directory_id', 'directories.id')
                );
            }
        }

        $directories = $baseQuery
            ->with([
                'subConsite:id,code,name',
                'property:id,name',
                'currentProperty:id,name',
            ])
            ->orderByRaw("COALESCE(NULLIF(address,''), 'zzz') asc")
            ->orderBy('name')
            ->paginate($this->perPage);

        $directoryImageUrls = $this->directoryImageUrlsFor($directories->getCollection());

        $dirIds = $directories->getCollection()->pluck('id')->map(fn($v) => (string) $v)->all();
        $listStatuses = [];
        $listSubStatuses = [];

        if ($this->activeElectionId && count($dirIds)) {
            $listStatuses = ElectionDirectoryCallStatus::query()
                ->where('election_id', (string) $this->activeElectionId)
                ->whereIn('directory_id', $dirIds)
                ->get(['directory_id', 'status'])
                ->mapWithKeys(fn($r) => [(string) $r->directory_id => (string) ($r->status ?? '')])
                ->all();

            // latest attempt sub-status: pick greatest attempt per directory
            $rows = ElectionDirectoryCallSubStatus::query()
                ->where('election_id', (string) $this->activeElectionId)
                ->whereIn('directory_id', $dirIds)
                ->orderBy('attempt')
                ->get(['directory_id', 'attempt', 'sub_status_id']);

            foreach ($rows as $r) {
                $did = (string) $r->directory_id;
                $listSubStatuses[$did] = [
                    'attempt' => (int) $r->attempt,
                    'sub_status_id' => (string) ($r->sub_status_id ?? ''),
                ];
            }
        }

        return view('livewire.call-center.call-center-beta', [
            'directories' => $directories,
            'subConsites' => $subConsites,
            'listStatuses' => $listStatuses,
            'listSubStatuses' => $listSubStatuses,
            'activeSubStatuses' => $this->activeSubStatuses,
            'directoryImageUrls' => $directoryImageUrls,
            'totalsPending' => $totalsPending,
            'totalsCompleted' => $totalsCompleted,
            'totalsCompletedByMe' => $totalsCompletedByMe,
            'totalsCompletedToday' => $totalsCompletedToday,
            'totalsAttemptsToday' => $totalsAttemptsToday,
            'totalsAttemptsTotal' => $totalsAttemptsTotal,
        ])->layout('layouts.master');
    }

    /**
     * Websocket listeners (via Laravel Echo + Livewire).
     * When any user updates a directory in Call Center Beta, other users will refresh.
     */
    public function getListeners(): array
    {
        return [
            'echo:elections.voters,VoterDataChanged' => 'handleVoterDataChanged',
            'reverb-voter-update' => 'handleVoterDataChanged',
            'window:voter-data-updated' => 'handleVoterDataChanged',
        ];
    }

    public function handleVoterDataChanged($payload = null): void
    {
        // Normalize payload into array
        if ($payload === null) {
            $payload = [];
        } elseif (is_string($payload)) {
            $decoded = json_decode($payload, true);
            $payload = json_last_error() === JSON_ERROR_NONE ? $decoded : ['raw' => $payload];
        } elseif (!is_array($payload)) {
            $payload = (array) $payload;
        }

        \Log::info('CallCenterBeta realtime payload received: ' . json_encode($payload, JSON_UNESCAPED_UNICODE));

        $directoryId = (string)($payload['directory_id'] ?? ($payload['directoryId'] ?? ''));
        $electionId = (string)($payload['election_id'] ?? ($payload['electionId'] ?? ''));

        // Refresh only if current election matches
        if ($this->activeElectionId && $electionId && $electionId !== (string)$this->activeElectionId) {
            return;
        }

        // Do NOT reset pagination here; it forces users back to page 1.
        // Just refresh the dataset while keeping the current page.
        $this->dispatch('$refresh');
    }
}
