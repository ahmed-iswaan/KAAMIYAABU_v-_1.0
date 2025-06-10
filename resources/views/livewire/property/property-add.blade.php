<div>
    <!--begin::Modal - Add Property-->
    <div wire:ignore.self class="modal fade" id="kt_modal_add_property" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Add Property</h2>
                    <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary"
                            data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"></i>
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
                                <input type="text" wire:model.defer="name"
                                       class="form-control form-control-solid @error('name') is-invalid @enderror"
                                       placeholder="Enter property name">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Property Type --}}
                            <div class="fv-row mb-5">
                                <label class="required fw-semibold fs-6 mb-2">Property Type</label>
                                <select wire:model.defer="property_type_id"
                                        class="form-select form-select-solid @error('property_type_id') is-invalid @enderror">
                                    <option value="">Select Type…</option>
                                    @foreach($types as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                    @endforeach
                                </select>
                                @error('property_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Registration Number --}}
                            <div class="fv-row mb-5">
                                <label class="required fw-semibold fs-6 mb-2">Registration Number</label>
                                <input type="text" wire:model.defer="register_number"
                                       class="form-control form-control-solid @error('register_number') is-invalid @enderror"
                                       placeholder="e.g. REG-2025-001">
                                @error('register_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Square Feet --}}
                            <div class="fv-row mb-5">
                                <label class="required fw-semibold fs-6 mb-2">Square Feet</label>
                                <input type="number" step="0.01" wire:model.defer="square_feet"
                                       class="form-control form-control-solid @error('square_feet') is-invalid @enderror"
                                       placeholder="Area in sq. ft.">
                                @error('square_feet') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Island --}}
                            <div class="fv-row mb-5">
                                <label class="required fw-semibold fs-6 mb-2">Island</label>
                                <select wire:model="island_id"
                                        class="form-select form-select-solid @error('island_id') is-invalid @enderror">
                                    <option value="">Select Island…</option>
                                    @foreach($islands as $isl)
                                        <option value="{{ $isl->id }}">{{ $isl->name }}</option>
                                    @endforeach
                                </select>
                                @error('island_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            @if($wards->isNotEmpty())
                                {{-- Ward --}}
                                <div class="fv-row mb-5">
                                    <label class="fw-semibold fs-6 mb-2">Ward</label>
                                    <select wire:model.defer="ward_id"
                                            class="form-select form-select-solid @error('ward_id') is-invalid @enderror">
                                        <option value="">Select Ward…</option>
                                        @foreach($wards as $w)
                                            <option value="{{ $w->id }}">{{ $w->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('ward_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            @endif


                         {{-- Map picker --}}
                        <div class="fv-row mb-5">
                        <label class="fw-semibold fs-6 mb-2">Pick on Map</label>
                        <div
                            id="property-map"
                              wire:ignore
                            style="width:100%; height:300px; border:1px solid #ddd; border-radius:4px">
                        </div>
                        <small class="text-gray-600">Click or drag marker to select coordinates {{$latitude}} - {{$longitude}}</small>

                        </div>
                        


                        </div>

                        <!-- Actions -->
                        <div class="text-center pt-10">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
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

  // When the modal fully shows, create the map fresh
  modalEl.addEventListener('shown.bs.modal', () => {
    // Destroy any old map instance
    if (map) {
      map.remove();
      map = null;
    }

    // Initialize a new Leaflet map
    map = L.map('property-map').setView([2.9472123612803185, 73.58467103227593], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Draggable marker
    marker = L.marker([2.9472123612803185, 73.58467103227593], { draggable: true })
      .addTo(map)
      .on('dragend', onMapPick);

    // Click on map to move marker
    map.on('click', onMapPick);
  });

  // When the modal hides, destroy the map to avoid stale containers
  modalEl.addEventListener('hidden.bs.modal', () => {
    if (map) {
      map.remove();
      map = null;
    }
  });

  // Shared handler for map click or marker drag
  function onMapPick(e) {
    const latlng = e.latlng || marker.getLatLng();
    marker.setLatLng(latlng);

    // Push back into Livewire
    @this.set('latitude',  latlng.lat);
    @this.set('longitude', latlng.lng);
  }

  // Listen for your Livewire events to open/close the modal
  window.addEventListener('showAddPropertyModal',  () => new bootstrap.Modal(modalEl).show());
  window.addEventListener('closeAddPropertyModal', () => bootstrap.Modal.getInstance(modalEl)?.hide());
});
</script>




