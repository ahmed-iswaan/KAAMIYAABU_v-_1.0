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
use League\Csv\Writer;
use SplTempFileObject;

class UserDetails extends Component
{
    use WithPagination, AuthorizesRequests;

    protected $paginationTheme = 'bootstrap';

    /**
     * Use a dedicated paginator name as the default for this component.
     * The second paginator will use its own query-string key.
     */
    protected string $pageName = 'attemptsPage';

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

    public function downloadAttemptsCsv()
    {
        $this->authorize('user-render');

        if (!$this->activeElectionId) {
            abort(404);
        }

        $user = User::query()->findOrFail($this->userId);

        $rows = ElectionDirectoryCallSubStatus::query()
            ->where('election_id', (string) $this->activeElectionId)
            ->where('updated_by', $this->userId)
            ->latest('updated_at')
            ->get(['updated_at', 'directory_id', 'attempt', 'sub_status_id', 'phone_number', 'notes']);

        $dirIds = $rows->pluck('directory_id')->map(fn($v) => (string) $v)->unique()->values()->all();
        $dirMap = count($dirIds)
            ? Directory::query()->whereIn('id', $dirIds)->get(['id', 'name', 'serial', 'id_card_number'])->keyBy(fn($d) => (string) $d->id)
            : collect();

        $csv = Writer::createFromFileObject(new SplTempFileObject());
        $csv->insertOne(['Date', 'Directory', 'Serial', 'NID', 'Attempt', 'Sub Status', 'Phone', 'Notes']);

        foreach ($rows as $r) {
            $dir = $dirMap[(string) $r->directory_id] ?? null;
            $ssid = (string) ($r->sub_status_id ?? '');
            $ssName = $ssid !== '' ? (($this->activeSubStatuses[$ssid] ?? '') ?: $ssid) : '';

            $csv->insertOne([
                optional($r->updated_at)->format('Y-m-d H:i:s'),
                $dir?->name ?? (string) $r->directory_id,
                $dir?->serial ?? '',
                $dir?->id_card_number ?? '',
                (int) ($r->attempt ?? 0),
                $ssName,
                (string) ($r->phone_number ?? ''),
                (string) ($r->notes ?? ''),
            ]);
        }

        $filename = 'UserAttempts_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) $user->name) . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($csv) {
            echo (string) $csv;
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function downloadCompletedCsv()
    {
        $this->authorize('user-render');

        if (!$this->activeElectionId) {
            abort(404);
        }

        $user = User::query()->findOrFail($this->userId);

        $rows = ElectionDirectoryCallStatus::query()
            ->where('election_id', (string) $this->activeElectionId)
            ->where('updated_by', $this->userId)
            ->where('status', ElectionDirectoryCallStatus::STATUS_COMPLETED)
            ->latest(DB::raw('COALESCE(completed_at, updated_at)'))
            ->get(['completed_at', 'updated_at', 'directory_id', 'status']);

        $dirIds = $rows->pluck('directory_id')->map(fn($v) => (string) $v)->unique()->values()->all();
        $dirMap = count($dirIds)
            ? Directory::query()->whereIn('id', $dirIds)->get(['id', 'name', 'serial', 'id_card_number'])->keyBy(fn($d) => (string) $d->id)
            : collect();

        $csv = Writer::createFromFileObject(new SplTempFileObject());
        $csv->insertOne(['Date', 'Directory', 'Serial', 'NID', 'Status']);

        foreach ($rows as $r) {
            $dir = $dirMap[(string) $r->directory_id] ?? null;
            $dt = $r->completed_at ?: $r->updated_at;

            $csv->insertOne([
                optional($dt)->format('Y-m-d H:i:s'),
                $dir?->name ?? (string) $r->directory_id,
                $dir?->serial ?? '',
                $dir?->id_card_number ?? '',
                (string) ($r->status ?? ''),
            ]);
        }

        $filename = 'UserCompletedDirectories_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) $user->name) . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($csv) {
            echo (string) $csv;
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
