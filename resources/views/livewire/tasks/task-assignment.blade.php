@section('title','Assign Tasks')
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
window.addEventListener('swal', e => { const d=e.detail||{}; Swal.fire({icon:d.type||'success',title:d.title||'',text:d.text||'',confirmButtonColor:'#0d6efd'}); });
</script>
@endpush
<div class="content fs-6 d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="toolbar" id="kt_toolbar">
        <div class="container-fluid d-flex flex-stack flex-wrap flex-sm-nowrap">
            <div class="d-flex flex-column align-items-start justify-content-center flex-wrap me-2 mb-4 mb-sm-0">
                <h1 class="text-dark fw-bold my-1 fs-2">Assign Tasks</h1>
                <ul class="breadcrumb fw-semibold fs-base my-1">
                    <li class="breadcrumb-item text-muted"><a href="#" class="text-muted text-hover-primary">Operations</a></li>
                    <li class="breadcrumb-item text-dark">Assign Tasks</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="post fs-6 d-flex flex-column-fluid" id="kt_post">
        <div class="container-xxl">
            <div class="row g-7">
                <!-- VOTERS LIST -->
                <div class="col-xl-7 order-xl-1 order-2">
                    <div class="card h-100">
                        <div class="card-header border-0 pt-6 pb-0 d-flex flex-wrap gap-4 align-items-center">
                            <h3 class="card-title fw-bold mb-0">Voters</h3>
                            <!-- Filter Button + Panel -->
                            <div class="ms-auto" x-data="{open:false}" @keydown.escape.window="open=false">
                                <button type="button" class="btn btn-sm btn-primary d-flex align-items-center gap-2" @click="open=!open">
                                    <i class="ki-outline ki-filter fs-5"></i><span>Filter</span>
                                </button>
                                <div class="position-absolute end-0 mt-2" x-cloak x-show="open" x-transition @click.outside="open=false" style="z-index: 105;">
                                    <div class="card shadow w-325px">
                                        <div class="card-header py-4 px-5">
                                            <div class="card-title m-0 fw-bold">Filter Options</div>
                                            <button type="button" class="btn btn-sm btn-icon btn-light" @click="open=false"><i class="ki-outline ki-cross fs-2"></i></button>
                                        </div>
                                        <div class="separator my-0"></div>
                                        <div class="card-body p-5 d-flex flex-column gap-5">
                                            <div>
                                                <label class="form-label fs-7 fw-semibold">Search</label>
                                                <input type="text" class="form-control form-control-sm form-control-solid" placeholder="Name / ID" wire:model.defer="directorySearchDraft">
                                            </div>
                                            <div>
                                                <label class="form-label fs-7 fw-semibold">Party</label>
                                                <select class="form-select form-select-sm form-select-solid" wire:model.defer="filterPartyIdDraft">
                                                    <option value="">All Parties</option>
                                                    @foreach($parties ?? [] as $p)
                                                        <option value="{{ $p->id }}">{{ $p->short_name ?? $p->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="form-label fs-7 fw-semibold">SubConsite</label>
                                                <select class="form-select form-select-sm form-select-solid" wire:model.defer="filterSubConsiteIdDraft">
                                                    <option value="">All</option>
                                                    @foreach($subConsites ?? [] as $sc)
                                                        <option value="{{ $sc->id }}">{{ $sc->code }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="form-label fs-7 fw-semibold">Only voters with no tasks</label>
                                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                                    <input type="checkbox" class="form-check-input" wire:model.defer="filterNoTaskOnlyDraft" id="noTaskOnlyCheck">
                                                    <label class="form-check-label" for="noTaskOnlyCheck">No Task Only</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="separator my-0"></div>
                                        <div class="card-footer d-flex justify-content-between align-items-center p-4">
                                            <button type="button" class="btn btn-light btn-sm" wire:click="clearFilters" @click="open=false">Reset</button>
                                            <button type="button" class="btn btn-primary btn-sm" wire:click="applyFilters" @click="open=false">Apply</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End Filter Button + Panel -->
                        </div>
                        <div class="card-body pt-4 d-flex flex-column">
                            <div class="pe-2" style="max-height:640px; overflow-y:auto;">
                                @php $pageIds=[]; @endphp
                                @forelse($directories as $dir)
                                    @php
                                        $pageIds[] = $dir->id;
                                        $locParts = [];
                                        if($dir->island && $dir->island->name) $locParts[] = $dir->island->name;
                                        if($dir->country && $dir->country->name) $locParts[] = $dir->country->name;
                                        if($dir->property && $dir->property->name) $locParts[] = $dir->property->name;
                                        $location = $locParts ? implode(', ', $locParts) : null;
                                        $initial = mb_strtoupper(mb_substr($dir->name ?? 'U',0,1));
                                    @endphp
                                    @php
    $isExcluded = $selectMode && in_array($dir->id,$excludedDirectoryIds);
    $isSelectedRow = $selectMode ? !$isExcluded : in_array($dir->id,$selectedDirectoryIds);
@endphp
<div class="d-flex align-items-start gap-4 py-3 border-bottom @if($isSelectedRow) bg-light-primary @endif @if($isExcluded) opacity-50 @endif" style="transition:.15s; cursor:pointer;" wire:click="toggleDirectory('{{ $dir->id }}')">
    <div class="form-check form-check-sm form-check-custom form-check-solid mt-2">
        <input class="form-check-input" type="checkbox" wire:click.stop="toggleDirectory('{{ $dir->id }}')" @checked($isSelectedRow) />
    </div>
    <div class="symbol symbol-40px flex-shrink-0">
        @if($dir->profile_picture)
            <img src="{{ asset('storage/'.$dir->profile_picture) }}" alt="" class="object-cover" />
        @else
            <div class="symbol-label bg-light-primary text-primary fw-bold">{{ $initial }}</div>
        @endif
    </div>
    <div class="flex-grow-1 min-w-0">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div class="d-flex flex-column">
                <span class="fw-semibold text-gray-900 text-hover-primary">{{ $dir->name ?? 'Unnamed' }}</span>
                <div class="d-flex flex-wrap gap-3 mt-1">
                    <span class="fs-8 text-muted">ID: {{ $dir->id_card_number ?? '—' }}</span>
                    @if($dir->gender)
                        <span class="badge badge-light fs-8 fw-normal text-capitalize">{{ $dir->gender }}</span>
                    @endif
                    @if($dir->party)
                        <span class="d-inline-flex align-items-center gap-1">
                            @if($dir->party->logo)
                                <img src="{{ asset('storage/'.$dir->party->logo) }}" alt="{{ $dir->party->name }}" class="rounded" style="width:18px;height:18px;object-fit:cover;" />
                            @else
                                <span class="badge badge-light-info fs-8 fw-normal">{{ $dir->party->short_name ?? Str::limit($dir->party->name,3,'') }}</span>
                            @endif
                        </span>
                    @endif
                    @if($dir->subConsite)
                        <span class="badge badge-light-warning fs-8 fw-normal">{{ $dir->subConsite->code ?? $dir->subConsite->name }}</span>
                    @endif
                    @if($location)
                        <span class="fs-8 text-muted">{{ $location }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
                                @empty
                                    <div class="text-center text-muted py-10">No voters found.</div>
                                @endforelse
                            </div>
                            <div class="mt-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                                <div class="fs-8 text-muted">
                                    @if($selectMode==='all')
                                        Selecting ALL voters ({{ number_format($bulkBaseCount) }}) @if(count($excludedDirectoryIds)) minus {{ count($excludedDirectoryIds) }} excluded @endif => {{ number_format($selectionCount) }}
                                    @elseif($selectMode==='filtered')
                                        Selecting ALL filtered ({{ number_format($bulkBaseCount) }}) @if(count($excludedDirectoryIds)) minus {{ count($excludedDirectoryIds) }} excluded @endif => {{ number_format($selectionCount) }}
                                    @else
                                        Selected: {{ $selectionCount }}
                                    @endif
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <button class="btn btn-sm btn-light" wire:click="selectCurrentPage" @disabled($selectMode!==null)>Select Page</button>
                                    <button class="btn btn-sm btn-light-primary" wire:click="selectAllFiltered" @disabled($selectMode==='filtered') title="Select all voters matching current filters">All Filtered</button>
                                    <button class="btn btn-sm btn-primary" wire:click="selectAll" @disabled($selectMode==='all') title="Select every voter (ignores filters)">All</button>
                                    @if($selectMode)
                                        <button class="btn btn-sm btn-danger" wire:click="exitSelectMode" title="Exit bulk selection">Exit Bulk</button>
                                    @else
                                        <button class="btn btn-sm btn-light-danger" wire:click="clearSelectedDirectories" @disabled($selectionCount===0)>Clear</button>
                                    @endif
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="d-flex justify-content-center">{{ $directories->onEachSide(1)->links() }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- TASK FORM -->
                <div class="col-xl-5 order-xl-2 order-1">
                    <div class="card h-100">
                        <div class="card-header border-0 pt-6 pb-0">
                            <h3 class="card-title fw-bold mb-0">Task Details</h3>
                        </div>
                        <div class="card-body pt-5">
                            <form wire:submit.prevent="createTasks" class="d-flex flex-column gap-6">
                                <div class="row g-6">
                                    <div class="col-md-12">
                                        <label class="form-label required">Title</label>
                                        <input type="text" class="form-control form-control-solid" wire:model.defer="taskTitle" placeholder="Task title">
                                        @error('taskTitle')<div class="text-danger fs-8 mt-1">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="row g-6">
                                    <div class="col-md-6">
                                        <label class="form-label">Due At</label>
                                        <input type="datetime-local" class="form-control form-control-solid" wire:model.defer="taskDueAt">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Priority</label>
                                        <select class="form-select form-select-solid" wire:model.defer="taskPriority">
                                            <option value="low">Low</option>
                                            <option value="normal">Normal</option>
                                            <option value="high">High</option>
                                            <option value="urgent">Urgent</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-6">
                                    <div class="col-md-6">
                                        <label class="form-label">Type</label>
                                        <select class="form-select form-select-solid" wire:model.defer="taskType">
                                            <option value="other">Other</option>
                                            <option value="form_fill">Fill Form</option>
                                            <option value="pickup">Pickup</option>
                                            <option value="dropoff">Dropoff</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Form (optional)</label>
                                        <select class="form-select form-select-solid" wire:model.defer="taskFormId">
                                            <option value="">— None —</option>
                                            @foreach($forms as $f)
                                                <option value="{{ $f->id }}">{{ $f->title }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-6">
                                    <div class="col-md-6">
                                        <label class="form-label">Election (optional)</label>
                                        <select class="form-select form-select-solid" wire:model.defer="taskElectionId">
                                            <option value="">— None —</option>
                                            @foreach($elections as $el)
                                                <option value="{{ $el->id }}">{{ $el->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label required">Assignees</label>
                                        <select class="form-select form-select-solid" multiple size="6" wire:model.defer="assigneeIds">
                                            @foreach($users as $u)
                                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('assigneeIds')<div class="text-danger fs-8 mt-1">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label">Notes</label>
                                    <textarea rows="4" class="form-control form-control-solid" wire:model.defer="taskNotes" placeholder="Additional instructions..."></textarea>
                                </div>
                                <div class="d-flex justify-content-between flex-wrap gap-3">
                                    <div class="fs-8 text-muted">Will create {{ max(1,$selectionCount) }} task(s)</div>
                                    <div class="d-flex gap-2">
                                        <button type="reset" class="btn btn-light" wire:click="$reset(['taskTitle','taskNotes','taskType','taskPriority','taskDueAt','taskFormId','taskElectionId','assigneeIds'])">Reset</button>
                                        <button type="submit" class="btn btn-primary" @disabled($selectionCount===0)>Create</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- END TASK FORM -->
            </div>
        </div>
    </div>
</div>
