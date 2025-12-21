<div class="modal fade" id="finalPledgeModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Set Final Pledge</h5>
                <button type="button" class="btn btn-sm btn-icon btn-active-light-success" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>
                </button>
            </div>
            <div class="modal-body">
                @php $options=['yes'=>'Yes','no'=>'No','neutral'=>'Undecided']; @endphp
                <div class="vstack gap-3">
                    @foreach($options as $val=>$label)
                        <label class="form-check form-check-custom form-check-solid">
                            <input type="radio" name="final_pledge" class="form-check-input" value="{{ $val }}" wire:model.defer="final_status">
                            <span class="form-check-label">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" wire:click="saveFinalPledge">Save</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function cleanupBootstrapBackdrop() {
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('padding-right');
    }

    function attachCleanupOnHidden(id){
        const el = document.getElementById(id);
        if(!el) return;
        el.addEventListener('hidden.bs.modal', cleanupBootstrapBackdrop, { once: false });
    }

    window.addEventListener('show-final-pledge-modal', ()=>{
        const el = document.getElementById('finalPledgeModal');
        if(!el || typeof bootstrap==='undefined') return;
        const modal = new bootstrap.Modal(el, { backdrop: true });
        attachCleanupOnHidden('finalPledgeModal');
        modal.show();
    });
    window.addEventListener('hide-final-pledge-modal', ()=>{
        const el = document.getElementById('finalPledgeModal');
        if(!el || typeof bootstrap==='undefined') return;
        const modal = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
        modal.hide();
        setTimeout(cleanupBootstrapBackdrop, 150);
    });
</script>
@endpush
