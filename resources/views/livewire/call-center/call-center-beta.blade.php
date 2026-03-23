<div>
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
                'do_not_call' => 'dark',
                default => 'secondary'
            };
        };
        $statusText = function($dirId) use ($listStatuses, $dirStatusLabelMap){
            $s = $listStatuses[(string)$dirId] ?? 'not_started';
            return $dirStatusLabelMap[$s] ?? ucfirst(str_replace('_',' ', (string)$s));
        };
        $statusBadge = function($dirId) use ($listStatuses, $badgeForDirStatus){
            $s = $listStatuses[(string)$dirId] ?? 'not_started';
            return $badgeForDirStatus((string)$s);
        };

        $subStatusLabelMap = ($activeSubStatuses ?? []) + [
            // fallback for legacy string codes (if any old rows exist)
            'unreachable' => 'Unreachable',
            'wrong_number' => 'Wrong number',
            'callback' => 'Call back',
            'do_not_call' => 'Do not call',
        ];
        $badgeForSubStatus = function($v){
            return match($v){
                'callback' => 'info',
                'unreachable' => 'warning',
                'wrong_number' => 'danger',
                'do_not_call' => 'dark',
                default => 'secondary'
            };
        };
        $lastAttemptFor = function($dirId) use ($listSubStatuses){
            return $listSubStatuses[(string)$dirId] ?? null;
        };
        $lastAttemptText = function($dirId) use ($lastAttemptFor, $subStatusLabelMap){
            $a = $lastAttemptFor($dirId);
            if (!$a) return '—';
            $attemptNo = (int)($a['attempt'] ?? 0);
            $sub = (string)($a['sub_status_id'] ?? '');
            $label = $subStatusLabelMap[$sub] ?? ($sub !== '' ? $sub : '—');
            return $attemptNo > 0 ? ('Attempt ' . $attemptNo . ' • ' . $label) : $label;
        };
        $lastAttemptBadge = function($dirId) use ($lastAttemptFor, $badgeForSubStatus){
            $a = $lastAttemptFor($dirId);
            if (!$a) return 'secondary';
            $sub = (string)($a['sub_status_id'] ?? '');
            return $badgeForSubStatus($sub);
        };

        $statusBadgeClass = function($dirId) use ($listStatuses){
            $s = (string)($listStatuses[(string)$dirId] ?? 'not_started');
            return $s === 'completed'
                ? 'badge badge-success'
                : 'badge badge-warning';
        };

        // For attempts: always use danger badge as requested
        $attemptBadgeClass = function($dirId) use ($listSubStatuses){
            $has = array_key_exists((string)$dirId, $listSubStatuses) && !empty($listSubStatuses[(string)$dirId]);
            return $has ? 'badge badge-danger' : 'badge badge-light';
        };
    @endphp

    <div class="content fs-6 d-flex flex-column flex-column-fluid" id="kt_content">
        <div class="toolbar" id="kt_toolbar">
            <div class="container-fluid d-flex flex-stack flex-wrap flex-sm-nowrap">
                <div class="d-flex flex-column align-items-start justify-content-center flex-wrap me-2 mb-4 mb-sm-0">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <h1 class="text-dark fw-bold my-1 fs-2 mb-0">Call Center <span class="badge badge-light-primary ms-2">BETA</span></h1>
                        <a href="{{ route('call-center.index') }}" class="btn btn-sm btn-light">Open Classic</a>
                        <a href="{{ route('call-center.beta.completed-daily') }}" class="btn btn-sm btn-light-primary">Daily Completed</a>
                    </div>
                    <ul class="breadcrumb fw-semibold fs-base my-1">
                        <li class="breadcrumb-item text-muted"><a href="#" class="text-muted text-hover-primary">Operations</a></li>
                        <li class="breadcrumb-item text-dark">Call Center Beta</li>
                    </ul>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <span class="badge badge-light">Default: Pending</span>
                    <span class="badge badge-light-success">Fast list</span>
                </div>
            </div>
        </div>

        <div class="post fs-6 d-flex flex-column-fluid" id="kt_post">
            <div class="container-fluid">

                <!-- Summary card -->
                <div class="card border border-gray-200 shadow-sm mb-6">
                    <div class="card-body py-5">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div>
                                <div class="fw-bold text-gray-900 fs-4">Overview</div>
                                <div class="text-muted fs-8">Totals based on your current filters/search (sub consite + phone + search).</div>
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-6 col-md-4 col-xl-2">
                                <div class="p-3 rounded bg-light-warning">
                                    <div class="text-muted fs-8">Pending</div>
                                    <div class="fw-bold fs-2 text-gray-900">{{ (int)($totalsPending ?? 0) }}</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-4 col-xl-2">
                                <div class="p-3 rounded bg-light-success">
                                    <div class="text-muted fs-8">Completed</div>
                                    <div class="fw-bold fs-2 text-gray-900">{{ (int)($totalsCompleted ?? 0) }}</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-4 col-xl-2">
                                <div class="p-3 rounded bg-light-primary">
                                    <div class="text-muted fs-8">Completed By User</div>
                                    <div class="fw-bold fs-2 text-gray-900">{{ (int)($totalsCompletedByMe ?? 0) }}</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-4 col-xl-2">
                                <div class="p-3 rounded bg-light-info">
                                    <div class="text-muted fs-8">Completed Today</div>
                                    <div class="fw-bold fs-2 text-gray-900">{{ (int)($totalsCompletedToday ?? 0) }}</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-4 col-xl-2">
                                <div class="p-3 rounded bg-light-danger">
                                    <div class="text-muted fs-8">Attempts Today</div>
                                    <div class="fw-bold fs-2 text-gray-900">{{ (int)($totalsAttemptsToday ?? 0) }}</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-4 col-xl-2">
                                <div class="p-3 rounded bg-light">
                                    <div class="text-muted fs-8">Attempts Total</div>
                                    <div class="fw-bold fs-2 text-gray-900">{{ (int)($totalsAttemptsTotal ?? 0) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border border-gray-200 shadow-sm">
                    <div class="card-header border-0 pt-6 pb-3">
                        <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-3 w-100">

                            <div class="flex-grow-1">
                                <div class="position-relative">
                                    <span class="position-absolute top-50 translate-middle-y ms-4 text-muted">
                                        <i class="ki-duotone ki-magnifier fs-4">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </span>
                                    <input type="text" class="form-control form-control-solid ps-12" placeholder="Search name / NID / SERIAL / phone" wire:model.live.debounce.500ms="search" />
                                </div>
                            </div>

                            <div class="d-flex gap-3 flex-wrap justify-content-between justify-content-md-end">
                                <select class="form-select form-select-solid" style="min-width: 220px;" wire:model.live.debounce.250ms="filterSubConsiteId">
                                    <option value="">All Sub Consites</option>
                                    @foreach($subConsites as $sc)
                                        <option value="{{ $sc->id }}">{{ $sc->code }}{{ $sc->name ? ' - '.$sc->name : '' }}</option>
                                    @endforeach
                                </select>

                                <select class="form-select form-select-solid" style="min-width: 160px;" wire:model.live.debounce.250ms="filterStatus">
                                    <option value="pending">Pending</option>
                                    <option value="completed">Completed</option>
                                    <option value="all">All</option>
                                </select>

                                <select class="form-select form-select-solid w-auto" wire:model.live.debounce.250ms="perPage">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>

                                <div class="form-check form-check-custom form-check-solid mb-0 align-self-center">
                                    <input class="form-check-input" type="checkbox" id="cc_beta_hide_no_phone" wire:model.live.debounce.250ms="hideWithoutPhone">
                                    <label class="form-check-label text-muted" for="cc_beta_hide_no_phone">Hide no phone</label>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card-body pt-0">

                        <!-- Mobile cards -->
                        <div class="d-md-none">
                            <div class="d-flex flex-column gap-3">
                                @forelse($directories as $dir)
                                    @php
                                        $loc = $dir->permanentLocationString();
                                        if (!$loc || $loc === 'N/A') {
                                            $loc = $dir->currentLocationString();
                                        }
                                        $phonesText = is_array($dir->phones) ? implode(', ', array_filter($dir->phones)) : (string) $dir->phones;
                                        $imgUrl = $directoryImageUrls[(string)$dir->id] ?? null;
                                        $fallback = asset('assets/media/avatars/blank.png');
                                    @endphp

                                    <div class="card border border-gray-200 shadow-sm">
                                        <div class="card-body p-4">
                                            <div class="d-flex justify-content-between align-items-start gap-3">
                                                <div class="d-flex align-items-start gap-3 min-w-0">
                                                    <a href="{{ route('call-center.beta.directory', ['directory' => $dir->id]) }}" class="symbol symbol-45px symbol-circle flex-shrink-0">
                                                        <img src="{{ $imgUrl ?: $fallback }}" alt="{{ $dir->name }}" class="w-45px h-45px object-fit-cover" />
                                                    </a>
                                                    <div class="min-w-0">
                                                        <a href="{{ route('call-center.beta.directory', ['directory' => $dir->id]) }}" class="fw-bold text-gray-900 text-hover-primary text-truncate d-block">{{ $dir->name }}</a>
                                                        <div class="text-muted fs-8">{{ $dir->subConsite?->code }}</div>
                                                    </div>
                                                </div>

                                                <span class="{{ $statusBadgeClass($dir->id) }}">{{ $statusText($dir->id) }}</span>
                                            </div>

                                            <div class="mt-2">
                                                <span class="{{ $attemptBadgeClass($dir->id) }}">{{ $lastAttemptText($dir->id) }}</span>
                                            </div>

                                            <div class="separator my-3"></div>

                                            <div class="row g-3">
                                                <div class="col-6">
                                                    <div class="text-muted fs-8">NID</div>
                                                    <div class="fw-semibold text-gray-800 text-truncate">{{ $dir->id_card_number ?: '-' }}</div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="text-muted fs-8">Serial</div>
                                                    <div class="fw-semibold text-gray-800 text-truncate">{{ $dir->serial ?: '-' }}</div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="text-muted fs-8">Phone(s)</div>
                                                    <div class="fw-semibold text-gray-800" style="word-break: break-word;">{{ $phonesText ?: '-' }}</div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="text-muted fs-8">Block / Address</div>
                                                    <div class="fw-semibold text-gray-800" style="word-break: break-word;">{{ $loc ?: '-' }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center text-muted py-8">No directories found.</div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Desktop table -->
                        <div class="d-none d-md-block">
                            <div class="table-responsive">
                                <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-3">
                                    <thead>
                                        <tr class="fw-bold text-muted">
                                            <th class="min-w-240px">Directory</th>
                                            <th class="min-w-120px">Serial</th>
                                            <th class="min-w-160px">Phone(s)</th>
                                            <th class="min-w-260px">Block / Address</th>
                                            <th class="min-w-120px">Sub Consite</th>
                                            <th class="min-w-110px">Status</th>
                                            <th class="min-w-180px">Last attempt</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($directories as $dir)
                                            @php
                                                $imgUrl = $directoryImageUrls[(string)$dir->id] ?? null;
                                                $fallback = asset('assets/media/avatars/blank.png');
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <a href="{{ route('call-center.beta.directory', ['directory' => $dir->id]) }}" class="symbol symbol-40px symbol-circle flex-shrink-0">
                                                            <img src="{{ $imgUrl ?: $fallback }}" alt="{{ $dir->name }}" class="w-40px h-40px object-fit-cover" />
                                                        </a>
                                                        <div class="min-w-0">
                                                            <a href="{{ route('call-center.beta.directory', ['directory' => $dir->id]) }}" class="text-dark fw-semibold text-hover-primary text-truncate d-block">{{ $dir->name }}</a>
                                                            <div class="text-muted fs-8 text-truncate">{{ $dir->subConsite?->code }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-muted">{{ $dir->serial }}</td>
                                                <td class="text-muted" style="max-width: 240px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                    {{ is_array($dir->phones) ? implode(', ', array_filter($dir->phones)) : (string) $dir->phones }}
                                                </td>
                                                <td class="text-muted" style="max-width: 380px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                    @php
                                                        $loc = $dir->permanentLocationString();
                                                        if (!$loc || $loc === 'N/A') {
                                                            $loc = $dir->currentLocationString();
                                                        }
                                                    @endphp
                                                    {{ $loc }}
                                                </td>
                                                <td class="text-muted">{{ $dir->subConsite?->code }}</td>
                                                <td>
                                                    <span class="{{ $statusBadgeClass($dir->id) }}">{{ $statusText($dir->id) }}</span>
                                                </td>
                                                <td>
                                                    <span class="{{ $attemptBadgeClass($dir->id) }}">{{ $lastAttemptText($dir->id) }}</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center text-muted py-8">No directories found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="mt-4">
                            {{ $directories->links() }}
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
