<div class="modal fade" id="kt_modal_user_votingboxes" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Manage Voting Boxes</h3>
                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-1"></i>
                </div>
            </div>
            <div class="modal-body">
                @if($votingBoxOptions && count($votingBoxOptions))
                    <div class="row g-6">
                        @foreach($votingBoxOptions as $opt)
                            <div class="col-md-6">
                                <label class="form-check form-check-custom form-check-solid">
                                    <input type="checkbox" class="form-check-input" wire:model.live="selectedVotingBoxIds" value="{{ $opt['id'] }}" />
                                    <span class="form-check-label">
                                        <strong>{{ $opt['name'] }}</strong>
                                    </span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-warning">No voting boxes found.</div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" wire:click="saveUserVotingBoxes">Save</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    window.addEventListener('showVotingBoxesModal', () => {
        const modal = new bootstrap.Modal(document.getElementById('kt_modal_user_votingboxes'));
        modal.show();
    });
    window.addEventListener('closeVotingBoxesModal', () => {
        const el = document.getElementById('kt_modal_user_votingboxes');
        const modal = bootstrap.Modal.getInstance(el);
        modal && modal.hide();
    });
</script>
@endpush
