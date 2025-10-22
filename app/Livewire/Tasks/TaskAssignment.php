<?php

namespace App\Livewire\Tasks;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\{Task, Directory, Form, User, Election, Party, SubConsite};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TaskAssignment extends Component
{
    use WithPagination, AuthorizesRequests;

    // Use Bootstrap 5 pagination styling to match Laravel 11 + Metronic
    protected $paginationTheme = 'bootstrap'; // Livewire 3 uses this to pick bootstrap views
    // Explicit page name to avoid conflicts and ensure reset works
    protected $pageName = 'directories_page';
    protected string $paginationView = 'livewire.bootstrap';

    public $directorySearch='';
    public $selectedDirectoryIds = [];

    // Active filters used in query
    public $filterPartyId = '';
    public $filterSubConsiteId = '';

    // Draft (UI) values edited in popup; only applied when user clicks Apply
    public $directorySearchDraft='';
    public $filterPartyIdDraft='';
    public $filterSubConsiteIdDraft='';

    public $taskTitle='';
    public $taskNotes='';
    public $taskType='other';
    public $taskPriority='normal';
    public $taskDueAt=null; // datetime-local string
    public $taskFormId=null;
    public $taskElectionId=null; // will be selected via dropdown now
    public $assigneeIds=[]; // user ids

    // Bulk selection mode: null | 'filtered' | 'all'
    public $selectMode = null; 
    public $excludedDirectoryIds = []; // exclusions when in selectMode
    public $selectionCount = 0; // live count displayed
    public $bulkBaseCount = 0; // cached total for current bulk selection

    public $currentPageIds = []; // IDs of directories on current pagination page

    protected $queryString = [
        'directorySearch' => ['except' => ''],
        'filterPartyId' => ['except' => ''],
        'filterSubConsiteId' => ['except' => ''],
    ];

    public function mount()
    {
        // Initialize drafts to current active filter values (including query string preload)
        $this->directorySearchDraft = $this->directorySearch;
        $this->filterPartyIdDraft = $this->filterPartyId;
        $this->filterSubConsiteIdDraft = $this->filterSubConsiteId;
        $this->recalculateSelectionCount();
    }

    public function updatingDirectorySearch(){ $this->resetPage($this->pageName); }
    public function updatingFilterPartyId(){ $this->resetPage($this->pageName); }
    public function updatingFilterSubConsiteId(){ $this->resetPage($this->pageName); }

    public function applyFilters()
    {
        $changed = false;
        if($this->directorySearch !== $this->directorySearchDraft){ $this->directorySearch = $this->directorySearchDraft; $changed = true; }
        if($this->filterPartyId !== $this->filterPartyIdDraft){ $this->filterPartyId = $this->filterPartyIdDraft; $changed = true; }
        if($this->filterSubConsiteId !== $this->filterSubConsiteIdDraft){ $this->filterSubConsiteId = $this->filterSubConsiteIdDraft; $changed = true; }
        if($changed){
            $this->resetPage($this->pageName);
            if($this->selectMode === 'filtered'){
                $this->bulkBaseCount = $this->baseFilteredQuery()->count();
                if(count($this->excludedDirectoryIds)){
                    $this->excludedDirectoryIds = $this->baseFilteredQuery()->whereIn('id',$this->excludedDirectoryIds)->pluck('id')->all();
                }
            } elseif($this->selectMode === 'all') {
                // no change
            } else {
                if(count($this->selectedDirectoryIds)){
                    $this->selectedDirectoryIds = $this->baseFilteredQuery()->whereIn('id',$this->selectedDirectoryIds)->pluck('id')->all();
                }
            }
        }
        $this->recalculateSelectionCount();
        $this->dispatch('swal', type:'success', title:'Filters Applied');
    }

    public function clearFilters()
    {
        $this->reset(['filterPartyId','filterSubConsiteId','directorySearch','filterPartyIdDraft','filterSubConsiteIdDraft','directorySearchDraft']);
        $this->resetPage($this->pageName);
        $this->exitSelectMode();
        $this->recalculateSelectionCount();
    }

    public function toggleDirectory($id)
    {
        if($this->selectMode){
            if(in_array($id, $this->excludedDirectoryIds)){
                $this->excludedDirectoryIds = array_values(array_diff($this->excludedDirectoryIds, [$id]));
            } else { $this->excludedDirectoryIds[] = $id; }
        } else {
            if(in_array($id,$this->selectedDirectoryIds)){
                $this->selectedDirectoryIds = array_values(array_diff($this->selectedDirectoryIds, [$id]));
            } else { $this->selectedDirectoryIds[] = $id; }
        }
        $this->recalculateSelectionCount();
    }

