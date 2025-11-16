<?php

namespace App\Livewire\Tasks;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\{Task, User, Party, SubConsite, SubStatus, EventLog};
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TaskList extends Component
{
    use WithPagination, AuthorizesRequests;

    protected $paginationTheme = 'bootstrap';

    public $search='';
    public $status='';
    public $type='';
    public $priority='';
    public $filterPartyId='';
    public $filterSubConsiteId='';
    public $filterAssigneeId='';
    public $filterSubStatusId='';
    public $perPage = 25; // ensure default
    protected array $perPageOptions = [5,10,25,50,100];

    // draft copies for dropdown filtering (apply on click)
    public $searchDraft='';
    public $statusDraft='';
    public $typeDraft='';
    public $priorityDraft='';
    public $filterPartyIdDraft='';
    public $filterSubConsiteIdDraft='';
    public $filterAssigneeIdDraft='';
    public $filterSubStatusIdDraft='';

    // Bulk assignment state
    public array $selectedTasks = [];            // ensure Livewire treats as array
    public $bulkAssignUserId = '';          // user to assign
    public array $currentPageTaskIds = [];  // IDs of tasks in current pagination page

    protected $queryString=[
        'search'=>['except'=>''],
        'status'=>['except'=>''],
        'type'=>['except'=>''],
        'priority'=>['except'=>''],
        'filterPartyId'=>['except'=>''],
        'filterSubConsiteId'=>['except'=>''],
        'filterAssigneeId'=>['except'=>''],
        'filterSubStatusId'=>['except'=>''],
        'perPage'=>['except'=>25],
    ];

    public function mount()
    {
        $this->searchDraft = $this->search;
        $this->statusDraft = $this->status;
        $this->typeDraft = $this->type;
        $this->priorityDraft = $this->priority;
        $this->filterPartyIdDraft = $this->filterPartyId;
        $this->filterSubConsiteIdDraft = $this->filterSubConsiteId;
        $this->filterAssigneeIdDraft = $this->filterAssigneeId;
        $this->filterSubStatusIdDraft = $this->filterSubStatusId;
    }

    // Remove old updatingPerPage, replace with updatedPerPage with validation & casting
    public function updatedPerPage($value): void
    {
        $value = (int)$value;
        if(! in_array($value, $this->perPageOptions, true)) {
            $value = 25; // fallback
        }
        $this->perPage = $value; // casted
        $this->resetPage();
    }

    public function applyFilters(): void
    {
        $changed = false;
        foreach (['search','status','type','priority','filterPartyId','filterSubConsiteId','filterAssigneeId','filterSubStatusId'] as $f) {
            $draft = $f.'Draft';
            if ($this->$f !== $this->$draft) { $this->$f = $this->$draft; $changed = true; }
        }
        if ($changed) { $this->resetPage(); }
    }

    public function resetFilters(): void
    {
        $this->reset(['search','status','type','priority','filterPartyId','filterSubConsiteId','filterAssigneeId','filterSubStatusId','searchDraft','statusDraft','typeDraft','priorityDraft','filterPartyIdDraft','filterSubConsiteIdDraft','filterAssigneeIdDraft','filterSubStatusIdDraft']);
        $this->resetPage();
    }

    public function getStatsProperty(): array
    {
        // Build a base filtered query matching the table view (without pagination)
        $base = Task::query()
            ->when($this->search, function($q){
                $term = trim($this->search);
                $q->where(function($qq) use ($term){
                    $qq->where('title','like','%'.$term.'%')
                        ->orWhere('number','like','%'.$term.'%')
                        ->orWhereHas('directory', function($dq) use ($term){
                            $dq->where('name','like','%'.$term.'%')
                               ->orWhere('id_card_number','like','%'.$term.'%')
                               ->orWhere('phones','like','%'.$term.'%');
                        });
                });
            })
            ->when($this->status, fn($q)=>$q->where('status',$this->status))
            ->when($this->type, fn($q)=>$q->where('type',$this->type))
            ->when($this->priority, fn($q)=>$q->where('priority',$this->priority))
            ->when($this->filterSubStatusId, fn($q)=>$q->where('sub_status_id',$this->filterSubStatusId))
            ->when($this->filterAssigneeId, fn($q)=>$q->whereHas('users', fn($uq)=>$uq->where('user_id',$this->filterAssigneeId)))
            ->when($this->filterPartyId, fn($q)=>$q->whereHas('directory', fn($dq)=>$dq->where('party_id',$this->filterPartyId)))
            ->when($this->filterSubConsiteId, fn($q)=>$q->whereHas('directory', fn($dq)=>$dq->where('sub_consite_id',$this->filterSubConsiteId)));

        $total = (clone $base)->count();
        if($total === 0){
            return [
                'completed'=>0,
                'pending'=>0,
                'follow_up'=>0,
                'total'=>0,
                'percentages'=>['completed'=>0,'pending'=>0,'follow_up'=>0],
            ];
        }

        // Efficient grouped counts
        $groupCounts = (clone $base)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate','status');

        $completed = (int)($groupCounts['completed'] ?? 0);
        $pending   = (int)($groupCounts['pending'] ?? 0);
        $followUp  = (int)($groupCounts['follow_up'] ?? 0);

        return [
            'completed'=>$completed,
            'pending'=>$pending,
            'follow_up'=>$followUp,
            'total'=>$total,
            'percentages'=>[
                'completed'=>(int)round(($completed/$total)*100),
                'pending'=>(int)round(($pending/$total)*100),
                'follow_up'=>(int)round(($followUp/$total)*100),
            ],
        ];
    }

    public function render()
    {
        $this->authorize('task-list-render');

        $tasks = Task::with(['directory.party','directory.subConsite','assignees','subStatus'])
            ->when($this->search, function($q){
                $term = trim($this->search);
                $q->where(function($qq) use ($term){
                    $qq->where('title','like','%'.$term.'%')
                        ->orWhere('number','like','%'.$term.'%')
                        ->orWhereHas('directory', function($dq) use ($term){
                            $dq->where('name','like','%'.$term.'%')
                               ->orWhere('id_card_number','like','%'.$term.'%')
                               ->orWhere('phones','like','%'.$term.'%');
                        });
                });
            })
            ->when($this->status, fn($q)=>$q->where('status',$this->status))
            ->when($this->type, fn($q)=>$q->where('type',$this->type))
            ->when($this->priority, fn($q)=>$q->where('priority',$this->priority))
            ->when($this->filterSubStatusId, fn($q)=>$q->where('sub_status_id',$this->filterSubStatusId))
            ->when($this->filterAssigneeId, fn($q)=>$q->whereHas('users', fn($uq)=>$uq->where('user_id',$this->filterAssigneeId)))
            ->when($this->filterPartyId, fn($q)=>$q->whereHas('directory', fn($dq)=>$dq->where('party_id',$this->filterPartyId)))
            ->when($this->filterSubConsiteId, fn($q)=>$q->whereHas('directory', fn($dq)=>$dq->where('sub_consite_id',$this->filterSubConsiteId)))
            ->latest()
            ->paginate((int)$this->perPage); // cast to int explicitly
        // Track current page task IDs for selection toggling
        $this->currentPageTaskIds = $tasks->pluck('id')->toArray();

        $assignees   = User::orderBy('name')->get(['id','name']);
        $parties     = Party::orderBy('short_name')->get(['id','short_name','name']);
        $subConsites = SubConsite::orderBy('code')->get(['id','code']);
        $subStatuses = SubStatus::where('active',true)->orderBy('name')->get(['id','name']);

        return view('livewire.tasks.task-list', [
            'tasks' => $tasks,
            'assignees' => $assignees,
            'parties' => $parties,
            'subConsites' => $subConsites,
            'subStatuses' => $subStatuses,
        ])->layout('layouts.master');
    }

    public function toggleSelectPage(): void
    {
        $pageIds = $this->currentPageTaskIds;
        if (empty($pageIds)) return;
        $alreadySelectedCount = count(array_intersect($pageIds, $this->selectedTasks));
        if ($alreadySelectedCount === count($pageIds)) {
            // unselect all on this page
            $this->selectedTasks = array_values(array_diff($this->selectedTasks, $pageIds));
        } else {
            // add all page IDs
            $this->selectedTasks = array_values(array_unique(array_merge($this->selectedTasks, $pageIds)));
        }
    }

    protected function filteredBaseQuery()
    {
        return Task::query()
            ->when($this->search, function($q){
                $term = trim($this->search);
                $q->where(function($qq) use ($term){
                    $qq->where('title','like','%'.$term.'%')
                        ->orWhere('number','like','%'.$term.'%')
                        ->orWhereHas('directory', function($dq) use ($term){
                            $dq->where('name','like','%'.$term.'%')
                               ->orWhere('id_card_number','like','%'.$term.'%')
                               ->orWhere('phones','like','%'.$term.'%');
                        });
                });
            })
            ->when($this->status, fn($q)=>$q->where('status',$this->status))
            ->when($this->type, fn($q)=>$q->where('type',$this->type))
            ->when($this->priority, fn($q)=>$q->where('priority',$this->priority))
            ->when($this->filterSubStatusId, fn($q)=>$q->where('sub_status_id',$this->filterSubStatusId))
            ->when($this->filterAssigneeId, fn($q)=>$q->whereHas('users', fn($uq)=>$uq->where('user_id',$this->filterAssigneeId)))
            ->when($this->filterPartyId, fn($q)=>$q->whereHas('directory', fn($dq)=>$dq->where('party_id',$this->filterPartyId)))
            ->when($this->filterSubConsiteId, fn($q)=>$q->whereHas('directory', fn($dq)=>$dq->where('sub_consite_id',$this->filterSubConsiteId)));
    }

    public function assignUserToSelected(): void
    {
        $this->authorize('task-bulk-assign');
        if (!$this->bulkAssignUserId) return;
        if (empty($this->selectedTasks)) return;
        $userId = (int)$this->bulkAssignUserId;
        $targets = Task::whereIn('id',$this->selectedTasks)
            ->whereDoesntHave('users', fn($q)=>$q->where('user_id',$userId))
            ->pluck('id');
        if($targets->isEmpty()){
            session()->flash('bulk_message','No eligible tasks to assign.');
            return;
        }
        // Attach in chunks to reduce queries
        $targets->chunk(100)->each(function($chunk) use ($userId){
            Task::whereIn('id',$chunk)->get()->each(fn($task)=>$task->users()->syncWithoutDetaching([$userId]));
        });
        session()->flash('bulk_message', $targets->count().' task(s) assigned.');
        EventLog::create([
            'user_id'=>auth()->id(),
            'event_type'=>'task_bulk_assign',
            'event_tab'=>'tasks',
            'event_entry_id'=>null,
            'description'=>'Bulk assign user to selected tasks',
            'event_data'=>[
                'assigned_user_id'=>$userId,
                'task_ids'=>$targets->values(),
                'count'=>$targets->count(),
                'scope'=>'selected'
            ],
            'ip_address'=>request()->ip(),
        ]);
    }

    public function assignUserToAllFiltered(): void
    {
        $this->authorize('task-bulk-assign');
        if (!$this->bulkAssignUserId) return;
        $userId = (int)$this->bulkAssignUserId;
        $ids = $this->filteredBaseQuery()
            ->whereDoesntHave('users', fn($q)=>$q->where('user_id',$userId))
            ->pluck('id');
        if($ids->isEmpty()){
            session()->flash('bulk_message','No filtered tasks eligible.');
            return;
        }
        $ids->chunk(100)->each(function($chunk) use ($userId){
            Task::whereIn('id',$chunk)->get()->each(fn($task)=>$task->users()->syncWithoutDetaching([$userId]));
        });
        session()->flash('bulk_message', $ids->count().' filtered task(s) assigned.');
        EventLog::create([
            'user_id'=>auth()->id(),
            'event_type'=>'task_bulk_assign',
            'event_tab'=>'tasks',
            'event_entry_id'=>null,
            'description'=>'Bulk assign user to filtered tasks',
            'event_data'=>[
                'assigned_user_id'=>$userId,
                'task_ids'=>$ids->values(),
                'count'=>$ids->count(),
                'scope'=>'filtered'
            ],
            'ip_address'=>request()->ip(),
        ]);
    }

    public function unassignUserFromSelected(): void
    {
        $this->authorize('task-bulk-unassign');
        if (!$this->bulkAssignUserId) return;
        if (empty($this->selectedTasks)) return;
        $userId = (int)$this->bulkAssignUserId;
        $targets = Task::whereIn('id',$this->selectedTasks)
            ->whereHas('users', fn($q)=>$q->where('user_id',$userId))
            ->pluck('id');
        if($targets->isEmpty()){
            session()->flash('bulk_message','No eligible tasks to unassign.');
            return;
        }
        $targets->chunk(100)->each(function($chunk) use ($userId){
            Task::whereIn('id',$chunk)->get()->each(fn($task)=>$task->users()->detach($userId));
        });
        session()->flash('bulk_message', $targets->count().' task(s) unassigned.');
        EventLog::create([
            'user_id'=>auth()->id(),
            'event_type'=>'task_bulk_unassign',
            'event_tab'=>'tasks',
            'event_entry_id'=>null,
            'description'=>'Bulk unassign user from selected tasks',
            'event_data'=>[
                'unassigned_user_id'=>$userId,
                'task_ids'=>$targets->values(),
                'count'=>$targets->count(),
                'scope'=>'selected'
            ],
            'ip_address'=>request()->ip(),
        ]);
    }

    public function unassignUserFromAllFiltered(): void
    {
        $this->authorize('task-bulk-unassign');
        if (!$this->bulkAssignUserId) return;
        $userId = (int)$this->bulkAssignUserId;
        $ids = $this->filteredBaseQuery()
            ->whereHas('users', fn($q)=>$q->where('user_id',$userId))
            ->pluck('id');
        if($ids->isEmpty()){
            session()->flash('bulk_message','No filtered tasks eligible to unassign.');
            return;
        }
        $ids->chunk(100)->each(function($chunk) use ($userId){
            Task::whereIn('id',$chunk)->get()->each(fn($task)=>$task->users()->detach($userId));
        });
        session()->flash('bulk_message', $ids->count().' filtered task(s) unassigned.');
        EventLog::create([
            'user_id'=>auth()->id(),
            'event_type'=>'task_bulk_unassign',
            'event_tab'=>'tasks',
            'event_entry_id'=>null,
            'description'=>'Bulk unassign user from filtered tasks',
            'event_data'=>[
                'unassigned_user_id'=>$userId,
                'task_ids'=>$ids->values(),
                'count'=>$ids->count(),
                'scope'=>'filtered'
            ],
            'ip_address'=>request()->ip(),
        ]);
    }

    public function updatedSelectedTasks(): void
    {
        $this->selectedTasks = array_values(array_filter($this->selectedTasks, fn($v)=>$v!==null && $v!==''));
    }

    public function getSelectedCountProperty(): int
    {
        return count($this->selectedTasks);
    }
}
