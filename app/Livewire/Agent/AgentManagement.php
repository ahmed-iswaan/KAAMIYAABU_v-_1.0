<?php

namespace App\Livewire\Agent;

use App\Models\Country;
use App\Models\Island;
use App\Models\Property;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Task;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\Directory;
use App\Models\Party;
use App\Models\SubConsite;
use App\Models\VoterNote;
use App\Models\VoterOpinion;
use App\Models\VoterRequest;
use App\Models\OpinionType;
use App\Models\RequestType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;
use App\Events\TaskDataChanged;
use App\Events\TaskStatsUpdated; // added
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use App\Models\SubStatus; // added

class AgentManagement extends Component
{
    use WithPagination, AuthorizesRequests;

    public $search = '';
    public $taskSearch = '';
    public $perPage = 10;
    public $pageTitle = 'Agents';

    public $taskStatus = '';
    public $taskType = '';
    public $filterPartyId = '';
    public $filterSubConsiteId = '';
    public $filterSubStatusId = ''; // added missing property for sub status filter

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

    // Current task & form submission
    public $selectedTaskId = null;
    /** @var array<int|string, mixed> */
    public $submissionAnswers = [];
    public $submissionId = null;

    // Directory current location editing
    public $currentAddress = '';
    public $currentStreetAddress = '';
    public $selectedDirectoryId = null;
    public $contactEmail = '';
    public $contactPhones = '';
    public $currentCountryId = '';
    public $currentIslandId = '';
    public $currentPropertyId = '';
    public $maldivesCountryId = null;

    // Notes / opinions / requests
    public $newNote = '';
    public $voterNotes = [];
    public $activeTab = 'notes';
    public $voterOpinions = [];
    public $voterRequests = [];
    public $opinionTypeId = null;
    public $opinionRating = null;
    public $opinionNote = '';
    public $requestTypeId = null;
    public $requestAmount = null;
    public $requestNote = '';

    // Task status (editable)
    public $taskStatusEdit = '';
    public $tasksLimit = 12;
    public $followUpDate = null; // added

    // Online users (flat list for the **currently selected task**)
    public $onlineUsers = [];

    // Sub status
    public $subStatusId = ''; // added selected sub status
    public $subStatuses = []; // list of active sub statuses

    protected $queryString = [
        'search' => ['except' => ''],
        'taskStatus' => ['except' => ''],
        'taskType' => ['except' => ''],
        'directorySearch' => ['except' => ''],
        'taskSearch' => ['except' => ''],
        'filterPartyId' => ['except' => ''],
        'filterSubConsiteId' => ['except' => ''],
        'filterSubStatusId' => ['except' => ''], // added
    ];

    /* Pagination resets */
    public function updatingSearch() { $this->resetPage(); }
    public function updatingPerPage() { $this->resetPage(); }
    public function updatingTaskStatus() { $this->resetPage('tasks_page'); }
    public function updatingTaskType() { $this->resetPage('tasks_page'); }
    public function updatingDirectorySearch() { $this->resetPage('directories_page'); }
    public function updatingTaskSearch(){ $this->resetPage('tasks_page'); }
    public function updatingFilterPartyId(){ $this->resetPage('tasks_page'); }
    public function updatingFilterSubConsiteId(){ $this->resetPage('tasks_page'); }
    public function updatingFilterSubStatusId(){ $this->resetPage('tasks_page'); } // added

    public function resetTaskFilters(): void
    {
        $this->taskSearch = $this->taskStatus = $this->taskType = $this->filterPartyId = $this->filterSubConsiteId = $this->filterSubStatusId = ''; // added sub status
        $this->tasksLimit = 12;
        $this->resetPage('tasks_page');
    }
    public function applyTaskFilters(): void
    {
        $this->tasksLimit = 12;
        $this->resetPage('tasks_page');
    }

    /* Select a task */
    public function selectTask($id): void
    {
        $start = microtime(true);
        $task = Task::with([
            'form.sections.questions.options',
            'form.questions.options',
            'submission.answers',
            'directory.party',
            'directory.subConsite',
            'assignees',
            'completedBy', // added
            'followUpBy',  // added
        ])
        ->where('id', $id)
        ->whereHas('users', fn($q)=>$q->where('user_id', auth()->id()))
        ->first();

        if (!$task) {
            $this->dispatch('swal', icon:'error', title:'Restricted', text:'Task not assigned to you.');
            return;
        }

        $this->selectedTaskId = $task->id;
        $this->taskStatusEdit = $task->status;
        $this->followUpDate = $task->follow_up_date?->format('Y-m-d\TH:i'); // preload
        $this->subStatusId = $task->sub_status_id ?? ''; // preload sub status
        $this->loadSubmissionStateFromTask($task);
        $this->loadDirectoryLocationFieldsFromTask($task);
        $this->loadVoterNotes();
        $this->loadVoterOpinions();
        $this->loadVoterRequests();

        // reset form inputs
        $this->newNote = '';
        $this->opinionTypeId = null;
        $this->opinionRating = null;
        $this->opinionNote = '';
        $this->requestTypeId = null;
        $this->requestAmount = null;
        $this->requestNote = '';

        \Log::debug('selectTask', ['task_id' => $task->id, 'user_id' => auth()->id(), 'duration_ms' => round((microtime(true)-$start)*1000)]);

        // mark presence in PHP + notify JS to join presence channel
        $this->userOpenedTask($task->id);
        $this->dispatch('task-selected', taskId: (string)$task->id);
    }