    public function selectCurrentPage()
    {
        if($this->selectMode){ return; }
        foreach($this->currentPageIds as $id){
            if(!in_array($id,$this->selectedDirectoryIds)) $this->selectedDirectoryIds[] = $id;
        }
        $this->recalculateSelectionCount();
    }

    public function clearSelectedDirectories(){
        $this->selectedDirectoryIds = [];
        $this->recalculateSelectionCount();
    }

    public function selectAllFiltered()
    {
        $this->selectMode = 'filtered';
        $this->selectedDirectoryIds = [];
        $this->excludedDirectoryIds = [];
        $this->bulkBaseCount = $this->baseFilteredQuery()->count();
        $this->selectionCount = $this->bulkBaseCount;
        $this->dispatch('swal', type:'success', title:'All Filtered Selected');
    }

    public function selectAll()
    {
        $this->selectMode = 'all';
        $this->selectedDirectoryIds = [];
        $this->excludedDirectoryIds = [];
        $this->bulkBaseCount = Directory::count();
        $this->selectionCount = $this->bulkBaseCount;
        $this->dispatch('swal', type:'success', title:'All Voters Selected');
    }

    public function exitSelectMode()
    {
        $this->selectMode = null;
        $this->excludedDirectoryIds = [];
        $this->bulkBaseCount = 0;
        $this->recalculateSelectionCount();
    }

    private function baseFilteredQuery()
    {
        return Directory::query()
            ->when($this->directorySearch,function($q){
                $term = $this->directorySearch;
                $q->where(function($qq) use ($term){
                    $qq->where('name','like','%'.$term.'%')
                       ->orWhere('id_card_number','like','%'.$term.'%')
                       ->orWhere('id','like','%'.$term.'%')
                       ->orWhereRaw('CAST(phones AS CHAR) LIKE ?', ['%'.$term.'%']);
                });
            })
            ->when($this->filterPartyId, fn($q)=>$q->where('party_id',$this->filterPartyId))
            ->when($this->filterSubConsiteId, fn($q)=>$q->where('sub_consite_id',$this->filterSubConsiteId));
    }

    private function recalculateSelectionCount()
    {
        if($this->selectMode === 'all'){
            // Always refresh total count (in case directories added/removed)
            $this->bulkBaseCount = Directory::count();
            $this->selectionCount = $this->bulkBaseCount - count($this->excludedDirectoryIds);
        } elseif($this->selectMode === 'filtered') {
            // Always recalc filtered base count (avoid stale cached value after new filters applied)
            $this->bulkBaseCount = $this->baseFilteredQuery()->count();
            if(count($this->excludedDirectoryIds)){
                $excludedInFiltered = $this->baseFilteredQuery()->whereIn('id',$this->excludedDirectoryIds)->count();
                $this->selectionCount = $this->bulkBaseCount - $excludedInFiltered;
            } else {
                $this->selectionCount = $this->bulkBaseCount;
            }
        } else {
            // Manual selection
            $this->selectionCount = count($this->selectedDirectoryIds);
        }
    }

    private function syncSelectionCountsAfterLoad($directories)
    {
        // Use paginator totals to avoid race where baseFilteredQuery()->count() returns 0 before data loads
        if($this->selectMode === 'filtered'){
            $total = $directories->total();
            if($total !== $this->bulkBaseCount || $this->bulkBaseCount === 0){
                $this->bulkBaseCount = $total;
            }
            if(count($this->excludedDirectoryIds)){
                $excludedInFiltered = $this->baseFilteredQuery()->whereIn('id',$this->excludedDirectoryIds)->count();
                $this->selectionCount = max(0, $this->bulkBaseCount - $excludedInFiltered);
            } else {
                $this->selectionCount = $this->bulkBaseCount;
            }
        } elseif($this->selectMode === 'all') {
            $total = Directory::count();
            if($total !== $this->bulkBaseCount){ $this->bulkBaseCount = $total; }
            $this->selectionCount = max(0, $this->bulkBaseCount - count($this->excludedDirectoryIds));
        } else {
            $this->selectionCount = count($this->selectedDirectoryIds);
        }
    }

    // Computed property for dynamic task creation count (avoids stale selectionCount after filters)
    public function getWillCreateCountProperty()
    {
        if($this->selectMode === 'all'){
            $total = Directory::count();
            return max(0, $total - count($this->excludedDirectoryIds));
        }
        if($this->selectMode === 'filtered'){
            $filteredTotal = $this->baseFilteredQuery()->count();
            if(count($this->excludedDirectoryIds)){
                $excludedInFiltered = $this->baseFilteredQuery()->whereIn('id',$this->excludedDirectoryIds)->count();
                return max(0, $filteredTotal - $excludedInFiltered);
            }
            return $filteredTotal;
        }
        return count($this->selectedDirectoryIds);
    }

