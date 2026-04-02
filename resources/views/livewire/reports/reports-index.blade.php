<div class="container-xxl py-6">
    <div class="d-flex align-items-center justify-content-between mb-6">
        <div>
            <div class="fs-3 fw-bold">Reports</div>
            <div class="fs-7 text-muted">Generated files are kept for download after processing.</div>
        </div>
        <button type="button" class="btn btn-sm btn-light" wire:click="$refresh">
            Refresh
        </button>
    </div>

    <div class="card card-flush shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed gy-4">
                    <thead>
                    <tr class="text-gray-600 fw-semibold text-uppercase fs-8">
                        <th>Type</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Started</th>
                        <th>Finished</th>
                        <th class="text-end">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($reports as $r)
                        <tr>
                            <td class="fw-semibold">{{ $r->type }}</td>
                            <td>
                                @php
                                    $badge = match($r->status){
                                        'completed' => 'success',
                                        'failed' => 'danger',
                                        'running' => 'primary',
                                        default => 'warning',
                                    };
                                @endphp
                                <span class="badge badge-light-{{ $badge }} text-capitalize">{{ $r->status }}</span>
                                @if($r->status === 'failed' && $r->error)
                                    <div class="text-muted fs-8 mt-1" style="max-width:420px;white-space:normal;">{{ $r->error }}</div>
                                @endif
                            </td>
                            <td class="text-muted">{{ optional($r->created_at)->format('Y-m-d H:i') }}</td>
                            <td class="text-muted">{{ optional($r->started_at)->format('Y-m-d H:i') ?: '—' }}</td>
                            <td class="text-muted">{{ optional($r->finished_at)->format('Y-m-d H:i') ?: '—' }}</td>
                            <td class="text-end">
                                @if($r->status === 'completed')
                                    <button type="button" class="btn btn-sm btn-light-primary" wire:click="download('{{ $r->id }}')">
                                        Download
                                    </button>
                                @else
                                    <span class="text-muted fs-8">Not ready</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-10">No reports.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
