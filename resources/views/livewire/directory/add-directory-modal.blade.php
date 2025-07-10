<div wire:ignore.self class="modal fade" id="kt_modal_add_user" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Add New Directory</h2>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"></i>
                </button>
            </div>

            <div class="modal-body py-10 px-lg-17">
                <form wire:submit.prevent="save">

                <div class="fv-row mb-7">
                <!--begin::Label-->
                <label class="d-block fw-semibold fs-6 mb-5">Profile Picture</label>
                <!--end::Label-->

                <style>
                    .image-input-placeholder {
                        background-image: url('{{ asset("assets/media/svg/files/blank-image.svg") }}');
                    }
                    [data-bs-theme="dark"] .image-input-placeholder {
                        background-image: url('{{ asset("assets/media/svg/files/blank-image-dark.svg") }}');
                    }
                </style>

                <!--begin::Image input-->
                <div class="image-input image-input-outline image-input-placeholder {{ $profile_picture ? '' : 'image-input-empty' }}"
                    data-kt-image-input="true" wire:ignore>
                    
                    <!-- Preview Image -->
                    <div class="image-input-wrapper w-125px h-125px"
                        style="background-image:
                        @if($profile_picture)
                            url('{{ $profile_picture->temporaryUrl() }}')
                        @elseif(isset($user) && $user->profile_picture)
                            url('{{ asset("storage/{$user->profile_picture}") }}')
                        @else
                            none
                        @endif;">
                    </div>

                    <!-- Change button -->
                    <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                        data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change picture">
                        <i class="ki-duotone ki-pencil fs-7">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        <input type="file" wire:model="profile_picture" accept=".png, .jpg, .jpeg">
                    </label>

                    <!-- Cancel -->
                    <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                        data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel picture">
                        <i class="ki-duotone ki-cross fs-2">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </span>

                    <!-- Remove -->
                    <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                        data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove picture"
                        onclick="@this.set('profile_picture', null)">
                        <i class="ki-duotone ki-cross fs-2">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </span>
                </div>
                <!--end::Image input-->

                <div class="form-text">Allowed file types: png, jpg, jpeg. Max size: 1MB.</div>
                @error('profile_picture') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
 
                    <div class="row mb-5">
                        <div class="col-md-6">
                            <label class="form-label required">Name</label>
                            <input type="text" class="form-control form-control-solid" wire:model.defer="name">
                            @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Description</label>
                            <textarea class="form-control form-control-solid" wire:model.defer="description"></textarea>
                        </div>
                    </div>

                    <div class="row mb-5">
                        <div class="col-md-6">
                            <label class="form-label">Directory Type</label>
                            <select class="form-select form-select-solid" wire:model.defer="directory_type_id">
                                <option value="">Select...</option>
                                @foreach($directoryTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                            @error('directory_type_id') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Registration Type</label>
                            <select class="form-select form-select-solid" wire:model.defer="registration_type_id">
                                <option value="">Select...</option>
                                @foreach($registrationTypes as $reg)
                                    <option value="{{ $reg->id }}">{{ $reg->name }}</option>
                                @endforeach
                            </select>
                            @error('registration_type_id') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="row mb-5">
                        <div class="col-md-6">
                            <label class="form-label">Registration Number</label>
                            <input type="text" class="form-control form-control-solid" wire:model.defer="registration_number">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">GST Number</label>
                            <input type="text" class="form-control form-control-solid" wire:model.defer="gst_number">
                        </div>
                    </div>

                    <div class="row mb-5">
                        <div class="col-md-4">
                            <label class="form-label">Gender</label>
                            <select class="form-select form-select-solid" wire:model.defer="gender">
                                <option value="">Select...</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control form-control-solid" wire:model.defer="date_of_birth">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Death Date</label>
                            <input type="date" class="form-control form-control-solid" wire:model.defer="death_date">
                        </div>
                    </div>

                    <div class="row mb-5">
                        <div class="col-md-6">
                            <label class="form-label">Contact Person</label>
                            <input type="text" class="form-control form-control-solid" wire:model.defer="contact_person">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control form-control-solid" wire:model.defer="phone">
                        </div>
                    </div>

                    <div class="row mb-5">
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control form-control-solid" wire:model.defer="email">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Website</label>
                            <input type="url" class="form-control form-control-solid" wire:model.defer="website">
                        </div>
                    </div>

                        <div class="mb-5" wire:ignore>
                            <label class="form-label required">Country</label>
                            <select class="form-select form-select-solid" id="kt_select2_country_id" data-placeholder="Select Country">
                                <option></option>
                                @foreach($countries as $country)
                                    <option value="{{ $country->id }}">{{ $country->name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" id="hidden_country_id" wire:model.defer="country_id">
                            @error('country_id') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4" wire:ignore>
                            <label class="form-label required">Island</label>
                            <select class="form-select form-select-solid" id="kt_select2_island_id" data-placeholder="Select Island">
                                <option></option>
                                @foreach($islands as $island)
                                    <option value="{{ $island->id }}">{{ $island->name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" id="hidden_island_id" wire:model.defer="island_id">
                            @error('island_id') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                    
                    <div class="mb-5" wire:ignore>
                        <label class="form-label required">Property</label>
                        <select class="form-select form-select-solid" id="kt_select2_property_id" data-placeholder="Select Property">
                            <option></option>
                            @foreach($properties as $property)
                                <option value="{{ $property->id }}">
                                    {{ $property->name }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" id="hidden_property_id" wire:model.defer="property_id">
                        @error('property_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="row mb-5">
                        <div class="col-md-6">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control form-control-solid" wire:model.defer="address">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Street Address</label>
                            <input type="text" class="form-control form-control-solid" wire:model.defer="street_address">
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="form-label">Location Type</label>
                        <input type="text" class="form-control form-control-solid" wire:model="location_type" readonly>
                    </div>


                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('kt_modal_add_user');
    const mainContent = document.getElementById('main-content');

    const selectsConfig = [
        { id: 'kt_select2_country_id', hiddenId: 'hidden_country_id', livewireProperty: 'country_id' },
        { id: 'kt_select2_island_id', hiddenId: 'hidden_island_id', livewireProperty: 'island_id' },
        { id: 'kt_select2_property_id', hiddenId: 'hidden_property_id', livewireProperty: 'properties_id' },
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
            dropdownParent: $('#kt_modal_add_user'),
            placeholder: $select.data('placeholder') || 'Select...',
            allowClear: true,
            minimumResultsForSearch: 0,
            width: '100%',
            dropdownAutoWidth: true,
            dropdownPosition: 'below' // Force dropdown below
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
    }, 150); // Wait to ensure the DOM is ready
});




    // Remove the element.updated hook from the previous attempt.
    // It's less effective here if Livewire is replacing parent elements.
});
</script>
@endpush