    // Computed dynamic task count (used instead of stale $selectionCount)
    public function getCreateTaskCountProperty()
    {
        if($this->selectMode === 'all'){
            return max(0, Directory::count() - count($this->excludedDirectoryIds));
        }
        if($this->selectMode === 'filtered'){
            $total = $this->baseFilteredQuery()->count();
            if(count($this->excludedDirectoryIds)){
                $excluded = $this->baseFilteredQuery()->whereIn('id',$this->excludedDirectoryIds)->count();
                return max(0, $total - $excluded);
            }
            return $total;
        }
        return count($this->selectedDirectoryIds);
    }

    public function createTasks()
    {
        // Validate task fields first
        $this->validate([
            'taskTitle' => ['required','string','max:255'],
            'assigneeIds' => ['required','array','min:1'],
            'assigneeIds.*' => ['integer','exists:users,id'],
            'taskType' => ['required', Rule::in(['form_fill','pickup','dropoff','other'])],
            'taskPriority' => ['required', Rule::in(['low','normal','high','urgent'])],
            'taskFormId' => ['nullable','uuid','exists:forms,id'],
            'taskElectionId' => ['nullable','uuid','exists:elections,id'],
            'taskDueAt' => ['nullable','date'],
        ]);

        $countToCreate = $this->createTaskCount; // use computed
        if($countToCreate === 0){
            $this->addError('selection','Select at least one voter.');
            return;
        }

        $due = $this->taskDueAt ? now()->parse($this->taskDueAt) : null;

        DB::transaction(function() use ($due){
            if($this->selectMode){
                $query = $this->selectMode === 'all' ? Directory::query() : $this->baseFilteredQuery();
                if(count($this->excludedDirectoryIds)){
                    $query->whereNotIn('id', $this->excludedDirectoryIds);
                }
                $query->select('id')->chunk(1000, function($chunk){
                    foreach($chunk as $dir){
                        $task = Task::create([
                            'title' => $this->taskTitle,
                            'notes' => $this->taskNotes,
                            'type' => $this->taskType,
                            'priority' => $this->taskPriority,
                            'status' => 'pending',
                            'form_id' => $this->taskFormId,
                            'directory_id' => $dir->id,
                            'election_id' => $this->taskElectionId,
                            'due_at' => $this->taskDueAt ? now()->parse($this->taskDueAt) : null,
                            'created_by' => auth()->id(),
                        ]);
                        $task->assignees()->sync($this->assigneeIds);
                    }
                });
            } else {
                foreach($this->selectedDirectoryIds as $dirId){
                    $task = Task::create([
                        'title' => $this->taskTitle,
                        'notes' => $this->taskNotes,
                        'type' => $this->taskType,
                        'priority' => $this->taskPriority,
                        'status' => 'pending',
                        'form_id' => $this->taskFormId,
                        'directory_id' => $dirId,
                        'election_id' => $this->taskElectionId,
                        'due_at' => $due,
                        'created_by' => auth()->id(),
                    ]);
                    $task->assignees()->sync($this->assigneeIds);
                }
            }
        });

        $count = $this->selectionCount;
        $this->reset(['taskTitle','taskNotes','taskType','taskPriority','taskDueAt','taskFormId','taskElectionId','assigneeIds','selectedDirectoryIds','selectMode','excludedDirectoryIds','bulkBaseCount']);
        $this->taskType = 'other';
        $this->taskPriority = 'normal';
        $this->selectionCount = 0;
        $this->dispatch('swal', type: 'success', title: 'Tasks Created', text: "$count task(s) created.");
    }

    public function render()
    {
        $this->authorize('task-render');

        // Fetch directories first
        $directories = $this->baseFilteredQuery()
            ->with(['party','subConsite','island','country','property'])
            ->latest()
            ->paginate(15, ['*'], $this->pageName);

        // Capture current page IDs for selectCurrentPage()
        $this->currentPageIds = $directories->pluck('id')->all();

        // Synchronize counts based on loaded paginator (prevents 0/1 anomaly before refresh)
        $this->syncSelectionCountsAfterLoad($directories);

        $forms = Form::orderBy('title')->get(['id','title']);
        $users = User::orderBy('name')->get(['id','name']);
        $elections = Election::orderByDesc('start_date')->get(['id','name']);
        $parties = Party::orderBy('name')->get(['id','name','short_name']);
        $subConsites = SubConsite::orderBy('code')->get(['id','code']);

        return view('livewire.tasks.task-assignment', [
            'directories' => $directories,
            'forms' => $forms,
            'users' => $users,
            'elections' => $elections,
            'parties' => $parties,
            'subConsites' => $subConsites,
        ])->layout('layouts.master');
    }
}
