<div class="container-fluid py-5">
    <div class="card shadow-sm mb-5">
        <div class="card-header border-0 pt-6 pb-4">
            <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between">
                <div>
                    <h3 class="card-title m-0 fw-bold">Voted List</h3>
                    <div class="text-muted small">Showing {{ $rows->total() }} record(s)</div>
                </div>

                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <div class="position-relative" style="min-width: 280px">
                        <input
                            type="text"
                            class="form-control form-control-sm ps-10"
                            placeholder="Search name / ID card"
                            wire:model.live.debounce.300ms="search"
                        />
                        <span class="position-absolute top-50 translate-middle-y text-muted" style="left: 10px">
                            <i class="bi bi-search"></i>
                        </span>
                    </div>

                    <select class="form-select form-select-sm" wire:model.live="votingBoxId" style="min-width: 220px">
                        <option value="">All Voting Boxes</option>
                        @foreach(($votingBoxes ?? []) as $vb)
                            <option value="{{ $vb->id }}">{{ $vb->name }}</option>
                        @endforeach
                    </select>

                    <select class="form-select form-select-sm" wire:model.live="perPage" style="width: 120px">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-row-bordered table-row-gray-200 align-middle mb-0">
                    <thead class="text-uppercase text-muted small" style="position: sticky; top: 0; z-index: 1; background: var(--bs-body-bg);">
                        <tr>
                            <th class="ps-4" style="width: 70px">#</th>
                            <th>Directory</th>
                            <th style="width: 170px">ID Card</th>
                            <th style="width: 180px">Sub Consite</th>
                            <th style="width: 160px">Voting Box</th>
                            <th style="width: 240px">Marked By</th>
                            <th style="width: 180px">Voted At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <?php $dir = $row->directory; ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="badge bg-light text-dark border">
                                        {{ ($rows->currentPage() - 1) * $rows->perPage() + $loop->iteration }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="{{ ($directoryImageUrls[$row->id] ?? null) ?: asset('assets/media/avatars/blank.png') }}" alt="" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;" />
                                        <div class="min-w-0">
                                            <div class="fw-semibold text-truncate" style="max-width: 320px">{{ $dir?->name ?? '-' }}</div>
                                            @if(!empty($dir?->serial))
                                                <div class="text-muted small mt-1">
                                                    <span class="badge bg-light text-dark border">S: {{ $dir->serial }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="fw-semibold">{{ $dir?->id_card_number ?? '-' }}</td>
                                <td>{{ $dir?->subConsite?->name ?? '-' }}</td>
                                <td>{{ $dir?->votingBox?->name ?? '-' }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <?php $u = $row->user; ?>
                                        <?php $userImg = ($u && $u->profile_picture) ? asset('storage/'.$u->profile_picture) : null; ?>

                                        @if($userImg)
                                            <img src="{{ $userImg }}" alt="" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;" />
                                        @else
                                            <div class="rounded-circle bg-light border" style="width:32px;height:32px;"></div>
                                        @endif
                                        <div class="min-w-0">
                                            <div class="fw-semibold text-truncate" style="max-width: 200px">{{ $u?->name ?? '-' }}</div>
                                            <div class="text-muted small">User ID: {{ $row->user_id }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $votedAt = $row->voted_at;
                                        if (is_string($votedAt) && $votedAt !== '') {
                                            try {
                                                $votedAt = \Illuminate\Support\Carbon::parse($votedAt);
                                            } catch (\Throwable $e) {
                                                $votedAt = null;
                                            }
                                        }
                                    @endphp

                                    @if($votedAt)
                                        <div class="fw-semibold">{{ $votedAt->format('Y-m-d') }}</div>
                                        <div class="text-muted small">{{ $votedAt->format('H:i:s') }}</div>
                                    @else
                                        <div class="text-muted">—</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-7">
                                    <div class="d-flex flex-column align-items-center gap-2">
                                        <div class="rounded-circle bg-light border" style="width:52px;height:52px;"></div>
                                        <div class="fw-semibold">No voted records found</div>
                                        <div class="small">Try changing the search text or per-page size.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer bg-transparent border-0 pt-4">
            {{ $rows->links() }}
        </div>
    </div>
</div>