    protected function loadSubmissionStateFromTask($task): void
    {
        $this->submissionId = null;
        $this->submissionAnswers = [];
        if (!$task || !$task->form) return;
        $questionsById = $task->form->questions->keyBy('id');
        if ($task->submission) {
            $this->submissionId = $task->submission->id;
            foreach ($task->submission->answers as $ans) {
                $question = $questionsById->get($ans->form_question_id);
                if (!$question) continue;
                if (in_array($question->type, ['checkbox','multiselect'])) {
                    $stored = $ans->value_json ?? [];
                    if ($stored && array_keys($stored) !== range(0, count($stored)-1)) {
                        $stored = array_keys(array_filter($stored));
                    }
                    $stored = array_map('strval', array_values(array_unique($stored)));
                    $this->submissionAnswers[$question->id] = $stored;
                } else {
                    $val = $ans->value_json ?? $ans->value_text ?? null;
                    $this->submissionAnswers[$ans->form_question_id] = $val;
                }
            }
        }
        foreach ($task->form->questions as $q) {
            if (!array_key_exists($q->id, $this->submissionAnswers)) {
                $this->submissionAnswers[$q->id] = in_array($q->type, ['checkbox','multiselect']) ? [] : null;
            }
        }
    }

    protected function loadDirectoryLocationFieldsFromTask($task): void
    {
        $this->selectedDirectoryId  = $task->directory->id ?? null;
        $this->currentAddress       = $task->directory->current_address ?? '';
        $this->currentStreetAddress = $task->directory->current_street_address ?? '';
        $this->contactEmail         = $task->directory->email ?? '';
        $this->contactPhones        = is_array($task->directory->phones) ? implode(', ', $task->directory->phones) : ($task->directory->phones ?? '');
        $this->currentCountryId     = $task->directory->current_country_id ?? '';
        $this->currentIslandId      = $task->directory->current_island_id ?? '';
        $this->currentPropertyId    = $task->directory->current_properties_id ?? '';
    }

    protected function loadSubmissionState(): void
    {
        $this->submissionId = null;
        $this->submissionAnswers = [];

        if (!$this->selectedTaskId) return;

        $task = Task::with(['form.questions.options', 'submission.answers'])->find($this->selectedTaskId);
        if (!$task || !$task->form) return;

        $questionsById = $task->form->questions->keyBy('id');

        if ($task->submission) {
            $this->submissionId = $task->submission->id;
            foreach ($task->submission->answers as $ans) {
                $question = $questionsById->get($ans->form_question_id);
                if (!$question) continue;

                if (in_array($question->type, ['checkbox','multiselect'])) {
                    $stored = $ans->value_json ?? [];
                    if ($stored && array_keys($stored) !== range(0, count($stored)-1)) { // normalize assoc → list
                        $stored = array_keys(array_filter($stored));
                    }
                    $stored = array_map('strval', array_values(array_unique($stored)));
                    $this->submissionAnswers[$question->id] = $stored;
                } else {
                    $val = $ans->value_json ?? $ans->value_text ?? null;
                    $this->submissionAnswers[$ans->form_question_id] = $val;
                }
            }
        }

        // ensure every question has a slot
        foreach ($task->form->questions as $q) {
            if (!array_key_exists($q->id, $this->submissionAnswers)) {
                $this->submissionAnswers[$q->id] = in_array($q->type, ['checkbox','multiselect']) ? [] : null;
            }
        }
    }

    protected function loadDirectoryLocationFields(): void
    {
        $this->selectedDirectoryId = null;
        $this->currentAddress = '';
        $this->currentStreetAddress = '';
        $this->contactEmail = '';
        $this->contactPhones = '';
        $this->currentCountryId = '';
        $this->currentIslandId = '';
        $this->currentPropertyId = '';

        if (!$this->selectedTaskId) return;

        $task = Task::with('directory')->find($this->selectedTaskId);
        if (!$task || !$task->directory) return;

        $dir = $task->directory;
        $this->selectedDirectoryId  = $dir->id;
        $this->currentAddress       = $dir->current_address ?? '';
        $this->currentStreetAddress = $dir->current_street_address ?? '';
        $this->contactEmail         = $dir->email ?? '';
        $this->contactPhones        = is_array($dir->phones) ? implode(', ', $dir->phones) : ($dir->phones ?? '');
        $this->currentCountryId     = $dir->current_country_id ?? '';
        $this->currentIslandId      = $dir->current_island_id ?? '';
        $this->currentPropertyId    = $dir->current_properties_id ?? '';
    }

