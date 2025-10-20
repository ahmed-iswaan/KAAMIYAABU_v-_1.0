<div wire:ignore.self class="modal fade" id="kt_modal_add_user" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content rounded shadow-sm">
            <!-- Header -->
            <div class="modal-header pb-0 border-0">
                <h2 class="fw-bold">Add New Directory</h2>
                    <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </button>
            </div>

            <!-- Body -->
            <div class="modal-body py-10 px-lg-17">
                <form wire:submit.prevent="save" class="form">

               <!-- Profile Picture -->
                    <div class="mb-7">
                                <label class="d-block fw-semibold fs-6 mb-5">Profile Picture</label>
                                <style>
                                    .image-input-placeholder { background-image: url('{{ asset("assets/media/svg/files/blank-image.svg") }}'); }
                                    [data-bs-theme="dark"] .image-input-placeholder { background-image: url('{{ asset("assets/media/svg/files/blank-image-dark.svg") }}'); }
                                </style>
                                <div class="image-input image-input-outline image-input-placeholder image-input-empty" data-kt-image-input="true" wire:ignore>
                                    <div class="image-input-wrapper w-125px h-125px" style="background-image: @if($profile_picture) url('{{ $profile_picture->temporaryUrl() }}') @else none @endif;"></div>
                                    <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" title="Change Picture">
                                        <i class="ki-duotone ki-pencil fs-7"><span class="path1"></span><span class="path2"></span></i>
                                        <input type="file" wire:model="profile_picture" accept=".png, .jpg, .jpeg">
                                        <input type="hidden" wire:model="profile_picture_remove" value="1">
                                    </label>
                                    <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" title="Cancel" onclick="Livewire.find('{{ $this->getId() }}').set('profile_picture', null)"><i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i></span>
                                </div>
                                <div class="form-text">Allowed types: png, jpg, jpeg. Max 1MB.</div>
                                @error('profile_picture') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>

                    <!-- Basic Info -->
                    <div class="row mb-6">
                        <div class="col-md-6">
                            <label class="form-label required">Name</label>
                            <input type="text" class="form-control form-control-solid" wire:model.defer="name" placeholder="Full name">
                            @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Description</label>
                            <textarea class="form-control form-control-solid" wire:model.defer="description" rows="2" placeholder="Description"></textarea>
                              @error('description') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row mb-6">
                        <div class="col-md-4">
                            <label class="form-label">ID Card Number</label>
                            <input type="text" class="form-control form-control-solid" wire:model.defer="id_card_number" placeholder="e.g. A123456">
                            @error('id_card_number') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Gender</label>
                            <select class="form-select form-select-solid" wire:model.defer="gender">
                                <option value="">Select...</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                            @error('gender') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control form-control-solid" wire:model.defer="date_of_birth">
                            @error('date_of_birth') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row mb-6">
                        <div class="col-md-4">
                            <label class="form-label">Death Date</label>
                            <input type="date" class="form-control form-control-solid" wire:model.defer="death_date">
                            @error('death_date') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4" wire:ignore>
                            <label class="form-label">Party</label>
                            <select class="form-select form-select-solid js-dir-select2" id="kt_select2_add_party_id" data-placeholder="Select Party" wire:ignore.self>
                                <option></option>
                                @foreach($parties as $p)
                                    <option value="{{ $p->id }}" data-logo="{{ $p->logo ? asset('storage/'.$p->logo) : asset('assets/media/svg/files/blank-image.svg') }}">{{ $p->short_name ?? $p->name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" id="hidden_party_id" wire:model.defer="party_id">
                            @error('party_id') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4" wire:ignore>
                            <label class="form-label">Consite</label>
                            <select class="form-select form-select-solid js-dir-select2" id="kt_select2_add_consite_or_sub_id" data-placeholder="Select Consite" wire:ignore.self>
                                <option></option>
                                @foreach($consites as $c)
                                    <optgroup label="{{ $c->name }}">
                                        @foreach($c->subConsites as $sc)
                                            <option value="{{ $sc->id }}" data-type="sub" data-parent="{{ $c->name }}">{{ $sc->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <input type="hidden" id="hidden_consite_or_sub_id" wire:model.defer="consite_or_sub_id">
                        </div>
                    </div>
                    <div class="row mb-6">
                        <div class="col-md-4" wire:ignore>
                            <label class="form-label">Status</label>
                            <select class="form-select form-select-solid" wire:model.defer="status">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                            @error('status') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <!-- Phones (dynamic) -->
                    <div class="mb-6">
                        <label class="form-label">Phone Numbers</label>
                        @foreach($phones as $i => $p)
                            <div class="d-flex mb-2" wire:key="phone-{{ $i }}">
                                <input type="text" class="form-control form-control-solid me-2" wire:model.defer="phones.{{ $i }}" placeholder="Phone {{ $i+1 }}">
                                <button type="button" class="btn btn-icon btn-light-danger" title="Remove" wire:click="removePhoneField({{ $i }})" @if(count($phones)==1) disabled @endif>
                                    <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>
                                </button>
                            </div>
                        @endforeach
                        <button type="button" class="btn btn-sm btn-light-primary" wire:click="addPhoneField"><i class="ki-duotone ki-plus fs-2"><span class="path1"></span><span class="path2"></span></i>Add Phone</button>
                        @error('phones') <div class="text-danger">{{ $message }}</div> @enderror
                        @error('phones.*') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>

                    <div class="row mb-6">
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control form-control-solid" wire:model.defer="email">
                            @error('email') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Website</label>
                            <input type="url" class="form-control form-control-solid" wire:model.defer="website">
                             @error('website') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <!-- Permanent Location (Replaced Section) -->
                    <div class="separator my-8"></div>
                    <h5 class="fw-bold mb-4">Permanent Location</h5>
                    <!-- SECTION: Select2 Location Fields -->
                    <div class="mb-6" wire:ignore>
                        <label class="form-label required">Country</label>
                        <select class="form-select form-select-solid" id="kt_select2_country_id" data-placeholder="Select Country">
                            <option></option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" id="hidden_country_id" wire:model.defer="country_id">
                        @error('country_id') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-6" x-data="{ show: @entangle('is_island_visible') }" x-show="show" x-transition>
                        <label class="form-label required">Island</label>
                        <select class="form-select form-select-solid" id="kt_select2_island_id" data-placeholder="Select Island">
                            <option></option>
                            @foreach($islands as $island)
                                <option value="{{ $island->id }}">{{ $island?->atoll?->code }}. {{ $island->name }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" id="hidden_island_id" wire:model.defer="island_id">
                        @error('island_id') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-6" x-data="{ show: @entangle('is_property_visible') }" x-show="show" x-transition>
                        <label class="form-label required">Property</label>
                        <select class="form-select form-select-solid" id="kt_select2_property_id" data-placeholder="Select Property">
                            <option></option>
                            @foreach($properties as $property)
                                <option value="{{ $property->id }}">{{ $property->name }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" id="hidden_property_id" wire:model.defer="properties_id">
                        @error('properties_id') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>

                    <!-- SECTION: Address -->
                    <div class="row mb-6">
                        <div class="col-md-6">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control form-control-solid" wire:model.defer="address">
                            @error('address') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Street Address</label>
                            <input type="text" class="form-control form-control-solid" wire:model.defer="street_address">
                            @error('street_address') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <!-- Current Location (Added) -->
                    <div class="separator my-8"></div>
                    <h5 class="fw-bold mb-4">Current Location</h5>
                    <div class="mb-6" wire:ignore>
                        <label class="form-label">Current Country</label>
                        <select class="form-select form-select-solid" id="kt_select2_current_country_id" data-placeholder="Select Country">
                            <option></option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" id="hidden_current_country_id" wire:model.defer="current_country_id">
                        @error('current_country_id') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-6" x-data="{ show: @entangle('is_current_island_visible') }" x-show="show" x-transition>
                        <label class="form-label">Current Island</label>
                        <select class="form-select form-select-solid" id="kt_select2_current_island_id" data-placeholder="Select Island">
                            <option></option>
                            @foreach($islands as $island)
                                <option value="{{ $island->id }}">{{ $island?->atoll?->code }}. {{ $island->name }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" id="hidden_current_island_id" wire:model.defer="current_island_id">
                        @error('current_island_id') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-6" x-data="{ show: @entangle('is_current_property_visible') }" x-show="show" x-transition>
                        <label class="form-label">Current Property</label>
                        <select class="form-select form-select-solid" id="kt_select2_current_property_id" data-placeholder="Select Property">
                            <option></option>
                            @foreach($properties as $property)
                                @if($property->island_id === $current_island_id)
                                    <option value="{{ $property->id }}">{{ $property->name }}</option>
                                @endif
                            @endforeach
                        </select>
                        <input type="hidden" id="hidden_current_property_id" wire:model.defer="current_properties_id">
                        @error('current_properties_id') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>

                    <div class="row mb-6">
                        <div class="col-md-6">
                            <label class="form-label">Current Address</label>
                            <input type="text" class="form-control form-control-solid" wire:model.defer="current_address">
                            @error('current_address') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Current Street Address</label>
                            <input type="text" class="form-control form-control-solid" wire:model.defer="current_street_address">
                            @error('current_street_address') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="separator my-10"></div>

                  <div class="mb-5" x-data="{ show: @entangle('has_contact_person') }">
                    <label class="form-label">Add Contact Person?</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" wire:model.live="has_contact_person" id="has_contact_person">
                        <label class="form-check-label" for="has_contact_person">Yes</label>
                    </div>

                    <!-- Conditional Fields -->
                    <div x-show="show" x-transition>
                        <div class="mb-5" wire:ignore>
                            <label class="form-label required">Contact Person</label>
                            <select class="form-select form-select-solid" id="kt_select2_contact_contact_directory_id" data-placeholder="Select Contact Person">
                                <option></option>
                                @foreach($contacts as $contact)
                                    <option value="{{ $contact->id }}">{{ $contact->name }} - {{ $contact->id_card_number }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" id="hidden_contact_directory_id" wire:model.defer="contact_directory_id">
                            @error('contact_directory_id') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-6">
                            <label class="form-label">Designation</label>
                            <input type="text" wire:model.defer="contact_designation" class="form-control form-control-solid" placeholder="E.g. Manager, Agent">
                            @error('contact_designation') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                    <!-- Buttons -->
                    <div class="text-end mt-8">
                        <button type="submit" class="btn btn-primary">
                                <span class="indicator-label">Save</span>
                                <span wire:loading wire:target="save" class="indicator-progress">
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
<style>
/* Ensure Select2 always matches form-select styling */
.select2-container--default .select2-selection--single { height: 38px; padding: 6px 12px; border-radius: .475rem; border: 1px solid var(--bs-gray-300); background: var(--bs-body-bg); }
.select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 24px; }
.select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
/* Full width fix */
.select2-container { width: 100% !important; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('kt_modal_add_user');
    const mainContent = document.getElementById('main-content');

    const selectsConfig = [
        { id: 'kt_select2_add_party_id', hiddenId: 'hidden_party_id', livewireProperty: 'party_id' },
        { id: 'kt_select2_add_consite_or_sub_id', hiddenId: 'hidden_consite_or_sub_id', livewireProperty: 'consite_or_sub_id' },
        { id: 'kt_select2_country_id', hiddenId: 'hidden_country_id', livewireProperty: 'country_id' },
        { id: 'kt_select2_island_id', hiddenId: 'hidden_island_id', livewireProperty: 'island_id' },
        { id: 'kt_select2_property_id', hiddenId: 'hidden_property_id', livewireProperty: 'properties_id' },
        { id: 'kt_select2_current_country_id', hiddenId: 'hidden_current_country_id', livewireProperty: 'current_country_id' },
        { id: 'kt_select2_current_island_id', hiddenId: 'hidden_current_island_id', livewireProperty: 'current_island_id' },
        { id: 'kt_select2_current_property_id', hiddenId: 'hidden_current_property_id', livewireProperty: 'current_properties_id' },
        { id: 'kt_select2_contact_contact_directory_id', hiddenId: 'hidden_contact_directory_id', livewireProperty: 'contact_directory_id' },
    ];

    let integrityInterval = null;

    const elementIsVisible = (el) => {
        if (!el) return false;
        return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
    };

    const initSingleSelect2 = (selectId, force = false) => {
        const config = selectsConfig.find(c => c.id === selectId);
        if (!config) return;
        const el = document.getElementById(config.id);
        if (!el) return;
        if (!elementIsVisible(el) && !force) { setTimeout(() => initSingleSelect2(selectId), 150); return; }
        const $select = $(el);
        const $hiddenInput = document.getElementById(config.hiddenId);
        if ($select.data('select2')) { return; }

        let options = {
            dropdownParent: $('#kt_modal_add_user .modal-content'),
            placeholder: $select.data('placeholder') || 'Select...',
            allowClear: true,
            width: '100%',
            minimumResultsForSearch: 0
        };
        if (config.id === 'kt_select2_add_party_id') {
            const placeholderImg = @js(asset('assets/media/svg/files/blank-image.svg'));
            options.templateResult = function (data) {
                if (!data.id) return data.text;
                const logo = $(data.element).data('logo') || placeholderImg;
                return $('<span class="d-flex align-items-center"><img src="'+logo+'" class="rounded me-2" style="width:24px;height:24px;object-fit:cover;" onerror="this.src=\''+placeholderImg+'\'" /><span>'+data.text+'</span></span>');
            };
            options.templateSelection = function (data) {
                if (!data.id) return data.text;
                const logo = $(data.element).data('logo') || placeholderImg;
                return $('<span class="d-flex align-items-center"><img src="'+logo+'" class="rounded me-2" style="width:20px;height:20px;object-fit:cover;" onerror="this.src=\''+placeholderImg+'\'" /><span>'+data.text+'</span></span>');
            };
            options.escapeMarkup = m => m;
        }
        if (config.id === 'kt_select2_add_consite_or_sub_id') {
            options.templateResult = function (data) {
                if (!data.id) return data.text;
                const $el = $(data.element);
                const parent = $el.data('parent');
                if ($el.data('type') === 'sub') {
                    return $('<span><span class="badge badge-light me-2">'+parent+'</span>'+$el.text()+'</span>');
                }
                return data.text;
            };
            options.templateSelection = function (data) {
                if (!data.id) return data.text;
                const $el = $(data.element);
                const parent = $el.data('parent');
                if ($el.data('type') === 'sub') {
                    return parent + ' - ' + $el.text();
                }
                return data.text;
            };
        }

        $select.select2(options);
        const lwVal = @this.get(config.livewireProperty);
        if (lwVal) {
            if (!$select.find('option[value="'+lwVal+'"]').length) {
                $select.append(new Option(lwVal, lwVal, true, true));
            }
            $select.val(lwVal).trigger('change.select2');
        }
        $select.off('change.dir').on('change.dir', function(){
            const val = this.value || '';
            if ($hiddenInput) { $hiddenInput.value = val; $hiddenInput.dispatchEvent(new Event('input', { bubbles: true })); }
            @this.set(config.livewireProperty, val || null);
        });
    };

    const initAllSelect2s = () => {
        selectsConfig.forEach(cfg => initSingleSelect2(cfg.id));
    };

    const ensureSelect2Integrity = () => {
        selectsConfig.forEach(cfg => {
            const el = document.getElementById(cfg.id);
            if (!el) return;
            const $el = $(el);
            if (elementIsVisible(el) && !$el.data('select2')) {
                initSingleSelect2(cfg.id, true);
            }
        });
    };

    const startIntegrityLoop = () => {
        if (integrityInterval) return;
        integrityInterval = setInterval(ensureSelect2Integrity, 600); // lightweight check
    };
    const stopIntegrityLoop = () => {
        if (integrityInterval) { clearInterval(integrityInterval); integrityInterval = null; }
    };

    // MutationObserver to catch option list changes (Livewire re-render)
    const observer = new MutationObserver((mutations) => {
        let needsCheck = false;
        for (const m of mutations) {
            if (m.type === 'childList') {
                // If Livewire replaced a select, its select2 instance is gone
                needsCheck = true; break;
            }
        }
        if (needsCheck) {
            setTimeout(() => {
                ensureSelect2Integrity();
            }, 80);
        }
    });

    modalEl.addEventListener('shown.bs.modal', () => {
        initAllSelect2s();
        startIntegrityLoop();
        observer.observe(modalEl, { subtree: true, childList: true });
    });

    modalEl.addEventListener('hidden.bs.modal', () => {
        stopIntegrityLoop();
        observer.disconnect();
        selectsConfig.forEach(cfg => { const $s = $('#' + cfg.id); if ($s.data('select2')) { $s.select2('destroy'); } });
        bootstrap.Modal.getInstance(modalEl)?.dispose();
        mainContent?.removeAttribute('inert');
    });

    Livewire.on('showAddDirectoryModal', () => {
        mainContent?.setAttribute('inert', '');
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });
    Livewire.on('closeAddDirectoryModal', () => { bootstrap.Modal.getInstance(modalEl)?.hide(); });

    Livewire.on('formSubmittedOrReset', () => {
        selectsConfig.forEach(cfg => {
            const $select = $('#' + cfg.id);
            const $hidden = document.getElementById(cfg.hiddenId);
            if ($select.data('select2')) { $select.val('').trigger('change.select2'); }
            if ($hidden) { $hidden.value=''; $hidden.dispatchEvent(new Event('input', { bubbles:true })); }
            @this.set(cfg.livewireProperty, null);
        });
        setTimeout(() => ensureSelect2Integrity(), 100);
    });

    Livewire.hook('message.processed', () => {
        // After Livewire DOM patching, ensure missing instances are re-initialized
        setTimeout(() => { ensureSelect2Integrity(); }, 120);
    });
});
</script>
@endpush