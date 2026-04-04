<div class="container-fluid py-4">
    <div class="d-flex flex-wrap flex-stack mb-5">
        <div class="d-flex flex-column">
            <h1 class="d-flex align-items-center text-dark fw-bold my-1 fs-3">Voting Boxes</h1>
            <div class="text-muted">Browse boxes and view assigned directories</div>
        </div>
        @if($selectedBox)
            <div class="d-flex align-items-center gap-2">
                <span class="badge badge-light-info">Selected: {{ $selectedBox->name }}</span>
                <button class="btn btn-sm btn-light" wire:click="clearBox">Clear</button>
            </div>
        @endif
    </div>

    <div class="row g-5 g-xl-8">
        <div class="col-12 col-xl-5">
            <div class="card card-flush h-100">
                <div class="card-header pt-5">
                    <div class="card-title">
                        <h3 class="fw-bold">Boxes</h3>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-8">
                            <input type="text" class="form-control form-control-solid" placeholder="Search box name..." wire:model.live.debounce.400ms="search">
                        </div>
                        <div class="col-4">
                            <select class="form-select form-select-solid" wire:model.live="perPage">
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle gs-0 gy-3">
                            <thead>
                            <tr class="fw-bold text-muted">
                                <th>Box</th>
                                <th class="text-end">Directories</th>
                                <th class="text-end">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($boxes as $b)
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-gray-900">{{ $b->name }}</div>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge badge-light-primary">{{ (int) ($b->directories_count ?? 0) }}</span>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm {{ $selectedBox && $selectedBox->id === $b->id ? 'btn-primary' : 'btn-light-primary' }}" wire:click="viewBox('{{ $b->id }}')">View</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-10">No voting boxes found.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end">
                          {{ $boxes->links('vendor.pagination.new') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="card card-flush h-100">
                <div class="card-header pt-5">
                    <div class="card-title">
                        <h3 class="fw-bold">Box Details</h3>
                    </div>
                </div>
                <div class="card-body">
                    @if(!$selectedBox)
                        <div class="text-muted">Click <b>View</b> on a box to see directories in that box.</div>
                    @else
                        <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                            <div class="text-gray-900 fw-semibold">{{ $selectedBox->name }}</div>
                            <span class="badge badge-light-info">Total directories: {{ method_exists($directories, 'total') ? $directories->total() : $directories->count() }}</span>
                        </div>

                        <div class="row g-3 align-items-center mb-4">
                            <div class="col-12 col-md-7">
                                <input type="text" class="form-control form-control-solid" placeholder="Search name / NID / serial (use: s 123)" wire:model.live.debounce.400ms="directoriesSearch">
                                <div class="text-muted fs-8 mt-1">Tip: to search only by serial, type <b>s</b> then the number (e.g. <b>s 123</b>).</div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle gs-0 gy-3">
                                <thead>
                                <tr class="fw-bold text-muted">
                                    <th>Name</th>
                                    <th style="width: 140px">NID</th>
                                    <th style="width: 110px">Serial</th>
                                    <th>Phones / Email</th>
                                    <th>Party / SubConsite</th>
                                    <th>Permanent Address</th>
                                    <th>Final Pledge</th>
                                    @can('votedRepresentative-markAsVoted')
                                        <th class="text-end">Action</th>
                                    @endcan
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($directories as $d)
                                    <tr>
                                        <td class="fw-semibold text-gray-900">{{ $d->name }}</td>
                                        <td>
                                            <span class="badge badge-light">{{ $d->id_card_number ?? '-' }}</span>
                                        </td>
                                        <td>
                                            @if(!empty($d->serial))
                                                <span class="badge badge-light">{{ $d->serial }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $phones = $d->phones;
                                                if (is_string($phones)) {
                                                    $decoded = json_decode($phones, true);
                                                    if (json_last_error() === JSON_ERROR_NONE) {
                                                        $phones = $decoded;
                                                    }
                                                }
                                                if (is_array($phones)) {
                                                    $phones = implode(', ', array_filter($phones));
                                                }
                                            @endphp
                                            <div class="text-gray-800">{{ $phones ?: '-' }}</div>
                                            <div class="text-muted fs-8">{{ $d->email ?: '' }}</div>
                                        </td>
                                        <td>
                                            <div class="text-gray-800">{{ $d->party_short ?: '-' }}</div>
                                            <div class="text-muted fs-8">{{ $d->sub_consite_code ?: '-' }}</div>
                                        </td>
                                        <td>
                                            <div class="text-gray-800">{{ $d->street_address ?: $d->address ?: '-' }}</div>
                                        </td>
                                        <td>
                                            @php $fp = strtolower((string)($d->final_pledge_status ?? 'pending')); @endphp
                                            <span class="badge badge-light-{{ $fp === 'yes' ? 'success' : ($fp === 'no' ? 'danger' : ($fp === 'undecided' ? 'warning' : 'secondary')) }}">
                                                {{ $fp ?: 'pending' }}
                                            </span>
                                        </td>

                                        <td class="text-end">
                                            @if(!empty($d->is_voted))
                                                <div class="d-flex flex-column align-items-end gap-1">
                                                    <span class="badge badge-light-success">Voted</span>
                                                    @can('box-voted-undo')
                                                        <button type="button" class="btn btn-sm btn-light-danger" wire:click="undoVoted('{{ $d->id }}')">Undo</button>
                                                    @endcan
                                                </div>
                                            @else
                                                @can('votedRepresentative-markAsVoted')
                                                    <button type="button" class="btn btn-sm btn-success" wire:click="markAsVoted('{{ $d->id }}')">Vote</button>
                                                @endcan
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-10">No directories assigned to this box.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if(method_exists($directories, 'links'))
                            <div class="d-flex justify-content-end">
                                {{ $directories->links('vendor.pagination.new') }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
