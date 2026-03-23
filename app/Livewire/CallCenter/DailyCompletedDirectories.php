<?php

namespace App\Livewire\CallCenter;

use App\Models\Directory;
use App\Models\Election;
use App\Models\ElectionDirectoryCallStatus;
use App\Models\SubConsite;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class DailyCompletedDirectories extends Component
{
    use WithPagination, AuthorizesRequests;

    protected $paginationTheme = 'bootstrap';

    public string $date = '';
    public string $search = '';
    public string $filterSubConsiteId = '';
    public int $perPage = 25;

    public bool $hideWithoutPhone = false;

    public ?string $activeElectionId = null;

    protected $queryString = [
        'date' => ['except' => ''],
        'search' => ['except' => ''],
        'filterSubConsiteId' => ['except' => ''],
        'perPage' => ['except' => 25],
        'hideWithoutPhone' => ['except' => false],
    ];

    public function updatingDate(): void { $this->resetPage(); }
    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterSubConsiteId(): void { $this->resetPage(); }
    public function updatedPerPage(): void { $this->resetPage(); }
    public function updatedHideWithoutPhone(): void { $this->resetPage(); }

    public function mount(): void
    {
        $this->authorize('call-center-daily-completed-render');

        $this->activeElectionId = Election::query()
            ->where('status', Election::STATUS_ACTIVE)
            ->value('id');

        if (!$this->date) {
            $this->date = now()->toDateString();
        }
    }

    protected function allowedSubConsiteIds(): array
    {
        return Auth::user()?->subConsites()->pluck('sub_consites.id')->all() ?? [];
    }

    public function render()
    {
        $this->authorize('call-center-daily-completed-render');

        $allowed = $this->allowedSubConsiteIds();

        $subConsites = SubConsite::query()
            ->whereIn('id', $allowed)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $rows = collect();

        if ($this->activeElectionId && count($allowed)) {
            $rows = ElectionDirectoryCallStatus::query()
                ->from('election_directory_call_statuses as edcs')
                ->select([
                    'edcs.directory_id',
                    'edcs.updated_by',
                    DB::raw('COALESCE(edcs.completed_at, edcs.updated_at) as completed_dt'),
                    'd.name as directory_name',
                    'd.serial as directory_serial',
                    'd.id_card_number as directory_nid',
                    'd.phones as directory_phones',
                    'sc.code as sub_consite_code',
                ])
                ->join('directories as d', 'd.id', '=', 'edcs.directory_id')
                ->leftJoin('sub_consites as sc', 'sc.id', '=', 'd.sub_consite_id')
                ->where('edcs.election_id', (string) $this->activeElectionId)
                ->where('edcs.status', ElectionDirectoryCallStatus::STATUS_COMPLETED)
                ->where('d.status', 'Active')
                ->whereIn('d.sub_consite_id', $allowed)
                ->when($this->filterSubConsiteId, fn($q) => $q->where('d.sub_consite_id', $this->filterSubConsiteId))
                ->when($this->hideWithoutPhone, function ($q) {
                    $q->whereNotNull('d.phones')
                        ->whereRaw("TRIM(d.phones) <> ''")
                        ->whereRaw("TRIM(d.phones) <> '[]'")
                        ->whereRaw("TRIM(d.phones) <> '[ ]'")
                        ->whereRaw("TRIM(d.phones) <> '[null]'")
                        ->whereRaw("TRIM(d.phones) <> 'null'")
                        ->whereRaw("TRIM(d.phones) <> '{}' ")
                        ->whereRaw("d.phones REGEXP '[0-9]'");
                })
                ->when($this->search, function ($q) {
                    $term = trim($this->search);
                    $q->where(function ($qq) use ($term) {
                        $qq->where('d.name', 'like', '%' . $term . '%')
                            ->orWhere('d.id_card_number', 'like', '%' . $term . '%')
                            ->orWhere('d.serial', 'like', '%' . $term . '%')
                            ->orWhere('d.phones', 'like', '%' . $term . '%');
                    });
                })
                ->whereRaw('DATE(COALESCE(edcs.completed_at, edcs.updated_at)) = ?', [$this->date])
                ->orderByDesc(DB::raw('COALESCE(edcs.completed_at, edcs.updated_at)'))
                ->paginate($this->perPage);

            // eager map user names for current page
            $userIds = collect($rows->items() ?? [])->pluck('updated_by')->filter()->unique()->values()->all();
            $users = count($userIds)
                ? User::query()->whereIn('id', $userIds)->get(['id', 'name'])->keyBy('id')
                : collect();

            // attach user_name and normalized phones text
            $rows->getCollection()->transform(function ($r) use ($users) {
                $r->user_name = $users[$r->updated_by]->name ?? '';

                $phones = $r->directory_phones;
                if (is_array($phones)) {
                    $r->phones_text = implode(', ', array_filter($phones));
                } else {
                    $r->phones_text = (string) $phones;
                }

                return $r;
            });
        }

        return view('livewire.call-center.daily-completed-directories', [
            'rows' => $rows,
            'subConsites' => $subConsites,
        ])->layout('layouts.master');
    }
}