    public function updatedCurrentCountryId(): void
    {
        $this->currentIslandId = '';
        $this->currentPropertyId = '';

        if ($this->maldivesCountryId && $this->currentCountryId !== $this->maldivesCountryId) {
            $this->currentIslandId = '';
            $this->currentPropertyId = '';
        }
    }

    public function updatedCurrentIslandId(): void
    {
        $this->currentPropertyId = '';
    }

    protected function loadVoterNotes(): void
    {
        $this->voterNotes = [];
        if (!$this->selectedTaskId) return;

        $task = Task::with('directory')->find($this->selectedTaskId);
        if (!$task || !$task->directory) return;

        $this->voterNotes = VoterNote::with('author:id,name')
            ->where('directory_id', $task->directory_id)
            ->orderByDesc('created_at')
            ->get();
    }

    protected function loadVoterOpinions(): void
    {
        $this->voterOpinions = [];
        if (!$this->selectedTaskId) return;

        $task = Task::with('directory')->find($this->selectedTaskId);
        if (!$task || !$task->directory) return;

        $this->voterOpinions = VoterOpinion::with(['takenBy:id,name', 'type:id,name'])
            ->where('directory_id', $task->directory_id)
            ->orderByDesc('created_at')
            ->get();
    }

    protected function loadVoterRequests(): void
    {
        $this->voterRequests = [];
        if (!$this->selectedTaskId) return;

        $task = Task::with('directory')->find($this->selectedTaskId);
        if (!$task || !$task->directory) return;

        $this->voterRequests = VoterRequest::with(['author:id,name', 'type:id,name'])
            ->where('directory_id', $task->directory_id)
            ->orderByDesc('created_at')
            ->get();
    }

    public function setActiveTab(string $tab): void
    {
        if (!in_array($tab, ['notes','opinions','requests'], true)) return;
        $this->activeTab = $tab;
    }

    public function addOpinion(): void
    {
        if (!$this->selectedTaskId) return;

        $task = Task::with('directory','users')->find($this->selectedTaskId);
        if (!$task || !$task->directory) return;

        $this->validate([
            'opinionTypeId' => 'required|uuid|exists:opinion_types,id',
            'opinionRating' => 'nullable|integer|min:1|max:5',
            'opinionNote'   => 'nullable|string|max:2000',
        ]);

        $opinion = VoterOpinion::create([
            'directory_id'    => $task->directory_id,
            'election_id'     => $task->election_id,
            'opinion_type_id' => $this->opinionTypeId,
            'rating'          => $this->opinionRating,
            'note'            => $this->opinionNote,
            'taken_by'        => auth()->id(),
            'status'          => 'active',
        ]);

        $this->opinionTypeId = null;
        $this->opinionRating = null;
        $this->opinionNote   = '';

        $this->loadVoterOpinions();
        $this->broadcastTaskChange($task, 'engagement_changed', ['section' => 'opinions']);
        $this->logTaskEvent('opinions.added', $task, ['opinion_id' => $opinion->id ?? null]);
        $this->dispatch('swal', icon:'success', title:'Added', text:'Opinion added.');
    }

    public function deleteOpinion($id): void
    {
        $op = VoterOpinion::with('directory')->where('id',$id)->first();
        if ($op && $op->taken_by === auth()->id()) {
            $task = Task::with('users')
                ->where('directory_id',$op->directory_id)
                ->where('election_id',$op->election_id)
                ->first();

            $op->delete();
            $this->loadVoterOpinions();

            if ($task) {
                $this->broadcastTaskChange($task, 'engagement_changed', ['section' => 'opinions']);
            }

            $this->dispatch('swal', icon:'success', title:'Deleted', text:'Opinion removed.');
        }
    }

    public function addRequest(): void
    {
        if (!$this->selectedTaskId) return;

        $task = Task::with('directory','users')->find($this->selectedTaskId);
        if (!$task || !$task->directory) return;

        $this->validate([
            'requestTypeId' => 'required|uuid|exists:request_types,id',
            'requestAmount' => 'nullable|numeric|min:0',
            'requestNote'   => 'nullable|string|max:2000',
        ]);

        $request = VoterRequest::create([
            'directory_id'    => $task->directory_id,
            'election_id'     => $task->election_id,
            'request_type_id' => $this->requestTypeId,
            'amount'          => $this->requestAmount,
            'note'            => $this->requestNote,
            'status'          => 'open',
            'created_by'      => auth()->id(),
        ]);

        $this->requestTypeId = null;
        $this->requestAmount = null;
        $this->requestNote   = '';

        $this->loadVoterRequests();
        $this->broadcastTaskChange($task,'engagement_changed', ['section'=>'requests']);
        $this->logTaskEvent('requests.added', $task, ['request_id' => $request->id ?? null]);
        $this->dispatch('swal', icon:'success', title:'Added', text:'Request added.');
    }

