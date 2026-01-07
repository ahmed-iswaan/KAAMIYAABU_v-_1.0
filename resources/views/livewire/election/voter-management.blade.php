<div>
    <div class="content fs-6 d-flex flex-column flex-column-fluid" id="kt_content">
        <div class="toolbar" id="kt_toolbar">
            <div class="container-fluid d-flex flex-stack flex-wrap flex-sm-nowrap">
                <div class="d-flex flex-column align-items-start justify-content-center flex-wrap me-2">
                    <h1 class="text-dark fw-bold my-1 fs-2">{{$pageTitle}}</h1>
                    <ul class="breadcrumb fw-semibold fs-base my-1">
                        <li class="breadcrumb-item text-muted"><a href="#" class="text-muted text-hover-primary">Election</a></li>
                        <li class="breadcrumb-item text-dark">Voters </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="post fs-6 d-flex flex-column-fluid" id="kt_post">
            <div class="container-xxl">
                <div class="row g-6 mb-6">
                        @can('voters-openProvisionalPledge')
                    <div class="col-md-6">
                        <div class="card card-bordered shadow-sm">
                            <div class="card-body py-4">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="ki-duotone ki-flag fs-2 text-primary me-2"><span class="path1"></span><span class="path2"></span></i>
                                    <h6 class="mb-0">Provisional Pledges</h6>
                                </div>
                                <div class="d-flex flex-wrap gap-3">
                                    <span class="badge badge-light-primary">Yes: {{ $totalsProv['yes'] ?? 0 }}</span>
                                    <span class="badge badge-light-warning">No: {{ $totalsProv['no'] ?? 0 }}</span>
                                    <span class="badge badge-light-secondary">Undecided: {{ $totalsProv['neutral'] ?? 0 }}</span>
                                    <span class="badge badge-light">Pending: {{ $totalsProv['pending'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endcan
                    @can('voters-openFinalPledge')
                    <div class="col-md-6">
                        <div class="card card-bordered shadow-sm">
                            <div class="card-body py-4">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="ki-duotone ki-flag fs-2 text-success me-2"><span class="path1"></span><span class="path2"></span></i>
                                    <h6 class="mb-0">Final Pledges</h6>
                                </div>
                                <div class="d-flex flex-wrap gap-3">
                                    <span class="badge badge-light-primary">Yes: {{ $totalsFinal['yes'] ?? 0 }}</span>
                                    <span class="badge badge-light-warning">No: {{ $totalsFinal['no'] ?? 0 }}</span>
                                    <span class="badge badge-light-secondary">Undecided: {{ $totalsFinal['neutral'] ?? 0 }}</span>
                                    <span class="badge badge-light">Pending: {{ $totalsFinal['pending'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endcan
                </div>
                <div class="card">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title d-flex flex-wrap align-items-center gap-3 w-100">
                            <div class="d-flex align-items-center position-relative flex-grow-1 my-1 me-md-4" style="min-width:200px;">
                                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5"><span class="path1"></span><span class="path2"></span></i>
                                <input type="text" wire:model.live.debounce.500ms="search" class="form-control form-control-solid w-100 ps-13" placeholder="Search by Name, Email or ID Card">
                            </div>

                            {{-- Export button --}}
                            @can('voters-exportProvisionalPledgesCsv')
                                <div class="flex-grow-0" style="min-width:220px;">
                                    <button type="button" class="btn btn-light-primary w-100" wire:click="exportProvisionalPledgesCsv">
                                        Export Prov. Pledge CSV
                                    </button>
                                </div>
                            @endcan

                            <div class="flex-grow-0" style="min-width:220px;">
                                <select class="form-select form-select-solid w-100" wire:model="electionId">
                                    @foreach($elections as $el)
                                        <option value="{{$el->id}}">{{$el->name}} ({{$el->status}})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex-grow-0" style="min-width:220px;">
                                <select class="form-select form-select-solid w-100" wire:model.live="filterSubConsiteId">
                                    <option value="">All SubConsites</option>
                                    @foreach($subConsites as $sc)
                                        <option value="{{ $sc->id }}">{{ $sc->code }}{{ $sc->name ? ' - '.$sc->name : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Multi Final Pledge Filter Dropdown -->
                            <div class="position-relative" style="min-width:260px;" wire:ignore.self>
                                <button type="button" class="btn btn-light btn-active-light-primary w-100 d-flex justify-content-between align-items-center px-4" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                    <span class="d-flex align-items-center flex-grow-1 text-start">
                                        <i class="ki-duotone ki-filter fs-2 text-primary me-2"><span class="path1"></span><span class="path2"></span></i>
                                        <span class="fw-semibold">Final Pledges</span>
                                    </span>
                                    <span class="d-flex align-items-center gap-1 me-2">
                                        @php $previewColors=['strong_yes'=>'success','yes'=>'primary','neutral'=>'warning','no'=>'danger','strong_no'=>'dark']; @endphp
                                        @if(count($finalPledgeFilters))
                                            @foreach(array_slice($finalPledgeFilters,0,3) as $pv)
                                                @if($pv!=='pending')
                                                    <span class="badge badge-dot bg-{{ $previewColors[$pv] ?? 'secondary' }}"></span>
                                                @else
                                                    <span class="badge badge-dot bg-secondary"></span>
                                                @endif
                                            @endforeach
                                            @if(count($finalPledgeFilters) > 3)
                                                <span class="badge badge-light fs-8">+{{ count($finalPledgeFilters)-3 }}</span>
                                            @endif
                                        @else
                                            <span class="text-muted fs-8">All</span>
                                        @endif
                                    </span>
                                    <i class="ki-duotone ki-down fs-2 ms-1"></i>
                                </button>
                                <div class="menu menu-sub menu-sub-dropdown w-325px p-0" data-kt-menu="true">
                                    <div class="d-flex align-items-center justify-content-between px-5 py-4 border-bottom">
                                        <div class="d-flex align-items-center">
                                            <i class="ki-duotone ki-filter fs-2 text-primary me-2"><span class="path1"></span><span class="path2"></span></i>
                                            <h6 class="mb-0 fw-bold">Select Final Pledges</h6>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-light-danger" wire:click="clearFinalPledgeFilters">Clear</button>
                                    </div>
                                    <div class="px-5 pt-4 pb-2">
                                        @if(count($finalPledgeFilters))
                                            <div class="d-flex flex-wrap gap-2 mb-3">
                                                @php $chipMap=['pending'=>'secondary','strong_yes'=>'success','yes'=>'primary','neutral'=>'warning','no'=>'danger','strong_no'=>'dark']; @endphp
                                                @foreach($finalPledgeFilters as $sel)
                                                    <span class="badge badge-light-{{ $chipMap[$sel] ?? 'secondary' }} badge-outline fw-semibold py-2 px-3 d-inline-flex align-items-center gap-1">
                                                        <span class="text-capitalize small">{{ str_replace('_',' ',$sel) }}</span>
                                                        <i class="ki-duotone ki-cross fs-6 cursor-pointer" wire:click="finalPledgeFilters = array_values(array_filter($finalPledgeFilters, fn($x)=>$x!=='$sel'))"><span class="path1"></span><span class="path2"></span></i>
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-muted small mb-3">No specific statuses selected. All voters shown.</div>
                                        @endif
                                    </div>
                                    <div class="separator"></div>
                                    <div class="scroll-y mh-260px py-3">
                                        @php
                                            $pledgeOptions = [
                                                'pending' => 'Pending',
                                                'strong_yes' => 'Strong Yes',
                                                'yes' => 'Yes',
                                                'neutral' => 'Neutral',
                                                'no' => 'No',
                                                'strong_no' => 'Strong No',
                                            ];
                                            $colorMap = [
                                                'pending'=>'secondary','strong_yes'=>'success','yes'=>'primary','neutral'=>'warning','no'=>'danger','strong_no'=>'dark'
                                            ];
                                        @endphp
                                        <ul class="list-unstyled m-0">
                                            @foreach($pledgeOptions as $val=>$label)
                                                @php $checked = in_array($val,$finalPledgeFilters ?? [],true); @endphp
                                                <li class="px-5 py-2 hover-bg align-items-center d-flex {{ $checked? 'bg-light-primary rounded' : '' }}" wire:key="pledge-opt-{{$val}}">
                                                    <div class="form-check form-check-custom form-check-solid me-3">
                                                        <input type="checkbox" id="fp_{{$val}}" class="form-check-input" value="{{$val}}" wire:model.defer="finalPledgeFilters" />
                                                    </div>
                                                    <label for="fp_{{$val}}" class="flex-grow-1 cursor-pointer fw-semibold text-gray-800 mb-0">{{ $label }}</label>
                                                    <span class="badge badge-light-{{ $colorMap[$val] ?? 'secondary' }} fw-bold">{{ $label }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                    <div class="p-5 border-top bg-light d-flex flex-column gap-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <button type="button" class="btn btn-sm btn-light" wire:click="finalPledgeFilters=@js(array_keys($pledgeOptions))">Select All</button>
                                            <span class="text-muted small">{{ count($finalPledgeFilters) }} selected</span>
                                        </div>
                                        <button type="button" class="btn btn-primary w-100" wire:click="applyFinalPledgeFilters" data-kt-menu-dismiss="true">
                                            <span class="indicator-label">Apply</span>
                                            <span class="indicator-progress" wire:loading wire:target="applyFinalPledgeFilters">Processing...
                                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <!-- End Multi Final Pledge Filter Dropdown -->
                        </div>
                    </div>
                    <div class="card-body py-4">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-200px">Name</th>
                                        <th class="min-w-150px">Phones / Email</th>
                                        <th class="min-w-150px">Party / SubConsite</th>
                                        <th class="min-w-200px">Permanent Address</th>
                                        <th class="min-w-120px">Prov. Pledge</th>
                                           @can('voters-openFinalPledge')
                                        <th class="min-w-120px">Final Pledge</th>
                                        @endcan
                                        <th class="min-w-120px text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @forelse($voters as $entry)
                                        @php
                                            $pledgeFinal = $entry->final_pledge_status;
                                            $pledgeProv = $entry->provisional_pledge_status;
                                            // Map to labels for table display
                                            $labelFinal = $pledgeFinal ? strtoupper(str_replace('_',' ', $pledgeFinal)) : 'PENDING';
                                            // Restrict provisional labels to YES / NO / UNDECIDED
                                            $labelProv = match($pledgeProv){
                                                'yes' => 'YES',
                                                'no' => 'NO',
                                                'neutral' => 'UNDECIDED',
                                                null => 'PENDING',
                                                default => strtoupper(str_replace('_',' ', $pledgeProv))
                                            };
                                            // Colors
                                            $colorMapFinal = ['strong_yes'=>'success','yes'=>'primary','neutral'=>'secondary','no'=>'warning','strong_no'=>'danger'];
                                            $colorFinal = $pledgeFinal ? ($colorMapFinal[$pledgeFinal] ?? 'secondary') : 'light';
                                            $colorProv = match($pledgeProv){
                                                'yes' => 'primary',
                                                'no' => 'warning',
                                                'neutral' => 'secondary',
                                                null => 'light',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <tr>
                                            <td class="d-flex align-items-center cursor-pointer" wire:click="viewVoter('{{ $entry->id }}')" style="user-select:none;">
                                                @php
                                                    $g = strtolower($entry->gender ?? '');
                                                    $genderDot = ($g==='male' || $g==='m') ? 'primary' : (($g==='female' || $g==='f') ? 'pink' : 'secondary');
                                                @endphp
                                                @if($entry->profile_picture)
                                                    @php $avatarClass = $g==='male' || $g==='m' ? 'bg-light-primary border border-primary border-2' : ($g==='female' || $g==='f' ? 'bg-light-danger border border-danger border-2' : 'bg-light-primary border border-secondary border-2'); @endphp
                                                    <div class="symbol symbol-50px overflow-hidden me-3 position-relative">
                                                        <div class="symbol-label p-0 d-flex align-items-center justify-content-center {{ $avatarClass }}" style="border-radius:6px;">
                                                            <img src="{{ asset('storage/' . $entry->profile_picture) }}" alt="{{ $entry->name }}" class="w-100 h-100 object-fit-cover" style="border-radius:6px;">
                                                        </div>
                                                        <span class="status-dot bg-{{ $genderDot }}" title="Gender: {{ ucfirst($entry->gender ?? 'N/A') }}"></span>
                                                    </div>
                                                @else
                                                    @php $avatarClass = $g==='male' || $g==='m' ? 'bg-light-primary text-primary border border-primary border-2' : ($g==='female' || $g==='f' ? 'bg-light-danger text-danger border border-danger border-2' : 'bg-light-primary text-primary border border-secondary border-2'); @endphp
                                                    <div class="symbol symbol-50px overflow-hidden me-3 position-relative">
                                                        <div class="symbol-label fs-3 {{ $avatarClass }}" style="border-radius:6px;">{{ Str::substr($entry->name,0,1) }}</div>
                                                        <span class="status-dot bg-{{ $genderDot }}" title="Gender: {{ ucfirst($entry->gender ?? 'N/A') }}"></span>
                                                    </div>
                                                @endif
                                                <div class="d-flex flex-column">
                                                    <span class="text-gray-800 text-hover-primary mb-1">{{ ucwords(strtolower($entry->name)) }}</span>
                                                    <small class="text-muted">{{ $entry->id_card_number ?? '—' }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                @if(is_array($entry->phones))
                                                    @foreach($entry->phones as $p)
                                                        <div><i class="ki-duotone ki-call fs-6 me-1"><span class="path1"></span><span class="path2"></span></i>{{ $p }}</div>
                                                    @endforeach
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                                <div class="mt-1 small text-muted">Email: {{ $entry->email ?? '—' }}</div>
                                            </td>
                                            <td>
                                                @php $party = $entry->party; @endphp
                                                <div class="d-flex align-items-center">
                                                    @if($party && $party->logo)
                                                        <div class="symbol symbol-circle symbol-30px overflow-hidden me-2"><img src="{{ asset('storage/' . $party->logo) }}" alt="{{ $party->short_name ?? $party->name }}" class="w-30px" /></div>
                                                    @elseif($party)
                                                        <div class="symbol symbol-circle symbol-30px bg-light fw-bold text-uppercase me-2">{{ Str::substr($party->short_name ?? $party->name,0,2) }}</div>
                                                    @endif
                                                    <div>
                                                        <div>{{ $party->short_name ?? $party->name ?? '' }}</div>
                                                        <small class="text-muted">{{ optional($entry->subConsite)->code ?? '' }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-gray-700 small">{{ $entry->permanentLocationString() }}</div>
                                            </td>
                                            <td><span class="badge badge-{{ $colorProv }} fw-bold">{{ $labelProv }}</span></td>
                                               @can('voters-openFinalPledge')
                                            <td><span class="badge badge-{{ $colorFinal }} fw-bold">{{ $labelFinal }}</span></td>
                                            @endcan
                                            <td class="text-end">
                                                <a href="#" class="btn btn-light btn-active-light-primary btn-flex btn-center btn-sm" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions
                                                    <i class="ki-duotone ki-down fs-5 ms-1"></i>
                                                </a>
                                                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-175px py-4" data-kt-menu="true">
                                                    @can('voters-viewVoter')
                                                    <div class="menu-item px-3">
                                                        <a href="#" class="menu-link px-3" wire:click="viewVoter('{{ $entry->id }}')">View</a>
                                                    </div>
                                                    @endcan
                                                    @can('voters-openProvisionalPledge')
                                                    <div class="menu-item px-3">
                                                        <a href="#" class="menu-link px-3" wire:click="openProvisionalPledgeModal('{{ $entry->id }}')">Provisional Pledge</a>
                                                    </div>
                                                    @endcan
                                                    @can('voters-viewProvisionalHistory')
                                                    <div class="menu-item px-3">
                                                        <a href="#" class="menu-link px-3" wire:click="openProvisionalHistory('{{ $entry->id }}')">Provisional History</a>
                                                    </div>
                                                    @endcan
                                                    @can('voters-openFinalPledge')
                                                    <div class="menu-item px-3">
                                                        <a href="#" class="menu-link px-3" wire:click="openFinalPledgeModal('{{ $entry->id }}')">Final Pledge</a>
                                                    </div>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center text-muted">No voters found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-end mt-4">{{ $voters->links('vendor.pagination.new') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('livewire.election.partials.view-voter-modal')
    @include('livewire.election.partials.pledge-provisional-modal')
    @include('livewire.election.partials.pledge-final-modal')
    @include('livewire.election.partials.provisional-history-modal')

    @push('scripts')
    <script>
        window.addEventListener('show-view-voter-modal', ()=>{
            const modalEl = document.getElementById('viewVoterModal');
            if(!modalEl || typeof bootstrap === 'undefined') return;
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        });
        window.addEventListener('hide-view-voter-modal', ()=>{
            const modalEl = document.getElementById('viewVoterModal');
            if(!modalEl || typeof bootstrap === 'undefined') return;
            const modal = bootstrap.Modal.getInstance(modalEl);
            if(modal){ modal.hide(); }
        });

        // Global safety net: if any modal is closed but body is still locked, unlock it.
        (function(){
            function cleanupBootstrapBackdrop() {
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
            }

            document.addEventListener('hidden.bs.modal', function () {
                // allow bootstrap to finish its own cleanup first
                setTimeout(cleanupBootstrapBackdrop, 50);
            });
        })();
    </script>
    @endpush

    @push('styles')
    <style>
    /* Enhanced filter dropdown */
    .badge-dot{width:10px;height:10px;border-radius:50%;display:inline-block;}
    .hover-bg:hover{background:var(--bs-light) !important;}
    .text-pink{color:#d63384 !important;}
    .gender-icon,.gender-male,.gender-female{display:none !important;}
    .symbol-badge.badge.badge-circle { width:20px; height:20px; display:flex; align-items:center; justify-content:center; }
    .symbol-badge .ki-duotone { line-height:1; }
    .status-dot { position:absolute; width:14px; height:14px; border:2px solid #fff; border-radius:50%; bottom:2px; right:2px; box-shadow:0 0 0 2px rgba(0,0,0,0.05); }
    .bg-pink { background-color:#d63384 !important; }
    .symbol.symbol-50px .symbol-label { width:50px; height:50px; }
    .symbol.symbol-50px img { object-fit:cover; }
    </style>
    @endpush
       @stack('scripts')
</div>
