<div wire:ignore.self class="modal fade" id="kt_modal_add_register" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Waste Management Registration</h2>
                <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"></i>
                </button>
            </div>

            <div class="modal-body">
                <form wire:submit.prevent="register" class="px-5">

                    <div class="mb-5">
                        <label class="form-label">Property</label>
                        <select id="property_select" class="form-select" name="property_id">
                            <option value="">Select Property</option>
                            @foreach($properties as $property)
                                <option value="{{ $property->id }}" @if($property->id == $property_id) selected @endif>{{ $property->name }}</option>
                            @endforeach
                        </select>
                        @error('property_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-5">
                        <label class="form-label">Directory</label>
                        <select id="directory_select" class="form-select" name="directories_id">
                            <option value="">Select Directory</option>
                            @foreach($directories as $dir)
                                <option value="{{ $dir->id }}" @if($dir->id == $directories_id) selected @endif>{{ $dir->name }}</option>
                            @endforeach
                        </select>
                        @error('directories_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-5">
                        <label class="form-label">Price List</label>
                        <select id="price_list_select" class="form-select" name="fk_waste_price_list">
                            <option value="">Select Price</option>
                            @foreach($priceLists as $price)
                                <option value="{{ $price->id }}" @if($price->id == $fk_waste_price_list) selected @endif>{{ $price->name }} - {{ $price->amount }} MVR</option>
                            @endforeach
                        </select>
                        @error('fk_waste_price_list') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-5">
                        <label class="form-label">Floor (optional)</label>
                        <input type="text" wire:model.defer="floor" class="form-control" name="floor">
                    </div>

                    <div class="mb-5">
                        <label class="form-label">Applicant</label>
                        <select wire:model.defer="applicant_is" class="form-select" name="applicant_is">
                            <option value="">Select</option>
                            <option value="owner">Owner</option>
                            <option value="renter">Renter</option>
                        </select>
                        @error('applicant_is') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-5">
                        <label class="form-label">Vehicle (optional)</label>
                        <select id="vehicle_select" class="form-select" name="vehicle_id">
                            <option value="">Select vehicle</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" @if($vehicle->id == $vehicle_id) selected @endif>{{ $vehicle->registration_number }} ({{ $vehicle->model }})</option>
                            @endforeach
                        </select>
                        @error('vehicle_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-5">
                        <label class="form-label">Start Date</label>
                        <input type="date" wire:model.defer="start_date" class="form-control" name="start_date">
                        @error('start_date') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="text-center pt-5">
                        <button type="submit" class="btn btn-primary">
                            <span class="indicator-label">Submit</span>
                            <span wire:loading wire:target="register" class="spinner-border spinner-border-sm ms-2"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const modalEl = document.getElementById('kt_modal_add_register');

        const mappings = [
            { id: 'property_select', model: 'property_id' },
            { id: 'directory_select', model: 'directories_id' },
            { id: 'price_list_select', model: 'fk_waste_price_list' },
            { id: 'vehicle_select', model: 'vehicle_id' }
        ];

        mappings.forEach(({ id, model }) => {
            const $el = $('#' + id);
            $el.select2({
                dropdownParent: $('#kt_modal_add_register'),
                width: '100%',
                placeholder: 'Select',
                allowClear: true
            });

            $el.on('change', function () {
                const selectedValue = $(this).val();
                window.livewire.emit('setLivewireValue', model, selectedValue);
            });
        });

        Livewire.on('showModal', () => {
            $('#kt_modal_add_register').modal('show');
        });

        Livewire.on('closeModal', () => {
            $('#kt_modal_add_register').modal('hide');
        });
    });
</script>