    public function deleteRequest($id): void
    {
        $req = VoterRequest::with('directory')->where('id',$id)->first();
        if ($req && $req->created_by === auth()->id()) {
            $task = Task::with('users')
                ->where('directory_id',$req->directory_id)
                ->where('election_id',$req->election_id)
                ->first();

            $req->delete();
            $this->loadVoterRequests();

            if ($task) {
                $this->broadcastTaskChange($task,'engagement_changed',['section'=>'requests']);
            }

            $this->dispatch('swal', icon:'success', title:'Deleted', text:'Request removed.');
        }
    }

    public function saveCurrentLocation(): void
    {
        if (!$this->selectedDirectoryId) return;

        $this->validate([
            'currentAddress'       => 'nullable|string|max:500',
            'currentStreetAddress' => 'nullable|string|max:255',
        ]);

        Directory::where('id',$this->selectedDirectoryId)->update([
            'current_address'       => $this->currentAddress ?: null,
            'current_street_address'=> $this->currentStreetAddress ?: null,
        ]);

        $this->dispatch('swal', icon:'success', title:'Updated', text:'Current location saved.');
        $this->loadDirectoryLocationFields();
    }

    public function updateDirectoryContact(): void
    {
        if (!$this->selectedDirectoryId) return;

        $this->validate([
            'currentAddress'     => 'nullable|string|max:500',
            'currentStreetAddress' => 'nullable|string|max:255',
            'contactEmail'       => 'nullable|email:rfc,dns|max:255',
            'contactPhones'      => 'nullable|string|max:255',
            'currentCountryId'   => 'nullable|uuid|exists:countries,id',
            'currentIslandId'    => 'nullable|uuid|exists:islands,id',
            'currentPropertyId'  => 'nullable|uuid|exists:properties,id',
        ]);

        $phones = collect(preg_split('/[,\n]/', (string)$this->contactPhones))
            ->map(fn($p)=>trim($p))
            ->filter()
            ->unique()
            ->values()
            ->all();

        Directory::where('id',$this->selectedDirectoryId)->update([
            'current_address'       => $this->currentAddress ?: null,
            'current_street_address' => $this->currentStreetAddress ?: null,
            'email'                 => $this->contactEmail ?: null,
            'phones'                => $phones ?: null,
            'current_country_id'    => $this->currentCountryId ?: null,
            'current_island_id'     => $this->currentIslandId ?: null,
            'current_properties_id' => $this->currentPropertyId ?: null,
        ]);

        $this->dispatch('swal', icon:'success', title:'Updated', text:'Directory contact updated.');
        $this->loadDirectoryLocationFields();
    }

    public function addNote(): void
    {
        if (!$this->selectedTaskId) return;

        $task = Task::with('directory','users')->find($this->selectedTaskId);
        if (!$task || !$task->directory) return;

        $this->validate(['newNote' => 'required|string|max:2000']);

        $note = VoterNote::create([
            'directory_id' => $task->directory_id,
            'election_id'  => $task->election_id,
            'note'         => $this->newNote,
            'created_by'   => auth()->id(),
        ]);

        $this->newNote = '';
        $this->loadVoterNotes();
        $this->broadcastTaskChange($task,'engagement_changed',['section'=>'notes']);
        $this->logTaskEvent('notes.added', $task, ['note_id' => $note->id ?? null]);
        $this->dispatch('swal', icon:'success', title:'Added', text:'Note added.');
    }

    public function deleteNote($id): void
    {
        $note = VoterNote::where('id',$id)->first();

        if ($note) {
            if ($note->created_by === auth()->id()) {
                $task = Task::with('users')
                    ->where('directory_id',$note->directory_id)
                    ->where('election_id',$note->election_id)
                    ->first();

                $note->delete();
                $this->loadVoterNotes();

                if ($task) {
                    $this->broadcastTaskChange($task,'engagement_changed',['section'=>'notes']);
                }

                $this->dispatch('swal', icon:'success', title:'Deleted', text:'Note removed.');
            } else {
                $this->dispatch('swal', icon:'error', title:'Forbidden', text:'You cannot delete this note.');
            }
        }
    }

