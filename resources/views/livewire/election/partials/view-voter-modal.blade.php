<div id="voter-modals-wrapper"> <!-- root wrapper -->
<!-- View Voter Modal Extracted Partial -->
<div class="modal fade" id="viewVoterModal" tabindex="-1" aria-hidden="true" wire:ignore.self data-livewire-modal="voter"> <!-- restored wire:ignore.self -->
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Voter Information</h5>
                <button type="button" class="btn btn-sm btn-icon btn-active-light-primary" data-bs-dismiss="modal" aria-label="Close" wire:click="closeViewVoter">
                    <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>
                </button>
            </div>
            <div class="modal-body">
                @if($viewingVoter)
                    <div wire:key="voter-modal-wrapper-{{ $viewingVoter->id }}-{{ $modalRefreshTick }}"> <!-- keyed wrapper to force full refresh -->
                    <!--begin::Voter Profile Card-->
                    <div class="card mb-5 mb-xl-10">
                        <div class="card-body pt-9 pb-0">
                            <!--begin::Details-->
                            <div class="d-flex flex-wrap flex-sm-nowrap mb-3">
                                <!--begin::Pic-->
                                <div class="me-7 mb-4">
                                    @php $g = strtolower($viewingVoter->gender ?? ''); $genderDot = ($g==='male' || $g==='m') ? 'primary' : (($g==='female' || $g==='f') ? 'pink' : 'secondary'); @endphp
                                    <div class="symbol symbol-100px symbol-lg-160px symbol-fixed position-relative">
                                        @if($viewingVoter->profile_picture)
                                            <img src="{{ asset('storage/'.$viewingVoter->profile_picture) }}" alt="{{ $viewingVoter->name }}" />
                                        @else
                                            <div class="symbol-label fs-2 fw-bolder text-primary">{{ Str::substr($viewingVoter->name,0,1) }}</div>
                                        @endif
                                        <div class="position-absolute translate-middle bottom-0 start-100 mb-6 rounded-circle border border-4 border-body h-20px w-20px bg-{{ $genderDot }}" title="Gender: {{ ucfirst($viewingVoter->gender ?? 'N/A') }}"></div>
                                    </div>
                                </div>
                                <!--end::Pic-->
                                <!--begin::Info-->
                                <div class="flex-grow-1">
                                    <!--begin::Title-->
                                    <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                                        <!--begin::User-->
                                        <div class="d-flex flex-column">
                                            <!--begin::Name-->
                                            <div class="d-flex align-items-center mb-2">
                                                <span class="text-gray-900 text-hover-primary fs-2 fw-bolder me-1">{{ ucwords(strtolower($viewingVoter->name)) }}</span>
                                                @php
                                                    $finalStatus = $final_status;
                                                    $pledgeColorMap = [
                                                        'strong_yes'=>'success','yes'=>'primary','neutral'=>'secondary','no'=>'warning','strong_no'=>'danger'
                                                    ];
                                                    $finalBadgeColor = $finalStatus ? ($pledgeColorMap[$finalStatus] ?? 'secondary') : 'light';
                                                    $finalLabel = $finalStatus ? strtoupper(str_replace('_',' ',$finalStatus)) : 'PENDING';
                                                @endphp
                                                <span class="badge badge-light-{{ $finalBadgeColor }} fw-bolder ms-2 fs-8 py-1 px-3">Final: {{ $finalLabel }}</span>
                                            </div>
                                            <!--end::Name-->
                                            <!--begin::Info-->
                                            <div class="d-flex flex-wrap fw-bold fs-6 mb-4 pe-2">
                                                <span class="d-flex align-items-center text-gray-400 me-5 mb-2">
                                                    <i class="ki-duotone ki-profile-circle fs-4 me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                                    ID: {{ $viewingVoter->id_card_number ?? '—' }}
                                                </span>
                                                <span class="d-flex align-items-center text-gray-400 me-5 mb-2">
                                                    <i class="ki-duotone ki-geolocation fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                                                    {{ optional($viewingVoter->island)->name }}
                                                </span>
                                                <span class="d-flex align-items-center text-gray-400 mb-2">
                                                    <i class="ki-duotone ki-sms fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                                                    {{ $viewingVoter->email ?? '—' }}
                                                </span>
                                            </div>
                                            <!--end::Info-->
                                        </div>
                                        <!--end::User-->
                                    </div>
                                    <!--end::Title-->
                                    <!--begin::Stats-->
                                    <div class="d-flex flex-wrap flex-stack">
                                        <div class="d-flex flex-column flex-grow-1 pe-8">
                                            <div class="d-flex flex-wrap">
                                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                                    <div class="d-flex align-items-center"><i class="ki-duotone ki-like fs-3 text-success me-2"><span class="path1"></span><span class="path2"></span></i><div class="fs-2 fw-bolder">{{ count($voterOpinions) }}</div></div>
                                                    <div class="fw-bold fs-6 text-gray-400">Opinions</div>
                                                </div>
                                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                                    <div class="d-flex align-items-center"><i class="ki-duotone ki-message-text-2 fs-3 text-primary me-2"><span class="path1"></span><span class="path2"></span></i><div class="fs-2 fw-bolder">{{ count($voterRequests) }}</div></div>
                                                    <div class="fw-bold fs-6 text-gray-400">Requests</div>
                                                </div>
                                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                                    <div class="d-flex align-items-center"><i class="ki-duotone ki-notepad fs-3 text-info me-2"><span class="path1"></span><span class="path2"></span></i><div class="fs-2 fw-bolder">{{ count($voterNotes) }}</div></div>
                                                    <div class="fw-bold fs-6 text-gray-400">Notes</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!--end::Stats-->
                                </div>
                                <!--end::Info-->
                            </div>
                            <!--end::Details-->
                            <!--begin::Navs-->
                            <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bolder">
                                <li class="nav-item mt-2"><a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $activeTab==='details' ? 'active' : '' }}" href="#" wire:click.prevent="setActiveTab('details')">Details</a></li>
                                <li class="nav-item mt-2"><a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $activeTab==='opinions' ? 'active' : '' }}" href="#" wire:click.prevent="setActiveTab('opinions')">Opinions</a></li>
                                <li class="nav-item mt-2"><a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $activeTab==='notes' ? 'active' : '' }}" href="#" wire:click.prevent="setActiveTab('notes')">Notes</a></li>
                                <li class="nav-item mt-2"><a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $activeTab==='requests' ? 'active' : '' }}" href="#" wire:click.prevent="setActiveTab('requests')">Requests</a></li>
                            </ul>
                            <!--end::Navs-->
                        </div>
                    </div>
                    <!--end::Voter Profile Card-->

                    <div class="tab-content" id="voterTabsContent">
                        <div class="tab-pane fade {{ $activeTab==='details' ? 'show active' : '' }}" id="voter_details_tab" role="tabpanel">
                            <!--begin::Details View-->
                            <div class="card mb-5 mb-xl-10">
                                <div class="card-header cursor-pointer">
                                    <div class="card-title m-0">
                                        <h3 class="fw-bolder m-0">Profile Details</h3>
                                    </div>
                                </div>
                                <div class="card-body p-9">
                                    <div class="row mb-7">
                                        <label class="col-lg-4 fw-bold text-muted">Full Name</label>
                                        <div class="col-lg-8"><span class="fw-bolder fs-6 text-gray-800">{{ ucwords(strtolower($viewingVoter->name)) }}</span></div>
                                    </div>
                                    <div class="row mb-7">
                                        <label class="col-lg-4 fw-bold text-muted">Contact Phone</label>
                                        <div class="col-lg-8 d-flex align-items-center">
                                            <span class="fw-bolder fs-6 text-gray-800 me-2">
                                                @if(is_array($viewingVoter->phones) && count($viewingVoter->phones) > 0)
                                                    {{ implode(', ', $viewingVoter->phones) }}
                                                @else — @endif
                                            </span>
                                        </div>
                                    </div>
                                    <div class="row mb-7">
                                        <label class="col-lg-4 fw-bold text-muted">Gender</label>
                                        <div class="col-lg-8"><span class="fw-bolder fs-6 text-gray-800">{{ $viewingVoter->gender ?? '—' }}</span></div>
                                    </div>
                                    <div class="row mb-7">
                                        <label class="col-lg-4 fw-bold text-muted">Date of Birth</label>
                                        <div class="col-lg-8"><span class="fw-bolder fs-6 text-gray-800">{{ $viewingVoter->date_of_birth ? \Carbon\Carbon::parse($viewingVoter->date_of_birth)->format('d M Y') : '—' }}</span></div>
                                    </div>
                                    <div class="row mb-7">
                                        <label class="col-lg-4 fw-bold text-muted">Party</label>
                                        <div class="col-lg-8"><span class="fw-bolder fs-6 text-gray-800">{{ optional($viewingVoter->party)->name ?? '—' }}</span></div>
                                    </div>
                                    <div class="row mb-7">
                                        <label class="col-lg-4 fw-bold text-muted">Country</label>
                                        <div class="col-lg-8"><span class="fw-bolder fs-6 text-gray-800">{{ optional($viewingVoter->country)->name ?? '—' }}</span></div>
                                    </div>
                                    <div class="row mb-10">
                                        <label class="col-lg-4 fw-bold text-muted">Address</label>
                                        <div class="col-lg-8">
                                            <span class="fw-bolder fs-6 text-gray-800">{{ optional($viewingVoter->property)->name ?? '' }}</span>
                                            <div class="text-gray-600">{{ $viewingVoter->street_address ?? '' }}</div>
                                            <div class="text-gray-600">{{ optional($viewingVoter->island)->name }}, {{ optional(optional($viewingVoter->island)->atoll)->code }}</div>
                                        </div>
                                    </div>
                                    <div class="row mb-7">
                                        <label class="col-lg-4 fw-bold text-muted">Sub-Consite</label>
                                        <div class="col-lg-8"><span class="fw-bolder fs-6 text-gray-800">{{ optional($viewingVoter->subConsite)->code ?? '—' }} {{ optional($viewingVoter->subConsite)->name }}</span></div>
                                    </div>
                                    <!-- Pledges -->
                                    <div class="row mb-7">
                                        <label class="col-lg-4 fw-bold text-muted">Provisional Pledge</label>
                                        <div class="col-lg-8 d-flex align-items-center flex-wrap gap-3">
                                            @php $p = $provisional_status; $pLabel = $p ? strtoupper(str_replace('_',' ',$p)) : 'PENDING'; $colorMap=['strong_yes'=>'success','yes'=>'primary','neutral'=>'secondary','no'=>'warning','strong_no'=>'danger']; $pColor = $p? ($colorMap[$p]??'secondary') : 'light'; @endphp
                                            <span class="badge badge-{{ $pColor }} fw-bold px-4 py-2">{{ $pLabel }}</span>
                                            <button type="button" class="btn btn-sm btn-light-primary" wire:click="openPledgeModal">Change</button>
                                        </div>
                                    </div>
                                    <div class="row mb-7">
                                        <label class="col-lg-4 fw-bold text-muted">Final Pledge</label>
                                        <div class="col-lg-8 d-flex align-items-center flex-wrap gap-3">
                                            @php $f = $final_status; $fLabel = $f ? strtoupper(str_replace('_',' ',$f)) : 'PENDING'; $fColor = $f? ($colorMap[$f]??'secondary') : 'light'; @endphp
                                            <span class="badge badge-{{ $fColor }} fw-bold px-4 py-2">{{ $fLabel }}</span>
                                            <button type="button" class="btn btn-sm btn-light-success" wire:click="openPledgeModal">Change</button>
                                        </div>
                                    </div>
                                    <!-- End Pledges -->
                                    <div class="row mb-7">
                                        <label class="col-lg-4 fw-bold text-muted">Attempt</label>
                                        <div class="col-lg-8">
                                            @php $attemptStatus = $this->latestOpinionStatus; $attemptColor = match($attemptStatus){'success'=>'success','failed_attempt'=>'danger','follow_up'=>'warning', default=>'secondary'}; @endphp
                                            @if($attemptStatus)
                                                <span class="badge badge-light-{{ $attemptColor }} text-capitalize fw-bold">{{ str_replace('_',' ', $attemptStatus) }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--end::Details View-->
                        </div>
                        <div class="tab-pane fade {{ $activeTab==='opinions' ? 'show active' : '' }}" id="voter_opinions_tab" role="tabpanel">
                            <div class="d-flex flex-column flex-lg-row gap-5">
                                <!-- Master list -->
                                <div class="flex-lg-grow-1 w-100 w-lg-50">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h6 class="fw-bold mb-0">Opinions ({{ count($voterOpinions) }})</h6>
                                        <button type="button" class="btn btn-sm btn-primary" wire:click="openAddOpinion">
                                            <i class="ki-duotone ki-plus fs-2 me-1"><span class="path1"></span><span class="path2"></span></i>Add
                                        </button>
                                    </div>
                                    <div class="card mb-0">
                                        <div class="card-body p-3 pt-4">
                                            <div class="mh-350px hover-scroll-overlay-y pe-2">
                                                @forelse($voterOpinions as $op)
                                                    @php
                                                        $rating = $op->rating;
                                                        $typeName = $op->type->name ?? '—';
                                                        $author = $op->takenBy->name ?? '—';
                                                        $time = $op->created_at?->diffForHumans();
                                                        $active = $selectedOpinionId === $op->id;
                                                        $statusColor = match($op->status){'success'=>'success','failed_attempt'=>'danger','follow_up'=>'warning', default=>'secondary'};
                                                    @endphp
                                                    <div class="opinion-item card card-bordered mb-4 cursor-pointer {{ $active ? 'opinion-item-active' : '' }}" role="button" wire:click="selectOpinion('{{ $op->id }}')">
                                                        <div class="card-body p-4 py-3">
                                                            <div class="d-flex">
                                                                <div class="opinion-accent me-4"></div>
                                                                <div class="flex-grow-1">
                                                                    <div class="d-flex flex-wrap align-items-center mb-1 gap-2">
                                                                        <span class="badge badge-light-primary fw-semibold">{{ $typeName }}</span>
                                                                        @if(!is_null($rating))
                                                                            <span class="badge badge-{{ $ratingColors[$rating] ?? 'light' }} fw-semibold">{{ $ratingLabels[$rating] ?? ($rating.'/5') }}</span>
                                                                        @else
                                                                            <span class="badge badge-light fs-8 text-muted">No Rating</span>
                                                                        @endif
                                                                        <span class="text-muted fs-8 ms-auto">{{ $time }}</span>
                                                                    </div>
                                                                    <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                                                                        <span class="text-gray-500 fs-8">By {{ $author }}</span>
                                                                        <span class="badge badge-light-{{ $statusColor }} fw-semibold text-capitalize">{{ str_replace('_',' ',$op->status) }}</span>
                                                                    </div>
                                                                    @if($op->note)
                                                                        <div class="text-gray-700 fs-7 text-break opinion-note-clamp">{{ Str::limit($op->note,140) }}</div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="text-center text-muted py-10">No opinions recorded.</div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Detail panel -->
                                <div class="w-100 w-lg-50 mt-5 mt-lg-0">
                                    <div class="card h-100">
                                        <div class="card-header border-0 pb-0">
                                            <h6 class="card-title fw-bold mb-0">Opinion Detail</h6>
                                        </div>
                                        <div class="card-body pt-4">
                                            @if($selectedOpinion)
                                                <div class="mb-5">
                                                    <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                                                        <span class="badge badge-light-primary fw-semibold">{{ $selectedOpinion->type->name ?? '—' }}</span>
                                                        @if(!is_null($selectedOpinion->rating))
                                                            <span class="badge badge-{{ $ratingColors[$selectedOpinion->rating] ?? 'light' }} fw-semibold">{{ $ratingLabels[$selectedOpinion->rating] ?? ($selectedOpinion->rating.'/5') }}</span>
                                                        @else
                                                            <span class="badge badge-light fs-8 text-muted">No Rating</span>
                                                        @endif
                                                        <span class="text-muted fs-8 ms-auto">{{ $selectedOpinion->created_at?->diffForHumans() }}</span>
                                                    </div>
                                                    <div class="row g-4 mb-2">
                                                        <div class="col-6">
                                                            <label class="fw-semibold text-muted small d-block mb-1">Recorded By</label>
                                                            <div class="fw-bold text-gray-800">{{ $selectedOpinion->takenBy->name ?? '—' }}</div>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="fw-semibold text-muted small d-block mb-1">Recorded At</label>
                                                            <div class="fw-bold text-gray-800">{{ $selectedOpinion->created_at?->format('Y-m-d H:i') }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mb-4">
                                                    <label class="fw-semibold text-muted small mb-2">Note</label>
                                                    <div class="bg-light p-4 rounded text-gray-700 fs-7">{{ $selectedOpinion->note ?: '—' }}</div>
                                                </div>
                                            @else
                                                <div class="text-center text-muted py-20">Select an opinion to view details.</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade {{ $activeTab==='notes' ? 'show active' : '' }}" id="voter_notes_tab" role="tabpanel">
                            <!-- Composer -->
                            <div class="card card-bordered mb-10">
                                <div class="card-body p-0 pt-5 px-5 pb-2">
                                    <div class="mb-0 position-relative">
                                        <textarea wire:model.defer="note_text" class="form-control form-control-solid placeholder-gray-600 fw-bold fs-4 ps-9 pt-7" rows="6" placeholder="Add Note"></textarea>
                                        @error('note_text')<div class="text-danger small mt-2 ms-2">{{ $message }}</div>@enderror
                                        <button type="button" wire:click="saveNote" wire:loading.attr="disabled" class="btn btn-primary mt-n20 mb-20 position-relative float-end me-7">
                                            <span wire:loading.remove wire:target="saveNote">Send</span>
                                            <span wire:loading wire:target="saveNote" class="spinner-border spinner-border-sm"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <!-- Notes Thread -->
                            <div class="mb-6">
                                @php $shown = 0; @endphp
                                @forelse($voterNotes as $n)
                                    @if($shown < $notesLimit)
                                        @php
                                            $authorName = $n->author->name ?? 'User';
                                            $initial = strtoupper(mb_substr($authorName,0,1));
                                            $palette = ['primary','success','info','warning','danger'];
                                            $color = $palette[$loop->index % count($palette)];
                                        @endphp
                                        <div class="mb-9">
                                            <div class="card card-bordered w-100">
                                                <div class="card-body p-6">
                                                    <div class="w-100 d-flex flex-stack mb-8">
                                                        <div class="d-flex align-items-center">
                                                            <div class="symbol symbol-50px me-5">
                                                                <div class="symbol-label fs-2 fw-bold bg-light-{{ $color }} text-{{ $color }}">{{ $initial }}</div>
                                                            </div>
                                                            <div class="d-flex flex-column fw-semibold fs-6 text-gray-600 text-dark">
                                                                <div class="d-flex align-items-center flex-wrap">
                                                                    <span class="text-gray-800 fw-bold text-hover-primary fs-5 me-3">{{ $authorName }}</span>
                                                                </div>
                                                                <span class="text-muted fw-semibold fs-7">{{ $n->created_at?->diffForHumans() }}</span>
                                                            </div>
                                                        </div>
                                                     
                                                    </div>
                                                    <p class="fw-normal fs-6 text-gray-700 m-0">{{ $n->note }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        @php $shown++; @endphp
                                    @endif
                                @empty
                                    <div class="text-center text-muted py-10">No notes recorded.</div>
                                @endforelse

                                @if(count($voterNotes) > $notesLimit)
                                    <div class="text-center mb-5">
                                        <button type="button" wire:click="loadMoreNotes" class="btn btn-light-primary">
                                            <span wire:loading.remove wire:target="loadMoreNotes">Load More</span>
                                            <span wire:loading wire:target="loadMoreNotes" class="spinner-border spinner-border-sm"></span>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="tab-pane fade {{ $activeTab==='requests' ? 'show active' : '' }}" id="voter_requests_tab" role="tabpanel">
                            <div class="d-flex flex-column flex-lg-row gap-5">
                                <div class="flex-lg-grow-1 w-100 w-lg-50">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h6 class="fw-bold mb-0">Requests ({{ count($voterRequests) }})</h6>
                                        <button type="button" class="btn btn-sm btn-primary" wire:click="openAddRequest"><i class="ki-duotone ki-plus fs-2 me-1"><span class="path1"></span><span class="path2"></span></i>Add</button>
                                    </div>
                                    <div class="card mb-0">
                                        <div class="card-body p-4 pt-3">
                                            <div class="mh-350px hover-scroll-overlay-y pe-2">
                                                @forelse($voterRequests as $req)
                                                    @php
                                                        $statusColor = match($req->status){'pending'=>'warning','in_progress'=>'info','fulfilled'=>'success','rejected'=>'danger', default=>'secondary'};
                                                    @endphp
                                                    <div class="card card-bordered request-item mb-4 cursor-pointer {{ $selectedRequestId === $req->id ? 'request-item-active' : '' }}" wire:click="selectRequest('{{ $req->id }}')">
                                                        <div class="card-body p-4 py-3">
                                                            <div class="d-flex">
                                                                <div class="request-status-bar bg-{{ $statusColor }} me-4"></div>
                                                                <div class="flex-grow-1">
                                                                    <div class="d-flex flex-wrap align-items-center mb-1 gap-2">
                                                                        <span class="fw-bold text-gray-900">{{ $req->request_number }}</span>
                                                                        <span class="badge badge-light-{{ $statusColor }} fw-semibold text-capitalize">{{ str_replace('_',' ',$req->status) }}</span>
                                                                        <span class="text-muted fs-8 ms-auto">{{ $req->created_at?->diffForHumans() }}</span>
                                                                    </div>
                                                                    <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                                                                        <span class="badge badge-light-primary fw-semibold">{{ $req->type->name ?? '—' }}</span>
                                                                        <span class="text-gray-600 fw-semibold">Amount: <span class="text-gray-800">{{ $req->amount !== null ? number_format($req->amount,2) : '—' }}</span></span>
                                                                        <span class="text-gray-500 fs-8">By {{ $req->author->name ?? '—' }}</span>
                                                                    </div>
                                                                    @if($req->note)
                                                                        <div class="text-gray-700 fs-7 text-break note-text-clamp">{{ Str::limit($req->note,120) }}</div>
                                                                    @endif
                                                                    <div class="mt-3">
                                                                        <button type="button" class="btn btn-light btn-sm" wire:click.stop="openRequestDetail('{{ $req->id }}')">Open Detail</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="text-center text-muted py-10">No requests recorded.</div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Inline Detail Panel -->
                                <div class="w-100 w-lg-50 mt-5 mt-lg-0">
                                    <div class="card h-100">
                                        <div class="card-header border-0 pb-0 d-flex align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="card-title fw-bold mb-0">Request Detail</h6>
                                                @if($selectedRequest)
                                                    <div class="text-muted fs-8">{{ $selectedRequest->request_number }}</div>
                                                @endif
                                            </div>
                                            @if($selectedRequest)
                                                <button type="button" class="btn btn-sm btn-light d-inline d-lg-none" wire:click="closeRequestDetail">Close</button>
                                            @endif
                                        </div>
                                        <div class="card-body pt-4">
                                            @if($selectedRequest)
                                                @php $statusColor = match($selectedRequest->status){'pending'=>'warning','in_progress'=>'info','fulfilled'=>'success','rejected'=>'danger', default=>'secondary'}; @endphp
                                                <div class="mb-5">
                                                    <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                                                        <span class="badge badge-light-{{ $statusColor }} text-capitalize">{{ str_replace('_',' ',$selectedRequest->status) }}</span>
                                                        <span class="badge badge-light-primary">{{ $selectedRequest->type->name ?? '—' }}</span>
                                                        <span class="text-muted fs-8">{{ $selectedRequest->created_at?->diffForHumans() }}</span>
                                                    </div>
                                                    <div class="fw-bold text-gray-800">Amount: {{ $selectedRequest->amount !== null ? number_format($selectedRequest->amount,2) : '—' }}</div>
                                                    <div class="text-gray-500 fs-7 mt-1">By {{ $selectedRequest->author->name ?? '—' }}</div>
                                                </div>
                                                <div class="mb-6">
                                                    <label class="fw-semibold text-muted small mb-2">Note</label>
                                                    <div class="bg-light p-3 rounded text-gray-700 fs-7">{{ $selectedRequest->note ?: '—' }}</div>
                                                </div>
                                                <div class="mb-4">
                                                    <h6 class="fw-bold mb-3">Responses ({{ $selectedRequest->responses->count() }})</h6>
                                                    @if($selectedRequest->responses->count())
                                                        <div class="vstack gap-4">
                                                            @foreach($selectedRequest->responses->sortByDesc('created_at') as $resp)
                                                                @php
                                                                    $respStatus = $resp->status_after ?: $selectedRequest->status;
                                                                    $respColor = match($respStatus){'pending'=>'warning','in_progress'=>'info','fulfilled'=>'success','rejected'=>'danger', default=>'secondary'};
                                                                    $responderName = $resp->responder->name ?? '—';
                                                                    $initial = strtoupper(mb_substr($responderName,0,1));
                                                                @endphp
                                                                <div class="voter-response-item border rounded p-4 position-relative">
                                                                    <div class="d-flex align-items-start">
                                                                        <div class="symbol symbol-40px symbol-circle me-4 flex-shrink-0 bg-light">
                                                                            <span class="symbol-label fw-bold text-primary">{{ $initial }}</span>
                                                                        </div>
                                                                        <div class="flex-grow-1">
                                                                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                                                                <span class="fw-semibold text-gray-800">{{ $responderName }}</span>
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
                                                <div class="text-center text-muted py-20">Select a request to view details.</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div> <!-- end keyed wrapper -->
                @else
                    <div class="text-center text-muted">No voter selected.</div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal" wire:click="closeViewVoter">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Nested Add Opinion Modal (shows above the voter modal) -->
<div class="modal fade" id="addOpinionModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Opinion</h5>
                <button type="button" class="btn btn-sm btn-icon btn-active-light-primary" data-bs-dismiss="modal" aria-label="Close" wire:click="closeAddOpinion">
                    <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>
                </button>
            </div>
            <div class="modal-body">
                @if($viewingVoter)
                    <div class="mb-4">
                        <label class="form-label required">Type</label>
                        <select class="form-select" wire:model="opinion_type_id">
                            <option value="">Select type...</option>
                            @foreach($opinionTypes as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                        @error('opinion_type_id')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label required">Status</label>
                        <div class="btn-group w-100 flex-wrap" role="group">
                            <button type="button" class="btn btn-sm {{ $opinion_status==='success' ? 'btn-success' : 'btn-light-success' }}" wire:click="$set('opinion_status','success')">
                                <i class="ki-duotone ki-check fs-2 me-1"><span class="path1"></span><span class="path2"></span></i>Success
                            </button>
                            <button type="button" class="btn btn-sm {{ $opinion_status==='failed_attempt' ? 'btn-danger' : 'btn-light-danger' }}" wire:click="$set('opinion_status','failed_attempt')">
                                <i class="ki-duotone ki-cross-circle fs-2 me-1"><span class="path1"></span><span class="path2"></span></i>Failed Attempt
                            </button>
                            <button type="button" class="btn btn-sm {{ $opinion_status==='follow_up' ? 'btn-warning' : 'btn-light-warning' }}" wire:click="$set('opinion_status','follow_up')">
                                <i class="ki-duotone ki-time fs-2 me-1"><span class="path1"></span><span class="path2"></span></i>Follow Up
                            </button>
                        </div>
                        @error('opinion_status')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label required">Rating</label>
                        <div class="d-flex flex-column gap-2">
                            @foreach([5,4,3,2,1] as $r)
                                @php $active = (int)$opinion_rating === (int)$r; @endphp
                                <button type="button" class="btn btn-sm d-flex justify-content-between align-items-center {{ $active ? 'btn-'.($ratingColors[$r]??'light') : 'btn-light' }}" wire:click="setOpinionRating({{ $r }})">
                                    <span class="fw-semibold">{{ $ratingLabels[$r] ?? $r }}</span>
                                    @if($active)
                                        <i class="ki-duotone ki-check fs-2"><span class="path1"></span><span class="path2"></span></i>
                                    @endif
                                </button>
                            @endforeach
                            <button type="button" class="btn btn-sm btn-light" wire:click="setOpinionRating(null)">Clear</button>
                        </div>
                        @error('opinion_rating')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Note</label>
                        <textarea class="form-control" rows="4" wire:model.defer="opinion_note" placeholder="Enter note (optional)"></textarea>
                        @error('opinion_note')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" wire:click="closeAddOpinion">Cancel</button>
                <button type="button" class="btn btn-primary" wire:click="saveOpinion" @disabled(!$viewingVoter)>Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Request Modal -->
<div class="modal fade" id="addRequestModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Request</h5>
                <button type="button" class="btn btn-sm btn-icon btn-active-light-primary" data-bs-dismiss="modal" aria-label="Close" wire:click="closeAddRequest">
                    <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>
                </button>
            </div>
            <div class="modal-body">
                @if($viewingVoter)
                    <div class="mb-4">
                        <label class="form-label required">Type</label>
                        <select class="form-select" wire:model="request_type_id">
                            <option value="">Select type...</option>
                            @foreach($requestTypes as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                        @error('request_type_id')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Amount (Optional)</label>
                        <input type="number" step="0.01" class="form-control" wire:model.defer="request_amount" placeholder="0.00" />
                        @error('request_amount')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Note</label>
                        <textarea class="form-control" rows="4" wire:model.defer="request_note" placeholder="Enter note (optional)"></textarea>
                        @error('request_note')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" wire:click="closeAddRequest">Cancel</button>
                <button type="button" class="btn btn-primary" wire:click="saveRequest" @disabled(!$viewingVoter)>Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Note Modal -->
<div class="modal fade" id="addNoteModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Note</h5>
                <button type="button" class="btn btn-sm btn-icon btn-active-light-primary" data-bs-dismiss="modal" aria-label="Close" wire:click="closeAddNote">
                    <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>
                </button>
            </div>
            <div class="modal-body">
                @if($viewingVoter)
                    <div class="mb-4">
                        <label class="form-label required">Note</label>
                        <textarea class="form-control" rows="6" wire:model.defer="note_text" placeholder="Enter note..."></textarea>
                        @error('note_text')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" wire:click="closeAddNote">Cancel</button>
                <button type="button" class="btn btn-primary" wire:click="saveNote" @disabled(!$viewingVoter)>Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Pledge Selection Modal -->
<div class="modal fade" id="pledgeModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Set Voter Pledges</h5>
        <button type="button" class="btn btn-sm btn-icon btn-active-light-primary" data-bs-dismiss="modal" aria-label="Close">
          <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>
        </button>
      </div>
      <div class="modal-body py-6">
        <div class="row g-6">
          <div class="col-12 col-md-6">
            <div class="card h-100 shadow-sm border-dashed">
              <div class="card-header border-0 pb-0">
                <h6 class="card-title fw-bold mb-0">Provisional</h6>
              </div>
              <div class="card-body pt-4">
                <div class="d-flex flex-column gap-3">
                  @php $pSel = $provisional_status; @endphp
                  @foreach(['strong_yes'=>'Strong Yes','yes'=>'Yes','neutral'=>'Neutral','no'=>'No','strong_no'=>'Strong No'] as $k=>$lbl)
                    @php $cMap=['strong_yes'=>'success','yes'=>'primary','neutral'=>'secondary','no'=>'warning','strong_no'=>'danger']; $active = $pSel===$k; @endphp
                    <div class="d-flex align-items-center justify-content-between p-3 rounded border {{ $active? 'border-'.$cMap[$k].' bg-light-'.$cMap[$k] : 'border-dashed' }}">
                      <div class="d-flex align-items-center gap-3">
                        <div class="symbol symbol-30px symbol-circle bg-light-{{ $cMap[$k] }}">
                          <span class="symbol-label fw-bold text-{{ $cMap[$k] }} text-uppercase">{{ substr($lbl,0,1) }}</span>
                        </div>
                        <span class="fw-semibold text-gray-800">{{ $lbl }}</span>
                      </div>
                      <button type="button" wire:click="setProvisionalPledge('{{ $k }}')" class="btn btn-sm {{ $active? 'btn-'.$cMap[$k] : 'btn-light' }}">{{ $active? 'Selected' : 'Select' }}</button>
                    </div>
                  @endforeach
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-6">
            <div class="card h-100 shadow-sm border-dashed">
              <div class="card-header border-0 pb-0">
                <h6 class="card-title fw-bold mb-0">Final</h6>
              </div>
              <div class="card-body pt-4">
                <div class="d-flex flex-column gap-3">
                  @php $fSel = $final_status; @endphp
                  @foreach(['strong_yes'=>'Strong Yes','yes'=>'Yes','neutral'=>'Neutral','no'=>'No','strong_no'=>'Strong No'] as $k=>$lbl)
                    @php $cMap=['strong_yes'=>'success','yes'=>'primary','neutral'=>'secondary','no'=>'warning','strong_no'=>'danger']; $active = $fSel===$k; @endphp
                    <div class="d-flex align-items-center justify-content-between p-3 rounded border {{ $active? 'border-'.$cMap[$k].' bg-light-'.$cMap[$k] : 'border-dashed' }}">
                      <div class="d-flex align-items-center gap-3">
                        <div class="symbol symbol-30px symbol-circle bg-light-{{ $cMap[$k] }}">
                          <span class="symbol-label fw-bold text-{{ $cMap[$k] }} text-uppercase">{{ substr($lbl,0,1) }}</span>
                        </div>
                        <span class="fw-semibold text-gray-800">{{ $lbl }}</span>
                      </div>
                      <button type="button" wire:click="setFinalPledge('{{ $k }}')" class="btn btn-sm {{ $active? 'btn-'.$cMap[$k] : 'btn-light' }}">{{ $active? 'Selected' : 'Select' }}</button>
                    </div>
                  @endforeach
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
          <span class="badge badge-light-primary">Prov: {{ $pSel ? strtoupper(str_replace('_',' ',$pSel)) : 'PENDING' }}</span>
          <span class="badge badge-light-success">Final: {{ $fSel ? strtoupper(str_replace('_',' ',$fSel)) : 'PENDING' }}</span>
        </div>
        <button type="button" class="btn btn-primary" wire:click="closePledgeModal">Done</button>
      </div>
    </div>
  </div>
</div>
<!-- End Pledge Selection Modal -->

@push('scripts')
<script>
(function(){
    if(typeof bootstrap === 'undefined'){ console.warn('[VoterModal] Bootstrap not loaded'); return; }
    const mainModalEl = document.getElementById('viewVoterModal');
    const modalConfig = {
        addOpinionModal: { show: 'show-add-opinion-modal', hide: 'hide-add-opinion-modal', saved: 'opinion-saved' },
        addRequestModal: { show: 'show-add-request-modal', hide: 'hide-add-request-modal', saved: 'request-saved' },
        addNoteModal:    { show: 'show-add-note-modal',    hide: 'hide-add-note-modal',    saved: 'note-saved' }
    };

    let mainModalInstance = null;
    const nestedInstances = {};

    function initInstance(el, opts){
        const existing = bootstrap.Modal.getInstance(el);
        return existing || new bootstrap.Modal(el, opts);
    }

    function cleanupBackdrops(){
        setTimeout(()=>{
            const openModals = document.querySelectorAll('.modal.show');
            const backdrops = Array.from(document.querySelectorAll('.modal-backdrop'));
            // Keep only one backdrop if at least one modal open; else remove all
            if(openModals.length){
                backdrops.slice(1).forEach(b=>b.remove());
                document.body.classList.add('modal-open');
            }else{
                backdrops.forEach(b=>b.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow='';
                document.body.style.paddingRight='';
            }
        },320);
    }

    // Main modal events
    window.addEventListener('show-view-voter-modal', ()=>{
        if(!mainModalInstance){ mainModalInstance = initInstance(mainModalEl,{backdrop:'static'}); }
        mainModalInstance.show();
    });
    window.addEventListener('hide-view-voter-modal', ()=>{
        const inst = bootstrap.Modal.getInstance(mainModalEl);
        if(inst) inst.hide();
    });
    mainModalEl.addEventListener('hidden.bs.modal', cleanupBackdrops);

    // Nested modals
    Object.keys(modalConfig).forEach(id => {
        const cfg = modalConfig[id];
        const el = document.getElementById(id);
        if(!el) return;
        nestedInstances[id] = initInstance(el,{backdrop:'static'});

        window.addEventListener(cfg.show, ()=>{ nestedInstances[id].show(); });
        window.addEventListener(cfg.hide, ()=>{ nestedInstances[id].hide(); });
        el.addEventListener('hidden.bs.modal', cleanupBackdrops);
        window.addEventListener(cfg.saved, ()=>{ nestedInstances[id].hide(); cleanupBackdrops(); });
    });

    // Pledge modal events
    const pledgeModalEl = document.getElementById('pledgeModal');
    let pledgeInstance = null;
    function getPledgeInstance(){ return pledgeInstance || (pledgeInstance = new bootstrap.Modal(pledgeModalEl,{backdrop:'static'})); }
    window.addEventListener('show-pledge-modal', ()=>{ getPledgeInstance().show(); });
    window.addEventListener('hide-pledge-modal', ()=>{ if(pledgeInstance){ pledgeInstance.hide(); }});

    // SweetAlert notifications for pledge updates
    window.addEventListener('pledge-updated', ()=>{
        if(window.Swal){
            Swal.fire({
                toast:true,position:'top-end',icon:'success',title:'Pledge updated',showConfirmButton:false,timer:1800
            });
        }
    });
})();
</script>
@endpush

@once('note-styles')
@push('styles')
<style>
.note-text-clamp{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
/* Requests tab enhancements */
.request-item{border-left:4px solid transparent;}
.request-item-active{border-color:var(--bs-primary)!important;}
.request-item-active .request-status-bar{box-shadow:0 0 0 .2rem rgba(var(--bs-primary-rgb),.15);} 
.request-item:hover{border-color:var(--bs-primary);}
.request-status-bar{width:4px;border-radius:4px;}
/* Opinions list */
.opinion-item{position:relative;border-left:4px solid transparent;transition:all .2s;}
.opinion-item .opinion-accent{width:4px;border-radius:4px;background:var(--bs-primary);opacity:.15;transition:opacity .2s,box-shadow .2s;}
.opinion-item:hover{border-color:var(--bs-primary);} 
.opinion-item-active{border-color:var(--bs-primary)!important;}
.opinion-item-active .opinion-accent{opacity:1;box-shadow:0 0 0 .25rem rgba(var(--bs-primary-rgb),.15);} 
.rating-stars i{margin-right:.15rem;}
.rating-stars-large i{margin-right:.25rem;}
/* Pledge cards refinements */
#pledgeModal .card{transition:box-shadow .2s ease;}
#pledgeModal .card:hover{box-shadow:0 0 0 .25rem rgba(var(--bs-primary-rgb),.08);} 
#pledgeModal .border-dashed{border:1px dashed var(--bs-gray-300)!important;}
/* --- Enhanced voter request responses styling --- */
.voter-response-item{background:#fff;transition:box-shadow .15s ease, border-color .15s ease;}
.voter-response-item:hover{box-shadow:0 4px 12px rgba(0,0,0,.05);border-color:var(--bs-primary);} 
</style>
@endpush
@endonce

@once('opinion-status-enhancements')
@push('styles')
<style>
/* status badges styling if needed */
.badge-opinion-success{background:var(--bs-success) !important;}
.badge-opinion-failed{background:var(--bs-danger) !important;}
.bg-pink { background-color:#d63384 !important; }
</style>
@endpush
@endonce

@push('scripts')
<script>
// Persist & restore modal open state across Livewire morphs so interior can update in real-time
(function(){
    if(typeof bootstrap === 'undefined') return;
    const modalEl = document.getElementById('viewVoterModal');
    if(!modalEl) return;
    function markOpen(){ modalEl.dataset.open = '1'; delete modalEl.dataset.closing; }
    function markClosed(){ delete modalEl.dataset.open; }
    modalEl.addEventListener('shown.bs.modal', markOpen);
    modalEl.addEventListener('hide.bs.modal', ()=>{ modalEl.dataset.closing = '1'; });
    modalEl.addEventListener('hidden.bs.modal', markClosed);

    // Use morph.finished so we act after all diffs are applied (avoids flicker/close on click)
    if(window.Livewire){
        Livewire.hook('morph.finished', () => {
            if(!modalEl.dataset.open || modalEl.dataset.closing) return; // only if it was open and not intentionally closing
            // Ensure Bootstrap instance still considers it shown
            if(!modalEl.classList.contains('show')){
                const inst = bootstrap.Modal.getOrCreateInstance(modalEl,{backdrop:'static'});
                inst.show();
            }
        });
    }
})();
</script>
@endpush

@push('styles')
<style>
.voter-modal-pulse{animation: voterPulse .6s ease-in-out;}
@keyframes voterPulse{0%{box-shadow:0 0 0 0 rgba(13,110,253,.0);}40%{box-shadow:0 0 0 .75rem rgba(13,110,253,.15);}100%{box-shadow:0 0 0 0 rgba(13,110,253,.0);}}
</style>
@endpush
