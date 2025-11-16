@section('title','Tasks')
<div class="content fs-6 d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="toolbar" id="kt_toolbar">
        <div class="container-fluid d-flex flex-stack flex-wrap flex-sm-nowrap">
            <div class="d-flex flex-column align-items-start justify-content-center flex-wrap me-2 mb-4 mb-sm-0">
                <h1 class="text-dark fw-bold my-1 fs-2">Tasks</h1>
                <ul class="breadcrumb fw-semibold fs-base my-1">
                    <li class="breadcrumb-item text-muted"><a href="#" class="text-muted text-hover-primary">Operations</a></li>
                    <li class="breadcrumb-item text-dark">Tasks</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="post fs-6 d-flex flex-column-fluid" id="kt_post">
        <div class="container-fluid">
            <!-- Flash message for bulk operations -->
            @if(session('bulk_message'))
                <div class="alert alert-success d-flex align-items-center p-3 mb-4">
                    <i class="ki-duotone ki-check-circle fs-2 me-2"></i>
                    <span>{{ session('bulk_message') }}</span>
                </div>
            @endif
            <div class="row g-3 mb-4">
                @php $s = $this->stats; @endphp
                <div class="col-md-4">
                    <div class="card shadow-sm h-100 border border-success-subtle">
                        <div class="card-body py-4 d-flex flex-column justify-content-center">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fs-7 text-muted">Completed</span>
                                <span class="badge badge-light-success" title="{{ $s['completed'] }} of {{ $s['total'] }}">{{ $s['percentages']['completed'] }}%</span>
                            </div>
                            <div class="fw-bold fs-2 text-success lh-1">{{ $s['completed'] }}</div>
                            <div class="text-muted fs-8">of {{ $s['total'] }} total</div>
                            <div class="progress h-6px bg-light-success mt-3">
                                <div class="progress-bar bg-success" style="width: {{ $s['percentages']['completed'] }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm h-100 border border-warning-subtle">
                        <div class="card-body py-4 d-flex flex-column justify-content-center">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fs-7 text-muted">Pending</span>
                                <span class="badge badge-light-warning" title="{{ $s['pending'] }} of {{ $s['total'] }}">{{ $s['percentages']['pending'] }}%</span>
                            </div>
                            <div class="fw-bold fs-2 text-warning lh-1">{{ $s['pending'] }}</div>
                            <div class="text-muted fs-8">of {{ $s['total'] }} total</div>
                            <div class="progress h-6px bg-light-warning mt-3">
                                <div class="progress-bar bg-warning" style="width: {{ $s['percentages']['pending'] }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm h-100 border border-primary-subtle">
                        <div class="card-body py-4 d-flex flex-column justify-content-center">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fs-7 text-muted">Follow Up</span>
                                <span class="badge badge-light-primary" title="{{ $s['follow_up'] }} of {{ $s['total'] }}">{{ $s['percentages']['follow_up'] }}%</span>
                            </div>
                            <div class="fw-bold fs-2 text-primary lh-1">{{ $s['follow_up'] }}</div>
                            <div class="text-muted fs-8">of {{ $s['total'] }} total</div>
                            <div class="progress h-6px bg-light-primary mt-3">
                                <div class="progress-bar bg-primary" style="width: {{ $s['percentages']['follow_up'] }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card craft-card">
                <div class="card-header pt-6 pb-0 border-0 craft-card__header">
                    <div class="d-flex flex-wrap gap-3 w-100 align-items-center">
                        <h3 class="card-title fw-bold mb-0">All Tasks</h3>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <select class="form-select form-select-sm form-select-solid" wire:model="bulkAssignUserId">
                                <option value="">User...</option>
                                @foreach($assignees as $a)
                                    <option value="{{ $a->id }}">{{ $a->name }}</option>
                                @endforeach
                            </select>
                            @if($bulkAssignUserId && $this->selectedCount > 0)
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-light-primary" wire:click="assignUserToSelected" wire:loading.attr="disabled">Assign Selected ({{ $this->selectedCount }})</button>
                                    <button type="button" class="btn btn-light-danger" wire:click="unassignUserFromSelected" wire:loading.attr="disabled">Unassign Selected</button>
                                </div>
                            @endif
                        </div>
                        <div class="ms-auto d-flex align-items-center gap-2">
                            <!-- Filter dropdown -->
                            <div class="dropdown craft-filters" x-data="{open:false}" @click.outside="open=false">
                                <button class="btn btn-sm btn-primary d-flex align-items-center gap-2" @click="open=!open" type="button">
                                    <i class="ki-duotone ki-filter fs-2"></i><span>Filter</span>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end p-0 shadow w-375px show craft-filters__menu" x-show="open" x-transition.origin.top.right style="display:none;">
                                    <div class="border rounded-3 overflow-hidden">
                                        <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center bg-light">
                                            <span class="fw-semibold">Filter Options</span>
                                            <button type="button" class="btn btn-sm btn-icon" @click="open=false"><i class="ki-duotone ki-cross fs-2"></i></button>
                                        </div>
                                        <div class="px-4 py-4 d-flex flex-column gap-5">
                                            <div class="d-flex flex-column gap-2">
                                                <label class="form-label fw-semibold mb-1">Search</label>
                                                <input type="text" class="form-control form-control-sm form-control-solid" placeholder="Title / Number / Directory" wire:model.defer="searchDraft">
                                            </div>
                                            <div class="d-flex flex-column gap-2">
                                                <label class="form-label fw-semibold mb-1">Status</label>
                                                <select class="form-select form-select-sm form-select-solid" wire:model.defer="statusDraft">
                                                    <option value="">All Statuses</option>
                                                    <option value="pending">Pending</option>
                                                    <option value="follow_up">Follow Up</option>
                                                    <option value="completed">Completed</option>
                                                </select>
                                            </div>
                                            <div class="d-flex flex-column gap-2">
                                                <label class="form-label fw-semibold mb-1">Type</label>
                                                <select class="form-select form-select-sm form-select-solid" wire:model.defer="typeDraft">
                                                    <option value="">All Types</option>
                                                    <option value="other">Other</option>
                                                    <option value="form_fill">Form Fill</option>
                                                    <option value="pickup">Pickup</option>
                                                    <option value="dropoff">Dropoff</option>
                                                </select>
                                            </div>
                                            <div class="d-flex flex-column gap-2">
                                                <label class="form-label fw-semibold mb-1">Priority</label>
                                                <select class="form-select form-select-sm form-select-solid" wire:model.defer="priorityDraft">
                                                    <option value="">All Priorities</option>
                                                    <option value="low">Low</option>
                                                    <option value="normal">Normal</option>
                                                    <option value="high">High</option>
                                                    <option value="urgent">Urgent</option>
                                                </select>
                                            </div>
                                            <div class="d-flex flex-column gap-2">
                                                <label class="form-label fw-semibold mb-1">Assignee</label>
                                                <select class="form-select form-select-sm form-select-solid" wire:model.defer="filterAssigneeIdDraft">
                                                    <option value="">All Assignees</option>
                                                    @foreach($assignees as $a)
                                                        <option value="{{ $a->id }}">{{ $a->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="d-flex flex-column gap-2">
                                                <label class="form-label fw-semibold mb-1">Party</label>
                                                <select class="form-select form-select-sm form-select-solid" wire:model.defer="filterPartyIdDraft">
                                                    <option value="">All Parties</option>
                                                    @foreach($parties as $p)
                                                        <option value="{{ $p->id }}">{{ $p->short_name ?? $p->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="d-flex flex-column gap-2">
                                                <label class="form-label fw-semibold mb-1">SubConsite</label>
                                                <select class="form-select form-select-sm form-select-solid" wire:model.defer="filterSubConsiteIdDraft">
                                                    <option value="">All SubConsites</option>
                                                    @foreach($subConsites as $sc)
                                                        <option value="{{ $sc->id }}">{{ $sc->code }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="d-flex flex-column gap-2">
                                                <label class="form-label fw-semibold mb-1">Sub Status</label>
                                                <select class="form-select form-select-sm form-select-solid" wire:model.defer="filterSubStatusIdDraft">
                                                    <option value="">All Sub Statuses</option>
                                                    @foreach($subStatuses as $ss)
                                                        <option value="{{ $ss->id }}">{{ $ss->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="px-4 py-3 border-top d-flex justify-content-between gap-3 bg-light-subtle">
                                            <button type="button" class="btn btn-light btn-sm" wire:click="resetFilters">Reset</button>
                                            <button type="button" class="btn btn-primary btn-sm" @click="open=false" wire:click="applyFilters">Apply</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <a href="{{ route('tasks.assign') }}" class="btn btn-sm btn-light-success">Create</a>
                            <select class="form-select form-select-sm form-select-solid w-auto" wire:model.live="perPage">
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-4 craft-card__body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 craft-table">
                            <thead>
                                <tr class="text-gray-600 fw-semibold">
                                    <th style="width:30px;">
                                        <button type="button" class="btn btn-xs btn-light" wire:click="toggleSelectPage" title="Toggle select page">
                                            <i class="ki-duotone ki-check-square fs-4"></i>
                                        </button>
                                    </th>
                                    <th>Number</th>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Type</th>
                                    <th>Priority</th>
                                    <th>Directory</th>
                                    <th>Party</th>
                                    <th>SubConsite</th>
                                    <th>Sub Status</th>
                                    <th>Assignees</th>
                                    <th>Created</th>
                                    <th>Due</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tasks as $task)
                                    @php
                                        $statusClass = match($task->status){
                                            'completed' => 'badge-light-success',
                                            'follow_up' => 'badge-light-primary',
                                            default => 'badge-light-warning'
                                        };
                                        $priorityClass = $task->priorityBadge();
                                    @endphp
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input" value="{{ $task->id }}" wire:model="selectedTasks">
                                        </td>
                                        <td class="fw-semibold">{{ $task->number }}</td>
                                        <td class="fw-semibold text-gray-800">{{ Str::limit($task->title,40) }}</td>
                                        <td><span class="badge {{ $statusClass }}">{{ ucfirst(str_replace('_',' ',$task->status)) }}</span></td>
                                        <td>{{ ucfirst(str_replace('_',' ',$task->type)) }}</td>
                                        <td><span class="badge {{ $priorityClass }}">{{ ucfirst($task->priority) }}</span></td>
                                        <td>{{ $task->directory?->name }}</td>
                                        <td>{{ $task->directory?->party?->short_name }}</td>
                                        <td>{{ $task->directory?->subConsite?->code }}</td>
                                        <td>{{ $task->subStatus?->name }}</td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-2">
                                                @foreach($task->assignees as $u)
                                                    <div class="d-inline-flex align-items-center bg-light rounded px-2 py-1 gap-2" style="line-height:1;">
                                                        <div class="symbol symbol-25px">
                                                            <img src="{{ $u->profile_picture ? asset('storage/'.$u->profile_picture) : asset('assets/media/avatars/blank.png') }}" alt="{{ $u->name }}" class="rounded-circle" style="width:25px;height:25px;object-fit:cover;">
                                                        </div>
                                                        <span class="fw-semibold text-gray-700" style="font-size: .75rem;">{{ Str::limit($u->name,18) }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-muted" title="{{ $task->created_at }}">{{ $task->created_at->diffForHumans() }}</span>
                                        </td>
                                        <td>
                                            @if($task->due_at)
                                                <span class="@if($task->isOverdue()) text-danger fw-bold @else text-muted @endif" title="{{ $task->due_at }}">{{ $task->due_at->diffForHumans() }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('tasks.edit',$task->id) }}" class="btn btn-sm btn-light-primary">Edit</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="14" class="text-center text-muted py-10">No tasks found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $tasks->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