    public function saveSubmission(): void
    {
        $task = Task::with('form.questions.options','users')->find($this->selectedTaskId);
        if (!$task || !$task->form) {
            $this->dispatch('swal', icon:'error', title:'No form', text:'This task has no form.');
            return;
        }

        DB::transaction(function () use ($task) {
            $submission = $task->submission;

            if (!$submission) {
                $submission = FormSubmission::create([
                    'form_id'           => $task->form_id,
                    'task_id'           => $task->id,
                    'directory_id'      => $task->directory_id,
                    'election_id'       => $task->election_id,
                    'assigned_agent_id' => auth()->id(),
                    'status'            => 'in_progress',
                ]);
            } elseif ($submission->status !== 'submitted') {
                $submission->status = 'in_progress';
                $submission->save();
            }

            foreach ($task->form->questions as $q) {
                $value  = $this->submissionAnswers[$q->id] ?? null;
                $answer = $submission->answers()->firstOrNew(['form_question_id'=>$q->id]);

                if (in_array($q->type,['checkbox','multiselect'])) {
                    $list = is_array($value) ? array_values(array_unique($value)) : [];
                    $list = array_map('strval', $list);
                    $answer->value_json = $list;
                    $answer->value_text = null;
                } elseif (is_array($value)) {
                    $answer->value_json = $value;
                    $answer->value_text = null;
                } else {
                    $answer->value_text = $value === '' ? null : $value;
                    $answer->value_json = null;
                }

                $answer->save();
            }

            $this->submissionId = $submission->id;
        });

        TaskDataChanged::dispatch($task->id, (string)auth()->id(), 'submission_saved', ['task_id'=>$task->id]);
        $this->broadcastTaskChange($task,'submission_saved',['task_id'=>$task->id]);
        $this->logTaskEvent('submission.saved', $task, ['task_id' => $task->id]);
        $this->dispatch('swal', icon:'success', title:'Saved', text:'Progress saved.');
    }

    public function submitSubmission(): void
    {
        $this->saveSubmission();
        if (!$this->submissionId) return;

        $task = Task::with('form.questions')->find($this->selectedTaskId);
        if (!$task || !$task->form) return;

        // Minimal required validation
        $missing = [];
        foreach ($task->form->questions as $q) {
            if (!$q->is_required) continue;
            $val = $this->submissionAnswers[$q->id] ?? null;

            if (in_array($q->type,['checkbox','multiselect'])) {
                if (!is_array($val) || count($val) === 0) $missing[] = $q->question_text;
            } else {
                if ($val === null || $val === '') $missing[] = $q->question_text;
            }
        }

        if ($missing) {
            $this->dispatch('swal', icon:'error', title:'Missing Required', text:'Please fill required: '.implode(', ', $missing));
            return;
        }

        DB::transaction(function () use ($task) {
            FormSubmission::where('id',$this->submissionId)->update(['status'=>'submitted']);

            if ($task->type === 'form_fill') {
                $task->status = 'completed';
                $task->completed_at = now();
                $task->save();
            }
        });

        $this->loadSubmissionState();
        $this->broadcastTaskChange($task,'submission_submitted',['status'=>$task->status]);
        $this->logTaskEvent('submission.submitted', $task, ['task_id' => $task->id]);
        $this->dispatch('swal', icon:'success', title:'Submitted', text:'Form submitted.');
    }

    /* Computed stats */
    public function getStatsProperty(): array
    {
        $base = Task::whereHas('users', fn($q)=>$q->where('user_id', auth()->id()));

        $total = (clone $base)->count();
        if ($total === 0) {
            return [
                'completed' => 0,
                'pending' => 0,
                'follow_up' => 0,
                'total' => 0,
                'percentages' => ['completed'=>0,'pending'=>0,'follow_up'=>0],
            ];
        }

        $completed = (clone $base)->where('status','completed')->count();
        $pending   = (clone $base)->where('status','pending')->count();
        $follow_up = (clone $base)->where('status','follow_up')->count();

        return [
            'completed' => $completed,
            'pending' => $pending,
            'follow_up' => $follow_up,
            'total' => $total,
            'percentages' => [
                'completed' => (int) round(($completed / $total) * 100),
                'pending'   => (int) round(($pending   / $total) * 100),
                'follow_up' => (int) round(($follow_up / $total) * 100),
            ],
        ];
    }

    /* Legacy helpers */
    public function toggleDirectory($id): void
    {
        if (in_array($id, $this->selectedDirectoryIds, true)) {
            $this->selectedDirectoryIds = array_values(array_diff($this->selectedDirectoryIds, [$id]));
        } else {
            $this->selectedDirectoryIds[] = $id;
        }
    }
    public function clearSelectedDirectories(): void
    {
        $this->selectedDirectoryIds = [];
    }
    public function selectCurrentPage($ids): void
    {
        foreach ($ids as $id) {
            if (!in_array($id, $this->selectedDirectoryIds, true)) {
                $this->selectedDirectoryIds[] = $id;
            }
        }
    }

