@section('title','Call Center')
<div class="content fs-6 d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="toolbar" id="kt_toolbar">
        <div class="container-fluid d-flex flex-stack flex-wrap flex-sm-nowrap">
            <div class="d-flex flex-column align-items-start justify-content-center flex-wrap me-2 mb-4 mb-sm-0">
                <h1 class="text-dark fw-bold my-1 fs-2">Call Center</h1>
                <ul class="breadcrumb fw-semibold fs-base my-1">
                    <li class="breadcrumb-item text-muted"><a href="#" class="text-muted text-hover-primary">Operations</a></li>
                    <li class="breadcrumb-item text-dark">Call Center</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="post fs-6 d-flex flex-column-fluid" id="kt_post">
        <div class="container-fluid">

            <!-- Overview card (separate from list/table card) -->
            <div class="card border border-gray-200 shadow-sm mb-6">
                <div class="card-body py-5">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-4">
                        <div>
                            <div class="fw-bold text-gray-900 fs-4">Overview</div>
                            <div class="text-muted fs-8">Totals for directories you can access (current filters/search applied).</div>
                        </div>

                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <span class="badge badge-light">Total: {{ $totalsAll ?? 0 }}</span>
                            <span class="badge badge-light-success">Completed: {{ $totalsCompleted ?? 0 }}</span>
                            <span class="badge badge-light-warning">Pending: {{ $totalsPending ?? 0 }}</span>
                            <span class="badge badge-light-primary">Completed by me: {{ $totalsCompletedByMe ?? 0 }}</span>
                        </div>
                    </div>

                    <div class="mt-4">
                        @php
                            $tAll = (int)($totalsAll ?? 0);
                            $tDone = (int)($totalsCompleted ?? 0);
                            $pct = $tAll > 0 ? (int) round(($tDone / $tAll) * 100) : 0;
                        @endphp
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="text-muted fs-8">Completion</div>
                            <div class="text-muted fs-8">{{ $pct }}%</div>
                        </div>
                        <div class="progress h-6px bg-light mt-1">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Directories list/table card -->
            <div class="card">
                <div class="card-header border-0 pt-6">
                    <div class="card-title d-flex flex-wrap gap-3 align-items-center w-100">
                        <div class="d-flex align-items-center gap-3">
                            <input type="text" class="form-control form-control-sm" style="min-width: 260px;" placeholder="Search name / NID / SERIAL / phone" wire:model.live="search" />
                            <select class="form-select form-select-sm" style="min-width: 220px;" wire:model.live="filterSubConsiteId">
                                <option value="">All Sub Consites</option>
                                @foreach($subConsites as $sc)
                                    <option value="{{ $sc->id }}">{{ $sc->code }}{{ $sc->name ? ' - '.$sc->name : '' }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="ms-auto d-flex align-items-center gap-2">
                             <select class="form-select form-select-sm w-auto" wire:model.live="perPage">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card-body pt-0">
                    <!-- Mobile card list -->
                    <div class="d-block d-md-none pt-4">
                        @php
                            $dirStatusLabelMap = [
                                'not_started' => 'Pending',
                                'in_progress' => 'In progress',
                                'callback' => 'Call back',
                                'unreachable' => 'Unreachable',
                                'wrong_number' => 'Wrong number',
                                'do_not_call' => 'Do not call',
                                'completed' => 'Completed',
                            ];
                            $badgeForDirStatus = function($v){
                                return match($v){
                                    'completed' => 'success',
                                    'in_progress' => 'primary',
                                    'callback' => 'info',
                                    'unreachable' => 'warning',
                                    'wrong_number' => 'danger',
                                    'do_not_call' => 'danger',
                                    default => 'secondary'
                                };
                            };
                            $subStatusLabelMap = ($activeSubStatuses ?? []) + [
                                // fallback for legacy string codes (if any old rows exist)
                                'unreachable' => 'Unreachable',
                                'wrong_number' => 'Wrong number',
                                'callback' => 'Call back',
                                'do_not_call' => 'Do not call',
                            ];
                            $badgeForSubStatus = function($v){
                                // If we now store SubStatus UUIDs, keep a neutral badge.
                                // Legacy values still get their colors.
                                return match($v){
                                    'callback' => 'info',
                                    'unreachable' => 'warning',
                                    'wrong_number' => 'danger',
                                    'do_not_call' => 'dark',
                                    default => 'secondary'
                                };
                            };
                        @endphp

                        <div class="d-flex flex-column gap-4">
                            @forelse($directories as $d)
                                @php
                                    $phones = is_array($d->phones) ? implode(' / ', array_filter($d->phones)) : (is_string($d->phones ?? null) ? $d->phones : '');
                                    $perm = method_exists($d, 'permanentLocationString') ? $d->permanentLocationString() : ($d->address ?? '');
                                    $imgUrl = $directoryImageUrls[$d->id] ?? null;
                                    $fallback = asset('assets/media/avatars/blank.png');

                                    $dsRow = $listStatuses[(string)$d->id] ?? null;
                                    $ds = is_array($dsRow) ? (string)($dsRow['status'] ?? 'not_started') : (string)($dsRow ?? 'not_started');
                                    $dsLabel = $dirStatusLabelMap[$ds] ?? $ds;
                                    $dsBadge = $badgeForDirStatus($ds);

                                    $ss = $listSubStatuses[(string)$d->id]['sub_status_id'] ?? '';
                                    $ssAttempt = $listSubStatuses[(string)$d->id]['attempt'] ?? null;
                                    $ssLabel = $subStatusLabelMap[$ss] ?? $ss;
                                    $ssBadge = $badgeForSubStatus($ss);
                                @endphp

                                <div class="card border-0 shadow-sm">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="symbol symbol-50px symbol-circle flex-shrink-0">
                                                <img src="{{ $imgUrl ?: $fallback }}" alt="{{ $d->name }}" class="w-50px h-50px object-fit-cover" />
                                            </div>

                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start gap-2">
                                                    <div>
                                                        <a href="#" class="fw-bold text-gray-900 text-hover-primary" wire:click.prevent="openDirectory('{{ $d->id }}')">{{ $d->name }}</a>

                                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                                            <span class="badge badge-light-{{ $dsBadge }}">{{ $dsLabel }}</span>
                                                            @if(!empty($ss))
                                                                <span class="badge badge-light-{{ $ssBadge }}">A{{ $ssAttempt }}: {{ $ssLabel }}</span>
                                                            @endif
                                                            <span class="badge badge-light">{{ $d->id_card_number }}</span>
                                                            @if(!empty($d->serial))
                                                                <span class="badge badge-light">S: {{ $d->serial }}</span>
                                                            @endif
                                                            @if($d->subConsite)
                                                                <span class="badge badge-light">{{ $d->subConsite->code }}</span>
                                                            @endif
                                                        </div>

                                                        <div class="text-muted small mt-2">
                                                            <span class="badge badge-light me-2">{{ $d->party?->short_name ?? $d->party?->name ?? '—' }}</span>
                                                            @if(!empty($phones))
                                                                <span class="badge badge-light">{{ $phones }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mt-3">
                                                    <div class="text-muted small">{{ !empty($perm) ? $perm : '—' }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-10">No directories found for your sub consites.</div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Desktop table -->
                    <div class="table-responsive d-none d-md-block">
                        @php
                            $dirStatusLabelMap = [
                                'not_started' => 'Pending',
                                'in_progress' => 'In progress',
                                'callback' => 'Call back',
                                'unreachable' => 'Unreachable',
                                'wrong_number' => 'Wrong number',
                                'do_not_call' => 'Do not call',
                                'completed' => 'Completed',
                            ];
                            $badgeForDirStatus = function($v){
                                return match($v){
                                    'completed' => 'success',
                                    'in_progress' => 'primary',
                                    'callback' => 'info',
                                    'unreachable' => 'warning',
                                    'wrong_number' => 'danger',
                                    'do_not_call' => 'danger',
                                    default => 'secondary'
                                };
                            };
                            $subStatusLabelMap = ($activeSubStatuses ?? []) + [
                                // fallback for legacy string codes (if any old rows exist)
                                'unreachable' => 'Unreachable',
                                'wrong_number' => 'Wrong number',
                                'callback' => 'Call back',
                                'do_not_call' => 'Do not call',
                            ];
                            $badgeForSubStatus = function($v){
                                // If we now store SubStatus UUIDs, keep a neutral badge.
                                // Legacy values still get their colors.
                                return match($v){
                                    'callback' => 'info',
                                    'unreachable' => 'warning',
                                    'wrong_number' => 'danger',
                                    'do_not_call' => 'dark',
                                    default => 'secondary'
                                };
                            };
                        @endphp

                        <table class="table align-middle table-row-dashed fs-7">
                            <thead>
                                <tr class="text-gray-600 fw-semibold">
                                    <th style="width: 60px;">Profile</th>
                                    <th>Status + Name</th>
                                    <th>Serial + Party + Sub Consite</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($directories as $d)
                                    @php
                                        $imgUrl = $directoryImageUrls[$d->id] ?? null;
                                        $fallback = asset('assets/media/avatars/blank.png');

                                        $dsRow = $listStatuses[(string)$d->id] ?? null;
                                        $ds = is_array($dsRow) ? (string)($dsRow['status'] ?? 'not_started') : (string)($dsRow ?? 'not_started');
                                        $dsLabel = $dirStatusLabelMap[$ds] ?? $ds;
                                        $dsBadge = $badgeForDirStatus($ds);

                                        $ss = $listSubStatuses[(string)$d->id]['sub_status_id'] ?? '';
                                        $ssAttempt = $listSubStatuses[(string)$d->id]['attempt'] ?? null;
                                        $ssLabel = $subStatusLabelMap[$ss] ?? $ss;
                                        $ssBadge = $badgeForSubStatus($ss);
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="symbol symbol-45px symbol-circle">
                                                <img src="{{ $imgUrl ?: $fallback }}" alt="Profile" class="w-45px h-45px object-fit-cover" />
                                            </div>
                                        </td>

                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <div class="d-flex align-items-center flex-wrap gap-2">
                                                    <span class="badge badge-light-{{ $dsBadge }}">{{ $dsLabel }}</span>
                                                    @if(!empty($ss))
                                                        <span class="badge badge-light-{{ $ssBadge }}">Attempt {{ $ssAttempt }}: {{ $ssLabel }}</span>
                                                    @endif
                                                </div>

                                                <a href="#" class="fw-semibold text-gray-900 text-hover-primary" wire:click.prevent="openDirectory('{{ $d->id }}')">{{ $d->name }}</a>

                                                <div class="text-muted small">
                                                    <span class="badge badge-light">NID: {{ $d->id_card_number }}</span>
                                                </div>

                                                @php
                                                    $perm = method_exists($d, 'permanentLocationString') ? $d->permanentLocationString() : ($d->address ?? '');
                                                @endphp
                                                @if(!empty($perm))
                                                    <div class="text-muted small text-truncate" style="max-width: 520px;">{{ $perm }}</div>
                                                @endif
                                            </div>
                                        </td>

                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <div class="text-muted small">
                                                    @if(!empty($d->serial))
                                                        <span class="badge badge-light">S: {{ $d->serial }}</span>
                                                    @endif
                                                    <span class="badge badge-light">{{ $d->party?->short_name ?? $d->party?->name ?? '—' }}</span>
                                                    <span class="badge badge-light">{{ $d->subConsite?->code ?? '—' }}</span>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-10">No directories found for your sub consites.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $directories->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Directory Details Modal -->
    <div class="modal fade" id="callCenterDirectoryModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="btn btn-sm btn-icon btn-active-light-primary" data-bs-dismiss="modal" aria-label="Close" wire:click="closeDirectoryModal">
                        <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div wire:key="cc-modal-body-{{ $selectedDirectoryId ?? 'none' }}-{{ $modalRenderTick ?? 0 }}">
                    @if($selectedDirectory)
                        @php
                            $imgUrl = $directoryImageUrls[$selectedDirectory->id] ?? null;
                            $fallback = asset('assets/media/avatars/blank.png');
                            $phones = is_array($selectedDirectory->phones) ? implode(' / ', array_filter($selectedDirectory->phones)) : (is_string($selectedDirectory->phones ?? null) ? $selectedDirectory->phones : '');
                            $perm = method_exists($selectedDirectory, 'permanentLocationString') ? $selectedDirectory->permanentLocationString() : ($selectedDirectory->address ?? 'N/A');
                        @endphp

                        <!-- Enhanced Profile Header -->
                        <div class="bg-light rounded-3 p-5 mb-6 border border-gray-200">
                            <div class="d-flex align-items-start gap-4">
                                <div class="symbol symbol-70px symbol-circle flex-shrink-0 border border-3 border-white shadow-sm">
                                    <img src="{{ $imgUrl ?: $fallback }}" alt="{{ $selectedDirectory->name }}" class="w-100 h-100 object-fit-cover" />
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                                        <div>
                                            <h3 class="fs-2 fw-bold text-gray-900 mb-1">{{ $selectedDirectory->name ?? '' }}</h3>
                                            <div class="d-flex flex-wrap gap-2 text-muted fs-7 fw-semibold">
                                                <span>{{ $selectedDirectory->id_card_number ?? '—' }}</span>
                                                <span class="bullet bullet-dot bg-gray-400 w-5px h-5px"></span>
                                                <span>{{ $selectedDirectory->party?->short_name ?? $selectedDirectory->party?->name ?? '—' }}</span>
                                                @if(!empty($perm))
                                                    <span class="bullet bullet-dot bg-gray-400 w-5px h-5px"></span>
                                                    <span>{{ $perm }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center gap-2">
                                            @if($activeElectionId)
                                                @php
                                                    $dirStatus = $directoryCallStatus ?? 'not_started';
                                                    $dirStatusLabelMap = [
                                                        'not_started' => 'Not started',
                                                        'in_progress' => 'In progress',
                                                        'callback' => 'Call back',
                                                        'unreachable' => 'Unreachable',
                                                        'wrong_number' => 'Wrong number',
                                                        'do_not_call' => 'Do not call',
                                                        'completed' => 'Completed',
                                                    ];
                                                    $dirStatusBadgeColor = match($dirStatus){
                                                        'completed' => 'success',
                                                        'in_progress' => 'primary',
                                                        'callback' => 'info',
                                                        'unreachable' => 'warning',
                                                        'wrong_number' => 'danger',
                                                        'do_not_call' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                @endphp

                                                @if($dirStatus === 'completed')
                                                    @can('call-center-undo-status')
                                                        <button type="button"
                                                                class="btn btn-sm btn-light-warning fw-bold px-4 py-2"
                                                                wire:click="undoDirectoryStatus"
                                                                @disabled(!$activeElectionId)>
                                                            <i class="ki-duotone ki-arrow-left fs-2 me-1"><span class="path1"></span><span class="path2"></span></i>
                                                            Undo
                                                        </button>
                                                    @endcan

                                                    <span class="badge badge-light-{{ $dirStatusBadgeColor }} fs-7 fw-bold px-3 py-2">
                                                        {{ $dirStatusLabelMap[$dirStatus] ?? $dirStatus }}
                                                    </span>
                                                @else
                                                    @can('call-center-mark-completed')
                                                        <button type="button"
                                                                class="btn btn-sm btn-light-success fw-bold px-4 py-2"
                                                                wire:click="markAsCompleted"
                                                                @disabled(!$activeElectionId)>
                                                            <i class="ki-duotone ki-check fs-2 me-1"><span class="path1"></span><span class="path2"></span></i>
                                                            Completed
                                                        </button>
                                                    @endcan
                                                @endif
                                            @else
                                                <span class="badge badge-light-warning">No active election</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="d-flex flex-wrap gap-2 mt-3">
                                        @if(!empty($selectedDirectory->serial))
                                            <span class="badge badge-white border border-gray-300 text-gray-700">Serial: {{ $selectedDirectory->serial }}</span>
                                        @endif
                                        @if($selectedDirectory->subConsite)
                                            <span class="badge badge-white border border-gray-300 text-gray-700">{{ $selectedDirectory->subConsite->code }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @php
                                $phonesList = is_array($selectedDirectory->phones ?? null) ? array_filter($selectedDirectory->phones) : [];
                                $statusLabelMap = [
                                    'not_called' => 'Pending',
                                    'completed' => 'Completed',
                                    'wrong_number' => 'Wrong number',
                                    'no_answer' => 'No answer',
                                    'busy' => 'Busy',
                                    'switched_off' => 'Switched off',
                                    'callback' => 'Call back',
                                ];
                            @endphp

                            @if(count($phonesList))
                                <div class="separator separator-dashed border-gray-300 my-4"></div>
                                <div class="d-flex flex-column gap-2">
                                    <div class="fw-bold fs-7 text-gray-600 mb-1">PHONE NUMBERS</div>
                                    @foreach($phonesList as $p)
                                        @php
                                            $norm = \App\Models\DirectoryPhoneStatus::normalizePhone((string) $p);
                                            $subId = $norm ? ($this->phoneCallSubStatuses[$norm] ?? '') : '';
                                            $subName = $subId ? (($activeSubStatuses[$subId] ?? '') ?: $subId) : '';

                                            $st = $norm ? ($this->phoneCallStatuses[$norm] ?? 'not_called') : 'not_called';

                                            // Prefer showing SubStatus label if present, otherwise show legacy status label
                                            $displayText = $subId ? $subName : ($statusLabelMap[$st] ?? $st);

                                            $badgeColor = match(true){
                                                $subId !== '' => 'primary',
                                                $st === 'completed' => 'success',
                                                $st === 'wrong_number' => 'danger',
                                                $st === 'no_answer' => 'warning',
                                                $st === 'busy' => 'warning',
                                                $st === 'switched_off' => 'danger',
                                                $st === 'callback' => 'info',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="text-dark fw-bold fs-6 font-monospace">{{ $p }}</span>
                                            <span class="badge badge-light-{{ $badgeColor }}">{{ $displayText }}</span>
                                            @if($norm && !empty($this->phoneCallNotes[$norm] ?? ''))
                                                <span class="text-muted small ms-1 fst-italic">{{ $this->phoneCallNotes[$norm] }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <!-- Modal Tabs -->
                        <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6 fw-semibold" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link @if(($activeModalTab ?? 'cc_notes') === 'cc_form') active @endif" data-bs-toggle="tab" role="tab" href="#cc_form" wire:click="setActiveModalTab('cc_form')">Form</a>
                            </li>
                            @can('call-center-notes')
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link @if(($activeModalTab ?? 'cc_notes') === 'cc_notes') active @endif" data-bs-toggle="tab" role="tab" href="#cc_notes" wire:click="setActiveModalTab('cc_notes')">Notes</a>
                                </li>
                            @endcan
                            <li class="nav-item" role="presentation">
                                <a class="nav-link @if(($activeModalTab ?? 'cc_notes') === 'cc_requests') active @endif" data-bs-toggle="tab" role="tab" href="#cc_requests" wire:click="setActiveModalTab('cc_requests')">Requests</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link @if(($activeModalTab ?? 'cc_notes') === 'cc_status_attempts') active @endif" data-bs-toggle="tab" role="tab" href="#cc_status_attempts" wire:click="setActiveModalTab('cc_status_attempts')">Status & Attempts</a>
                            </li>
                            @can('call-center-call-status')
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link @if(($activeModalTab ?? 'cc_notes') === 'cc_call_status') active @endif" data-bs-toggle="tab" role="tab" href="#cc_call_status" wire:click="setActiveModalTab('cc_call_status')">Call Status</a>
                                </li>
                            @endcan
                            @can('call-center-history')
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link @if(($activeModalTab ?? 'cc_notes') === 'cc_history') active @endif" data-bs-toggle="tab" role="tab" href="#cc_history" wire:click="setActiveModalTab('cc_history')">History</a>
                                </li>
                            @endcan
                        </ul>

                        <div class="tab-content" wire:key="cc-tab-content-{{ $selectedDirectory->id }}">
                            <!-- Notes Tab -->
                            <div class="tab-pane fade @if(($activeModalTab ?? 'cc_notes') === 'cc_notes') show active @endif" id="cc_notes">
                                 <div class="mb-4">
                                     <textarea class="form-control" rows="4" wire:model.defer="newNoteText" placeholder="Write note..."></textarea>
                                     @error('newNoteText')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                                     <div class="d-flex justify-content-end mt-3">
                                         <button type="button" class="btn btn-primary" wire:click="submitNote" wire:loading.attr="disabled">
                                             <span wire:loading.remove wire:target="submitNote">Submit Note</span>
                                             <span wire:loading wire:target="submitNote" class="spinner-border spinner-border-sm"></span>
                                         </button>
                                     </div>
                                 </div>

                                 <div class="separator separator-dashed my-5"></div>

                                 <div class="vstack gap-4">
                                     @forelse($selectedNotes as $n)
                                         <div class="border rounded p-4">
                                             <div class="d-flex align-items-center mb-2">
                                                 <div class="fw-semibold">{{ $n['author'] ?: 'User' }}</div>
                                                 <div class="text-muted small ms-auto">{{ $n['created_at_human'] }}</div>
                                             </div>
                                             <div class="text-gray-700">{{ $n['note'] }}</div>
                                         </div>
                                     @empty
                                         <div class="text-center text-muted py-8">No notes yet.</div>
                                     @endforelse
                                 </div>
                            </div>

                            <!-- Requests Tab -->
                            <div class="tab-pane fade @if(($activeModalTab ?? 'cc_notes') === 'cc_requests') show active @endif" id="cc_requests" role="tabpanel">
                                <div class="mb-4">
                                    <div class="row g-3">
                                        <div class="col-12 col-md-4">
                                            <label class="form-label fw-semibold">Request Type</label>
                                            <select class="form-select" wire:model.defer="newRequestTypeId">
                                                <option value="">Select type</option>
                                                @foreach($requestTypes as $t)
                                                    <option value="{{ $t['id'] }}">{{ $t['name'] }}</option>
                                                @endforeach
                                            </select>
                                            @error('newRequestTypeId')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="col-12 col-md-4">
                                            <label class="form-label fw-semibold">Note (optional)</label>
                                            <input type="text" class="form-control" wire:model.defer="newRequestNote" placeholder="Details..." />
                                            @error('newRequestNote')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="col-12 d-flex justify-content-end">
                                            <button type="button" class="btn btn-primary" wire:click="submitRequest" wire:loading.attr="disabled">
                                                <span wire:loading.remove wire:target="submitRequest">Submit Request</span>
                                                <span wire:loading wire:target="submitRequest" class="spinner-border spinner-border-sm"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="separator separator-dashed my-5"></div>

                                <div class="vstack gap-4">
                                    @forelse($selectedRequests as $r)
                                        <div class="border rounded p-4">
                                            <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                                                <div class="fw-semibold">{{ $r['type'] ?: 'Request' }}</div>
                                                <div class="text-muted small">#{{ $r['request_number'] }}</div>
                                                <div class="ms-auto text-muted small">{{ $r['created_at_human'] }}</div>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2 mb-2">
                                                <span class="badge badge-light">Status: {{ $r['status'] }}</span>
                                                @if(!is_null($r['amount']))
                                                    <span class="badge badge-light">Amount: {{ $r['amount'] }}</span>
                                                @endif
                                                <span class="badge badge-light">By: {{ $r['author'] ?: 'User' }}</span>
                                            </div>
                                            @if(!empty($r['note']))
                                                <div class="text-gray-700">{{ $r['note'] }}</div>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="text-center text-muted py-8">No requests yet.</div>
                                    @endforelse
                                </div>
                            </div>

                            <!-- Status & Attempts Tab -->
                            <div class="tab-pane fade @if(($activeModalTab ?? 'cc_notes') === 'cc_status_attempts') show active @endif" id="cc_status_attempts" role="tabpanel">
                                <div class="card shadow-sm border border-gray-200 mb-6">
                                    <div class="card-header border-0 pt-5 pb-2 min-h-auto">
                                        <h3 class="card-title fw-bold text-gray-900 fs-4">Status & Attempts</h3>
                                        <div class="card-toolbar">
                                            @if(!$activeElectionId)
                                                <span class="badge badge-light-warning">No active election</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="card-body pt-2">
                                        <div class="text-muted fs-7 mb-5">Track specific call attempts below.</div>

                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="fw-bold text-gray-800">Call Attempts</div>
                                                @if(($visibleAttempts ?? 0) < 10)
                                                    <button type="button" class="btn btn-sm btn-icon btn-light-primary w-25px h-25px"
                                                            wire:click="addAttempt"
                                                            @disabled(!$activeElectionId)
                                                            title="Add Attempt">
                                                        <i class="ki-duotone ki-plus fs-2"><span class="path1"></span><span class="path2"></span></i>
                                                    </button>
                                                @endif
                                            </div>
                                            <span class="badge badge-light fs-8">Auto-save enabled</span>
                                        </div>

                                        @php
                                            $subStatusOptions = ['' => '—'] + ($activeSubStatuses ?? []);
                                            $phonesForAttempts = is_array($selectedDirectory->phones ?? null) ? array_values(array_filter($selectedDirectory->phones)) : [];
                                            $phonesForAttemptsNorm = array_map(fn($p) => \App\Models\DirectoryPhoneStatus::normalizePhone((string)$p), $phonesForAttempts);
                                            $phonesForAttemptsNorm = array_values(array_filter($phonesForAttemptsNorm));
                                            $defaultAttemptPhone = $phonesForAttemptsNorm[0] ?? '';
                                        @endphp

                                        <div id="ccAttemptsBox" class="vstack gap-3">
                                            @for($a = 1; $a <= ($visibleAttempts ?? 0); $a++)
                                                @php
                                                    $ss = $subStatusAttempts[(string)$a]['sub_status_id'] ?? '';
                                                    $isSet = !empty($ss);
                                                    $selPhone = $subStatusAttempts[(string)$a]['phone_number'] ?? '';
                                                    if (empty($selPhone) && !empty($defaultAttemptPhone)) {
                                                        $selPhone = $defaultAttemptPhone;
                                                    }
                                                @endphp

                                                <div class="p-4 rounded border {{ $isSet ? 'border-primary border-dashed bg-light-primary' : 'border-gray-300 border-dashed bg-light' }}">
                                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                                        <div class="fw-bold {{ $isSet ? 'text-primary' : 'text-gray-700' }}">Attempt {{ $a }}</div>
                                                        @if($isSet)
                                                            <span class="badge badge-light-primary fw-bold">{{ $subStatusOptions[$ss] ?? $ss }}</span>
                                                        @endif
                                                    </div>

                                                    <div class="row g-3 align-items-end">
                                                        <div class="col-12 col-md-3">
                                                            <label class="form-label fw-semibold fs-7 mb-1">Phone</label>
                                                            <select class="form-select form-select-sm" @disabled(!$activeElectionId || !count($phonesForAttemptsNorm))
                                                                    wire:change="updateSubStatusAttemptPhone('{{ $a }}', $event.target.value)">
                                                                @if(!count($phonesForAttemptsNorm))
                                                                    <option value="">No phone numbers</option>
                                                                @else
                                                                    @foreach($phonesForAttemptsNorm as $idx => $p)
                                                                        <option value="{{ $p }}" @selected((string)$selPhone === (string)$p)>
                                                                            {{ $phonesForAttempts[$idx] ?? $p }}
                                                                        </option>
                                                                    @endforeach
                                                                @endif
                                                            </select>
                                                        </div>

                                                        <div class="col-12 col-md-3">
                                                            <label class="form-label fw-semibold fs-7 mb-1">Sub status</label>
                                                            <select class="form-select form-select-sm" @disabled(!$activeElectionId)
                                                                    wire:change="updateSubStatusAttemptStatus('{{ $a }}', $event.target.value)">
                                                                @foreach($subStatusOptions as $val => $label)
                                                                    <option value="{{ $val }}" @selected($ss === (string) $val)>{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div class="col-12 col-md-6">
                                                            <label class="form-label fw-semibold fs-7 mb-1">Notes</label>
                                                            <input type="text" class="form-control form-control-sm" @disabled(!$activeElectionId)
                                                                   wire:model.defer="subStatusAttempts.{{ $a }}.notes"
                                                                   placeholder="What happened on attempt {{ $a }}?"
                                                                   wire:blur="updateSubStatusAttemptNotes('{{ $a }}')" />
                                                        </div>
                                                    </div>

                                                    @if($activeElectionId)
                                                        <div class="mt-3 d-flex justify-content-end">
                                                            <button type="button" class="btn btn-sm btn-icon btn-light-danger w-25px h-25px"
                                                                    wire:click="clearAttempt('{{ $a }}')" title="Clear attempt">
                                                                <i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                                                            </button>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endfor

                                            @if(($visibleAttempts ?? 0) === 0)
                                                <div class="text-center text-muted py-5 border border-dashed rounded bg-light">
                                                    <i class="ki-duotone ki-call fs-1 text-gray-400 mb-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span><span class="path7"></span><span class="path8"></span></i>
                                                    <div>No attempts added yet.</div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Call Status Tab -->
                            <div class="tab-pane fade @if(($activeModalTab ?? 'cc_notes') === 'cc_call_status') show active @endif" id="cc_call_status" role="tabpanel">
                                @php
                                    $phonesList = is_array($selectedDirectory->phones ?? null) ? array_filter($selectedDirectory->phones) : [];
                                    $subStatusOptions = ['' => '—'] + ($activeSubStatuses ?? []);
                                @endphp

                                @if(!count($phonesList))
                                    <div class="text-center text-muted py-8">No phone numbers found.</div>
                                @else
                                    <div class="vstack gap-4">
                                        @foreach($phonesList as $p)
                                            @php
                                                $norm = \App\Models\DirectoryPhoneStatus::normalizePhone((string) $p);
                                                $st = $norm ? ($this->phoneCallStatuses[$norm] ?? 'not_called') : 'not_called';
                                                $selectedSub = $norm ? ($this->phoneCallSubStatuses[$norm] ?? '') : '';
                                            @endphp

                                            <div class="border rounded p-4">
                                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                                    <div class="fw-semibold">{{ $p }}</div>
                                                </div>

                                                <div class="row g-3 align-items-end">
                                                    <div class="col-12 col-md-4">
                                                        <label class="form-label fw-semibold">Sub status</label>
                                                        <select class="form-select"
                                                                wire:change="updatePhoneCallStatusFromSubStatus('{{ $p }}', $event.target.value)">
                                                            @foreach($subStatusOptions as $val => $label)
                                                                <option value="{{ $val }}" @selected((string)$selectedSub === (string)$val)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                      
                                                    </div>

                                                    <div class="col-12 col-md-8">
                                                        <label class="form-label fw-semibold">Notes</label>
                                                        <input type="text" class="form-control"
                                                               wire:model.defer="phoneCallNotes.{{ $norm }}"
                                                               placeholder="Call notes..."
                                                               wire:blur="updatePhoneCallNotes('{{ $p }}')" />
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <!-- Form Tab -->
                            <div class="tab-pane fade @if(($activeModalTab ?? 'cc_notes') === 'cc_form') show active @endif" id="cc_form" role="tabpanel">
                                <div class="border rounded p-4">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
                                        @if(!$activeElectionId)
                                            <span class="badge badge-light-warning">No active election</span>
                                        @else
                                            <span class="badge badge-light-primary">Auto-save enabled</span>
                                        @endif
                                    </div>

                                    <div class="callcenter-dv-form" dir="rtl" lang="dv">
                                        <style>
                                            .callcenter-dv-form{font-family:Faruma, 'MV Faseyha', 'Noto Sans Thaana', Arial, sans-serif; font-size: 15px; line-height: 1.5;}
                                            .callcenter-dv-form .form-label{font-family:inherit; font-size: 15px;}
                                            .callcenter-dv-form .form-control,.callcenter-dv-form .form-select{font-family:inherit;text-align:right; font-size: 15px;}
                                            .callcenter-dv-form .text-muted.small{font-size: 13px;}
                                        </style>

                                        <!--begin::Alert-->
                                        <div class="alert alert-primary d-flex align-items-center p-5">
                                         
                                            <!--begin::Wrapper-->
                                            <div class="d-flex flex-column">
                                      

                                                <!--begin::Content-->
                                                <span class="fw-semibold">މި ގުޅާލީ ކުރިއަށް އޮތް ލޯކަލް ކައުންސިލް އިންތިހާބުގައި މާލެ ސިޓީ މޭޔަރ ކަމަށް މިހާރު މާލޭގެ މޭޔަރ އާދަމް އާޒިމް ކުރިމަތި ލައްވާ ފައިވާތީ ތިޔަ ބޭފުޅާގެ ހިޔާލު ހޯދާލުމަށް އާދަމް އާޒިމްގެ ކެމްޕެއިން އޮފީހުން. ފަސޭހަ ފުޅު ވަގުތެއްތޯ؟</span>
                                                <!--end::Content-->
                                            </div>
                                            <!--end::Wrapper-->
                                        </div>
                                        <!--end::Alert-->

                                        <div class="mb-5">
                                            <label class="form-label fw-semibold">
                                                1. މިހާރު މާލޭގެ މޭޔަރ ގޮތުގައި އާދަމް އާޒިމް ކުރައްވަމުން ގެންދަވާ މަސައްކަތާއި މެދު ދެކެވަޑައިގަންނަވަނީ ކިހިނެއްތޯ؟
                                            </label>
                                            <select class="form-select" wire:model.live="ccForm.q1_performance" @disabled(!$activeElectionId)>
                                                <option value="">—</option>
                                                <option value="kamudhey">ކަމުދޭ</option>
                                                <option value="kamunudhey">ކަމުނުދޭ</option>
                                                <option value="neyngey">ނޭނގޭ</option>
                                                <option value="mixed">މިކްސް އޮޕީނިއަން</option>
                                            </select>
                                        </div>

                                        @if(($ccForm['q1_performance'] ?? null) !== 'kamudhey')
                                            <div class="mb-5">
                                                <label class="form-label fw-semibold">
                                                    2. އެއީ ކިހިނެއްތޯ ވީ؟
                                                    <span class="text-muted small">(1 ގަި ކަމުނުދޭ / ނޭނގޭ / މިކްސް އޮޕީނިއަން ބުނެފިނަމަ)</span>
                                                </label>
                                                <textarea class="form-control" rows="3" wire:model.live="ccForm.q2_reason" placeholder="..." @disabled(!$activeElectionId)></textarea>
                                            </div>
                                        @endif

                                        <div class="mb-5">
                                            <label class="form-label fw-semibold">
                                                3. މާލޭގެ މޭޔަރ ކަމަށް އިތުރު ދައުރަކަށް އާދަމް އާޒިމް ކުރިމަތި ލެއްވުމަށް ތާއީދު ކުރައްވަންތޯ؟
                                            </label>
                                            <select class="form-select" wire:model.live="ccForm.q3_support" @disabled(!$activeElectionId)>
                                                <option value="">—</option>
                                                <option value="aanekey">އާނއެކޭ</option>
                                                <option value="noonekay">ނޫނެކޭ</option>
                                                <option value="neyngey">ނޭނގޭ</option>
                                            </select>
                                        </div>

                                        @if(($ccForm['q3_support'] ?? null) === 'aanekey')
                                            <div class="mb-5">
                                                <label class="form-label fw-semibold">
                                                    4. ވޯޓްލާ ދުވަހު ހުންނަވާނީ ކޮން ސަރަހައްދުގަތޯ؟
                                                </label>
                                                <select class="form-select" wire:model.live="ccForm.q4_voting_area" @disabled(!$activeElectionId)>
                                                    <option value="">—</option>
                                                    <option value="male">މާލެ</option>
                                                    <option value="vilimale">ވިލިމާލެ</option>
                                                    <option value="hulhumale_phase1">ހުޅުމާލެ ފޭސް 1</option>
                                                    <option value="hulhumale_phase2">ހުޅުމާލެ ފޭސް 2</option>
                                                    <option value="other">ނޫން (އެހެންނިހެން)</option>
                                                    <option value="unknown">ނޭނގޭ</option>
                                                </select>

                                                @if(($ccForm['q4_voting_area'] ?? null) === 'other')
                                                    <div class="mt-3">
                                                        <label class="form-label fw-semibold">ނޫން ނަމަ ކޮންތާކުތޯ؟</label>
                                                        <input type="text" class="form-control" wire:model.live="ccForm.q4_other_text" placeholder="..." @disabled(!$activeElectionId) />
                                                    </div>
                                                @else
                                                    <!-- Keep field disabled/hidden when not 'other' to avoid stale values in UI -->
                                                    <input type="hidden" wire:model.live="ccForm.q4_other_text" />
                                                @endif
                                            </div>

                                            @if(!in_array(($ccForm['q4_voting_area'] ?? null), ['other','unknown'], true))
                                                <div class="mb-5">
                                                    <label class="form-label fw-semibold">5. ވޯޓުލާން ދިއުމަށް އެހީތެރިކަމެއް ބޭނުންފުޅުވޭތޯ؟</label>
                                                    <select class="form-select" wire:model.live="ccForm.q5_help_needed" @disabled(!$activeElectionId)>
                                                        <option value="">—</option>
                                                        <option value="yes">އާނ</option>
                                                        <option value="no">ނޫން</option>
                                                        <option value="maybe">މަބީ</option>
                                                    </select>
                                                </div>
                                            @else
                                                <!-- Skip Q5 when Q4 is other/unknown, but keep field bound to avoid stale values -->
                                                <input type="hidden" wire:model.live="ccForm.q5_help_needed" />
                                            @endif

                                            <div class="mb-0">
                                                <label class="form-label fw-semibold">6. މޭޔަރ އަށް ދެއްވަން ބޭނުންފުޅުވާ ހިޔާލެއް އެބަ އޮތްތޯ؟ (ނޯޓްސް)</label>
                                                <textarea class="form-control" rows="4" wire:model.live="ccForm.q6_message_to_mayor" placeholder="..." @disabled(!$activeElectionId)></textarea>
                                            </div>
                                        @endif

                                        <!--begin::Alert-->
                                        <div class="alert alert-primary d-flex align-items-center p-5 pt-5 mt-6">
                                         
                                            <!--begin::Wrapper-->
                                            <div class="d-flex flex-column">
                                      

                                                <!--begin::Content-->
                                                <span class="fw-semibold">ތިޔަ ދެއްވި ވަގުތުކޮޅަށް ވަރަށް ބޮޑަށް ޝުކުރިއްޔާ! މިވަގުތު ވަކިވެލާނަން.</span>
                                                <!--end::Content-->
                                            </div>
                                            <!--end::Wrapper-->
                                        </div>
                                        <!--end::Alert-->

                                    </div>
                                </div>
                            </div>

                            <!-- History Tab -->
                            <div class="tab-pane fade @if(($activeModalTab ?? 'cc_notes') === 'cc_history') show active @endif" id="cc_history" role="tabpanel">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
                                    <div class="fw-semibold">History</div>
                                    <div class="text-muted small">All actions are logged (latest first).</div>
                                </div>

                                <div class="vstack gap-3">
                                    @forelse($selectedHistory as $h)
                                        <div class="border rounded p-4">
                                            <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                                                <div class="fw-semibold">{{ $h['event_type'] }}</div>
                                                <div class="text-muted small">{{ $h['created_at_human'] }}</div>
                                                <div class="ms-auto text-muted small">
                                                    {{ $h['user_name'] ?: 'User' }}
                                                    @if(!empty($h['ip_address']))
                                                        <span class="ms-2">({{ $h['ip_address'] }})</span>
                                                    @endif
                                                </div>
                                            </div>

                                            @if(!empty($h['description']))
                                                <div class="text-gray-800">{{ $h['description'] }}</div>
                                            @endif

                                            @if(!empty($h['event_data']))
                                                <details class="mt-3">
                                                    <summary class="text-muted small">View details</summary>
                                                    <pre class="bg-light rounded p-3 mt-2 mb-0" style="white-space:pre-wrap;">{{ json_encode($h['event_data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                </details>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="text-center text-muted py-8">No history logged yet.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:init', () => {
        // Preserve active tab inside the Call Center modal across Livewire re-renders
        let activeTabHref = '#cc_notes';

        const modalEl = document.getElementById('callCenterDirectoryModal');
        if (!modalEl) return;

        // Bootstrap modal instance
        let bsModal = null;
        const ensureModal = () => {
            if (!bsModal) bsModal = new bootstrap.Modal(modalEl, { backdrop: 'static' });
            return bsModal;
        };

        const rememberActiveTab = () => {
            const active = modalEl.querySelector('.nav-tabs .nav-link.active');
            if (active?.getAttribute('href')) {
                activeTabHref = active.getAttribute('href');
            }
        };

        const restoreActiveTab = () => {
            if (!activeTabHref) return;
            if (!modalEl.classList.contains('show')) return;

            const link = modalEl.querySelector(`.nav-tabs .nav-link[href="${activeTabHref}"]`);
            if (link && !link.classList.contains('active')) {
                const tab = new bootstrap.Tab(link);
                tab.show();
            }
        };

        // Show/Hide modal events from Livewire
        window.addEventListener('show-callcenter-directory-modal', () => {
            ensureModal().show();
            setTimeout(restoreActiveTab, 0);
        });
        window.addEventListener('hide-callcenter-directory-modal', () => ensureModal().hide());

        // New event names used by the restored Livewire methods
        window.addEventListener('open-call-center-directory-modal', () => {
            ensureModal().show();
            setTimeout(restoreActiveTab, 0);
        });
        window.addEventListener('close-call-center-directory-modal', () => ensureModal().hide());

        // When user changes tab, also update Livewire state so re-renders keep the same tab
        modalEl.addEventListener('shown.bs.tab', (e) => {
            rememberActiveTab();
            const href = e?.target?.getAttribute?.('href');
            if (href) {
                const id = href.replace('#', '');
                try {
                    Livewire.find(modalEl.closest('[wire\\:id]')?.getAttribute('wire:id'))
                        ?.call('setActiveModalTab', id);
                } catch (_) {
                    // ignore
                }
            }
        });

        // Before Livewire updates the DOM, remember the tab
        Livewire.hook('message.sent', () => {
            rememberActiveTab();
        });

        // After Livewire updates the DOM, restore the tab
        Livewire.hook('message.processed', () => {
            restoreActiveTab();
        });

        // When user closes the modal manually, tell Livewire to reset state
        modalEl.addEventListener('hidden.bs.modal', () => {
            Livewire.dispatch('closeDirectoryModal');
        });
    });
</script>

<script>
    document.addEventListener('livewire:init', () => {
        // Presence channel join/leave for directory modal
        const modalEl = document.getElementById('callCenterDirectoryModal');
        if (!modalEl) return;

        let currentChannelName = null;

        const getComponent = () => {
            const wid = modalEl.closest('[wire\\:id]')?.getAttribute('wire:id');
            return wid ? Livewire.find(wid) : null;
        };

        const syncMembersToLivewire = (members) => {
            const component = getComponent();
            if (!component) {
                console.warn('CallCenter presence: cannot find Livewire component for modal');
                return;
            }

            const list = [];
            if (Array.isArray(members)) {
                members.forEach(m => list.push(m));
            } else if (members && typeof members === 'object' && typeof members.each === 'function') {
                members.each(m => list.push(m));
            }

            component.call('syncPresenceUsers', list);
        };

        window.addEventListener('cc-presence-join', (e) => {
            const electionId = e?.detail?.electionId;
            const directoryId = e?.detail?.directoryId;
            if (!electionId || !directoryId) return;

            if (!window.Echo) {
                console.warn('CallCenter presence: window.Echo not available');
                return;
            }

            // leave previous
            if (currentChannelName) {
                try { window.Echo.leave(currentChannelName); } catch (_) {}
                currentChannelName = null;
            }

            currentChannelName = `call-center.directory.${electionId}.${directoryId}`;
            console.log('CallCenter presence: joining', currentChannelName);

            try {
                window.Echo.join(currentChannelName)
                    .here((users) => {
                        console.log('CallCenter presence: here', users);
                        syncMembersToLivewire(users);
                    })
                    .joining((user) => {
                        console.log('CallCenter presence: joining', user);
                        // We don't have a full list here; best-effort append
                        syncMembersToLivewire([user]);
                    })
                    .leaving((user) => {
                        console.log('CallCenter presence: leaving', user);
                        // best-effort: next .here will correct; meanwhile just keep list (optional)
                    })
                    .error((err) => {
                        console.error('CallCenter presence: error', err);
                    });
            } catch (err) {
                console.error('CallCenter presence: join failed', err);
            }
        });

        window.addEventListener('cc-presence-leave', () => {
            if (currentChannelName && window.Echo) {
                try { window.Echo.leave(currentChannelName); } catch (_) {}
            }
            currentChannelName = null;

            // Clear list in UI
            try { getComponent()?.call('syncPresenceUsers', []); } catch (_) {}
        });
    });
</script>
