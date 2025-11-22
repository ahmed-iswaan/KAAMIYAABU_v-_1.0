@section('title','Requests')
<div>
    <div class="content fs-6 d-flex flex-column flex-column-fluid" id="kt_content">
        <!-- Toolbar -->
        <div class="toolbar" id="kt_toolbar">
            <div class="container-fluid d-flex flex-stack flex-wrap flex-sm-nowrap">
                <div class="d-flex flex-column align-items-start justify-content-center flex-wrap me-2 py-3">
                    <div class="d-flex align-items-center mb-2">
                        <i class="ki-duotone ki-message-text-2 fs-2 text-primary me-2"><span class="path1"></span><span class="path2"></span></i>
                        <h1 class="text-dark fw-bold fs-2 mb-0">Requests</h1>
                    </div>
                    <ul class="breadcrumb fw-semibold fs-xs my-0">
                        <li class="breadcrumb-item text-muted"><a href="#" class="text-muted text-hover-primary">Election</a></li>
                        <li class="breadcrumb-item text-dark">Requests</li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- Post -->
        <div class="post fs-6 d-flex flex-column-fluid" id="kt_post">
            <div class="container-xxl">
                <!-- 2-Panel Layout (Craft Orders Review Inspired) -->
                <div class="d-flex flex-column flex-lg-row gap-7">
                    <!-- Left: List & Filters -->
                    <div class="flex-grow-1 flex-lg-grow-0 w-100 w-lg-350px">
                        <div class="card card-flush h-100 shadow-sm" id="requestListCard">
                            <div class="card-header border-0 pt-6 pb-4 flex-column align-items-stretch" style="overflow:visible;">
                                <div class="d-flex w-100 align-items-center mb-4">
                                    <h3 class="card-title fw-bold flex-grow-1 mb-0">All Requests</h3>
                                    <span class="badge badge-light-primary">{{ $requests->total() }}</span>
                                </div>
                                <div class="d-flex flex-column gap-4 w-100">
                                    <div class="position-relative">
                                        <i class="ki-duotone ki-magnifier fs-2 position-absolute top-50 translate-middle-y ms-4 text-gray-400"><span class="path1"></span><span class="path2"></span></i>
                                        <input type="text" class="form-control form-control-solid ps-12" placeholder="Search voter / ID / VRQ" wire:model.live.debounce.400ms="search" />
                                    </div>
                                    <div class="d-flex flex-wrap gap-3">
                                        @php
                                            $defaultElectionId = $elections->first()->id ?? null;
                                            $activeCount = collect([
                                                $status !== '' ? $status : null,
                                                $requestTypeId !== '' ? $requestTypeId : null,
                                                ($amountMin !== '' && $amountMin !== null) ? 'amin' : null,
                                                ($amountMax !== '' && $amountMax !== null) ? 'amax' : null,
                                                $dateFrom ?: null,
                                                $dateTo ?: null,
                                                ($electionId && $electionId !== $defaultElectionId) ? $electionId : null,
                                            ])->filter()->count();
                                        @endphp
                                        <div class="flex-grow-1" wire:ignore.self>
                                            <button type="button" class="btn btn-light btn-active-light-primary w-100 d-flex justify-content-between align-items-center" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-start">
                                                <span class="d-flex align-items-center">
                                                    <i class="ki-duotone ki-filter fs-2 text-primary me-2"><span class="path1"></span><span class="path2"></span></i>
                                                    <span class="fw-semibold">Filters</span>
                                                </span>
                                                <span class="badge badge-light {{ $activeCount? 'badge-outline' : '' }}">{{ $activeCount ?: 'All' }}</span>
                                            </button>
                                            <div class="menu menu-sub menu-sub-dropdown w-325px p-0 filter-menu" data-kt-menu="true">
                                                <div class="d-flex align-items-center justify-content-between px-5 py-4 border-bottom">
                                                    <h6 class="mb-0 fw-bold">Filter Requests</h6>
                                                    <button type="button" class="btn btn-sm btn-light-danger" wire:click="resetFilters" data-kt-menu-dismiss="true">Reset</button>
                                                </div>
                                                <div class="px-5 pt-5 pb-2 menu-scroll">
                                                    <div class="mb-5">
                                                        <label class="form-label fw-semibold small">Election</label>
                                                        <select class="form-select form-select-solid" wire:model.live="electionId">
                                                            @foreach($elections as $e)
                                                                <option value="{{ $e->id }}">{{ $e->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="mb-5">
                                                        <label class="form-label fw-semibold small">Status</label>
                                                        <select class="form-select form-select-solid" wire:model.live="status">
                                                            <option value="">All</option>
                                                            <option value="pending">Pending</option>
                                                            <option value="in_progress">In Progress</option>
                                                            <option value="fulfilled">Fulfilled</option>
                                                            <option value="rejected">Rejected</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-5">
                                                        <label class="form-label fw-semibold small">Type</label>
                                                        <select class="form-select form-select-solid" wire:model.live="requestTypeId">
                                                            <option value="">All</option>
                                                            @foreach($requestTypes as $t)
                                                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="mb-5">
                                                        <label class="form-label fw-semibold small">Amount Range</label>
                                                        <div class="d-flex gap-2">
                                                            <input type="number" step="0.01" placeholder="Min" class="form-control form-control-solid" wire:model.live="amountMin" />
                                                            <input type="number" step="0.01" placeholder="Max" class="form-control form-control-solid" wire:model.live="amountMax" />
                                                        </div>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label fw-semibold small">Date Range</label>
                                                        <div class="row g-2">
                                                            <div class="col-6">
                                                                <input type="date" class="form-control form-control-solid" wire:model.live="dateFrom" />
                                                            </div>
                                                            <div class="col-6">
                                                                <input type="date" class="form-control form-control-solid" wire:model.live="dateTo" />
                                                            </div>
                                                        </div>
                                                        <div class="form-text">Created date</div>
                                                    </div>
                                                </div>
                                                <div class="p-5 border-top bg-light d-flex justify-content-end">
                                                    <button type="button" class="btn btn-primary" data-kt-menu-dismiss="true">Apply</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-0 px-0">
                                <div class="request-list hover-scroll-overlay-y" style="max-height: calc(100vh - 370px);">
                                    @forelse($requests as $r)
                                        @php $statusColor = match($r->status){'pending'=>'warning','in_progress'=>'info','fulfilled'=>'success','rejected'=>'danger', default=>'secondary'}; @endphp
                                        <div class="request-item px-6 py-5 border-bottom cursor-pointer position-relative {{ $activeRequest && $activeRequest->id===$r->id ? 'active' : '' }}" wire:click="openRequest('{{ $r->id }}')" wire:key="req-{{ $r->id }}">
                                            <div class="d-flex align-items-start">
                                                <div class="symbol symbol-45px symbol-circle me-4 flex-shrink-0">
                                                    @if($r->voter->profile_picture)
                                                        <img src="{{ asset('storage/'.$r->voter->profile_picture) }}" alt="{{ $r->voter->name }}" />
                                                    @else
                                                        <span class="symbol-label bg-light-primary text-primary fw-bold">{{ strtoupper(mb_substr($r->voter->name,0,1)) }}</span>
                                                    @endif
                                                </div>
                                                <div class="flex-grow-1 pe-2">
                                                    <div class="d-flex align-items-center mb-1 gap-2 flex-wrap">
                                                        <span class="fw-bold text-gray-900">{{ $r->request_number }}</span>
                                                        <span class="badge badge-light-{{ $statusColor }} text-capitalize">{{ str_replace('_',' ',$r->status) }}</span>
                                                        <span class="text-muted fs-8 ms-auto">{{ $r->created_at?->diffForHumans() }}</span>
                                                    </div>
                                                    <div class="d-flex flex-wrap align-items-center gap-3 mb-1">
                                                        <span class="badge badge-light-primary fw-semibold">{{ $r->type->name ?? '—' }}</span>
                                                        <span class="text-gray-700 fw-semibold">{{ $r->amount !== null ? number_format($r->amount,2) : '—' }}</span>
                                                        <span class="text-gray-500 fs-8">{{ $r->voter->name }}</span>
                                                    </div>
                                                    @if($r->note)
                                                        <div class="text-gray-600 fs-8 line-clamp-2">{{ Str::limit($r->note,110) }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-center text-muted py-10">No requests found.</div>
                                    @endforelse
                                </div>
                                <div class="p-5">{{ $requests->links('vendor.pagination.new') }}</div>
                            </div>
                        </div>
                    </div>
                    <!-- Right: Detail -->
                    <div class="flex-grow-1">
                        <div class="card shadow-sm h-100" id="requestDetailCard">
                            <div class="card-header border-0 pt-6 pb-4">
                                <div class="d-flex flex-column flex-sm-row align-items-sm-center w-100">
                                    <div class="flex-grow-1">
                                        <h3 class="card-title fw-bold mb-0">Request Detail</h3>
                                        @if($activeRequest)
                                            <div class="text-muted fs-8 mt-1">{{ $activeRequest->request_number }}</div>
                                        @endif
                                    </div>
                                    @if($activeRequest)
                                        <div class="mt-4 mt-sm-0 d-flex gap-2">
                                            <button type="button" class="btn btn-light btn-sm" wire:click="closeDrawer">Close</button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                @if($activeRequest)
                                    @php $statusColor = match($activeRequest->status){'pending'=>'warning','in_progress'=>'info','fulfilled'=>'success','rejected'=>'danger', default=>'secondary'}; @endphp
                                    <!-- Summary -->
                                    <div class="border rounded p-5 mb-8 bg-light-primary bg-opacity-25">
                                        <div class="d-flex flex-wrap align-items-center gap-4">
                                            <div class="symbol symbol-60px symbol-circle">
                                                @if($activeRequest->voter->profile_picture)
                                                    <img src="{{ asset('storage/'.$activeRequest->voter->profile_picture) }}" alt="{{ $activeRequest->voter->name }}" />
                                                @else
                                                    <span class="symbol-label bg-light-primary text-primary fw-bold fs-3">{{ strtoupper(mb_substr($activeRequest->voter->name,0,1)) }}</span>
                                                @endif
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                                                    <span class="fw-bold fs-5 text-gray-900">{{ $activeRequest->voter->name }}</span>
                                                    <span class="badge badge-light-{{ $statusColor }} text-capitalize">{{ str_replace('_',' ',$activeRequest->status) }}</span>
                                                    <span class="badge badge-light-primary">{{ $activeRequest->type->name ?? '—' }}</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2 small text-gray-700">
                                                    <div class="d-flex flex-wrap gap-6">
                                                        <span>Amount: <strong class="text-gray-800">{{ $activeRequest->amount !== null ? number_format($activeRequest->amount,2) : '—' }}</strong></span>
                                                        <span>Created: <strong class="text-gray-800">{{ $activeRequest->created_at?->format('Y-m-d H:i') }}</strong></span>
                                                        <span>ID Card: <strong class="text-gray-800">{{ $activeRequest->voter->id_card_number ?: '—' }}</strong></span>
                                                    </div>
                                                    <!-- Phones -->
                                                    <div>
                                                        <span class="fw-semibold me-2">Phones:</span>
                                                        @php $phones = $activeRequest->voter->phones ?? []; @endphp
                                                        @if(!empty($phones))
                                                            @foreach(array_slice($phones,0,4) as $ph)
                                                                <span class="badge badge-light-dark fw-semibold me-1 mb-1">{{ $ph }}</span>
                                                            @endforeach
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </div>
                                                    <!-- Addresses -->
                                                    <div class="d-flex flex-column gap-1">
                                                        <div>
                                                            <span class="fw-semibold me-1">Permanent:</span>
                                                            <span class="text-gray-800">{{ $activeRequest->voter->permanentLocationString() }}</span>
                                                        </div>
                                                        <div>
                                                            <span class="fw-semibold me-1">Current:</span>
                                                            <span class="text-gray-800">{{ $activeRequest->voter->currentLocationString() }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="dropdown">
                                                <a href="#" class="btn btn-sm btn-light" data-bs-toggle="dropdown">Status</a>
                                                <div class="dropdown-menu dropdown-menu-sm">
                                                    <div class="px-3 py-2 text-muted fw-bold fs-8">Change status</div>
                                                    @foreach(['pending'=>'warning','in_progress'=>'info','fulfilled'=>'success','rejected'=>'danger'] as $sKey=>$sColor)
                                                        <a href="#" class="dropdown-item d-flex align-items-center {{ $sKey===$activeRequest->status ? 'active' : '' }}" wire:click.prevent="updateStatusInline('{{ $activeRequest->id }}','{{ $sKey }}')">
                                                            <span class="badge badge-light-{{ $sColor }} me-2"></span>{{ ucfirst(str_replace('_',' ',$sKey)) }}
                                                        </a>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Note -->
                                    <div class="mb-8">
                                        <h5 class="fw-bold mb-3">Request Note</h5>
                                        <div class="bg-light p-5 rounded fw-semibold text-gray-700 fs-7">{{ $activeRequest->note ?: '—' }}</div>
                                    </div>
                                    <!-- Add Response Form (moved above responses) -->
                                    <div class="mb-10" id="add_response_section">
                                        <div class="d-flex align-items-center mb-4">
                                            <h5 class="fw-bold mb-0">Add Response</h5>
                                            <span class="ms-2 badge badge-light-primary">New</span>
                                        </div>
                                        <div class="mb-4">
                                            <label class="form-label required">Response</label>
                                            <textarea rows="4" class="form-control form-control-solid" wire:model.debounce.500ms="response_text" placeholder="Enter response..."></textarea>
                                            @error('response_text')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="row g-5 mb-5">
                                            <div class="col-sm-6">
                                                <label class="form-label">Update Status</label>
                                                <select class="form-select form-select-solid" wire:model="response_status_after">
                                                    <option value="">Keep current ({{ ucfirst(str_replace('_',' ',$activeRequest->status)) }})</option>
                                                    <option value="pending">Pending</option>
                                                    <option value="in_progress">In Progress</option>
                                                    <option value="fulfilled">Fulfilled</option>
                                                    <option value="rejected">Rejected</option>
                                                </select>
                                                @error('response_status_after')<div class="text-danger small">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-end gap-3">
                                            <button class="btn btn-light" type="button" wire:click="closeDrawer">Cancel</button>
                                            <button class="btn btn-primary" type="button" wire:click="saveResponse">
                                                <span wire:loading.remove wire:target="saveResponse">Submit</span>
                                                <span wire:loading wire:target="saveResponse" class="spinner-border spinner-border-sm"></span>
                                            </button>
                                        </div>
                                    </div>
                                    <!-- Directory Notes -->
                                    <div class="mb-10">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <h5 class="fw-bold mb-0">Directory Notes</h5>
                                            <span class="badge badge-light-secondary">{{ $activeRequest->voter->voterNotes->count() }}</span>
                                        </div>
                                        @if($activeRequest->voter->voterNotes->count())
                                            <div class="vstack gap-4">
                                                @foreach($activeRequest->voter->voterNotes->sortByDesc('created_at') as $vn)
                                                    <div class="border rounded p-4 bg-light">
                                                        <div class="d-flex align-items-start gap-3">
                                                            <div class="symbol symbol-35px symbol-circle bg-white shadow-sm flex-shrink-0">
                                                                <span class="symbol-label text-primary fw-bold small">{{ strtoupper(mb_substr($vn->author->name ?? 'U',0,1)) }}</span>
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <div class="d-flex flex-wrap align-items-center mb-1 gap-2">
                                                                    <span class="fw-semibold text-gray-800">{{ $vn->author->name ?? '—' }}</span>
                                                                    <span class="text-muted fs-8 ms-auto" title="{{ $vn->created_at }}">{{ $vn->created_at?->diffForHumans() }}</span>
                                                                </div>
                                                                <div class="text-gray-700 fs-7">{{ $vn->note }}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-muted small">No notes for this directory.</div>
                                        @endif
                                    </div>
                                    <!-- Responses List (restyled) -->
                                    <div class="mb-5" id="responses_section">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <h5 class="fw-bold mb-0">Responses</h5>
                                            <span class="badge badge-light-secondary">{{ $activeRequest->responses->count() }}</span>
                                        </div>
                                        @if($activeRequest->responses->count())
                                            <div class="vstack gap-5">
                                                @foreach($activeRequest->responses->sortByDesc('created_at') as $resp)
                                                    @php
                                                        $respStatus = $resp->status_after ?: $activeRequest->status;
                                                        $respColor = match($respStatus){'pending'=>'warning','in_progress'=>'info','fulfilled'=>'success','rejected'=>'danger', default=>'secondary'};
                                                        $initial = strtoupper(mb_substr($resp->responder->name ?? 'U',0,1));
                                                    @endphp
                                                    <div class="response-item border rounded p-5 position-relative">
                                                        <div class="d-flex align-items-start">
                                                            <div class="symbol symbol-40px symbol-circle me-4 flex-shrink-0 bg-light">
                                                                <span class="symbol-label fw-bold text-primary">{{ $initial }}</span>
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <div class="d-flex flex-wrap align-items-center mb-1 gap-2">
                                                                    <span class="fw-semibold text-gray-800">{{ $resp->responder->name ?? '—' }}</span>
                                                                    @if($resp->status_after)
                                                                        <span class="badge badge-light-{{ $respColor }}">{{ ucfirst(str_replace('_',' ',$respStatus)) }}</span>
                                                                    @endif
                                                                    <span class="text-muted fs-8 ms-auto">{{ $resp->created_at?->diffForHumans() }}</span>
                                                                </div>
                                                                <div class="text-gray-700 fs-7">{{ $resp->response }}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-muted small">No responses yet.</div>
                                        @endif
                                    </div>
                                @else
                                    <div class="d-flex flex-column justify-content-center align-items-center text-center py-20">
                                        <i class="ki-duotone ki-message-text-2 fs-4x text-gray-300 mb-5"><span class="path1"></span><span class="path2"></span></i>
                                        <h5 class="text-gray-600 fw-semibold mb-2">Select a request</h5>
                                        <div class="text-muted fs-8">Choose a request from the left list to view its details.</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
#requestListCard .request-item{transition:background .15s ease, box-shadow .15s ease;}
#requestListCard .request-item:hover{background:#f9fafb;}
#requestListCard .request-item.active{background:var(--bs-light-primary);box-shadow:inset 3px 0 0 var(--bs-primary);}
.request-list{scrollbar-width:thin;}
.line-clamp-2{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
#requestListCard .card-header{overflow:visible;}
#requestListCard .menu-sub-dropdown{max-width:340px;width:100%;}
.filter-menu .menu-scroll{max-height:350px;overflow-y:auto;}
.response-item{background:#fff;transition:box-shadow .15s ease, border-color .15s ease;}
.response-item:hover{box-shadow:0 4px 12px rgba(0,0,0,.05);border-color:var(--bs-primary);} 
#responses_section .badge{font-weight:500;}
</style>
@endpush

@push('scripts')
<script>
window.addEventListener('response-saved', () => {
    if(window.Swal){
        Swal.fire({toast:true,position:'top-end',icon:'success',title:'Response saved',showConfirmButton:false,timer:1800});
    }
});
</script>
@endpush
