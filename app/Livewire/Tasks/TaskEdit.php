<?php

namespace App\Livewire\Tasks;

use Livewire\Component;
use App\Models\{Task, User, Form, SubStatus, EventLog};
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;

class TaskEdit extends Component
{
    use AuthorizesRequests;

    public Task $task;

    public $title='';
    public $notes='';
    public $status='pending';
    public $type='other';
    public $priority='normal';
    public $dueAt=null; // datetime-local string
    public $formId=null;
    public $assigneeIds=[]; // user ids
    public $subStatusId='';
    public $followUpDate=null; // datetime-local

    public function mount(Task $task)
    {
        $this->authorize('task-edit-render');
        $this->task = $task->load(['assignees','form','subStatus']);
        $this->title = $task->title;
        $this->notes = $task->notes;
        $this->status = $task->status;
        $this->type = $task->type;
        $this->priority = $task->priority;
        $this->dueAt = $task->due_at?->format('Y-m-d\TH:i');
        $this->formId = $task->form_id;
        $this->assigneeIds = $task->assignees()->pluck('users.id')->all();
        $this->subStatusId = $task->sub_status_id ?? '';
        $this->followUpDate = $task->follow_up_date?->format('Y-m-d\TH:i');
    }

    public function updateTask()
    {
        $this->authorize('task-edit-update');

        $this->validate([
            'title' => ['required','string','max:255'],
            'notes' => ['nullable','string','max:5000'],
            'status' => ['required', Rule::in(['pending','follow_up','completed'])],
            'type' => ['required', Rule::in(['form_fill','pickup','dropoff','other'])],
            'priority' => ['required', Rule::in(['low','normal','high','urgent'])],
            'dueAt' => ['nullable','date'],
            'formId' => ['nullable','uuid','exists:forms,id'],
            'assigneeIds' => ['required','array','min:1'],
            'assigneeIds.*' => ['integer','exists:users,id'],
            'subStatusId' => ['nullable','uuid','exists:sub_statuses,id'],
            'followUpDate' => [$this->status==='follow_up' ? 'required' : 'nullable','date'],
        ]);

        DB::transaction(function(){
            $this->task->title = $this->title;
            $this->task->notes = $this->notes;
            $this->task->status = $this->status;
            $this->task->type = $this->type;
            $this->task->priority = $this->priority;
            $this->task->form_id = $this->formId ?: null;
            $this->task->sub_status_id = $this->subStatusId ?: null;
            $this->task->due_at = $this->dueAt ? \Carbon\Carbon::parse($this->dueAt) : null;
            if($this->status==='completed'){
                $this->task->completed_at = $this->task->completed_at ?: now();
                $this->task->completed_by = auth()->id();
                $this->task->follow_up_by = null;
                $this->task->follow_up_date = null;
            } elseif($this->status==='follow_up') {
                $this->task->follow_up_by = auth()->id();
                $this->task->follow_up_date = $this->followUpDate ? \Carbon\Carbon::parse($this->followUpDate) : null;
                $this->task->completed_at = null;
                $this->task->completed_by = null;
            } else { // pending
                $this->task->completed_at = null;
                $this->task->completed_by = null;
                $this->task->follow_up_by = null;
                $this->task->follow_up_date = null;
            }
            $this->task->save();
            $this->task->assignees()->sync($this->assigneeIds);
            EventLog::create([
                'user_id' => auth()->id(),
                'event_type' => 'task_update',
                'event_tab' => 'tasks',
                'event_entry_id' => $this->task->id,
                'task_id' => $this->task->id,
                'description' => 'Task updated',
                'event_data' => [
                    'title'=>$this->title,
                    'status'=>$this->status,
                    'type'=>$this->type,
                    'priority'=>$this->priority,
                    'sub_status_id'=>$this->subStatusId ?: null,
                    'due_at'=>$this->dueAt,
                    'follow_up_date'=>$this->followUpDate,
                    'assignees'=>$this->assigneeIds,
                ],
                'ip_address' => request()->ip(),
            ]);
        });

        $this->dispatch('swal', type:'success', title:'Updated', text:'Task updated successfully');
    }

    public function render()
    {
        $forms = Form::orderBy('title')->get(['id','title']);
        $users = User::orderBy('name')->get(['id','name']);
        $subStatuses = SubStatus::where('active',true)->orderBy('name')->get(['id','name']);

        return view('livewire.tasks.task-edit',[
            'forms'=>$forms,
            'users'=>$users,
            'subStatuses'=>$subStatuses,
        ])->layout('layouts.master');
    }
}
