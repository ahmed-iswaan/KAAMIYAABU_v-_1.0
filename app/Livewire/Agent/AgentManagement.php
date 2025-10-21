<?php

namespace App\Livewire\Agent;

use App\Models\Country; // added
use App\Models\Island;  // added
use App\Models\Property; // added
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Task;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\Directory;
use App\Models\Party; // added
use App\Models\SubConsite; // added
use App\Models\VoterNote;
use App\Models\VoterOpinion; // new
use App\Models\VoterRequest; // new
use App\Models\OpinionType; // new
use App\Models\RequestType; // new
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;
use App\Events\TaskDataChanged; // added

class AgentManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $taskSearch = ''; // unified task search
    public $perPage = 10;
    public $pageTitle = 'Agents';

    public $taskStatus = '';
    public $taskType = '';
    public $filterPartyId = ''; // added
    public $filterSubConsiteId = ''; // added

    // (Legacy) directory selection props retained
    public $directorySearch = '';
    public $selectedDirectoryIds = [];

    // Task creation
    public $assigneeIds = [];
    public $taskTitle = '';
    public $taskNotes = '';
    public $newTaskType = 'other';
    public $newTaskPriority = 'normal';
    public $newTaskDueAt = null;
    public $newTaskFormId = null;
    public $newTaskElectionId = null;

    // Current task & form submission state
    public $selectedTaskId = null;
    /** @var array<int|string, mixed> */
    public $submissionAnswers = []; // checkbox/multiselect: list of selected option IDs
    public $submissionId = null;

    // Directory current location editing
    public $currentAddress = '';
    public $currentStreetAddress = '';
    public $selectedDirectoryId = null; // track for location editing
    // Added contact update fields
    public $contactEmail = '';
    public $contactPhones = '';
    // Added current location select fields
    public $currentCountryId = '';
    public $currentIslandId = '';
    public $currentPropertyId = '';
    public $maldivesCountryId = null; // store Maldives id for conditional island display

    // Notes (now voter notes via directory)
    public $newNote = ''; // note input
    public $voterNotes = []; // loaded notes
    // New tab + related collections
    public $activeTab = 'notes';
    public $voterOpinions = [];
    public $voterRequests = [];
    // Opinion form
    public $opinionTypeId = null;
    public $opinionRating = null;
    public $opinionNote = '';
    // Request form
    public $requestTypeId = null;
    public $requestAmount = null;
    public $requestNote = '';

    // added: editable status for selected task
    public $taskStatusEdit = '';
    public $tasksLimit = 12; // added: number of tasks to show for Load More

    // Online users for the selected task
    public $onlineUsers = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'taskStatus' => ['except' => ''],
        'taskType' => ['except' => ''],
        'directorySearch' => ['except' => ''],
        'taskSearch' => ['except' => ''],
        'filterPartyId' => ['except' => ''], // added
        'filterSubConsiteId' => ['except' => ''], // added
    ];

    /* Livewire pagination resets */
    public function updatingSearch() { $this->resetPage(); }
    public function updatingPerPage() { $this->resetPage(); }
    public function updatingTaskStatus() { $this->resetPage('tasks_page'); }
    public function updatingTaskType() { $this->resetPage('tasks_page'); }
    public function updatingDirectorySearch() { $this->resetPage('directories_page'); }
    public function updatingTaskSearch(){ $this->resetPage('tasks_page'); }
    public function updatingFilterPartyId(){ $this->resetPage('tasks_page'); }
    public function updatingFilterSubConsiteId(){ $this->resetPage('tasks_page'); }

    public function resetTaskFilters(): void
    {
        $this->taskSearch = $this->taskStatus = $this->taskType = $this->filterPartyId = $this->filterSubConsiteId = '';
        $this->tasksLimit = 12; // reset load more
        $this->resetPage('tasks_page');
    }
    public function applyTaskFilters(): void { $this->tasksLimit = 12; $this->resetPage('tasks_page'); }

    /* Select a task */
    public function selectTask($id): void
    {
        // Ensure task is assigned to current user
        $task = Task::where('id',$id)
            ->whereHas('users', fn($q)=>$q->where('user_id', auth()->id()))
            ->first();
        if(!$task){
            $this->dispatch('swal', icon:'error', title:'Restricted', text:'Task not assigned to you.');
            return;
        }
        $this->selectedTaskId = $task->id;
        $this->loadSubmissionState();
        $this->loadDirectoryLocationFields();
        $this->loadVoterNotes();
        $this->loadVoterOpinions();
        $this->loadVoterRequests();
        $this->newNote='';
        $this->opinionTypeId = $this->opinionRating = null; $this->opinionNote='';
        $this->requestTypeId = null; $this->requestAmount = null; $this->requestNote='';
        $this->taskStatusEdit = $task->status;

        // Mark user as online for this task
        $this->userOpenedTask($task->id);
    }

    protected function loadSubmissionState(): void
    {
        $this->submissionId = null;
        $this->submissionAnswers = [];
        if (!$this->selectedTaskId) { return; }

        $task = Task::with(['form.questions.options', 'submission.answers'])->find($this->selectedTaskId);
        if (!$task || !$task->form) { return; }

        $questionsById = $task->form->questions->keyBy('id');

        if ($task->submission) {
            $this->submissionId = $task->submission->id;
            foreach ($task->submission->answers as $ans) {
                $question = $questionsById->get($ans->form_question_id);
                if(!$question) continue;
                if(in_array($question->type,['checkbox','multiselect'])) {
                    $stored = $ans->value_json ?? [];
                    if($stored && array_keys($stored) !== range(0,count($stored)-1)) { // previously associative
                        $stored = array_keys(array_filter($stored));
                    }
                    // Normalize to list of unique string IDs (important for Livewire checkbox diffing)
                    $stored = array_map('strval', array_values(array_unique($stored)));
                    $this->submissionAnswers[$question->id] = $stored;
                } else {
                    $val = $ans->value_json ?? $ans->value_text ?? null;
                    $this->submissionAnswers[$ans->form_question_id] = $val;
                }
            }
        }

        foreach($task->form->questions as $q){
            if(!array_key_exists($q->id,$this->submissionAnswers)){
                $this->submissionAnswers[$q->id] = in_array($q->type,['checkbox','multiselect']) ? [] : null;
            }
        }
    }

    protected function loadDirectoryLocationFields(): void
    {
        $this->selectedDirectoryId = null;
        $this->currentAddress = '';
        $this->currentStreetAddress = '';
        $this->contactEmail='';
        $this->contactPhones='';
        $this->currentCountryId='';
        $this->currentIslandId='';
        $this->currentPropertyId='';
        if(!$this->selectedTaskId) return;
        $task = Task::with('directory')->find($this->selectedTaskId);
        if(!$task || !$task->directory) return;
        $dir = $task->directory;
        $this->selectedDirectoryId = $dir->id;
        $this->currentAddress = $dir->current_address ?? '';
        $this->currentStreetAddress = $dir->current_street_address ?? '';
        $this->contactEmail = $dir->email ?? '';
        $this->contactPhones = is_array($dir->phones) ? implode(', ', $dir->phones) : ($dir->phones ?? '');
        $this->currentCountryId = $dir->current_country_id ?? '';
        $this->currentIslandId = $dir->current_island_id ?? '';
        $this->currentPropertyId = $dir->current_properties_id ?? '';
    }

    public function updatedCurrentCountryId(): void
    {
        // Reset dependent fields when country changes
        $this->currentIslandId = '';
        $this->currentPropertyId = '';
        // If not Maldives clear islands (UI will hide via blade condition)
        if($this->maldivesCountryId && $this->currentCountryId !== $this->maldivesCountryId){
            $this->currentIslandId='';
            $this->currentPropertyId='';
        }
    }

    public function updatedCurrentIslandId(): void
    {
        $this->currentPropertyId = '';
    }

    protected function loadVoterNotes(): void
    {
        $this->voterNotes = [];
        if(!$this->selectedTaskId) return;
        $task = Task::with('directory')->find($this->selectedTaskId);
        if(!$task || !$task->directory) return;
        $this->voterNotes = VoterNote::with('author:id,name')
            ->where('directory_id',$task->directory_id)
            ->orderByDesc('created_at')
            ->get();
    }

    protected function loadVoterOpinions(): void
    {
        $this->voterOpinions = [];
        if(!$this->selectedTaskId) return;
        $task = Task::with('directory')->find($this->selectedTaskId);
        if(!$task || !$task->directory) return;
        $this->voterOpinions = VoterOpinion::with(['takenBy:id,name','type:id,name'])
            ->where('directory_id',$task->directory_id)
            ->orderByDesc('created_at')
            ->get();
    }

    protected function loadVoterRequests(): void
    {
        $this->voterRequests = [];
        if(!$this->selectedTaskId) return;
        $task = Task::with('directory')->find($this->selectedTaskId);
        if(!$task || !$task->directory) return;
        $this->voterRequests = VoterRequest::with(['author:id,name','type:id,name'])
            ->where('directory_id',$task->directory_id)
            ->orderByDesc('created_at')
            ->get();
    }

    public function setActiveTab(string $tab): void
    {
        if(!in_array($tab,['notes','opinions','requests'])) return;
        $this->activeTab = $tab;
    }

    public function addOpinion(): void
    {
        if(!$this->selectedTaskId) return; $task = Task::with('directory','users')->find($this->selectedTaskId); if(!$task||!$task->directory) return;
        $this->validate([
            'opinionTypeId' => 'required|uuid|exists:opinion_types,id',
            'opinionRating' => 'nullable|integer|min:1|max:5',
            'opinionNote'   => 'nullable|string|max:2000',
        ]);
        VoterOpinion::create([
            'directory_id' => $task->directory_id,
            'election_id'  => $task->election_id,
            'opinion_type_id' => $this->opinionTypeId,
            'rating'       => $this->opinionRating,
            'note'         => $this->opinionNote,
            'taken_by'     => auth()->id(),
            'status'       => 'active',
        ]);
        $this->opinionTypeId = $this->opinionRating = null; $this->opinionNote='';
        $this->loadVoterOpinions();
        $this->broadcastTaskChange($task,'engagement_changed',['section'=>'opinions']);
        $this->dispatch('swal', icon:'success', title:'Added', text:'Opinion added.');
    }

    public function deleteOpinion($id): void
    {
        $op = VoterOpinion::with('directory')->where('id',$id)->first();
        if($op && $op->taken_by === auth()->id()){
            $task = Task::with('users')->where('directory_id',$op->directory_id)->where('election_id',$op->election_id)->first();
            $op->delete();
            $this->loadVoterOpinions();
            if($task){ $this->broadcastTaskChange($task,'engagement_changed',['section'=>'opinions']); }
            $this->dispatch('swal', icon:'success', title:'Deleted', text:'Opinion removed.');
        }
    }

    public function addRequest(): void
    {
        if(!$this->selectedTaskId) return; $task = Task::with('directory','users')->find($this->selectedTaskId); if(!$task||!$task->directory) return;
        $this->validate([
            'requestTypeId' => 'required|uuid|exists:request_types,id',
            'requestAmount' => 'nullable|numeric|min:0',
            'requestNote'   => 'nullable|string|max:2000',
        ]);
        VoterRequest::create([
            'directory_id' => $task->directory_id,
            'election_id'  => $task->election_id,
            'request_type_id' => $this->requestTypeId,
            'amount'       => $this->requestAmount,
            'note'         => $this->requestNote,
            'status'       => 'open',
            'created_by'   => auth()->id(),
        ]);
        $this->requestTypeId = null; $this->requestAmount = null; $this->requestNote='';
        $this->loadVoterRequests();
        $this->broadcastTaskChange($task,'engagement_changed',['section'=>'requests']);
        $this->dispatch('swal', icon:'success', title:'Added', text:'Request added.');
    }

    public function deleteRequest($id): void
    {
        $req = VoterRequest::with('directory')->where('id',$id)->first();
        if($req && $req->created_by === auth()->id()){
            $task = Task::with('users')->where('directory_id',$req->directory_id)->where('election_id',$req->election_id)->first();
            $req->delete();
            $this->loadVoterRequests();
            if($task){ $this->broadcastTaskChange($task,'engagement_changed',['section'=>'requests']); }
            $this->dispatch('swal', icon:'success', title:'Deleted', text:'Request removed.');
        }
    }

    public function saveCurrentLocation(): void
    {
        if(!$this->selectedDirectoryId) return;
        $this->validate([
            'currentAddress' => 'nullable|string|max:500',
            'currentStreetAddress' => 'nullable|string|max:255',
        ]);
        Directory::where('id',$this->selectedDirectoryId)->update([
            'current_address' => $this->currentAddress ?: null,
            'current_street_address' => $this->currentStreetAddress ?: null,
        ]);
        $this->dispatch('swal', icon:'success', title:'Updated', text:'Current location saved.');
        // Refresh selected task relation to reflect changes in UI
        $this->loadDirectoryLocationFields();
    }

    public function updateDirectoryContact(): void
    {
        if(!$this->selectedDirectoryId) return;
        $this->validate([
            'currentAddress' => 'nullable|string|max:500',
            'currentStreetAddress' => 'nullable|string|max:255',
            'contactEmail' => 'nullable|email:rfc,dns|max:255',
            'contactPhones' => 'nullable|string|max:255',
            'currentCountryId' => 'nullable|uuid|exists:countries,id',
            'currentIslandId' => 'nullable|uuid|exists:islands,id',
            'currentPropertyId' => 'nullable|uuid|exists:properties,id',
        ]);
        $phones = collect(preg_split('/[,\n]/', (string)$this->contactPhones))
            ->map(fn($p)=>trim($p))
            ->filter()
            ->unique()
            ->values()
            ->all();
        Directory::where('id',$this->selectedDirectoryId)->update([
            'current_address' => $this->currentAddress ?: null,
            'current_street_address' => $this->currentStreetAddress ?: null,
            'email' => $this->contactEmail ?: null,
            'phones' => $phones ?: null,
            'current_country_id' => $this->currentCountryId ?: null,
            'current_island_id' => $this->currentIslandId ?: null,
            'current_properties_id' => $this->currentPropertyId ?: null,
        ]);
        $this->dispatch('swal', icon:'success', title:'Updated', text:'Directory contact updated.');
        $this->loadDirectoryLocationFields();
    }

    public function addNote(): void // now adds a voter note linked to directory
    {
        if(!$this->selectedTaskId) return;
        $task = Task::with('directory','users')->find($this->selectedTaskId);
        if(!$task || !$task->directory) return;
        $this->validate(['newNote' => 'required|string|max:2000']);
        VoterNote::create([
            'directory_id' => $task->directory_id,
            'election_id' => $task->election_id,
            'note' => $this->newNote,
            'created_by' => auth()->id(),
        ]);
        $this->newNote='';
        $this->loadVoterNotes();
        $this->broadcastTaskChange($task,'engagement_changed',['section'=>'notes']);
        $this->dispatch('swal', icon:'success', title:'Added', text:'Note added.');
    }

    public function deleteNote($id): void // delete voter note
    {
        $note = VoterNote::where('id',$id)->first();
        if($note){
            if($note->created_by === auth()->id()){
                $task = Task::with('users')->where('directory_id',$note->directory_id)->where('election_id',$note->election_id)->first();
                $note->delete();
                $this->loadVoterNotes();
                if($task){ $this->broadcastTaskChange($task,'engagement_changed',['section'=>'notes']); }
                $this->dispatch('swal', icon:'success', title:'Deleted', text:'Note removed.');
            } else {
                $this->dispatch('swal', icon:'error', title:'Forbidden', text:'You cannot delete this note.');
            }
        }
    }

    public function saveSubmission(): void
    {
        $task = Task::with('form.questions.options','users')->find($this->selectedTaskId);
        if(!$task || !$task->form){
            $this->dispatch('swal', icon:'error', title:'No form', text:'This task has no form.');
            return; }

        DB::transaction(function() use ($task){
            $submission = $task->submission;
            if(!$submission){
                $submission = FormSubmission::create([
                    'form_id'=>$task->form_id,
                    'task_id'=>$task->id,
                    'directory_id'=>$task->directory_id,
                    'election_id'=>$task->election_id,
                    'assigned_agent_id'=>auth()->id(),
                    'status'=>'in_progress',
                ]);
            } elseif($submission->status !== 'submitted') {
                $submission->status = 'in_progress';
                $submission->save();
            }
            foreach($task->form->questions as $q){
                $value = $this->submissionAnswers[$q->id] ?? null;
                $answer = $submission->answers()->firstOrNew(['form_question_id'=>$q->id]);
                if(in_array($q->type,['checkbox','multiselect'])){
                    $list = is_array($value) ? array_values(array_unique($value)) : [];
                    $list = array_map('strval', $list);
                    $answer->value_json = $list;
                    $answer->value_text = null;
                } elseif(is_array($value)) {
                    $answer->value_json = $value; $answer->value_text = null;
                } else {
                    $answer->value_text = $value === '' ? null : $value; $answer->value_json = null;
                }
                $answer->save();
            }
            $this->submissionId = $submission->id;
        });

        TaskDataChanged::dispatch($task->id, (string)auth()->id(), 'submission_saved', ['task_id'=>$task->id]); // legacy single
        $this->broadcastTaskChange($task,'submission_saved',['task_id'=>$task->id]); // new multi
        $this->dispatch('swal', icon:'success', title:'Saved', text:'Progress saved.');
    }

    public function submitSubmission(): void
    {
        $this->saveSubmission();
        if(!$this->submissionId){ return; }

        $task = Task::with('form.questions')->find($this->selectedTaskId);
        if(!$task || !$task->form){ return; }

        // Basic required validation (client-side already hints but enforce here)
        $missing = [];
        foreach($task->form->questions as $q){
            if(!$q->is_required) continue;
            $val = $this->submissionAnswers[$q->id] ?? null;
            if(in_array($q->type,['checkbox','multiselect'])){
                if(!is_array($val) || count($val) === 0){ $missing[] = $q->question_text; }
            } else {
                if($val === null || $val === ''){ $missing[] = $q->question_text; }
            }
        }
        if(count($missing)){
            $this->dispatch('swal', icon:'error', title:'Missing Required', text:'Please fill required: '.implode(', ', $missing));
            return;
        }

        DB::transaction(function() use ($task){
            FormSubmission::where('id',$this->submissionId)->update(['status'=>'submitted']);
            if($task->type === 'form_fill'){
                $task->status = 'completed';
                $task->completed_at = now();
                $task->save();
            }
        });

        $this->loadSubmissionState();
        $this->broadcastTaskChange($task,'submission_submitted',['status'=>$task->status]);
        $this->dispatch('swal', icon:'success', title:'Submitted', text:'Form submitted.');
    }

    /* Task stats (computed) */
    public function getStatsProperty(): array
    {
        $base = Task::whereHas('users', fn($q) => $q->where('user_id', auth()->id()));

        $total = (clone $base)->count();
        if ($total === 0) {
            return [
                'completed' => 0,
                'pending' => 0,
                'follow_up' => 0,
                'total' => 0,
                'percentages' => [
                    'completed' => 0,
                    'pending' => 0,
                    'follow_up' => 0,
                ],
            ];
        }

        $completed = (clone $base)->where('status', 'completed')->count();
        $pending   = (clone $base)->where('status', 'pending')->count();
        $follow_up = (clone $base)->where('status', 'follow_up')->count();

        return [
            'completed' => $completed,
            'pending' => $pending,
            'follow_up' => $follow_up,
            'total' => $total,
            'percentages' => [
                'completed' => (int) round(($completed / $total) * 100),
                'pending' => (int) round(($pending / $total) * 100),
                'follow_up' => (int) round(($follow_up / $total) * 100),
            ],
        ];
    }

    /* Legacy helpers */
    public function toggleDirectory($id): void { if (in_array($id, $this->selectedDirectoryIds, true)) { $this->selectedDirectoryIds = array_values(array_diff($this->selectedDirectoryIds, [$id])); } else { $this->selectedDirectoryIds[] = $id; } }
    public function clearSelectedDirectories(): void { $this->selectedDirectoryIds = []; }
    public function selectCurrentPage($ids): void { foreach($ids as $id){ if(!in_array($id,$this->selectedDirectoryIds,true)){ $this->selectedDirectoryIds[]=$id; } } }

    /* Bulk task creation (legacy) */
    public function createTasks(): void
    {
        $this->validate([
            'taskTitle'               => ['required', 'string', 'max:255'],
            'assigneeIds'             => ['required', 'array', 'min:1'],
            'assigneeIds.*'           => ['integer', 'exists:users,id'],
            'selectedDirectoryIds'    => ['required', 'array', 'min:1'],
            'selectedDirectoryIds.*'  => ['uuid', 'exists:directories,id'],
            'newTaskType'             => ['required', Rule::in(['form_fill', 'pickup', 'dropoff', 'other'])],
            'newTaskPriority'         => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'newTaskFormId'           => ['nullable', 'uuid', 'exists:forms,id'],
            'newTaskElectionId'       => ['nullable', 'uuid', 'exists:elections,id'],
            'newTaskDueAt'            => ['nullable', 'date'],
        ]);

        DB::transaction(function () {
            foreach ($this->selectedDirectoryIds as $dirId) {
                $task = Task::create([
                    'title'        => $this->taskTitle,
                    'notes'        => $this->taskNotes,
                    'type'         => $this->newTaskType,
                    'priority'     => $this->newTaskPriority,
                    'status'       => 'pending',
                    'form_id'      => $this->newTaskFormId,
                    'directory_id' => $dirId,
                    'election_id'  => $this->newTaskElectionId,
                    'due_at'       => $this->newTaskDueAt ? Carbon::parse($this->newTaskDueAt) : null,
                    'created_by'   => auth()->id(),
                ]);

                $task->assignees()->sync($this->assigneeIds);
            }
        });

        $count = count($this->selectedDirectoryIds);

        $this->reset([
            'taskTitle',
            'taskNotes',
            'newTaskType',
            'newTaskPriority',
            'newTaskDueAt',
            'newTaskFormId',
            'newTaskElectionId',
        ]);

        $this->newTaskType     = 'other';
        $this->newTaskPriority = 'normal';
        $this->selectedDirectoryIds = [];

        $this->dispatch('swal', [
            'type'  => 'success',
            'title' => 'Tasks Created',
            'text'  => "{$count} task(s) created.",
        ]);
    }

    public function render()
    {
        $agents = User::query()
            ->when($this->search, function ($q) {
                $q->where(function ($qq) {
                    $qq->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('staff_id', 'like', '%' . $this->search . '%');
                });
            })
            ->latest()
            ->paginate($this->perPage);

        $tasksQuery = Task::with(['assignees', 'form.questions.options', 'submission.answers','directory.party','directory.subConsite'])
            ->whereHas('users', fn($q)=>$q->where('user_id', auth()->id())) // restrict to my tasks
            ->when($this->taskStatus, fn ($q) => $q->where('status', $this->taskStatus))
            ->when($this->taskType, fn ($q) => $q->where('type', $this->taskType))
            ->when($this->filterPartyId, function($q){
                $q->whereHas('directory', fn($dq)=>$dq->where('party_id',$this->filterPartyId));
            })
            ->when($this->filterSubConsiteId, function($q){
                $q->whereHas('directory', fn($dq)=>$dq->where('sub_consite_id',$this->filterSubConsiteId));
            })
            ->when($this->taskSearch, function($q){
                $term = trim($this->taskSearch);
                $q->where(function($qq) use ($term){
                    $qq->where('title','like','%'.$term.'%')
                       ->orWhere('number','like','%'.$term.'%')
                       ->orWhereHas('directory', function($dq) use ($term){
                           $dq->where('name','like','%'.$term.'%')
                              ->orWhere('id_card_number','like','%'.$term.'%')
                              ->orWhereHas('party', fn($pq)=>$pq->where('short_name','like','%'.$term.'%'))
                              ->orWhereHas('subConsite', fn($sq)=>$sq->where('code','like','%'.$term.'%'));
                       });
                });
            })
            ->latest();

        $tasksTotal = (clone $tasksQuery)->count();
        if($this->tasksLimit > $tasksTotal){ $this->tasksLimit = $tasksTotal; }
        $tasks = $tasksQuery->take($this->tasksLimit)->get();

        if (!$this->selectedTaskId && $tasks->count() > 0) {
            $this->selectedTaskId = $tasks->first()->id;
            $this->loadSubmissionState();
            $this->loadDirectoryLocationFields();
            $this->loadVoterNotes();
            $this->loadVoterOpinions();
            $this->loadVoterRequests();
        }

        $selectedTask = null;
        if ($this->selectedTaskId) {
            $selectedTask = Task::with([
                'assignees',
                'directory.party','directory.subConsite',
                'form.questions.options',
                'submission.answers',
            ])
            ->whereHas('users', fn($q)=>$q->where('user_id', auth()->id()))
            ->find($this->selectedTaskId);

            if (!$selectedTask) {
                $this->selectedTaskId = null;
            }
        }

        $forms     = Form::orderBy('title')->get(['id', 'title']);
        $allAgents = User::orderBy('name')->get(['id', 'name']);
        $parties = Party::orderBy('short_name')->get(['id','short_name','name']);
        $subConsites = SubConsite::orderBy('code')->get(['id','code']);
        $opinionTypes = OpinionType::orderBy('name')->get(['id','name']);
        $requestTypes = RequestType::orderBy('name')->get(['id','name']);
        $countries = Country::orderBy('name')->get(['id','name']);
        $this->maldivesCountryId = $this->maldivesCountryId ?: ($countries->firstWhere('name','Maldives')->id ?? null);
        $currentIslands = ($this->currentCountryId && $this->currentCountryId === $this->maldivesCountryId)
            ? Island::orderBy('name')->get(['id','name'])
            : collect();
        $currentProperties = $this->currentIslandId ? Property::where('island_id',$this->currentIslandId)->orderBy('name')->get(['id','name']) : collect();
        return view(
            'livewire.agent.agent-management',
            [
                'agents'       => $agents,
                'tasks'        => $tasks,
                'tasksTotal'   => $tasksTotal, // added total for Load More button
                'selectedTask' => $selectedTask,
                'forms'        => $forms,
                'allAgents'    => $allAgents,
                'parties'      => $parties,
                'subConsites'  => $subConsites,
                'opinionTypes' => $opinionTypes,
                'requestTypes' => $requestTypes,
                'countries' => $countries,
                'currentIslands' => $currentIslands,
                'currentProperties' => $currentProperties,
                'maldivesCountryId' => $this->maldivesCountryId,
            ]
        )->layout('layouts.master');
    }

    /* Helper: broadcast change to all assigned users */
    protected function broadcastTaskChange(Task $task, string $changeType, array $extra = []): void
    {
        try {
            $userIds = $task->users()->pluck('users.id')->unique();
            foreach($userIds as $uid){
                TaskDataChanged::dispatch($task->id, (string)$uid, $changeType, $extra);
            }
        } catch(\Throwable $e){ /* swallow to avoid UI break */ }
    }

    /**
     * Livewire hook that runs when a public property is updated.
     * When taskStatusEdit is changed, this will trigger the update logic.
     */
    public function updatedTaskStatusEdit($value): void
    {
        $this->updateTaskStatus();
    }

    // Add method to update selected task status (pending, follow_up, completed)
    public function updateTaskStatus(): void
    {
        if(!$this->selectedTaskId) return;
        $this->validate(['taskStatusEdit' => 'required|in:pending,follow_up,completed']);
        $task = Task::with('users')->find($this->selectedTaskId);
        if(!$task) return;
        if($task->status !== $this->taskStatusEdit){
            $task->status = $this->taskStatusEdit;
            if($this->taskStatusEdit === 'completed'){
                $task->completed_at = $task->completed_at ?: now();
            } else { $task->completed_at = null; }
            $task->save();
            $this->broadcastTaskChange($task,'status_updated',['status'=>$task->status,'completed_at'=>$task->completed_at?->toISOString()]);
            $this->dispatch('swal', icon:'success', title:'Updated', text:'Task status updated.');
        }
    }

    public function loadMoreTasks(): void
    {
        $this->tasksLimit += 12; // increase limit
    }

    // Realtime: invoked from JS when TaskDataChanged broadcast received
    public function handleExternalTaskUpdate($payload): void
    {
        $taskId = null;
        if(is_array($payload)) {
            $taskId = $payload['task_id'] ?? $payload['taskId'] ?? null;
        } elseif(is_string($payload)) {
            $taskId = $payload;
        }
        if(!$taskId) return;
        if($this->selectedTaskId === $taskId){
            $this->loadSubmissionState();
            $this->loadDirectoryLocationFields();
            $this->loadVoterNotes();
            $this->loadVoterOpinions();
            $this->loadVoterRequests();
        }
        $this->dispatch('$refresh');
    }

    // Called when a user opens a task
    public function userOpenedTask($taskId)
    {
        $userId = auth()->id();
        \Log::info('userOpenedTask called', ['taskId' => $taskId, 'userId' => $userId]);
        $this->onlineUsers[$taskId][$userId] = true;
        // Broadcast presence
        broadcast(new \App\Events\TaskUserPresenceChanged($taskId, $userId, true))->toOthers();
    }

    // Called when a user closes a task
    public function userClosedTask($taskId)
    {
        $userId = auth()->id();
        \Log::info('userClosedTask called', ['taskId' => $taskId, 'userId' => $userId]);
        unset($this->onlineUsers[$taskId][$userId]);
        // Broadcast presence
        broadcast(new \App\Events\TaskUserPresenceChanged($taskId, $userId, false))->toOthers();
    }

    // Listen for presence events (called via Echo/JS)
    public function updateUserPresence($taskId, $userId, $isOnline)
    {
        \Log::info('updateUserPresence called', ['taskId' => $taskId, 'userId' => $userId, 'isOnline' => $isOnline]);
        if ($isOnline) {
            $this->onlineUsers[$taskId][$userId] = true;
        } else {
            unset($this->onlineUsers[$taskId][$userId]);
        }
        $this->dispatch('$refresh'); // force Livewire to refresh UI
    }
}
