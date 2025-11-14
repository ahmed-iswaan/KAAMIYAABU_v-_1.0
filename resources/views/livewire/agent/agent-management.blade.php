<div>
@section('title', $pageTitle)
{{-- Agents Management: Final layout (Task List | Directory Details + Forms/Notes/Task Detail) --}}
<div class="content fs-6 d-flex flex-column flex-column-fluid" id="kt_content">
    {{-- Toolbar --}}
    <div class="toolbar" id="kt_toolbar">
        <div class="container-fluid d-flex flex-stack flex-wrap flex-sm-nowrap">
            <div class="d-flex flex-column align-items-start justify-content-center flex-wrap me-2">
                <h1 class="text-dark fw-bold my-1 fs-2">{{$pageTitle}}</h1>
                <ul class="breadcrumb fw-semibold fs-base my-1">
                    <li class="breadcrumb-item text-muted"><a href="#" class="text-muted text-hover-primary">Operations</a></li>
                    <li class="breadcrumb-item text-dark">Agents</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="post fs-6 d-flex flex-column-fluid" id="kt_post">
        <div class="container-fluid">
            {{-- Status Summary Row --}}
            @php
                // FIX: previously used array_sum($this->stats) which added the 'total' value again producing wrong denominator
                $stats = $this->stats;
                $total = $stats['total'] ?: 1; // avoid division by zero
                $percentCompleted = $stats['percentages']['completed'] ?? 0;
                $percentPending   = $stats['percentages']['pending'] ?? 0;
                $percentFollowUp  = $stats['percentages']['follow_up'] ?? 0;
            @endphp
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="card h-100 hover-elevate-up shadow-sm border border-success-subtle">
                        <div class="card-body p-4 d-flex flex-column justify-content-center">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fs-7 text-muted">Completed</span>
                                <span class="badge badge-light-success" data-bs-toggle="tooltip" title="{{ $stats['completed'] }} of {{ $stats['total'] }} tasks">{{ $percentCompleted }}%</span>
                            </div>
                            <div class="fw-bold fs-2 text-success lh-1">{{ $stats['completed'] }}</div>
                            <div class="text-muted fs-8">of {{ $stats['total'] }} total</div>
                            <div class="progress h-6px bg-light-success mt-3">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percentCompleted }}%" aria-valuenow="{{ $percentCompleted }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 hover-elevate-up shadow-sm border border-warning-subtle">
                        <div class="card-body p-4 d-flex flex-column justify-content-center">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fs-7 text-muted">Pending</span>
                                <span class="badge badge-light-warning" data-bs-toggle="tooltip" title="{{ $stats['pending'] }} of {{ $stats['total'] }} tasks">{{ $percentPending }}%</span>
                            </div>
                            <div class="fw-bold fs-2 text-warning lh-1">{{ $stats['pending'] }}</div>
                            <div class="text-muted fs-8">of {{ $stats['total'] }} total</div>
                            <div class="progress h-6px bg-light-warning mt-3">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $percentPending }}%" aria-valuenow="{{ $percentPending }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 hover-elevate-up shadow-sm border border-primary-subtle">
                        <div class="card-body p-4 d-flex flex-column justify-content-center">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fs-7 text-muted">Follow Up</span>
                                <span class="badge badge-light-primary" data-bs-toggle="tooltip" title="{{ $stats['follow_up'] }} of {{ $stats['total'] }} tasks">{{ $percentFollowUp }}%</span>
                            </div>
                            <div class="fw-bold fs-2 text-primary lh-1">{{ $stats['follow_up'] }}</div>
                            <div class="text-muted fs-8">of {{ $stats['total'] }} total</div>
                            <div class="progress h-6px bg-light-primary mt-3">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $percentFollowUp }}%" aria-valuenow="{{ $percentFollowUp }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Main Layout Row: Left Task List | Right (Directory Details + Form & Engagement) --}}
            <div class="row g-3 mb-3">
                {{-- Left: Task List --}}
                <div class="col-xl-3 col-lg-4">
                    <div class="card h-100" style="max-height: calc(100vh - 200px);">
                        <div class="card-header border-0 pt-5 pb-2 d-flex flex-wrap gap-2 align-items-center">
                            <h3 class="card-title fw-bold mb-0">My Tasks</h3>
                            <div class="ms-auto d-flex gap-2 align-items-center">
                                <div class="dropdown" x-data="{open:false}" @click.outside="open=false">
                                    <button class="btn btn-sm btn-primary d-flex align-items-center gap-2" @click="open=!open" type="button">
                                        <i class="ki-duotone ki-filter fs-2"></i><span>Filter</span>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end p-0 shadow w-350px show" x-show="open" x-transition.origin.top.right style="display:none;">
                                        <div class="border rounded-3 overflow-hidden">
                                            <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center">
                                                <span class="fw-semibold">Filter Options</span>
                                                <button type="button" class="btn btn-sm btn-icon" @click="open=false"><i class="ki-duotone ki-cross fs-2"></i></button>
                                            </div>
                                            <div class="px-4 py-4 d-flex flex-column gap-5">
                                                <div class="d-flex flex-column gap-2">
                                                    <label class="form-label fw-semibold mb-1">Search</label>
                                                    <input type="text" class="form-control form-control-sm form-control-solid" placeholder="Title / Number / Directory" wire:model.debounce.500ms="taskSearch">
                                                </div>
                                                <div class="d-flex flex-column gap-2">
                                                    <label class="form-label fw-semibold mb-1">Status</label>
                                                    <select class="form-select form-select-sm form-select-solid" wire:model="taskStatus">
                                                        <option value="">All</option>
                                                        <option value="pending">Pending</option>
                                                        <option value="follow_up">Follow Up</option>
                                                        <option value="completed">Completed</option>
                                                    </select>
                                                </div>
                                                <div class="d-flex flex-column gap-2">
                                                    <label class="form-label fw-semibold mb-1">Type</label>
                                                    <select class="form-select form-select-sm form-select-solid" wire:model="taskType">
                                                        <option value="">All</option>
                                                        <option value="other">Other</option>
                                                        <option value="form_fill">Fill Form</option>
                                                        <option value="pickup">Pickup</option>
                                                        <option value="dropoff">Dropoff</option>
                                                    </select>
                                                </div>
                                                <div class="d-flex flex-column gap-2">
                                                    <label class="form-label fw-semibold mb-1">Party</label>
                                                    <select class="form-select form-select-sm form-select-solid" wire:model="filterPartyId">
                                                        <option value="">All Parties</option>
                                                        @foreach($parties as $p)
                                                            <option value="{{ $p->id }}">{{ $p->short_name ?? $p->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="d-flex flex-column gap-2">
                                                    <label class="form-label fw-semibold mb-1">SubConsite</label>
                                                    <select class="form-select form-select-sm form-select-solid" wire:model="filterSubConsiteId">
                                                        <option value="">All</option>
                                                        @foreach($subConsites as $sc)
                                                            <option value="{{ $sc->id }}">{{ $sc->code }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="px-4 py-3 border-top d-flex justify-content-between gap-3">
                                                <button type="button" class="btn btn-light btn-sm" wire:click="resetTaskFilters">Reset</button>
                                                <button type="button" class="btn btn-primary btn-sm" @click="open=false" wire:click="applyTaskFilters">Apply</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body pt-2 pb-4">
                            <div class="scroll-y me-n5 pe-5" style="max-height: calc(100vh - 290px);">
                                {{-- NEW Responsive Task List Styles --}}
                                @pushonce('styles')
                                <style>
                                    .task-list{display:flex;flex-direction:column;gap:.85rem;}
                                    .task-item{display:flex;gap:14px;padding:14px 16px;border:1px solid #e5e9ef;border-radius:16px;background:#fff;cursor:pointer;transition:.18s ease;position:relative;}
                                    .task-item:hover{border-color:#d2dae4;box-shadow:0 2px 4px rgba(20,32,50,.06);}
                                    .task-item.active{border-color:#3b82f6;background:#f0f6ff;box-shadow:0 3px 6px -2px rgba(30,64,175,.25);}    
                                    .task-item .avatar{width:52px;height:52px;border-radius:50%;background:#0d6efd;display:flex;align-items:center;justify-content:center;font-weight:600;color:#fff;font-size:18px;flex-shrink:0;overflow:hidden;}
                                    .task-item .avatar img{width:100%;height:100%;object-fit:cover;display:block;}
                                    .task-meta-top{display:flex;align-items:center;gap=8px;flex-wrap:wrap;}
                                    .task-number{font-size:12px;letter-spacing:.5px;}
                                    .task-due{margin-left:auto;font-size:9px;line-height:1.1;text-align:right;min-width:70px;white-space:nowrap;color:#64748b;}
                                    .task-badges{display:flex;flex-wrap:wrap;gap:6px;margin:.25rem 0 .35rem;}
                                    .task-title{font-weight:100;font-size:10px;color:#374151;line-height:1.25;}
                                    .task-item.active .task-title{color:#1d4ed8;}
                                    @media (max-width: 520px){
                                        .task-item{padding:12px 14px;}
                                        .task-due{flex:1 1 100%;margin-left:0;text-align:left;margin-top:2px;}
                                        .task-meta-top{width:100%;}
                                    }
                                </style>
                                @endpushonce
                                <div class="task-list pb-5">
                                    @forelse($tasks as $task)
                                        @php
                                            $directory = $task->directory;
                                            $avatar = $directory?->profile_picture;
                                            $initial = $directory? Str::upper(Str::substr($directory->name,0,1)) : 'T';
                                            $partyShort = $directory?->party?->short_name;
                                            $subCode = $directory?->subConsite?->code;
                                            $statusBadgeClass = match($task->status){
                                                'completed' => 'badge-light-success',
                                                'follow_up' => 'badge-light-primary',
                                                default => 'badge-light-warning'
                                            };
                                            $tooltip = $directory ? trim($directory->name . ($directory->id_card_number ? ' - '.$directory->id_card_number : '')) : 'Task';

                                            // Carbon-based human friendly due label
                                            $dueLabel = null; $dueClass='';
                                            if($task->due_at){
                                                $dueAt = $task->due_at; $now = \Carbon\Carbon::now();
                                                if($dueAt->isPast()){
                                                    $agoShort = $dueAt->diffForHumans($now, ['parts'=>2,'short'=>true]);
                                                    $agoShort = str_replace(' ago','',$agoShort);
                                                    $dueLabel = 'Overdue '.$agoShort; $dueClass='text-danger fw-semibold';
                                                } else {
                                                    if($dueAt->isToday()){
                                                        // Use future date as subject so we get 'in 45m' (not '45m before')
                                                        $futureShort = $dueAt->diffForHumans(['parts'=>2,'short'=>true]); // e.g. in 45m / in 2h
                                                        $dueLabel = ucfirst(str_replace('in ','',$futureShort));
                                                        if($dueLabel === '0s') $dueLabel='Now';
                                                    } elseif($dueAt->isTomorrow()) {
                                                        $dueLabel = 'Tomorrow '.$dueAt->format('H:i');
                                                    } else {
                                                        // For > tomorrow just use diff (in 3d) up to 7 days else date
                                                        if($now->diffInDays($dueAt) <= 7){
                                                            $futureShort = $dueAt->diffForHumans(['parts'=>2,'short'=>true]); // in 3d
                                                            $dueLabel = ucfirst(str_replace('in ','',$futureShort));
                                                        } else {
                                                            $dueLabel = $dueAt->format('M d');
                                                        }
                                                    }
                                                }
                                            }
                                        @endphp
                                        <div class="task-item @if($selectedTask && $selectedTask->id === $task->id) active @endif" wire:click="selectTask('{{ $task->id }}')" data-task-id="{{ $task->id }}">
                                            <div class="avatar" data-bs-toggle="tooltip" title="{{ $tooltip }}">
                                                @if($avatar)
                                                    <img src="{{ asset('storage/' . $avatar) }}" alt="{{ $directory?->name }}">
                                                @else
                                                    {{ $initial }}
                                                @endif
                                            </div>
                                            <div class="flex-grow-1 min-w-0 d-flex flex-column">
                                                <div class="task-meta-top">
                                                    @if($directory)
                                                        <span class="fw-bold text-gray-700" style="font-size:12px; max-width:160px;" title="{{ $directory->name }}">{{ Str::limit($directory->name,22) }}</span>
                                                    @endif
                                                    <span class="task-due @if($dueClass) {{ $dueClass }} @endif">
                                                        @if($dueLabel)
                                                            <span class="fw-semibold">{{ $dueLabel }}</span>
                                                        @endif
                                                    </span>
                                                </div>
                                                <div class="task-badges">
                                                    @if($partyShort)
                                                        <span class="badge badge-light-info fw-semibold px-2 py-1 fs-8">{{ $partyShort }}</span>
                                                    @endif
                                                    @if($subCode)
                                                        <span class="badge badge-light-info fw-semibold px-2 py-1 fs-8">{{ $subCode }}</span>
                                                    @endif
                                                    @php $firstPhone = null; if($directory){ $phonesRaw = is_array($directory->phones)? $directory->phones : ( $directory->phones ? json_decode($directory->phones,true) : [] ); if(is_array($phonesRaw) && count($phonesRaw)) { $firstPhone = $phonesRaw[0]; } } @endphp
                                                    @if($firstPhone)
                                                        <span class="badge badge-light-dark fw-semibold px-2 py-1 fs-8">{{ $firstPhone }}</span>
                                                    @endif
                                                    <span class="badge {{ $statusBadgeClass }} fw-semibold px-2 py-1 fs-8">{{ ucfirst(str_replace('_',' ',$task->status)) }}</span>
                                                    <span class="badge {{ $task->priorityBadge() }} fw-semibold px-2 py-1 fs-8">{{ ucfirst($task->priority) }}</span>
                                                </div>
                                                <span class="task-number fw-semibold text-gray-500" style="font-size:9px;">{{ $task->number }}</span>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-muted fs-8 text-center py-10">No tasks found.</div>
                                    @endforelse
                                    @if($tasks->count() < $tasksTotal)
                                        <div class="pt-2">
                                            <button type="button" class="btn btn-sm btn-light-primary w-100" wire:click="loadMoreTasks" wire:loading.attr="disabled">
                                                <span wire:loading.remove wire:target="loadMoreTasks">Load More ({{ $tasks->count() }} / {{ $tasksTotal }})</span>
                                                <span wire:loading wire:target="loadMoreTasks"><span class="spinner-border spinner-border-sm me-2"></span>Loading...</span>
                                            </button>
                                        </div>
                                    @else
                                        <div class="text-center text-muted fs-8 pt-2">All tasks loaded ({{ $tasksTotal }})</div>
                                    @endif
                                </div>
                            </div>
                            {{-- removed external load more section --}}
                        </div>
                    </div>
                </div>

                {{-- Right: Three-Tier Layout --}}
                <div class="col-xl-9 col-lg-8 d-flex flex-column gap-3">
                    {{-- Top: Task & Directory Details --}}
                    <div class="card shadow-sm">
                        @if($selectedTask && $selectedTask->directory)
                            @php
                                $d = $selectedTask->directory;
                                $partyLogo = $d->party?->logo_path;
                                $profile   = $d->profile_picture;
                                $avatar    = $partyLogo ?: $profile; // prefer party logo
                                $initial   = Str::upper(Str::substr($d->name,0,1));
                                $phones = is_array($d->phones) ? $d->phones : ( $d->phones ? json_decode($d->phones,true) : [] );
                                if(!is_array($phones)) { $phones = []; }
                            @endphp
                            <div class="card-header border-0 pt-5 pb-0 d-flex justify-content-between align-items-start flex-wrap gap-2">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-50px me-4" data-bs-toggle="tooltip" title="{{ $d->name }}@if($d->id_card_number) ({{ $d->id_card_number }})@endif">
                                        @if($avatar)
                                            <img src="{{ asset('storage/' . $avatar) }}" alt="{{ $d->name }}" style="object-fit:cover;" />
                                        @else
                                            <div class="symbol-label fs-2 fw-semibold bg-light-danger text-danger">{{ $initial }}</div>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <span class="text-gray-800 fs-4 fw-bold">{{ $d->name }}</span>
                                            <span class="badge badge-light-secondary">#{{ $selectedTask->number }}</span>
                                            <span class="badge {{ $selectedTask->priorityBadge() }}">{{ ucfirst($selectedTask->priority) }}</span>
                                            <span class="badge badge-light @switch($selectedTask->status) @case('completed') badge-light-success @break @case('follow_up') badge-light-primary @break @default badge-light-warning @endswitch">{{ ucfirst(str_replace('_',' ',$selectedTask->status)) }}</span>
                                            @if($selectedTask->due_at)
                                                <span class="fs-8 @if($selectedTask->isOverdue()) text-danger fw-bold @else text-muted @endif">Due: {{ $selectedTask->due_at->format('M d, H:i') }}</span>
                                            @endif
                                        </div>
                                        <div class="text-gray-500 fw-semibold fs-7">{{ $d->id_card_number }}</div>
                                        {{-- Online users for this task --}}
                                        <div class="mt-2">
                                            <span class="fw-semibold fs-8 text-muted">Working On this Task:</span>
                                            <div class="d-flex flex-wrap gap-2 mt-1">
                                                @foreach($onlineUsers as $user)
                                                    @if(isset($user['id']))
                                                        @php $userModel = \App\Models\User::find($user['id']); @endphp
                                                        @if($userModel)
                                                            <div class="d-flex align-items-center gap-2">
                                                                <div class="symbol symbol-30px" title="{{ $userModel->name }}">
                                                                    @if($userModel->profile_picture)
                                                                        <img src="{{ asset('storage/' . $userModel->profile_picture) }}" alt="{{ $userModel->name }}" style="object-fit:cover;" />
                                                                    @else
                                                                        <div class="symbol-label fs-7 fw-semibold bg-light-primary text-primary">{{ Str::upper(Str::substr($userModel->name,0,1)) }}</div>
                                                                    @endif
                                                                </div>
                                                                <span class="badge badge-light-success">{{ $userModel->name }}</span>
                                                            </div>
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    @if ($d->party)
                                        <span class="badge badge-light-info">{{ $d->party->short_name ?? $d->party->name }}</span>
                                    @endif
                                    @if ($d->subConsite)
                                        <span class="badge badge-light-primary">{{ $d->subConsite->code }}</span>
                                    @endif
                                    <span class="badge badge-light-{{ $d->gender === 'male' ? 'success' : 'warning' }}">{{ ucfirst($d->gender) }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-4">
                                <div class="row g-5 mb-4">
                                    <div class="col-md-3 col-6">
                                        <div class="fw-semibold fs-8 text-gray-400">Permanent</div>
                                        <div class="fs-7 fw-bold text-gray-700">{{ $d->permanentLocationString() }}</div>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <div class="fw-semibold fs-8 text-gray-400">Current</div>
                                        <div class="fs-7 fw-bold text-gray-700">{{ $d->currentLocationString() }}</div>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <div class="fw-semibold fs-8 text-gray-400">Email</div>
                                        <div class="fs-7 fw-bold text-gray-700">{{ $d->email }}</div>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <div class="fw-semibold fs-8 text-gray-400">Phones</div>
                                        <div class="fs-7 fw-bold">
                                            @foreach($phones as $phone)
                                                <span class="copy-to-clipboard" data-copy-text="{{ $phone }}">{{ $phone }}</span>{{ !$loop->last ? ',' : '' }}
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                {{-- Task notes --}}
                                <div class="mb-4">
                                    @if($selectedTask->notes)
                                        <div class="text-gray-800 fs-8">{!! nl2br(e($selectedTask->notes)) !!}</div>
                                    @else
                                        <div class="text-muted fs-8 fst-italic">No task notes.</div>
                                    @endif
                                </div>

                                {{-- Assignees & Status Update Row --}}
                                <div class="row g-5">
                                    <div class="col-md-6">
                                        <h6 class="fw-semibold mb-2">Assignees</h6>
                                        <div class="symbol-group symbol-hover">
                                            @php $__dcolors=['primary','success','info','warning','danger','dark']; @endphp
                                            @forelse($selectedTask->assignees as $ai=>$user)
                                                @php $c=$__dcolors[$ai % count($__dcolors)]; $initial=Str::upper(Str::substr($user->name,0,1)); $pic=$user->profile_picture ?? null; @endphp
                                                <div class="symbol symbol-circle symbol-40px" data-bs-toggle="tooltip" title="{{ $user->name }}">
                                                    @if($pic)
                                                        <img src="{{ asset('storage/'.$pic) }}" alt="{{ $user->name }}" style="object-fit:cover;" />
                                                    @else
                                                        <div class="symbol-label fs-6 fw-semibold bg-{{ $c }} text-inverse-{{ $c }}">{{ $initial }}</div>
                                                    @endif
                                                </div>
                                            @empty
                                                <span class="text-muted fs-8">None</span>
                                            @endforelse
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="fw-semibold mb-2">Update Status</h6>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <select class="form-select form-select-sm form-select-solid w-auto" wire:model.live="taskStatusEdit">
                                                <option value="pending">Pending</option>
                                                <option value="follow_up">Follow Up</option>
                                                <option value="completed">Completed</option>
                                            </select>
                                            @if($taskStatusEdit==='follow_up')
                                                <input type="datetime-local" class="form-control form-control-sm w-auto" wire:model.live="followUpDate" title="Follow Up Date" min="{{ now()->format('Y-m-d\TH:i') }}" />
                                                <button type="button" class="btn btn-sm btn-light-primary" wire:click="saveFollowUpStatus" wire:loading.attr="disabled">Save Follow Up</button>
                                            @endif
                                            <span class="spinner-border spinner-border-sm" wire:loading wire:target="taskStatusEdit,saveFollowUpStatus"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @elseif($selectedTask)
                            <div class="card-body d-flex align-items-center justify-content-center text-muted fs-8">No directory linked.</div>
                        @else
                            <div class="card-body d-flex align-items-center justify-content-center text-muted fs-8">Select a task to view details.</div>
                        @endif
                    </div>

                    {{-- Middle: Form & Engagement --}}
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card h-100 shadow-sm">
                                <div class="card-header border-0 pt-5 pb-0">
                                    <h4 class="fw-bold mb-0">Form</h4>
                                </div>
                                <div class="card-body pt-4">
                                    {{-- reuse existing form rendering --}}
                                    @if($selectedTask && $selectedTask->form)
                                        @php $__lang = strtolower($selectedTask->form->language); $isDhivehiForm = in_array($__lang, ['dhivehi','dv','dv-mv','mv','mlv']); @endphp
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <h4 class="fw-bold mb-0 @if($isDhivehiForm) dv-heading @endif" style="@if($isDhivehiForm) font-family: 'Faruma', 'Arial Unicode MS', sans-serif !important; @endif">{{ $selectedTask->form->title }}</h4>
                                            <span class="badge badge-light-info">{{ ucfirst($selectedTask->form->language) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <h6 class="fw-bold mb-0 @if($isDhivehiForm) dv-heading @endif" style="@if($isDhivehiForm) font-family: 'Faruma', 'Arial Unicode MS', sans-serif !important; @endif">{{ $selectedTask->form->description }}</h6>
                                        </div>
                                        <div class="scroll-y me-n5 pe-5" style="max-height: 600px;">
                                            <form wire:submit.prevent="saveSubmission" @if($isDhivehiForm) class="dv-form" dir="rtl" @endif>
                                                <div class="d-flex flex-column gap-6">
                                                    @if($selectedTask && $selectedTask->form && $selectedTask->form->sections)
                                                        @foreach($selectedTask->form->sections as $section)
                                                            <div class="card">
                                                             @if(!empty($section->title) || !empty($section->description))
                                                                    <div class="card-header">
                                                                        @if(!empty($section->title))
                                                                            <h5 class="form-section-title fw-bold mb-1">{{ $section->title }}</h5>
                                                                        @endif
                                                                        @if(!empty($section->description))
                                                                            <div class="form-section-description text-muted mb-2">{{ $section->description }}</div>
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                                <div class="card-body">
                                                                    @foreach($section->questions as $question)
                                                                        <div class="form-group mb-4">
                                                                            <label class="form-label fw-semibold">{{ $question->question_text }} @if($question->is_required)<span class="text-danger">*</span>@endif</label>
                                                                            @if($question->help_text)
                                                                                <div class="link-primary p-1 mb-2">{{ $question->help_text }}</div>
                                                                            @endif
                                                                            {{-- Render input based on question type --}}
                                                                            @switch($question->type)
                                                                                @case('text')
                                                                                    <input type="text" class="form-control form-control-solid" wire:model.defer="submissionAnswers.{{ $question->id }}">
                                                                                    @break
                                                                                @case('textarea')
                                                                                    <textarea class="form-control form-control-solid" rows="3" wire:model.defer="submissionAnswers.{{ $question->id }}"></textarea>
                                                                                    @break
                                                                                @case('select')
                                                                                    <select class="form-select form-select-solid" wire:model.defer="submissionAnswers.{{ $question->id }}">
                                                                                        <option value="">Select...</option>
                                                                                        @foreach($question->options as $option)
                                                                                            <option value="{{ $option->value ?: $option->id }}">{{ $option->label }}</option>
                                                                                        @endforeach
                                                                                    </select>
                                                                                    @break
                                                                                @case('radio')
                                                                                    <div class="d-flex flex-column gap-2 mt-2">
                                                                                        @foreach($question->options as $option)
                                                                                            <div class="form-check form-check-custom form-check-solid">
                                                                                                <input class="form-check-input" type="radio" value="{{ $option->value ?: $option->id }}" wire:model.defer="submissionAnswers.{{ $question->id }}">
                                                                                                <label class="form-check-label" >{{ $option->label }}</label>
                                                                                            </div>
                                                                                        @endforeach
                                                                                    </div>
                                                                                    @break
                                                                                @case('checkbox')
                                                                                    <div class="d-flex flex-column gap-2 mt-2">
                                                                                        @foreach($question->options as $option)
                                                                                            <div class="form-check form-check-custom form-check-solid">
                                                                                                <input class="form-check-input" type="checkbox" value="{{ $option->id }}" wire:model.defer="submissionAnswers.{{ $question->id }}">
                                                                                                <label class="form-check-label">{{ $option->label }}</label>
                                                                                            </div>
                                                                                        @endforeach
                                                                                    </div>
                                                                                    @break
                                                                                @case('multiselect')
                                                                                    <div class="d-flex flex-column gap-2 mt-2">
                                                                                        @foreach($question->options as $option)
                                                                                            <div class="form-check form-check-custom form-check-solid">
                                                                                                <input class="form-check-input" type="checkbox" value="{{ $option->id }}" wire:model.defer="submissionAnswers.{{ $question->id }}">
                                                                                                <label class="form-check-label">{{ $option->label }}</label>
                                                                                            </div>
                                                                                        @endforeach
                                                                                    </div>
                                                                                    @break
                                                                                @default
                                                                                    <input type="text" class="form-control form-control-solid" wire:model.defer="submissionAnswers.{{ $question->id }}">
                                                                            @endswitch
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                                <div class="mt-5 pt-5 border-top">
                                                    <div class="d-flex justify-content-end gap-3">
                                                        <button type="submit" class="btn btn-light" wire:loading.attr="disabled" wire:target="saveSubmission,submitSubmission">Save Progress</button>
                                                        <button type="button" class="btn btn-primary" wire:click="submitSubmission" wire:loading.attr="disabled" wire:target="saveSubmission,submitSubmission">Submit
                                                            <span class="spinner-border spinner-border-sm ms-2" wire:loading wire:target="submitSubmission"></span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                        {{-- Removed external buttons block --}}
                                    @elseif($selectedTask)
                                        <div class="text-muted fs-8">No form for this task.</div>
                                    @else
                                        <div class="text-muted fs-8">Select a task to view form.</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            {{-- Engagement Card (unchanged content) --}}
                            <div class="card h-100 shadow-sm">
                                <div class="card-header border-0 pt-5 pb-0 d-flex justify-content-between align-items-center">
                                    <h4 class="fw-bold mb-0">Engagement</h4>
                                    <div class="d-flex gap-2">
                                        <span class="badge badge-light-primary" title="Notes">{{ count($voterNotes) }}</span>
                                        <span class="badge badge-light-info" title="Opinions">{{ count($voterOpinions) }}</span>
                                        <span class="badge badge-light-warning" title="Requests">{{ count($voterRequests) }}</span>
                                    </div>
                                </div>
                                <div class="card-body pt-3 d-flex flex-column">
                                    @if($selectedTask)
                                        <ul class="nav nav-tabs nav-line-tabs fw-semibold mb-4 small">
                                            <li class="nav-item">
                                                <a href="#" class="nav-link @if($activeTab==='notes') active @endif" wire:click.prevent="setActiveTab('notes')">Notes</a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="#" class="nav-link @if($activeTab==='opinions') active @endif" wire:click.prevent="setActiveTab('opinions')">Opinions</a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="#" class="nav-link @if($activeTab==='requests') active @endif" wire:click.prevent="setActiveTab('requests')">Requests</a>
                                            </li>
                                        </ul>

                                        {{-- NOTES TAB --}}
                                        @if($activeTab==='notes')
                                            <form wire:submit.prevent="addNote" class="d-flex flex-column gap-2 mb-4">
                                                <textarea rows="3" class="form-control form-control-solid" placeholder="Add a note..." wire:model.defer="newNote"></textarea>
                                                <div class="d-flex justify-content-end">
                                                    <button type="submit" class="btn btn-sm btn-primary" wire:loading.attr="disabled">Add Note</button>
                                                </div>
                                            </form>
                                            <div class="scroll-y flex-grow-1" style="max-height:350px;">
                                                <div class="d-flex flex-column gap-3">
                                                    @forelse($voterNotes as $note)
                                                        <div class="border rounded p-3 position-relative">
                                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                                <div class="d-flex flex-column">
                                                                    <span class="fw-semibold fs-7">{{ $note->author?->name ?? 'User' }}</span>
                                                                    <span class="text-muted fs-8">{{ $note->created_at?->diffForHumans() }}</span>
                                                                </div>
                                                                @if($note->created_by === auth()->id())
                                                                    <button type="button" class="btn btn-xs btn-icon btn-light" wire:click="deleteNote('{{ $note->id }}')" title="Delete">
                                                                        <i class="ki-duotone ki-cross fs-4"></i>
                                                                    </button>
                                                                @endif
                                                            </div>
                                                            <div class="fs-8 text-gray-800">{!! nl2br(e($note->note)) !!}</div>
                                                        </div>
                                                    @empty
                                                        <div class="text-muted fs-8">No notes yet.</div>
                                                    @endforelse
                                                </div>
                                            </div>
                                        @endif

                                        {{-- OPINIONS TAB --}}
                                        @if($activeTab==='opinions')
                                            <form wire:submit.prevent="addOpinion" class="d-flex flex-column gap-2 mb-4">
                                                <div class="d-flex gap-2">
                                                    <select class="form-select form-select-sm form-select-solid" wire:model.defer="opinionTypeId">
                                                        <option value="">Type...</option>
                                                        @foreach($opinionTypes as $ot)
                                                            <option value="{{ $ot->id }}">{{ $ot->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    {{-- Rating textual scale --}}
                                                    <select class="form-select form-select-sm form-select-solid" wire:model.defer="opinionRating" style="max-width:140px;">
                                                        <option value="">Rating</option>
                                                        <option value="5">Strong Yes</option>
                                                        <option value="4">Yes</option>
                                                        <option value="3">Neutral</option>
                                                        <option value="2">No</option>
                                                        <option value="1">Strong No</option>
                                                    </select>
                                                </div>
                                                <textarea rows="2" class="form-control form-control-solid" placeholder="Opinion note (optional)" wire:model.defer="opinionNote"></textarea>
                                                <div class="d-flex justify-content-end">
                                                    <button type="submit" class="btn btn-sm btn-info" wire:loading.attr="disabled">Add Opinion</button>
                                                </div>
                                            </form>
                                            <div class="scroll-y flex-grow-1" style="max-height:350px;">
                                                <div class="d-flex flex-column gap-3">
                                                    @forelse($voterOpinions as $op)
                                                        @php 
                                                            $ratingLabels=[5=>'Strong Yes',4=>'Yes',3=>'Neutral',2=>'No',1=>'Strong No']; 
                                                            $ratingClasses=[
                                                                5=>'badge-light-success',
                                                                4=>'badge-light-success',
                                                                3=>'badge-light-warning',
                                                                2=>'badge-light-danger',
                                                                1=>'badge-light-danger',
                                                            ];
                                                        @endphp
                                                        <div class="border rounded p-3 position-relative">
                                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                                <div class="d-flex flex-column">
                                                                    <span class="fw-semibold fs-7">{{ $op->takenBy?->name ?? 'User' }}</span>
                                                                    <span class="text-muted fs-8">{{ $op->created_at?->diffForHumans() }}</span>
                                                                </div>
                                                                @if($op->taken_by === auth()->id())
                                                                    <button type="button" class="btn btn-xs btn-icon btn-light" wire:click="deleteOpinion('{{ $op->id }}')" title="Delete"><i class="ki-duotone ki-cross fs-4"></i></button>
                                                                @endif
                                                            </div>
                                                            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                                                <span class="badge badge-light-info">{{ $op->type?->name ?? 'Type' }}</span>
                                                                @if($op->rating)
                                                                    <span class="badge {{ $ratingClasses[$op->rating] ?? 'badge-light-secondary' }}">{{ $ratingLabels[$op->rating] ?? $op->rating }}</span>
                                                                @endif
                                                            </div>
                                                            @if($op->note)
                                                                <div class="fs-8 text-gray-800">{!! nl2br(e($op->note)) !!}</div>
                                                            @endif
                                                        </div>
                                                    @empty
                                                        <div class="text-muted fs-8">No opinions yet.</div>
                                                    @endforelse
                                                </div>
                                            </div>
                                        @endif

                                        {{-- REQUESTS TAB --}}
                                        @if($activeTab==='requests')
                                            <form wire:submit.prevent="addRequest" class="d-flex flex-column gap-2 mb-4">
                                                <div class="d-flex gap-2 flex-wrap">
                                                    <select class="form-select form-select-sm form-select-solid" wire:model.defer="requestTypeId">
                                                        <option value="">Type...</option>
                                                        @foreach($requestTypes as $rt)
                                                            <option value="{{ $rt->id }}">{{ $rt->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <textarea rows="2" class="form-control form-control-solid" placeholder="Request note (optional)" wire:model.defer="requestNote"></textarea>
                                                <div class="d-flex justify-content-end">
                                                    <button type="submit" class="btn btn-sm btn-warning" wire:loading.attr="disabled">Add Request</button>
                                                </div>
                                            </form>
                                            <div class="scroll-y flex-grow-1" style="max-height:350px;">
                                                <div class="d-flex flex-column gap-3">
                                                    @forelse($voterRequests as $req)
                                                        <div class="border rounded p-3 position-relative">
                                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                                <div class="d-flex flex-column">
                                                                    <span class="fw-semibold fs-7">{{ $req->author?->name ?? 'User' }}</span>
                                                                    <span class="text-muted fs-8">{{ $req->created_at?->diffForHumans() }}</span>
                                                                </div>
                                                                @if($req->created_by === auth()->id())
                                                                    <button type="button" class="btn btn-xs btn-icon btn-light" wire:click="deleteRequest('{{ $req->id }}')" title="Delete"><i class="ki-duotone ki-cross fs-4"></i></button>
                                                                @endif
                                                            </div>
                                                            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                                                <span class="badge badge-light-warning">{{ $req->type?->name ?? 'Type' }}</span>
                                                                @if($req->amount)
                                                                    <span class="badge badge-light-success">{{ number_format($req->amount,2) }}</span>
                                                                @endif
                                                                <span class="badge badge-light-secondary">#{{ $req->request_number }}</span>
                                                            </div>
                                                            @if($req->note)
                                                                <div class="fs-8 text-gray-800">{!! nl2br(e($req->note)) !!}</div>
                                                            @endif
                                                        </div>
                                                    @empty
                                                        <div class="text-muted fs-8">No requests yet.</div>
                                                    @endforelse
                                                </div>
                                            </div>
                                        @endif
                                    @else
                                        <div class="text-muted fs-8">Select a task to view engagement.</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Bottom: Directory Contact & Location --}}
                    <div class="card shadow-sm">
                        @if($selectedTask && $selectedTask->directory)
                            <div class="card-header">
                                <h3 class="card-title">Directory Contact & Current Location</h3>
                            </div>
                            <div class="card-body">
                                <div id="currentLocationState" data-country="{{ $currentCountryId }}" data-island="{{ $currentIslandId }}" data-property="{{ $currentPropertyId }}" class="d-none"></div>
                                <form wire:submit.prevent="updateDirectoryContact" class="d-flex flex-column gap-3">
                                    <div class="row g-2">
                                        <div class="col-sm-4">
                                            <label class="form-label fs-8 text-muted mb-1">Country</label>
                                            <div wire:ignore>
                                                <select id="currentCountrySelect" class="form-select form-select-sm form-select-solid">
                                                    <option value="">Select country...</option>
                                                    @foreach($countries as $c)
                                                        <option value="{{ $c->id }}" @selected($currentCountryId===$c->id)>{{ $c->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        @if($currentCountryId && $currentCountryId === $maldivesCountryId)
                                        <div class="col-sm-4">
                                            <label class="form-label fs-8 text-muted mb-1">Island</label>
                                            <div wire:ignore>
                                                <select id="currentIslandSelect" class="form-select form-select-sm form-select-solid" @disabled(!$currentCountryId)>
                                                    <option value="">@if($currentCountryId) Select island... @else Select country first @endif</option>
                                                    @foreach($currentIslands as $is)
                                                        <option value="{{ $is->id }}" @selected($currentIslandId===$is->id)>{{ $is->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        @endif
                                        @if($currentIslandId && count($currentProperties))
                                        <div class="col-sm-4">
                                            <label class="form-label fs-8 text-muted mb-1">Property</label>
                                            <div wire:ignore>
                                                <select id="currentPropertySelect" class="form-select form-select-sm form-select-solid" @disabled(!$currentIslandId)>
                                                    <option value="">@if($currentIslandId) Select property... @else Select island first @endif</option>
                                                    @foreach($currentProperties as $prop)
                                                        <option value="{{ $prop->id }}" @selected($currentPropertyId===$prop->id)>{{ $prop->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        @endif
                                        <div class="col-12">
                                            <label class="form-label fs-8 text-muted mb-1">Address</label>
                                            <input type="text" class="form-control form-control-sm form-control-solid" placeholder="Current address" wire:model.defer="currentAddress" />
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fs-8 text-muted mb-1">Street</label>
                                            <input type="text" class="form-control form-control-sm form-control-solid" placeholder="Street" wire:model.defer="currentStreetAddress" />
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label required fs-8 text-muted mb-1">Email</label>
                                            <input type="email" class="form-control form-control-sm form-control-solid" placeholder="email@example.com" wire:model.defer="contactEmail" />
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fs-8 text-muted mb-1">Phones (comma separated)</label>
                                            <input type="text" class="form-control form-control-sm form-control-solid" placeholder="7xxxxxx, 9xxxxxx" wire:model.defer="contactPhones" />
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <button type="submit" class="btn btn-sm btn-primary" wire:loading.attr="disabled">Save</button>
                                    </div>
                                    <div class="fs-8 text-muted">
                                        <span class="d-block">Permanent: {{ $selectedTask->directory->permanentLocationString() }}</span>
                                        <span class="d-block">Current: {{ $selectedTask->directory->currentLocationString() }}</span>
                                    </div>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            </div> {{-- /main row --}}
        </div> {{-- /container-fluid --}}
    </div> {{-- /post --}}
</div> {{-- /content --}}



@push('scripts')
<script>
/**
 * agent-management.optimized.js
 * Fast, low-overhead task switching + presence for Livewire 3 + Laravel Echo
 *
 * Key optimizations:
 * - Event delegation (one listener for all .task-item)
 * - Minimal work per click (sync close→open→join, server roundtrip deferred)
 * - Channel reuse + safe switching (leave-by-name only when needed)
 * - Burst coalescing (ignore rapid duplicate switches)
 * - Optional DEBUG gating for logs
 */

(() => {
  'use strict';

  // =========================
  // Config
  // =========================
  const DEBUG = false; // toggle verbose logs
  const SELECTOR_TASK_ITEM = '.task-item';
  const ATTR_TASK_ID = 'data-task-id';
  const ACTIVE_CLASS = 'task-item--active';

  // Polyfill for CSS.escape (older browsers)
  if (typeof CSS === 'undefined' || !CSS.escape) {
    window.CSS = window.CSS || {};
    CSS.escape = (s) => String(s).replace(/[^a-zA-Z0-9_\-]/g, '\\$&');
  }

  // =========================
  // Globals
  // =========================
  window.lastTaskId = window.lastTaskId ?? null;
  let echoChannel = null;
  let echoChannelName = null;
  let switching = false;     // coalesce rapid clicks
  let pendingTaskId = null;  // last requested switch to avoid redundant work

  const log = (...a) => DEBUG && console.log(...a);
  const warn = (...a) => DEBUG && console.warn(...a);

  // =========================
  // Presence helpers
  // =========================
  function leavePresenceChannel() {
    if (!window.Echo || !echoChannelName) return;
    try {
      window.Echo.leave(echoChannelName);
      log('[Echo] left', echoChannelName);
    } catch (e) {
      warn('[Echo] leave error:', e);
    } finally {
      echoChannel = null;
      echoChannelName = null;
    }
  }

  function joinPresenceChannel(taskId) {
    if (!window.Echo || !taskId) return;

    const newName = 'task.presence.' + taskId;
    if (echoChannelName === newName && echoChannel) {
      // Already on correct channel — no extra work.
      return;
    }

    leavePresenceChannel();
    echoChannelName = newName;

    echoChannel = window.Echo.join(echoChannelName)
      .listen('TaskUserPresenceChanged', (e) => {
        if (window.Livewire) {
          Livewire.dispatch('updateUserPresence', {
            taskId: e.taskId, userId: e.userId, isOnline: e.isOnline
          });
        }
      })
      .here((users) => {
        // Normalize to [{id,name?}, ...]
        let arr = Array.isArray(users) ? users : [];
        if (!Array.isArray(arr) || !arr.length) {
          arr = [];
        }
        if (!arr.length && window.Laravel?.user) {
          arr = [{ id: window.Laravel.user.id, name: window.Laravel.user.name }];
        }
        if (window.Livewire) {
          Livewire.dispatch('updateOnlineUsers', { users: arr });
        }
      })
      .joining((user) => window.Livewire && Livewire.dispatch('userJoined', { user }))
      .leaving((user) => window.Livewire && Livewire.dispatch('userLeft', { user }));
  }

  // =========================
  // UI helpers (optimistic)
  // =========================
  function setActiveTaskItem(id) {
    // Avoid heavy DOM work: only toggle if state actually changes
    if (id === window.lastTaskId) return;

    const prev = document.querySelector(`.${ACTIVE_CLASS}`);
    if (prev) prev.classList.remove(ACTIVE_CLASS);

    const next = document.querySelector(`${SELECTOR_TASK_ITEM}[${ATTR_TASK_ID}="${CSS.escape(id)}"]`);
    if (next) next.classList.add(ACTIVE_CLASS);
  }

  // =========================
  // Core switch routine
  // =========================
  function switchTask(newTaskId) {
    if (!newTaskId) return;

    // Coalesce bursts: if we're switching and user clicks again to same ID, skip
    if (switching && pendingTaskId === newTaskId) return;
    switching = true;
    pendingTaskId = newTaskId;

    // No-op if same as current
    if (window.lastTaskId === newTaskId) {
      switching = false;
      return;
    }

    // 1) Instant UI feedback
    setActiveTaskItem(newTaskId);

    // 2) Presence close → open (no await)
    if (window.Livewire) {
      if (window.lastTaskId) {
        Livewire.dispatch('user-closed-task', { taskId: window.lastTaskId });
      }
      Livewire.dispatch('user-opened-task', { taskId: newTaskId });
    }
    joinPresenceChannel(newTaskId);

    // 3) Commit local state
    window.lastTaskId = newTaskId;

    // 4) Ask server to load detail after a tick (keeps UI snappy)
    queueMicrotask(() => {
      try {
        if (window.Livewire) {
          Livewire.dispatch('task-selected', { taskId: newTaskId });
          // Or, to call a specific component method:
          // const cmpId = document.querySelector('[data-task-manager]')?.getAttribute('wire:id');
          // if (cmpId) Livewire.find(cmpId).call('selectTask', newTaskId);
        }
      } finally {
        switching = false;
      }
    });
  }

  // =========================
  // Event delegation (one-time)
  // =========================
  function onDelegatedClick(e) {
    const el = e.target.closest(SELECTOR_TASK_ITEM);
    if (!el) return;
    const tid = el.getAttribute(ATTR_TASK_ID);
    if (!tid) return;

    // Let wire:click handlers also run; we just optimize presence/UI
    switchTask(tid);
  }

  // =========================
  // Boot
  // =========================
  document.addEventListener('livewire:init', () => {
    log('[agent-management] optimized init');

    // One delegated listener — no rebind after morphs
    document.addEventListener('click', onDelegatedClick, { capture: true });

    // If PHP emits a selection programmatically
    if (window.Livewire) {
      Livewire.on('task-selected', ({ taskId }) => {
        if (!taskId || taskId === window.lastTaskId) return;
        switchTask(taskId);
      });
    }

    // Clean shutdown
    window.addEventListener('beforeunload', () => {
      if (window.lastTaskId && window.Livewire) {
        try { Livewire.dispatch('user-closed-task', { taskId: window.lastTaskId }); } catch (_) {}
      }
      leavePresenceChannel();
    });
  });

  // Optional debug API
  window.TaskPresence = {
    get lastTaskId() { return window.lastTaskId; },
    get channel() { return echoChannelName; },
    switch: switchTask,
    join: joinPresenceChannel,
    leave: leavePresenceChannel,
  };
})();
</script>



<script>
(function(){
    function ensureSelect2Css(){
        if(!document.querySelector('link[data-select2-css]')){
            const l=document.createElement('link'); l.rel='stylesheet'; l.href='https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css'; l.dataset.select2Css='1'; document.head.appendChild(l);
        }
    }
    function signature(select){ try { return Array.from(select.options).map(o=> (o.value||'')+':'+(o.text||'')).join('|'); } catch(_){ return ''; } }
    function currentState(){ const el=document.getElementById('currentLocationState'); if(!el) return {country:'',island:'',property:''}; return {country:el.dataset.country||'', island:el.dataset.island||'', property:el.dataset.property||''}; }
    function initOne(sel, placeholder, prop){
        if(typeof $ === 'undefined' || !$.fn.select2) return; const jq=$(sel); if(!jq.length) return; const native=jq.get(0); const sig=signature(native); const prev=jq.data('s2hash');
        if(!jq.data('select2') || sig!==prev){
            if(jq.data('select2')){ jq.off('change.select2.livewire'); jq.select2('destroy'); }
            jq.data('s2hash', sig);
            jq.select2({width:'100%', placeholder, allowClear:true, dropdownParent: jq.parent()});
            let t=null;
            jq.on('change.select2.livewire', function(){
                const v = jq.val() || '';
                clearTimeout(t); t=setTimeout(()=>{ @this.set(prop, v); }, 80);
            });
        }
    }
    function syncValues(){ const st=currentState(); const map=[{sel:'#currentCountrySelect',val:st.country},{sel:'#currentIslandSelect',val:st.island},{sel:'#currentPropertySelect',val:st.property}]; map.forEach(m=>{ const jq=$(m.sel); if(jq.data('select2')){ if((jq.val()||'')!==m.val){ jq.val(m.val||'').trigger('change.select2'); } } }); }
    function initAll(){ ensureSelect2Css(); initOne('#currentCountrySelect','Select country...','currentCountryId'); initOne('#currentIslandSelect','Select island...','currentIslandId'); initOne('#currentPropertySelect','Select property...','currentPropertyId'); syncValues(); }
    document.addEventListener('livewire:init', ()=>{ setTimeout(initAll,0); Livewire.hook('message.processed', ()=>{ initAll(); }); });
})();
</script>
<script>
// Tooltip init (re-run after Livewire updates)
(function(){
    function initTooltips(){
        if(!window.bootstrap || !bootstrap.Tooltip) return;
        const TT = bootstrap.Tooltip;
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el=>{
            try {
                // Dispose any existing instance (BS5 safe)
                const existing = TT.getInstance(el);
                if(existing) existing.dispose();
                new TT(el, {trigger:'hover focus'});
            } catch(_) {}
        });
    }

    // Expose globally (optional manual trigger)
    window.__refreshTooltips = initTooltips;

    // Livewire v2 events
    document.addEventListener('livewire:load', ()=>{
        initTooltips();
    });
    document.addEventListener('livewire:update', ()=>{
        // Slight delay to allow DOM diff to finish painting
        setTimeout(initTooltips, 10);
    });

    // Livewire v3 hooks fallback
    document.addEventListener('livewire:init', ()=>{
        if(window.Livewire && Livewire.hook){
            // message.processed (v2) still kept above; add commit for v3
            try { Livewire.hook('commit', ({succeed}) => { succeed(()=> setTimeout(initTooltips, 10)); }); } catch(_) {}
            try { Livewire.hook('message.processed', ()=> setTimeout(initTooltips, 10)); } catch(_) {}
        }
    });

    // MutationObserver as final fallback (in case hooks miss)
    const observer = new MutationObserver((muts)=>{
        for(const m of muts){
            if(m.addedNodes && m.addedNodes.length){
                if([...m.addedNodes].some(n => n.nodeType===1 && n.querySelector && n.querySelector('[data-bs-toggle="tooltip"]'))){
                    setTimeout(initTooltips, 20);
                    break;
                }
            }
        }
    });
    observer.observe(document.documentElement, {subtree:true, childList:true});
})();
</script>
<script>
// Improved robust tooltip initialization for dynamic Livewire updates (with lazy on-hover init)
(function(){
    function initTooltips(container){
        if(!window.bootstrap || !bootstrap.Tooltip) return;
        const scope = container || document;
        const TT = bootstrap.Tooltip;
        scope.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el=>{
            try {
                const existing = TT.getInstance(el);
                if(existing) return; // keep existing
                new TT(el, {trigger:'hover focus'});
            } catch(_) {}
        });
    }

    // Force refresh (clears + re-adds)
    function refreshTooltips(){
        if(!window.bootstrap || !bootstrap.Tooltip) return;
        const TT = bootstrap.Tooltip;
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el=>{
            try { const ex = TT.getInstance(el); if(ex) ex.dispose(); } catch(_) {}
        });
        // small delay to allow Livewire to complete DOM paint
        setTimeout(()=> initTooltips(), 0);
    }

    window.__refreshTooltips = refreshTooltips;

    // Livewire hooks
    document.addEventListener('livewire:load', refreshTooltips);
    document.addEventListener('livewire:update', () => setTimeout(refreshTooltips, 10));
    document.addEventListener('livewire:init', ()=>{
        if(window.Livewire && Livewire.hook){
            try { Livewire.hook('message.processed', () => setTimeout(refreshTooltips, 10)); } catch(_) {}
            try { Livewire.hook('commit', ({succeed}) => { succeed(()=> setTimeout(refreshTooltips, 10)); }); } catch(_) {}
        }
    });

    // Mutation observer fallback
    const observer = new MutationObserver((muts)=>{
        for(const m of muts){
            if(m.addedNodes && m.addedNodes.length){
                if([...m.addedNodes].some(n => n.nodeType===1 && n.querySelector && (n.matches?.('[data-bs-toggle="tooltip"]') || n.querySelector?.('[data-bs-toggle="tooltip"]')))){
                    setTimeout(refreshTooltips, 25);
                    break;
                }
            }
        }
    });
    observer.observe(document.getElementById('kt_content')||document.body, {childList:true, subtree:true});

    // Lazy on-hover init (guarantees tooltip even if hooks missed)
    document.addEventListener('mouseover', (e)=>{
        const el = e.target.closest('[data-bs-toggle="tooltip"]');
        if(!el) return;
        if(!window.bootstrap || !bootstrap.Tooltip) return;
        const TT = bootstrap.Tooltip;
        if(!TT.getInstance(el)){
            try { new TT(el, {trigger:'hover focus'}); } catch(_) {}
        }
    }, true);
})();
</script>
@endpush
</div>
