<div wire:ignore.self class="modal fade" id="kt_modal_edit_register" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Edit Waste Management Registration</h2>
<div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                                <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>                            </div>
            </div>

            <div class="modal-body">
                <form wire:submit.prevent="update" class="px-5">

                    <div class="mb-5" wire:ignore>
                        <label class="form-label required">Property</label>
                        <select class="form-select form-select-solid" id="kt_select2_edit_property_id" data-placeholder="Select Property">
                            <option></option>
                            @foreach($editProperties as $editProperty)
                                <option value="{{ $editProperty->id }}">
                                    {{ $editProperty->name }} / {{ $editProperty->island?->atoll?->code }}. {{ $editProperty->island?->name }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" id="hidden_edit_property_id" wire:model.defer="edit_property_id">
                        @error('edit_property_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-5" wire:ignore>
                        <label class="form-label required">Directory</label>
                        <select class="form-select form-select-solid" id="kt_select2_edit_directory_id" data-placeholder="Select Directory">
                            <option></option>
                            @foreach($editDirectories as $editDir)
                                <option value="{{ $editDir->id }}">{{ $editDir->name }} - {{ $editDir->registration_number }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" id="hidden_edit_directory_id" wire:model.defer="edit_directories_id">
                        @error('edit_directories_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-5" wire:ignore>
                        <label class="form-label required">Price List</label>
                        <select class="form-select form-select-solid" id="kt_select2_edit_price_list_id" data-placeholder="Select Price">
                           <option></option>
                            @foreach($editPriceLists as $editPrice)
                                <option value="{{ $editPrice->id }}">{{ $editPrice->name }} - {{ $editPrice->amount }} MVR</option>
                            @endforeach
                        </select>
                        <input type="hidden" id="hidden_edit_price_list_id" wire:model.defer="edit_fk_waste_price_list">
                        @error('edit_fk_waste_price_list') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-5">
                        <label class="form-label">Floor (optional)</label>
                        <input type="text" wire:model="edit_floor" class="form-control form-control-solid">
                    </div>

                    <div class="mb-5">
                        <label class="form-label required">Applicant</label>
                        <select wire:model="edit_applicant_is" class="form-select form-select-solid">
                            <option value="">Select</option>
                            <option value="owner">Owner</option>
                            <option value="renter">Renter</option>
                        </select>
                        @error('edit_applicant_is') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-5">
                        <label class="form-label">Inactive After Due Invoices</label>
                        <input type="number" wire:model.defer="edit_block_count" max="12" min="0" class="form-control form-control-solid" min="1" placeholder="e.g. 2">
                        @error('edit_block_count') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                 <div class="separator separator-content my-15">Collection</div>

                    <div class="mb-5">
                        <label class="form-label">Enable Waste Collection Schedule?</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" wire:model.live="edit_enable_schedule" id="edit_enable_schedule">
                            <label class="form-check-label" for="edit_enable_schedule">Yes</label>
                        </div>
                    </div>


                    @if($edit_enable_schedule)
                    <div class="mb-5">
                        <label class="form-label">Vehicle (optional)</label>
                        <select wire:model="edit_vehicle_id" class="form-select form-select-solid">
                            <option value="">Select vehicle</option>
                            @foreach($editVehicles as $editVehicle)
                                <option value="{{ $editVehicle->id }}">{{ $editVehicle->registration_number }} ({{ $editVehicle->model }})</option>
                            @endforeach
                        </select>
                        @error('edit_vehicle_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-5">
                        <label class="form-label required">Start Date</label>
                        <input type="date" wire:model="edit_start_date_collection" class="form-control form-control-solid">
                        @error('edit_start_date_collection') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-5">
                        <label class="form-label required">Next Collection Date</label>
                        <input type="date" wire:model="edit_next_collection_date" class="form-control form-control-solid">
                        @error('edit_next_collection_date') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-5">
                        <label class="form-label required">Recurrence</label>
                        <select wire:model="edit_recurrence_collection" class="form-select form-select-solid">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                        @error('edit_recurrence_collection') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-5">
                        <label class="form-label">Total Cycles</label>
                        <input type="number" wire:model.defer="edit_total_cycles_collection" min="0" class="form-control form-control-solid"  placeholder="e.g. 1">
                        @error('edit_total_cycles_collection') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>


                    @endif

                     <div class="separator separator-content my-15">Invoice</div>

                    <div class="mb-5">
                        <label class="form-label">Enable Invoice Schedule?</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" wire:model.live="edit_enable_invoice" id="edit_enable_invoice">
                            <label class="form-check-label" for="edit_enable_invoice">Yes</label>
                        </div>
                    </div>

                    @if($edit_enable_invoice)
                   <div class="mb-5">
                        <label class="form-label required">Start Date</label>
                        <input type="date" wire:model="edit_start_date_invoice" class="form-control form-control-solid">
                        @error('edit_start_date_invoice') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-5">
                        <label class="form-label required">Next Invoice Date</label>
                        <input type="date" wire:model="edit_next_invoice_date" class="form-control form-control-solid">
                        @error('edit_next_invoice_date') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-5">
                        <label class="form-label required">Recurrence</label>
                        <select wire:model="edit_recurrence_invoice" class="form-select form-select-solid">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                        @error('edit_recurrence_invoice') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-5">
                        <label class="form-label">Total Cycles</label>
                        <input type="number" wire:model.defer="edit_total_cycles_invoice" min="0" class="form-control form-control-solid"  placeholder="e.g. 1">
                        @error('edit_total_cycles_invoice') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-5">
                        <label class="form-label">Invoice Due ( Days )</label>
                        <input type="number" wire:model.defer="edit_due_days" min="0" class="form-control form-control-solid"  placeholder="e.g. 1">
                        @error('edit_due_days') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-5">
                        <label class="form-label required">Fine Interval</label>
                        <select wire:model.live="edit_fine_interval" class="form-select form-select-solid">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                        @error('edit_fine_interval') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-5">
                        <label class="form-label">Fine Rate</label>
                        <input type="number" wire:model.defer="edit_fine_rate" min="0" class="form-control form-control-solid"  placeholder="e.g. 1">
                        @error('edit_fine_rate') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-5">
                        <label class="form-label">Fine Grace Period (
                            @if($edit_fine_interval == 'daily')
                            Days
                            @elseif($edit_fine_interval == 'weekly')
                            Weeks
                            @else
                            Months
                            @endif
                             )</label>
                        <input type="number" wire:model.defer="edit_fine_grace_period" min="0" class="form-control form-control-solid"  placeholder="e.g. 1">
                        @error('edit_fine_grace_period') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    @endif


                    <div class="text-center pt-5">
                        <button type="submit" class="btn btn-primary">
                            <span class="indicator-label">Save Changes</span>
                            <span wire:loading wire:target="update" class="spinner-border spinner-border-sm ms-2"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('kt_modal_edit_register');
    const mainContent = document.getElementById('main-content');

    const selectsConfig = [
        { id: 'kt_select2_edit_property_id', hiddenId: 'hidden_edit_property_id', livewireProperty: 'edit_property_id' }, // Changed IDs and properties
        { id: 'kt_select2_edit_directory_id', hiddenId: 'hidden_edit_directory_id', livewireProperty: 'edit_directories_id' }, // Changed IDs and properties
        { id: 'kt_select2_edit_price_list_id', hiddenId: 'hidden_edit_price_list_id', livewireProperty: 'edit_fk_waste_price_list' } // Changed IDs and properties
    ];

    // Function to initialize a single Select2 instance
    const initSingleSelect2 = (selectId) => {
        const config = selectsConfig.find(c => c.id === selectId);
        if (!config) {
            console.warn(`[initSingleSelect2] Config not found for ID: ${selectId}`);
            return;
        }

        const $select = $(`#${config.id}`);
        const $hiddenInput = document.getElementById(config.hiddenId);

        if (!$select.length || !$hiddenInput) {
            console.warn(`[initSingleSelect2] Select element #${config.id} or hidden input #${config.hiddenId} not found in DOM. Skipping initialization.`);
            return;
        }

        // Destroy existing Select2 instance if it exists
        if ($select.data('select2')) {
            $select.select2('destroy');
            console.log(`[Select2 Init] Destroyed existing Select2 for #${config.id}`);
        }

        // Initialize Select2
        $select.select2({
            dropdownParent: $('#kt_modal_edit_register .modal-content'), // CRUCIAL for modals
            placeholder: $select.data('placeholder') || 'Select...',
            allowClear: true,
            minimumResultsForSearch: 0,
            width: '100%'
        });
        console.log(`[Select2 Init] Initialized Select2 for #${config.id}`);

        // Set the initial value from Livewire property
        // Use a short delay to ensure Livewire's internal state has propagated to the DOM
        // and options are fully rendered.
        setTimeout(() => {
            const livewireCurrentValue = @this.get(config.livewireProperty);
            console.log(`[Select2 Init] Attempting to set value for #${config.id}. Livewire property (${config.livewireProperty}):`, livewireCurrentValue);

            // Important: Re-check if the option exists after potential Livewire re-render
            if (livewireCurrentValue && !$select.find(`option[value="${livewireCurrentValue}"]`).length) {
                console.warn(`[Select2 Init] Option for value ${livewireCurrentValue} not found in #${config.id} after re-render. Appending temporary option.`);
                // Append a hidden option so Select2 can display the value
                // This is a fallback in case options haven't fully re-rendered or the value isn't among the standard options.
                $select.append(new Option('', livewireCurrentValue, true, true));
            }
            $select.val(livewireCurrentValue).trigger('change.select2');
            console.log(`[Select2 Init] Select2 value for #${config.id} set to: ${livewireCurrentValue || 'null/empty'}`);
        }, 100); // Slightly increased delay for more robustness

        // Bind change event to update the hidden input AND Livewire property directly
        // Use .off() to prevent multiple event listeners if initSingleSelect2 is called multiple times
$select.off('change').on('change', function () {
    const selectedVal = this.value;

    // Update Livewire via hidden input
    $hiddenInput.value = selectedVal;
    $hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));

    @this.set(config.livewireProperty, selectedVal);

    console.log(`[Select2 Change] ${config.id} changed. Re-initializing all Select2s.`);
    
    // 🔁 Re-initialize all Select2s on any change
    setTimeout(() => {
        initAllSelect2s();
    }, 100); // short delay to allow DOM state to settle
});

    };

    // Function to initialize ALL Select2 instances (used on modal show and after Livewire updates)
    const initAllSelect2s = () => {
        console.log('--- Initializing ALL Select2s ---');
        selectsConfig.forEach(config => initSingleSelect2(config.id));
    };

    // --- Modal Event Listeners ---

    // Initialize ALL Select2s when the modal is fully shown
    modalEl.addEventListener('shown.bs.modal', () => {
        console.log('Modal shown. Initializing ALL Select2s.');
        initAllSelect2s();
    });

    document.addEventListener('reinit-edit-select2', () => {
    setTimeout(() => {
        if (typeof initAllSelect2s === 'function') {
            initAllSelect2s();
        }
    }, 150);
});


    // Destroy ALL Select2s when the modal is hidden for cleanup
    modalEl.addEventListener('hidden.bs.modal', () => {
        console.log('Modal hidden. Destroying ALL Select2s and disposing modal instance.');
        selectsConfig.forEach(config => {
            const $select = $(`#${config.id}`);
            if ($select.data('select2')) {
                $select.select2('destroy');
            }
        });
        bootstrap.Modal.getInstance(modalEl)?.dispose();
        mainContent?.removeAttribute('inert');
    });

    // --- Livewire Event Listeners for Modal Control ---

    Livewire.on('showEditRegisterModal', () => {
        console.log('Livewire event: showEditRegisterModal received.');
        mainContent?.setAttribute('inert', '');
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });

    Livewire.on('closeEditRegisterModal', () => {
        console.log('Livewire event: closeEditRegisterModal received.');
        bootstrap.Modal.getInstance(modalEl)?.hide();
    });

    Livewire.on('formSubmittedOrReset', () => {
        console.log('Livewire event: formSubmittedOrReset received. Resetting form elements.');
        selectsConfig.forEach(config => {
            const $select = $(`#${config.id}`);
            const $hiddenInput = document.getElementById(config.hiddenId);

            if ($select.data('select2')) {
                $select.val('').trigger('change.select2'); // Clear Select2 visually
            }
            if ($hiddenInput) {
                $hiddenInput.value = '';
                $hiddenInput.dispatchEvent(new Event('input', { bubbles: true })); // Notify Livewire
            }
            @this.set(config.livewireProperty, null); // Explicitly reset Livewire property to null
        });
    });

    // --- Livewire Hook for Component-Wide DOM Updates ---
    // This hook fires after Livewire has processed a message and updated the DOM.
    // It's the most reliable point to re-initialize all Select2s when the modal is open
    // and Livewire might have replaced entire sections of the form.
Livewire.hook('message.processed', () => {
    setTimeout(() => {
        console.log('Livewire updated DOM. Re-initializing Select2...');
        initAllSelect2s();
    }, 100); // Wait to ensure the DOM is ready
});




    // Remove the element.updated hook from the previous attempt.
    // It's less effective here if Livewire is replacing parent elements.
});
</script>
@endpush