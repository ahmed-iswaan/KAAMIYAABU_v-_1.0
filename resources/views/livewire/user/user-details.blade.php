@section('title', 'User Details')

<div>
    <div class="content fs-6 d-flex flex-column flex-column-fluid" id="kt_content">
        <div class="toolbar" id="kt_toolbar">
            <div class="container-fluid d-flex flex-stack flex-wrap flex-sm-nowrap">
                <div class="d-flex flex-column align-items-start justify-content-center flex-wrap me-2">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <a href="{{ route('users') }}" class="btn btn-sm btn-light">Back</a>
                        <h1 class="text-dark fw-bold my-1 fs-2 mb-0">User Details</h1>
                    </div>
                    <ul class="breadcrumb fw-semibold fs-base my-1">
                        <li class="breadcrumb-item text-muted"><a href="#" class="text-muted text-hover-primary">User Management</a></li>
                        <li class="breadcrumb-item text-muted"><a href="{{ route('users') }}" class="text-muted text-hover-primary">Users</a></li>
                        <li class="breadcrumb-item text-dark">Details</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="post fs-6 d-flex flex-column-fluid" id="kt_post">
            <div class="container-xxl">

                <div class="card card-flush mb-6">
                    <div class="card-body py-6">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-4">
                            <div>
                                <div class="fw-bold fs-3">{{ $user->name }}</div>
                                <div class="text-muted">{{ $user->email }}</div>
                            </div>
                            <div class="text-muted fs-7">
                                Active election:
                                {{ $activeElectionName ?: ($activeElectionId ?: 'None') }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-6">
                    <div class="col-12 col-xl-6">
                        <div class="card card-flush">
                            <div class="card-header">
                                <div class="card-title fw-bold">Attempts ({{ $attempts->total() ?? 0 }})</div>
                                <div class="card-toolbar">
                                    <button type="button" class="btn btn-sm btn-light-primary" wire:click="downloadAttemptsCsv">
                                        Download CSV
                                    </button>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-dashed align-middle gy-4">
                                        <thead>
                                        <tr class="text-gray-600 fw-semibold text-uppercase fs-8">
                                            <th>Date</th>
                                            <th>Directory</th>
                                            <th>Attempt</th>
                                            <th>Sub Status</th>
                                            <th>Phone</th>
                                        </tr>
                                        </thead>
                                        <tbody class="fw-semibold text-gray-700">
                                        @forelse($attempts as $a)
                                            @php
                                                $dir = $directories[(string)$a->directory_id] ?? null;
                                                $ssid = (string)($a->sub_status_id ?? '');
                                                $ssName = $ssid !== '' ? (($activeSubStatuses[$ssid] ?? '') ?: $ssid) : '-';
                                            @endphp
                                            <tr>
                                                <td class="text-muted">{{ optional($a->updated_at)->format('d M Y, g:i a') }}</td>
                                                <td>
                                                    @if($dir)
                                                        <a class="text-gray-800 text-hover-primary" href="{{ route('call-center.beta.directory', ['directory' => (string)$dir->id]) }}">
                                                            {{ $dir->name }}
                                                        </a>
                                                        <div class="text-muted fs-8">SERIAL: {{ $dir->serial ?? '-' }} | NID: {{ $dir->id_card_number ?? '-' }}</div>
                                                    @else
                                                        <span class="text-muted">{{ (string)$a->directory_id }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-center"><span class="badge badge-light">{{ (int)$a->attempt }}</span></td>
                                                <td>{{ $ssName }}</td>
                                                <td class="font-monospace">{{ (string)($a->phone_number ?? '-') }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-muted py-6">No attempts found.</td></tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="d-flex justify-content-end">
                                    {{ $attempts->links('vendor.pagination.new') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-6">
                        <div class="card card-flush">
                            <div class="card-header">
                                <div class="card-title fw-bold">Completed Directories ({{ $completed->total() ?? 0 }})</div>
                                <div class="card-toolbar">
                                    <button type="button" class="btn btn-sm btn-light-primary" wire:click="downloadCompletedCsv">
                                        Download CSV
                                    </button>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-dashed align-middle gy-4">
                                        <thead>
                                        <tr class="text-gray-600 fw-semibold text-uppercase fs-8">
                                            <th>Date</th>
                                            <th>Directory</th>
                                            <th>Status</th>
                                        </tr>
                                        </thead>
                                        <tbody class="fw-semibold text-gray-700">
                                        @forelse($completed as $c)
                                            @php
                                                $dir = $directories[(string)$c->directory_id] ?? null;
                                                $dt = $c->completed_at ?: $c->updated_at;
                                            @endphp
                                            <tr>
                                                <td class="text-muted">{{ optional($dt)->format('d M Y, g:i a') }}</td>
                                                <td>
                                                    @if($dir)
                                                        <a class="text-gray-800 text-hover-primary" href="{{ route('call-center.beta.directory', ['directory' => (string)$dir->id]) }}">
                                                            {{ $dir->name }}
                                                        </a>
                                                        <div class="text-muted fs-8">SERIAL: {{ $dir->serial ?? '-' }} | NID: {{ $dir->id_card_number ?? '-' }}</div>
                                                    @else
                                                        <span class="text-muted">{{ (string)$c->directory_id }}</span>
                                                    @endif
                                                </td>
                                                <td><span class="badge badge-light-success">Completed</span></td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="text-center text-muted py-6">No completed directories found.</td></tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="d-flex justify-content-end">
                                    {{ $completed->links('vendor.pagination.new') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
