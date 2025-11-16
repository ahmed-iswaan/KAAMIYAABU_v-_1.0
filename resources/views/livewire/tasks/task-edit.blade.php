@section('title','Edit Task')
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>window.addEventListener('swal', e => { const d=e.detail||{}; Swal.fire({icon:d.type||'success',title:d.title||'',text:d.text||'',confirmButtonColor:'#0d6efd'}); });</script>
@endpush
<div class="content fs-6 d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="toolbar" id="kt_toolbar">
        <div class="container-fluid d-flex flex-stack flex-wrap flex-sm-nowrap">
            <div class="d-flex flex-column align-items-start justify-content-center flex-wrap me-2 mb-4 mb-sm-0">
                <h1 class="text-dark fw-bold my-1 fs-2">Edit Task</h1>
                <ul class="breadcrumb fw-semibold fs-base my-1">
                    <li class="breadcrumb-item text-muted"><a href="{{ route('tasks.index') }}" class="text-muted text-hover-primary">Tasks</a></li>
                    <li class="breadcrumb-item text-dark">Edit</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="post fs-6 d-flex flex-column-fluid" id="kt_post">
        <div class="container-xxl">
            <div class="row g-6">
                <div class="col-xl-8">
                    <div class="card">
                        <div class="card-header border-0 pt-6 pb-0">
                            <h3 class="card-title fw-bold mb-0">Task Details</h3>
                            <div class="ms-auto">
                                <a href="{{ route('tasks.index') }}" class="btn btn-light btn-sm">Back</a>
                            </div>
                        </div>
                        <div class="card-body pt-5">
                            <form wire:submit.prevent="updateTask" class="d-flex flex-column gap-6">
                                <div class="row g-6">
                                    <div class="col-md-8">
                                        <label class="form-label required">Title</label>
                                        <input type="text" class="form-control form-control-solid" wire:model.defer="title" placeholder="Task title">
                                        @error('title')<div class="text-danger fs-8 mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Number</label>
                                        <input type="text" class="form-control form-control-solid" value="{{ $task->number }}" disabled>
                                    </div>
                                </div>
                                <div class="row g-6">
                                    <div class="col-md-4">
                                        <label class="form-label">Status</label>
                                        <select class="form-select form-select-solid" wire:model="status">
                                            <option value="pending">Pending</option>
                                            <option value="follow_up">Follow Up</option>
                                            <option value="completed">Completed</option>
                                        </select>
                                        @error('status')<div class="text-danger fs-8 mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Follow Up Date</label>
                                        <input type="datetime-local" class="form-control form-control-solid" wire:model.defer="followUpDate" @disabled($status!=='follow_up')>
                                        @error('followUpDate')<div class="text-danger fs-8 mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Priority</label>
                                        <select class="form-select form-select-solid" wire:model="priority">
                                            <option value="low">Low</option>
                                            <option value="normal">Normal</option>
                                            <option value="high">High</option>
                                            <option value="urgent">Urgent</option>
                                        </select>
                                        @error('priority')<div class="text-danger fs-8 mt-1">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="row g-6">
                                    <div class="col-md-4">
                                        <label class="form-label">Type</label>
                                        <select class="form-select form-select-solid" wire:model="type">
                                            <option value="other">Other</option>
                                            <option value="form_fill">Form Fill</option>
                                            <option value="pickup">Pickup</option>
                                            <option value="dropoff">Dropoff</option>
                                        </select>
                                        @error('type')<div class="text-danger fs-8 mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Form</label>
                                        <select class="form-select form-select-solid" wire:model="formId">
                                            <option value="">— None —</option>
                                            @foreach($forms as $f)
                                                <option value="{{ $f->id }}">{{ $f->title }}</option>
                                            @endforeach
                                        </select>
                                        @error('formId')<div class="text-danger fs-8 mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Sub Status</label>
                                        <select class="form-select form-select-solid" wire:model="subStatusId">
                                            <option value="">— None —</option>
                                            @foreach($subStatuses as $ss)
                                                <option value="{{ $ss->id }}">{{ $ss->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('subStatusId')<div class="text-danger fs-8 mt-1">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="row g-6">
                                    <div class="col-md-6">
                                        <label class="form-label">Due At</label>
                                        <input type="datetime-local" class="form-control form-control-solid" wire:model.defer="dueAt">
                                        @error('dueAt')<div class="text-danger fs-8 mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label required">Assignees</label>
                                        <select class="form-select form-select-solid" multiple size="8" wire:model="assigneeIds">
                                            @foreach($users as $u)
                                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('assigneeIds')<div class="text-danger fs-8 mt-1">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label">Notes</label>
                                    <textarea rows="5" class="form-control form-control-solid" wire:model.defer="notes" placeholder="Task notes..."></textarea>
                                    @error('notes')<div class="text-danger fs-8 mt-1">{{ $message }}</div>@enderror
                                </div>
                                <div class="d-flex justify-content-between flex-wrap gap-3">
                                    <div class="fs-8 text-muted">Created {{ $task->created_at->diffForHumans() }} @if($task->completed_at) • Completed {{ $task->completed_at->diffForHumans() }} @endif</div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                        <a href="{{ route('tasks.index') }}" class="btn btn-light">Cancel</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header border-0 pt-6 pb-0">
                            <h3 class="card-title fw-bold mb-0">Directory</h3>
                        </div>
                        <div class="card-body pt-5">
                            @if($task->directory)
                                <div class="d-flex align-items-start gap-4">
                                    <div class="symbol symbol-60px flex-shrink-0">
                                        @if($task->directory->profile_picture)
                                            <img src="{{ asset('storage/'.$task->directory->profile_picture) }}" alt="" style="object-fit:cover;" />
                                        @else
                                            <div class="symbol-label bg-light-primary text-primary fw-bold">{{ Str::upper(Str::substr($task->directory->name,0,1)) }}</div>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex flex-column">
                                            <span class="fw-semibold fs-5 text-gray-800">{{ $task->directory->name }}</span>
                                            <span class="text-muted fs-8">{{ $task->directory->id_card_number }}</span>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                            @if($task->directory->party)
                                                <span class="badge badge-light-info">{{ $task->directory->party->short_name ?? $task->directory->party->name }}</span>
                                            @endif
                                            @if($task->directory->subConsite)
                                                <span class="badge badge-light-warning">{{ $task->directory->subConsite->code }}</span>
                                            @endif
                                        </div>
                                        <div class="mt-3 fs-8 text-gray-600">
                                            <div>Permanent: {{ $task->directory->permanentLocationString() }}</div>
                                            <div>Current: {{ $task->directory->currentLocationString() }}</div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="text-muted fs-8">No directory linked.</div>
                            @endif
                        </div>
                    </div>
                    <div class="card mt-6">
                        <div class="card-header border-0 pt-6 pb-0">
                            <h3 class="card-title fw-bold mb-0">Meta</h3>
                        </div>
                        <div class="card-body pt-5">
                            <div class="d-flex flex-column gap-2 fs-8 text-muted">
                                <div><span class="fw-semibold text-gray-700">Task ID:</span> {{ $task->id }}</div>
                                <div><span class="fw-semibold text-gray-700">Created By:</span> {{ $task->creator?->name }}</div>
                                @if($task->completed_by)
                                    <div><span class="fw-semibold text-gray-700">Completed By:</span> {{ $task->completedBy?->name }}</div>
                                @endif
                                @if($task->follow_up_by)
                                    <div><span class="fw-semibold text-gray-700">Follow Up By:</span> {{ $task->followUpBy?->name }}</div>
                                @endif
                                @if($task->follow_up_date)
                                    <div><span class="fw-semibold text-gray-700">Follow Up Date:</span> {{ $task->follow_up_date }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
