<div wire:ignore.self class="modal fade" id="kt_modal_edit_user" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content rounded shadow-sm">
            <div class="modal-header pb-0 border-0">
                <h2 class="fw-bold">Edit Directory</h2>
                    <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </button>
            </div>

            <div class="modal-body py-10 px-lg-17">
                <form wire:submit.prevent="edit" class="form">

                @php
                    if (isset($edit_profile_picture) && is_object($edit_profile_picture)) {
                        $url = $edit_profile_picture->temporaryUrl();
                    } elseif (!empty($edit_profile_picture_path)) {
                        $url = asset('storage/'.$edit_profile_picture_path);
                    } else {
                        $url = asset('assets/media/svg/files/blank-image.svg');
                    }
                @endphp

                <div class="fv-row mb-7">
                <label class="d-block fw-semibold fs-6 mb-5">Profile Picture</label>

                <div
                    class="image-input image-input-outline image-input-placeholder"
                    data-kt-image-input="true"
                    wire:key="edit-avatar-{{ $edit_profile_picture_path }}-{{ optional($edit_profile_picture)->getFilename() }}"
                >
                    <!-- 2) Apply it here, no nested quotes or backslashes -->
                    <div
                    class="image-input-wrapper w-125px h-125px"
                    style="background-image: url('{{ $url }}');"
                    ></div>

                    <!-- Change button -->
                    <label
                    class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                    data-kt-image-input-action="change"
                    title="Change avatar"
                    >
                    <i class="ki-duotone ki-pencil fs-7"><span class="path1"></span><span class="path2"></span></i>
                    <input type="file" wire:model="edit_profile_picture" accept=".png,.jpg,.jpeg" />
                    </label>

                    <!-- Cancel upload -->
                    <span
                    class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                    data-kt-image-input-action="cancel"
                    title="Cancel upload"
                    onclick="@this.set('edit_profile_picture', null)"
                    >
                    <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                </div>

                <!--begin::Hint-->
                    <div class="form-text">Allowed file types: png, jpg, jpeg. Max size: 1MB.</div>
                <!--end::Hint-->

                @error('edit_profile_picture')
                    <div class="text-danger mt-2">{{ $message }}</div>
                @enderror
                </div>

                    <div class="row mb-6">
                        <div class="col-md-6">
                            <label class="form-label required">Name</label>
                            <input type="text" class="form-control form-control-solid" wire:model.defer="edit_name" placeholder="Enter name">
                            @error('edit_name') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Description</label>
                            <textarea class="form-control form-control-solid" wire:model.defer="edit_description" rows="2" placeholder="Enter description"></textarea>
                             @error('edit_description') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row mb-6">
                        <div class="col-md-6">
                            <label class="form-label required">Directory Type</label>
                            <select class="form-select form-select-solid" wire:model.live.defer="edit_directory_type_id">
                                <option value="">Select...</option>
                                @foreach($directoryTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                            @error('edit_directory_type_id') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label required">Registration Type</label>
                            <select class="form-select form-select-solid" wire:model.live.defer="edit_registration_type_id">
                                <option value="">Select...</option>
                                @foreach($registrationTypes as $reg)
                                    <option value="{{ $reg->id }}">{{ $reg->name }}</option>
                                @endforeach
                            </select>
                            @error('edit_registration_type_id') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row mb-6">
                        <div class="col-md-6">
                            <label class="form-label required">{{ $edit_registration_label }} Number</label>
                            <input type="text" class="form-control form-control-solid" wire:model.defer="edit_registration_number">
                            @error('edit_registration_number') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6" x-data="{ show: @entangle('edit_is_gst_visible') }" x-show="show">
                            <label class="form-label">GST Number</label>
                            <input type="text" class="form-control form-control-solid" wire:model.defer="edit_gst_number">
                            @error('edit_gst_number') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>

                    </div>

                    <div class="row mb-6">
                        <div class="col-md-4" x-data="{ show: @entangle('edit_is_gender_visible') }" x-show="show">
                            <label class="form-label">Gender</label>
                            <select class="form-select form-select-solid" wire:model.defer="edit_gender">
                                <option value="">Select...</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                            @error('edit_gender') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ $edit_date_label }}</label>
                            <input type="date" class="form-control form-control-solid" wire:model.defer="edit_date_of_birth">
                             @error('edit_date_of_birth') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row mb-6">
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control form-control-solid" wire:model.defer="edit_phone">
                            @error('edit_phone') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row mb-6">
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control form-control-solid" wire:model.defer="edit_email">
                            @error('edit_email') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Website</label>
                            <input type="url" class="form-control form-control-solid" wire:model.defer="edit_website">
                             @error('edit_website') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-6" wire:ignore>
                        <label class="form-label required">Country</label>
                        <select class="form-select form-select-solid" id="kt_select2_edit_country_id" data-placeholder="Select Country">
                            <option></option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" id="hidden_edit_country_id" wire:model.live.defer="edit_country_id">
                        @error('edit_country_id') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-6" x-data="{ show: @entangle('edit_is_island_visible') }" x-show="show" wire:ignore>
                        <label class="form-label required">Island</label>
                        <select class="form-select form-select-solid" id="kt_select2_edit_island_id" data-placeholder="Select Island">
                            <option></option>
                            @foreach($islands as $island)
                                <option value="{{ $island->id }}">{{ $island?->atoll?->code }}. {{ $island->name }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" id="hidden_edit_island_id" wire:model.defer="edit_island_id">
                        @error('edit_island_id') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-6" x-data="{ show: @entangle('edit_is_property_visible') }" x-show="show" wire:ignore>
                        <label class="form-label required">Property</label>
                        <select class="form-select form-select-solid" id="kt_select2_edit_property_id" data-placeholder="Select Property">
                            <option></option>
                            @foreach($properties as $property)
                                <option value="{{ $property->id }}">{{ $property->name }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" id="hidden_edit_property_id" wire:model.defer="edit_property_id">
                        @error('edit_property_id') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>

                    <div class="row mb-6">
                        <div class="col-md-6">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control form-control-solid" wire:model.defer="edit_address">
                            @error('edit_address') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Street Address</label>
                            <input type="text" class="form-control form-control-solid" wire:model.defer="edit_street_address">
                             @error('edit_street_address') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="separator my-10"></div>

                  <div class="mb-5" x-data="{ show: @entangle('edit_has_contact_person') }">
                    <label class="form-label">Add Contact Person?</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" wire:model.live="edit_has_contact_person" id="edit_has_contact_person">
                        <label class="form-check-label" for="edit_has_contact_person">Yes</label>
                    </div>

                    <div x-show="show" x-transition>
                        <div class="mb-5" wire:ignore>
                            <label class="form-label required">Contact Person</label>
                            <select class="form-select form-select-solid" id="kt_select2_edit_contact_directory_id" data-placeholder="Select Contact Person">
                                <option></option>
                                @foreach($contacts as $contact)
                                    <option value="{{ $contact->id }}">{{ $contact->name }}- {{ $contact->registration_number }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" id="hidden_edit_contact_directory_id" wire:model.defer="edit_contact_directory_id">
                            @error('edit_contact_directory_id') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-6">
                            <label class="form-label">Designation</label>
                            <input type="text" wire:model.defer="edit_contact_designation" class="form-control form-control-solid" placeholder="E.g. Manager, Agent">
                            @error('edit_contact_designation') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                    <div class="text-end mt-8">
                        <button type="submit" class="btn btn-primary">
                                <span class="indicator-label">Save</span>
                                <span wire:loading wire:target="edit" class="indicator-progress">
                                    Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                             </button>
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
    const modalEl = document.getElementById('kt_modal_edit_user');
    const mainContent = document.getElementById('main-content');

    const selectsConfig = [
        { id: 'kt_select2_edit_country_id', hiddenId: 'hidden_edit_country_id', livewireProperty: 'edit_country_id' },
        { id: 'kt_select2_edit_island_id', hiddenId: 'hidden_edit_island_id', livewireProperty: 'edit_island_id' },
        { id: 'kt_select2_edit_property_id', hiddenId: 'hidden_edit_property_id', livewireProperty: 'edit_property_id' },
        { id: 'kt_select2_edit_contact_directory_id', hiddenId: 'hidden_edit_contact_directory_id', livewireProperty: 'edit_contact_directory_id' },
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
            dropdownParent: $('#kt_modal_edit_user .modal-content'), // CRUCIAL for modals
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

    Livewire.on('showEditDirectoryModal', () => {
        console.log('Livewire event: showEditDirectoryModal received.');
        mainContent?.setAttribute('inert', '');
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });

    Livewire.on('closeEditDirectoryModal', () => {
        console.log('Livewire event: closeEditDirectoryModal received.');
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