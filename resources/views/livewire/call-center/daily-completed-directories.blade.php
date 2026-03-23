<div>
    <div class="content fs-6 d-flex flex-column flex-column-fluid" id="kt_content">
        <div class="toolbar" id="kt_toolbar">
            <div class="container-fluid d-flex flex-stack flex-wrap flex-sm-nowrap">
                <div class="d-flex flex-column align-items-start justify-content-center flex-wrap me-2 mb-4 mb-sm-0">
                    <h1 class="text-dark fw-bold my-1 fs-2 mb-0">Daily Completed Directories</h1>
                    <ul class="breadcrumb fw-semibold fs-base my-1">
                        <li class="breadcrumb-item text-muted"><a href="#" class="text-muted text-hover-primary">Operations</a></li>
                        <li class="breadcrumb-item text-dark">Daily Completed</li>
                    </ul>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('call-center.beta') }}" class="btn btn-sm btn-light">Back to Call Center Beta</a>
                </div>
            </div>
        </div>

        <div class="post fs-6 d-flex flex-column-fluid" id="kt_post">
            <div class="container-fluid">
                <div class="card border border-gray-200 shadow-sm">
                    <div class="card-header border-0 pt-6 pb-3">
                        <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-3 w-100">
                            <div>
                                <label class="form-label text-muted mb-1">Date</label>
                                <input type="date" class="form-control form-control-solid" style="min-width: 170px;" wire:model.live.debounce.250ms="date" />
                            </div>

                            <div class="flex-grow-1">
                                <label class="form-label text-muted mb-1">Search</label>
                                <input type="text" class="form-control form-control-solid" placeholder="Search name / NID / SERIAL / phone" wire:model.live.debounce.500ms="search" />
                            </div>

                            <div>
                                <label class="form-label text-muted mb-1">Sub Consite</label>
                                <select class="form-select form-select-solid" style="min-width: 220px;" wire:model.live.debounce.250ms="filterSubConsiteId">
                                    <option value="">All Sub Consites</option>
                                    @foreach($subConsites as $sc)
                                        <option value="{{ $sc->id }}">{{ $sc->code }}{{ $sc->name ? ' - '.$sc->name : '' }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="form-label text-muted mb-1">Per page</label>
                                <select class="form-select form-select-solid" wire:model.live.debounce.250ms="perPage">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>

                            <div>
                                <label class="form-label text-muted mb-1">Phones</label>
                                <select class="form-select form-select-solid" style="min-width: 170px;" wire:model.live.debounce.250ms="phoneFilter">
                                    <option value="all">All</option>
                                    <option value="with_phone">With phone</option>
                                    <option value="no_phone">No phone</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-7 gy-4">
                                <thead>
                                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                        <th>Directory</th>
                                        <th>Phones</th>
                                        <th>Sub Consite</th>
                                        <th>Completed By</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody class="fw-semibold text-gray-700">
                                    @forelse($rows as $r)
                                        @php
                                            $phonesText = $r->phones_text ?? (is_array($r->directory_phones) ? implode(', ', array_filter($r->directory_phones)) : (string) $r->directory_phones);
                                        @endphp
                                        <tr>
                                            <td class="min-w-200px">
                                                <div class="d-flex flex-column">
                                                    <a class="text-gray-900 text-hover-primary fw-bold" href="{{ route('call-center.beta.directory', ['directory' => $r->directory_id]) }}">
                                                        {{ $r->directory_name ?? $r->directory_id }}
                                                    </a>
                                                    <div class="text-muted fs-8">
                                                        Serial: {{ $r->directory_serial ?: '-' }}
                                                        <span class="mx-2">•</span>
                                                        NID: {{ $r->directory_nid ?: '-' }}
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="min-w-250px" style="word-break: break-word;">{{ $phonesText ?: '-' }}</td>
                                            <td class="min-w-100px">{{ $r->sub_consite_code ?: '-' }}</td>
                                            <td class="min-w-150px">{{ $r->user_name ?: ($r->updated_by ?: '-') }}</td>
                                            <td class="min-w-160px">
                                                @php
                                                    $dt = $r->completed_dt ?? null;
                                                @endphp
                                                {{ $dt ? \Illuminate\Support\Carbon::parse($dt)->format('Y-m-d H:i:s') : '-' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-6">No completed directories for this date.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div class="text-muted fs-8">
                                @if($rows instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                    Showing {{ $rows->firstItem() ?? 0 }}–{{ $rows->lastItem() ?? 0 }} of {{ $rows->total() ?? 0 }}
                                @endif
                            </div>
                            <div>
                                {{ $rows->links() }}
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
