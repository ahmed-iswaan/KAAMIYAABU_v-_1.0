<div>
    <!--begin::Modal - Add Property-->
    <div wire:ignore.self class="modal fade" id="kt_modal_add_property" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Add Property</h2>
                    <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" onclick="closeAddModal()">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </button>
                </div>

                <div class="modal-body">
                    <form wire:submit.prevent="createProperty">
                        <div class="d-flex flex-column px-5">
                            @if (session()->has('error'))
                                <div class="alert alert-warning">{{ session('error') }}</div>
                            @endif

                            {{-- Name --}}
                            <div class="fv-row mb-5">
                                <label class="required fw-semibold fs-6 mb-2">Property Name</label>
                                <input type="text" wire:model.defer="name" class="form-control form-control-solid @error('name') is-invalid @enderror" placeholder="Enter property name">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Street Address --}}
                            <div class="fv-row mb-5">
                                <label class="required fw-semibold fs-6 mb-2">Street Address</label>
                                <input type="text" wire:model.defer="street_address" class="form-control form-control-solid @error('street_address') is-invalid @enderror" placeholder="Enter Street Address">
                                @error('street_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Property Type --}}
                            <div class="fv-row mb-5">
                                <label class="required fw-semibold fs-6 mb-2">Property Type</label>
                                <select wire:model.defer="property_type_id" class="form-select form-select-solid @error('property_type_id') is-invalid @enderror">
                                    <option value="">Select Type…</option>
                                    @foreach($types as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                    @endforeach
                                </select>
                                @error('property_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Number --}}
                            <div class="fv-row mb-5">
                                <label class="required fw-semibold fs-6 mb-2">Number</label>
                                <input type="number" wire:model.defer="number" class="form-control form-control-solid @error('number') is-invalid @enderror" placeholder="e.g. 0001">
                                @error('number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Registration Number --}}
                            <div class="fv-row mb-5">
                                <label class="required fw-semibold fs-6 mb-2">Registration Number</label>
                                <input type="text" wire:model.defer="register_number" class="form-control form-control-solid @error('register_number') is-invalid @enderror" placeholder="e.g. REG-2025-001">
                                @error('register_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Square Feet --}}
                            <div class="fv-row mb-5">
                                <label class="required fw-semibold fs-6 mb-2">Square Feet</label>
                                <input type="number" step="0.01" wire:model.defer="square_feet" class="form-control form-control-solid @error('square_feet') is-invalid @enderror" placeholder="Area in sq. ft.">
                                @error('square_feet') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Map Picker --}}
                            <div class="fv-row mb-5">
                                <label class="fw-semibold fs-6 mb-2">Pick on Map</label>
                                <div id="property-map" wire:ignore style="width:100%; height:300px; border:1px solid #ddd; border-radius:4px"></div>
                                <small class="text-gray-600">Click or drag marker to select coordinates: {{ $latitude }} - {{ $longitude }}</small>
                                @error('latitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                @error('longitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="text-center pt-10">
                            <button type="button" class="btn btn-light me-3" onclick="closeAddModal()">Discard</button>
                            <button type="submit" class="btn btn-primary">
                                <span class="indicator-label">Submit</span>
                                <span wire:loading wire:target="createProperty" class="indicator-progress">
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
    const modalEl = document.getElementById('kt_modal_add_property');

    modalEl.addEventListener('shown.bs.modal', () => {
        if (map) map.remove();
        map = L.map('property-map').setView([4.1740018873981, 73.513126373291], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        marker = L.marker([4.1740018873981, 73.513126373291], { draggable: true })
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
        @this.set('latitude', latlng.lat);
        @this.set('longitude', latlng.lng);
    }

    window.addEventListener('showAddPropertyModal', () => new bootstrap.Modal(modalEl).show());
    window.addEventListener('closeAddPropertyModal', () => {
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();

        // Manual cleanup just in case
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('padding-right');
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) backdrop.remove();
    });
});

function closeAddModal() {
    const modalEl = document.getElementById('kt_modal_add_property');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();

    setTimeout(() => {
        // Remove modal-related classes from body
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';

        // Remove any leftover modal backdrops
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());

        // Reset modal attributes just in case
        modalEl.classList.remove('show');
        modalEl.style.display = 'none';
        modalEl.removeAttribute('aria-modal');
        modalEl.setAttribute('aria-hidden', 'true');
    }, 300);
}
</script>
