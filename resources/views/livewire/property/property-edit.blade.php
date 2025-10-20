<div>
    <div wire:ignore.self class="modal fade" id="kt_modal_edit_property" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Edit Property</h2>
                    <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </button>
                </div>

                <div class="modal-body">
                    <form wire:submit.prevent="updateProperty">
                        <div class="d-flex flex-column px-5">
                            @if (session()->has('error'))
                                <div class="alert alert-warning">{{ session('error') }}</div>
                            @endif

                            <input type="hidden" wire:model.defer="editId">

                            <div class="fv-row mb-5">
                                <label class="required fw-semibold fs-6 mb-2">Property Name</label>
                                <input type="text" wire:model.defer="editName" class="form-control form-control-solid @error('editName') is-invalid @enderror">
                                @error('editName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="fv-row mb-5">
                                <label class="required fw-semibold fs-6 mb-2">Street Address</label>
                                <input type="text" wire:model.defer="editStreetAddress" class="form-control form-control-solid @error('editStreetAddress') is-invalid @enderror">
                                @error('editStreetAddress') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="fv-row mb-5">
                                <label class="required fw-semibold fs-6 mb-2">Property Type</label>
                                <select wire:model.defer="editPropertyTypeId" class="form-select form-select-solid @error('editPropertyTypeId') is-invalid @enderror">
                                    <option value="">Select Type…</option>
                                    @foreach($types as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                    @endforeach
                                </select>
                                @error('editPropertyTypeId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="fv-row mb-5">
                                <label class="required fw-semibold fs-6 mb-2">Number</label>
                                <input type="text" wire:model.defer="editNumber" class="form-control form-control-solid @error('editNumber') is-invalid @enderror">
                                @error('editNumber') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="fv-row mb-5">
                                <label class="required fw-semibold fs-6 mb-2">Registration Number</label>
                                <input type="text" wire:model.defer="editRegisterNumber" class="form-control form-control-solid @error('editRegisterNumber') is-invalid @enderror">
                                @error('editRegisterNumber') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="fv-row mb-5">
                                <label class="required fw-semibold fs-6 mb-2">Square Feet</label>
                                <input type="number" step="0.01" wire:model.defer="editSquareFeet" class="form-control form-control-solid @error('editSquareFeet') is-invalid @enderror">
                                @error('editSquareFeet') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="fv-row mb-5">
                                <label class="fw-semibold fs-6 mb-2">Pick on Map</label>
                                <div id="edit-property-map" wire:ignore style="width:100%; height:300px; border:1px solid #ddd; border-radius:4px"></div>
                                <small class="text-gray-600">Click or drag marker to select coordinates {{ $editLatitude }} - {{ $editLongitude }}</small>
                                @error('editLatitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                @error('editLongitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="text-center pt-10">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <span class="indicator-label">Update</span>
                                <span wire:loading wire:target="updateProperty" class="indicator-progress">
                                    Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', () => {
    let map, marker;
    const modalEl = document.getElementById('kt_modal_edit_property');

    modalEl.addEventListener('shown.bs.modal', () => {
        const lat = @this.get('editLatitude') || 4.1743563373714;
        const lng = @this.get('editLongitude') || 73.513383865356;

        if (map) map.remove();

        map = L.map('edit-property-map').setView([lat, lng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        marker = L.marker([lat, lng], { draggable: true })
            .addTo(map)
            .on('dragend', onMapPick);

        map.on('click', onMapPick);
    });

    modalEl.addEventListener('hidden.bs.modal', () => {
        if (map) {
            map.remove();
            map = null;
        }
    });

    function onMapPick(e) {
        const latlng = e.latlng || marker.getLatLng();
        marker.setLatLng(latlng);
        @this.set('editLatitude', latlng.lat);
        @this.set('editLongitude', latlng.lng);
    }

    window.addEventListener('showEditPropertyModal', () => {
        new bootstrap.Modal(document.getElementById('kt_modal_edit_property')).show();
    });
    window.addEventListener('closeEditPropertyModal', () => bootstrap.Modal.getInstance(modalEl)?.hide());
});
</script>