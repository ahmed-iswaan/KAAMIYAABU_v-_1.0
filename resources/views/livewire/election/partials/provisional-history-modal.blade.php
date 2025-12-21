<div class="modal fade" id="provisionalHistoryModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Provisional Pledge History</h5>
                <button type="button" class="btn btn-sm btn-icon btn-active-light-primary" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Status</th>
                                <th>Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($provisionalHistory as $item)
                                <tr>
                                    <td>{{ $item['user'] ?? '—' }}</td>
                                    <td>
                                        @php $cm=['yes'=>'primary','no'=>'warning','neutral'=>'secondary','pending'=>'light']; $lbl= strtoupper($item['status']); @endphp
                                        <span class="badge badge-{{ $cm[$item['status']] ?? 'light' }} fw-bold">{{ $lbl }}</span>
                                    </td>
                                    <td class="text-muted">{{ $item['updated_at'] ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted">No history found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function cleanupBootstrapBackdrop() {
        // Remove any lingering backdrops and reset body state
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('padding-right');
    }

    function attachCleanupOnHidden(id){
        const el = document.getElementById(id);
        if(!el) return;
        el.addEventListener('hidden.bs.modal', cleanupBootstrapBackdrop, { once: false });
    }

    window.addEventListener('show-provisional-history-modal', ()=>{
        const el = document.getElementById('provisionalHistoryModal');
        if(!el || typeof bootstrap==='undefined') return;
        const modal = new bootstrap.Modal(el, { backdrop: true });
        attachCleanupOnHidden('provisionalHistoryModal');
        modal.show();
    });
    window.addEventListener('hide-provisional-history-modal', ()=>{
        const el = document.getElementById('provisionalHistoryModal');
        if(!el || typeof bootstrap==='undefined') return;
        const modal = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
        modal.hide();
        setTimeout(cleanupBootstrapBackdrop, 150);
    });
</script>
@endpush