    /* Bulk task creation */
    public function createTasks(): void
    {
        $this->validate([
            'taskTitle'               => ['required','string','max:255'],
            'assigneeIds'             => ['required','array','min:1'],
            'assigneeIds.*'           => ['integer','exists:users,id'],
            'selectedDirectoryIds'    => ['required','array','min:1'],
            'selectedDirectoryIds.*'  => ['uuid','exists:directories,id'],
            'newTaskType'             => ['required', Rule::in(['form_fill','pickup','dropoff','other'])],
            'newTaskPriority'         => ['required', Rule::in(['low','normal','high','urgent'])],
            'newTaskFormId'           => ['nullable','uuid','exists:forms,id'],
            'newTaskElectionId'       => ['nullable','uuid','exists:elections,id'],
            'newTaskDueAt'            => ['nullable','date'],
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
            'taskTitle','taskNotes','newTaskType','newTaskPriority',
            'newTaskFormId','newTaskElectionId','newTaskDueAt',
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
        $this->authorize('agent-render');

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

        $tasksQuery = Task::with(['assignees','form.questions.options','submission.answers','directory.party','directory.subConsite','subStatus'])
            ->whereHas('users', fn($q)=>$q->where('user_id', auth()->id()))
            ->when($this->taskStatus, fn($q)=>$q->where('status',$this->taskStatus))
            ->when($this->taskType, fn($q)=>$q->where('type',$this->taskType))
            ->when($this->filterPartyId, fn($q)=>$q->whereHas('directory', fn($dq)=>$dq->where('party_id',$this->filterPartyId)))
            ->when($this->filterSubConsiteId, fn($q)=>$q->whereHas('directory', fn($dq)=>$dq->where('sub_consite_id',$this->filterSubConsiteId)))
            ->when($this->filterSubStatusId, fn($q)=>$q->where('sub_status_id',$this->filterSubStatusId)) // added
            ->when($this->taskSearch, function($q){
                $term = trim($this->taskSearch);
                $q->where(function($qq) use ($term){
                    $qq->where('title','like','%'.$term.'%')
                       ->orWhere('number','like','%'.$term.'%')
                       ->orWhereHas('directory', function($dq) use ($term){
                           $dq->where('name','like','%'.$term.'%')
                              ->orWhere('id_card_number','like','%'.$term.'%')
                              // phone search (phones stored as JSON or comma separated text)
                              ->orWhere('phones','like','%'.$term.'%')
                              ->orWhereHas('party', fn($pq)=>$pq->where('short_name','like','%'.$term.'%'))
                              ->orWhereHas('subConsite', fn($sq)=>$sq->where('code','like','%'.$term.'%'));
                       });
                });
            })
            ->latest();

        $tasksTotal = (clone $tasksQuery)->count();
        if ($this->tasksLimit > $tasksTotal) $this->tasksLimit = $tasksTotal;

        $tasks = $tasksQuery->take($this->tasksLimit)->get();

        // Do not auto-select the latest task; keep as null
        // if (!$this->selectedTaskId && $tasks->count() > 0) {
        //     $this->selectTask($tasks->first()->id);
        // }

        $selectedTask = null;
        if ($this->selectedTaskId) {
            $selectedTask = Task::with([
                'assignees','directory.party','directory.subConsite',
                'form.questions.options','submission.answers',
                'completedBy','followUpBy', // added
            ])
            ->whereHas('users', fn($q)=>$q->where('user_id', auth()->id()))
            ->find($this->selectedTaskId);

            if (!$selectedTask) {
                $this->selectedTaskId = null;
            }
        }

        $forms         = Form::orderBy('title')->get(['id','title']);
        $allAgents     = User::orderBy('name')->get(['id','name']);
        $parties       = Party::orderBy('short_name')->get(['id','short_name','name']);
        $subConsites   = SubConsite::orderBy('code')->get(['id','code']);
        $opinionTypes  = OpinionType::orderBy('name')->get(['id','name']);
        $requestTypes  = RequestType::where('active', true)->orderBy('name')->get(['id','name']);
        $countries     = Country::orderBy('name')->get(['id','name']);
        $subStatuses   = SubStatus::where('active',true)->orderBy('name')->get(['id','name']); // added
        $this->subStatuses = $subStatuses; // ensure property populated for blade

        $this->maldivesCountryId = $this->maldivesCountryId ?: ($countries->firstWhere('name','Maldives')->id ?? null);
        $currentIslands    = ($this->currentCountryId && $this->currentCountryId === $this->maldivesCountryId)
            ? Island::orderBy('name')->get(['id','name'])
            : collect();
        $currentProperties = $this->currentIslandId
            ? Property::where('island_id',$this->currentIslandId)->orderBy('name')->get(['id','name'])
            : collect();

        return view('livewire.agent.agent-management', [
            'agents'            => $agents,
            'tasks'             => $tasks,
            'tasksTotal'        => $tasksTotal,
            'selectedTask'      => $selectedTask,
            'forms'             => $forms,
            'allAgents'         => $allAgents,
            'parties'           => $parties,
            'subConsites'       => $subConsites,
            'opinionTypes'      => $opinionTypes,
            'requestTypes'      => $requestTypes,
            'countries'         => $countries,
            'currentIslands'    => $currentIslands,
            'currentProperties' => $currentProperties,
            'maldivesCountryId' => $this->maldivesCountryId,
            'subStatuses'      => $this->subStatuses, // use property
        ])->layout('layouts.master');
    }

    /* Broadcast changes to all assigned users */
    protected function broadcastTaskChange(Task $task, string $changeType, array $extra = []): void
    {
        try {
            $userIds = $task->users()->pluck('users.id')->unique();
            foreach ($userIds as $uid) {
                TaskDataChanged::dispatch($task->id, (string)$uid, $changeType, $extra);
            }
            // Global stats update (single dispatch) for ranking/summary tables
            if (in_array($changeType, ['status_updated','submission_submitted','submission_saved','engagement_changed'], true)) {
                TaskStatsUpdated::dispatch($task->id, $changeType, $userIds->map(fn($x)=>(string)$x)->toArray());
            }
        } catch (\Throwable $e) {
            // swallow; don't break UI
        }
    }

    /**
     * When public property changes in UI
     */
    public function updatedTaskStatusEdit($value): void
    {
        // Only auto update for statuses that do not require extra input
        if ($value !== 'follow_up') {
            $this->updateTaskStatus();
        }
    }

    public function saveFollowUpStatus(): void
    {
        // Explicit save when follow_up date chosen
        $this->updateTaskStatus();
    }

    public function updateTaskStatus(): void
    {
        if (!$this->selectedTaskId) return;

        $this->validate([
            'taskStatusEdit' => 'required|in:pending,follow_up,completed',
            'followUpDate' => $this->taskStatusEdit === 'follow_up' ? 'required|date|after_or_equal:today' : 'nullable',
        ]);

        $task = Task::with('users')->find($this->selectedTaskId);
        if (!$task) return;

        if ($task->status !== $this->taskStatusEdit || ($this->taskStatusEdit==='follow_up' && $this->followUpDate !== $task->follow_up_date?->format('Y-m-d\TH:i'))) {
            $original = $task->status;
            $task->status = $this->taskStatusEdit;

            if ($this->taskStatusEdit === 'completed') {
                $task->completed_at = $task->completed_at ?: now();
                $task->completed_by = auth()->id();
                $task->follow_up_by = null;
                $task->follow_up_date = null; // clear
            } elseif ($this->taskStatusEdit === 'follow_up') {
                $task->follow_up_by = auth()->id();
                $task->completed_at = null;
                $task->completed_by = null;
                $task->follow_up_date = $this->followUpDate ? \Carbon\Carbon::parse($this->followUpDate) : null;
            } else { // pending
                $task->completed_at = null;
                $task->completed_by = null;
                if ($original !== 'follow_up') { $task->follow_up_by = null; }
                $task->follow_up_date = null; // clear when returning to pending
            }

            $task->save();

            $this->broadcastTaskChange($task,'status_updated',[
                'status' => $task->status,
                'completed_at' => $task->completed_at?->toISOString(),
                'completed_by' => $task->completed_by,
                'follow_up_by' => $task->follow_up_by,
                'follow_up_date' => $task->follow_up_date?->toISOString(),
            ]);
            $this->logTaskEvent('task.status_changed', $task, [
                'status' => $task->status,
                'completed_by' => $task->completed_by,
                'follow_up_by' => $task->follow_up_by,
                'follow_up_date' => $task->follow_up_date?->toISOString(),
            ]);

            $this->dispatch('swal', icon:'success', title:'Updated', text:'Task status updated.');
        }
    }

    public function loadMoreTasks(): void
    {
        $this->tasksLimit += 12;
    }

    /* ===== Livewire 3 event listeners (from JS) ===== */

    #[On('updateUserPresence')]
    public function updateUserPresence(string $taskId, string $userId, bool $isOnline): void
    {
        \Log::info('updateUserPresence', ['taskId'=>$taskId,'userId'=>$userId,'isOnline'=>$isOnline]);

        if ((string)$this->selectedTaskId !== (string)$taskId) {
            // ignore presence for non-selected tasks
            return;
        }

        // Maintain a flat list of user objects [{id,name?,profile_picture?}, ...]
        $idx = null;
        foreach ($this->onlineUsers as $i => $u) {
            if ((string)($u['id'] ?? '') === (string)$userId) { $idx = $i; break; }
        }

        if ($isOnline) {
            if ($idx === null) {
                $this->onlineUsers[] = ['id' => $userId];
            }
        } else {
            if ($idx !== null) {
                array_splice($this->onlineUsers, $idx, 1);
            }
        }

        $this->dispatch('$refresh');
    }

    #[On('updateOnlineUsers')]
    public function updateOnlineUsers(array $users): void
    {
        \Log::info('updateOnlineUsers', ['users'=>$users, 'selectedTaskId'=>$this->selectedTaskId]);
        // $users is already a flat array for the current task
        $this->onlineUsers = $users;
    }

    #[On('userJoined')]
    public function userJoined(array $user): void
    {
        \Log::info('userJoined', ['user'=>$user,'before'=>$this->onlineUsers]);
        if (!isset($user['id'])) return;

        foreach ($this->onlineUsers as $u) {
            if ((string)($u['id'] ?? '') === (string)$user['id']) return;
        }
        $this->onlineUsers[] = $user;
    }

    #[On('userLeft')]
    public function userLeft(array $user): void
    {
        \Log::info('userLeft', ['user'=>$user,'before'=>$this->onlineUsers]);
        if (!isset($user['id'])) return;

        $this->onlineUsers = array_values(array_filter(
            $this->onlineUsers,
            fn($u) => (string)($u['id'] ?? '') !== (string)$user['id']
        ));
    }

    /* Called from PHP & JS */
#[On('user-opened-task')]
public function userOpenedTask(string $taskId): void
{
    $user = auth()->user();
    if (!$user) return;

    \Log::info('userOpenedTask', ['taskId'=>$taskId,'userId'=>$user->id]);

    if ((string)$this->selectedTaskId === (string)$taskId) {
        foreach ($this->onlineUsers as $u) {
            if ((string)($u['id'] ?? '') === (string)$user->id) {
                goto broadcast_presence;
            }
        }
        $this->onlineUsers[] = [
            'id' => (string)$user->id,
            'name' => $user->name,
            'profile_picture' => $user->profile_picture,
        ];
    }

    broadcast_presence:
    broadcast(new \App\Events\TaskUserPresenceChanged($taskId, (string)$user->id, true))->toOthers();
}

#[On('user-closed-task')]
public function userClosedTask(string $taskId): void
{
    $user = auth()->user();
    if (!$user) return;

    \Log::info('userClosedTask', ['taskId'=>$taskId,'userId'=>$user->id]);

    if ((string)$this->selectedTaskId === (string)$taskId) {
        $this->onlineUsers = array_values(array_filter(
            $this->onlineUsers,
            fn($u) => (string)($u['id'] ?? '') !== (string)$user->id
        ));
    }

    broadcast(new \App\Events\TaskUserPresenceChanged($taskId, (string)$user->id, false))->toOthers();
}


    /* External real-time update (optional, if you dispatch to this) */
    #[On('external-task-update')]
    public function handleExternalTaskUpdate($payload): void
    {
        $taskId = null;

        if (is_array($payload)) {
            $taskId = $payload['task_id'] ?? $payload['taskId'] ?? null;
        } elseif (is_string($payload)) {
            $taskId = $payload;
        }

        if (!$taskId) return;

        if ((string)$this->selectedTaskId === (string)$taskId) {
            $this->loadSubmissionState();
            $this->loadDirectoryLocationFields();
            $this->loadVoterNotes();
            $this->loadVoterOpinions();
            $this->loadVoterRequests();
        }

        $this->dispatch('$refresh');
    }

    protected function logTaskEvent(string $type, ?Task $task = null, array $data = [], ?string $tab = null, ?string $entryId = null, ?string $description = null): void
    {
        try {
            \App\Models\EventLog::create([
                'user_id' => auth()->id(),
                'event_type' => $type,
                'event_tab' => $tab ?? 'tasks',
                'event_entry_id' => $entryId,
                'description' => $description,
                'event_data' => $data,
                'ip_address' => request()->ip(),
                'task_id' => $task?->id,
            ]);
        } catch(\Throwable $e){ /* swallow logging errors */ }
    }

    public function updatedFollowUpDate($value): void
    {
        if ($this->taskStatusEdit === 'follow_up') {
            $this->updateTaskStatus();
        }
    }

    public function updateSubStatus(): void
    {
        if(!$this->selectedTaskId) return;
        $this->validate([
            'subStatusId' => 'nullable|uuid|exists:sub_statuses,id',
        ]);
        $task = Task::find($this->selectedTaskId);
        if(!$task) return;
        $old = $task->sub_status_id;
        $task->sub_status_id = $this->subStatusId ?: null;
        $task->save();
        $this->broadcastTaskChange($task,'status_updated',[ 'sub_status_id' => $task->sub_status_id ]);
        $this->logTaskEvent('task.sub_status_changed', $task, [
            'old_sub_status_id' => $old,
            'new_sub_status_id' => $task->sub_status_id,
        ], 'tasks', $task->id, 'Sub status updated');
        $this->dispatch('swal', icon:'success', title:'Updated', text:'Sub status updated.');
    }
}
