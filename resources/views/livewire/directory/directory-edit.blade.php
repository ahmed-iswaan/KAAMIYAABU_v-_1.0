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

                    <!-- Profile Picture -->
                    <div class="fv-row mb-7">
                        <label class="d-block fw-semibold fs-6 mb-5">Profile Picture</label>
                        <div class="image-input image-input-outline image-input-placeholder" data-kt-image-input="true" wire:key="edit-avatar-{{ $edit_profile_picture_path }}-{{ optional($edit_profile_picture)->getFilename() }}">
                            <div class="image-input-wrapper w-125px h-125px" style="background-image: url('{{ $url }}');"></div>
                            <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" title="Change avatar">
                                <i class="ki-duotone ki-pencil fs-7"><span class="path1"></span><span class="path2"></span></i>
                                <input type="file" wire:model="edit_profile_picture" accept=".png,.jpg,.jpeg" />
                            </label>
                            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" title="Cancel upload" onclick="@this.set('edit_profile_picture', null)">
                                <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>
                            </span>
                        </div>
                        <div class="form-text">Allowed file types: png, jpg, jpeg. Max size: 1MB.</div>
                        @error('edit_profile_picture') <div class="text-danger mt-2">{{ $message }}</div> @enderror
                    </div>

                    <!-- Basic Info -->
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
                        <div class="col-md-4">
                            <label class="form-label">ID Card Number</label>
                            <input type="text" class="form-control form-control-solid" wire:model.defer="edit_id_card_number" placeholder="e.g. A123456">
                            @error('edit_id_card_number') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
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
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control form-control-solid" wire:model.defer="edit_date_of_birth">
                            @error('edit_date_of_birth') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row mb-6">
                        <div class="col-md-4" >
                            <label class="form-label">Death Date</label>
                            <input type="date" class="form-control form-control-solid" wire:model.defer="edit_death_date">
                            @error('edit_death_date') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Party</label>
                            <select class="form-select form-select-solid" id="kt_select2_edit_party_id" data-placeholder="Select Party">
                                <option></option>
                                @foreach($parties as $p)
                                    <option value="{{ $p->id }}" data-logo="{{ $p->logo ? asset('storage/'.$p->logo) : asset('assets/media/svg/files/blank-image.svg') }}">{{ $p->short_name ?? $p->name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" id="hidden_edit_party_id" wire:model.defer="edit_party_id">
                            @error('edit_party_id') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Consite</label>
                            <select class="form-select form-select-solid" id="kt_select2_edit_consite_or_sub_id" data-placeholder="Select Consite">
                                <option></option>
                                @foreach($consites as $c)
                                    <optgroup label="{{ $c->name }}">
                                        @foreach($c->subConsites as $sc)
                                            <option value="{{ $sc->id }}" data-type="sub" data-parent="{{ $c->name }}">{{ $sc->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <input type="hidden" id="hidden_edit_consite_or_sub_id" wire:model.defer="edit_consite_or_sub_id">
                        </div>
                    </div>
                    <div class="row mb-6">
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select form-select-solid" wire:model.defer="edit_status">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                            @error('edit_status') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <!-- Dynamic Phones -->
                    <div class="mb-6">
                        <label class="form-label">Phone Numbers</label>
                        @foreach($edit_phones as $i => $p)
                            <div class="d-flex mb-2" wire:key="edit-phone-{{ $i }}">
                                <input type="text" class="form-control form-control-solid me-2" wire:model.defer="edit_phones.{{ $i }}" placeholder="Phone {{ $i+1 }}">
                                <button type="button" class="btn btn-icon btn-light-danger" title="Remove" wire:click="editRemovePhoneField({{ $i }})" @if(count($edit_phones)==1) disabled @endif>
                                    <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>
                                </button>
                            </div>
                        @endforeach
                        <button type="button" class="btn btn-sm btn-light-primary" wire:click="editAddPhoneField"><i class="ki-duotone ki-plus fs-2"><span class="path1"></span><span class="path2"></span></i>Add Phone</button>
                        @error('edit_phones') <div class="text-danger">{{ $message }}</div> @enderror
                        @error('edit_phones.*') <div class="text-danger">{{ $message }}</div> @enderror

                        @php
                            $phonesList = is_array($edit_phones) ? array_filter($edit_phones) : [];
                        @endphp

                        @if(count($phonesList))
                            <div class="mt-4 p-4 rounded border border-dashed">
                                <div class="fw-semibold mb-2">Call Status (per number)</div>

                                @foreach($phonesList as $p)
                                    @php
                                        $norm = preg_replace('/\D+/', '', (string) $p);
                                        $currentStatus = $this->phoneCallStatuses[$norm] ?? 'not_called';
                                    @endphp

                                    <div class="row g-2 align-items-center mb-2">
                                        <div class="col-md-3">
                                            <span class="badge badge-light-dark">{{ $p }}</span>
                                        </div>
                                        <div class="col-md-3">
                                            <select class="form-select form-select-sm form-select-solid"
                                                    wire:change="updatePhoneCallStatus('{{ $editingDirectoryId }}', '{{ $norm }}', $event.target.value)">
                                                <option value="not_called" @selected($currentStatus==='not_called')>Not called</option>
                                                <option value="completed" @selected($currentStatus==='completed')>Completed</option>
                                                <option value="wrong_number" @selected($currentStatus==='wrong_number')>Wrong number</option>
                                                <option value="no_answer" @selected($currentStatus==='no_answer')>No answer</option>
                                                <option value="busy" @selected($currentStatus==='busy')>Busy</option>
                                                <option value="switched_off" @selected($currentStatus==='switched_off')>Switched off</option>
                                                <option value="callback" @selected($currentStatus==='callback')>Call back</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <input type="text" class="form-control form-control-sm form-control-solid"
                                                   placeholder="Notes (optional)"
                                                   wire:model.lazy="phoneCallNotes.{{ $norm }}"
                                                   wire:change="updatePhoneCallNotes('{{ $editingDirectoryId }}', '{{ $norm }}')" />
                                        </div>
                                    </div>
                                @endforeach

                                <div class="fs-8 text-muted mt-2">Saved automatically when status/notes change.</div>
                            </div>
                        @endif
                    </div>

                    <!-- Contact Info -->
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

                    <!-- Permanent Location -->
                    <div class="separator my-8"></div>
                    <h5 class="fw-bold mb-4">Permanent Location</h5>
                    <div class="mb-6">
                        <label class="form-label">Country</label>
                        <select class="form-select form-select-solid" id="kt_select2_edit_country_id" data-placeholder="Select Country">
                            <option></option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" id="hidden_edit_country_id" wire:model.defer="edit_country_id">
                        @error('edit_country_id') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-6" x-data="{ show: @entangle('edit_is_island_visible') }" x-show="show" x-transition>
                        <label class="form-label">Island</label>
                        <select class="form-select form-select-solid" id="kt_select2_edit_island_id" data-placeholder="Select Island">
                            <option></option>
                            @foreach($islands as $island)
                                <option value="{{ $island->id }}">{{ $island?->atoll?->code }}. {{ $island->name }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" id="hidden_edit_island_id" wire:model.defer="edit_island_id">
                        @error('edit_island_id') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-6" x-data="{ show: @entangle('edit_is_property_visible') }" x-show="show" x-transition>
                        <label class="form-label">Property</label>
                        <select class="form-select form-select-solid" id="kt_select2_edit_property_id" data-placeholder="Select Property">
                            <option></option>
                            @foreach($properties as $property)
                                <option value="{{ $property->id }}">{{ $property->name }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" id="hidden_edit_property_id" wire:model.defer="edit_properties_id">
                        @error('edit_properties_id') <div class="text-danger">{{ $message }}</div> @enderror
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

                    <!-- Current Location -->
                    <div class="separator my-8"></div>
                    <h5 class="fw-bold mb-4">Current Location</h5>
                    <div class="mb-6">
                        <label class="form-label">Current Country</label>
                        <select class="form-select form-select-solid" id="kt_select2_edit_current_country_id" data-placeholder="Select Country">
                            <option></option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" id="hidden_edit_current_country_id" wire:model.defer="edit_current_country_id">
                        @error('edit_current_country_id') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-6" x-data="{ show: @entangle('edit_is_current_island_visible') }" x-show="show" x-transition>
                        <label class="form-label">Current Island</label>
                        <select class="form-select form-select-solid" id="kt_select2_edit_current_island_id" data-placeholder="Select Island">
                            <option></option>
                            @foreach($islands as $island)
                                <option value="{{ $island->id }}">{{ $island?->atoll?->code }}. {{ $island->name }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" id="hidden_edit_current_island_id" wire:model.defer="edit_current_island_id">
                        @error('edit_current_island_id') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-6" x-data="{ show: @entangle('edit_is_current_property_visible') }" x-show="show" x-transition>
                        <label class="form-label">Current Property</label>
                        <select class="form-select form-select-solid" id="kt_select2_edit_current_property_id" data-placeholder="Select Property">
                            <option></option>
                            @foreach($properties as $property)
                                @if($property->island_id === $edit_current_island_id)
                                    <option value="{{ $property->id }}">{{ $property->name }}</option>
                                @endif
                            @endforeach
                        </select>
                        <input type="hidden" id="hidden_edit_current_property_id" wire:model.defer="edit_current_properties_id">
                        @error('edit_current_properties_id') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="row mb-6">
                        <div class="col-md-6">
                            <label class="form-label">Current Address</label>
                            <input type="text" class="form-control form-control-solid" wire:model.defer="edit_current_address">
                            @error('edit_current_address') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Current Street Address</label>
                            <input type="text" class="form-control form-control-solid" wire:model.defer="edit_current_street_address">
                            @error('edit_current_street_address') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="separator my-10"></div>

                    <!-- Contact Person -->
                    <div class="mb-5" x-data="{ show: @entangle('edit_has_contact_person') }">
                        <label class="form-label">Add Contact Person?</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" wire:model.live="edit_has_contact_person" id="edit_has_contact_person">
                            <label class="form-check-label" for="edit_has_contact_person">Yes</label>
                        </div>
                        <div x-show="show" x-transition>
                            <div class="mb-5">
                                <label class="form-label required">Contact Person</label>
                                <select class="form-select form-select-solid" id="kt_select2_edit_contact_directory_id" data-placeholder="Select Contact Person">
                                    <option></option>
                                    @foreach($contacts as $contact)
                                        <option value="{{ $contact->id }}">{{ $contact->name }} - {{ $contact->id_card_number }}</option>
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
                            <span wire:loading wire:target="edit" class="indicator-progress">Please wait...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
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
.select2-container--default .select2-selection--single { height: 38px; padding: 6px 12px; border-radius: .475rem; border: 1px solid var(--bs-gray-300); background: var(--bs-body-bg); }
.select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 24px; }
.select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
.select2-container { width: 100% !important; }
</style>
<script>
// Harden & lazy load Select2 so modal opens even if library missing initially
(function(){
    const SELECT2_JS = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js';
    const SELECT2_CSS = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css';
    function ensureSelect2(cb){
        if(window.jQuery && jQuery.fn && jQuery.fn.select2){ return cb && cb(); }
        // load css once
        if(!document.querySelector('link[data-auto-select2]')){
            const l=document.createElement('link'); l.rel='stylesheet'; l.href=SELECT2_CSS; l.dataset.autoSelect2='1'; document.head.appendChild(l);
        }
        // load js once
        if(!document.querySelector('script[data-auto-select2]')){
            const s=document.createElement('script'); s.src=SELECT2_JS; s.dataset.autoSelect2='1'; s.onload=()=> setTimeout(()=> cb && cb(),50); document.head.appendChild(s);
        } else {
            setTimeout(()=> cb && cb(),100);
        }
    }
    window.__ensureSelect2 = ensureSelect2; // expose for debugging
})();
</script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const modalEl = document.getElementById('kt_modal_edit_user');
    const mainContent = document.getElementById('main-content');
    const placeholderImg = @js(asset('assets/media/svg/files/blank-image.svg'));

    const selectsConfig = [
        { id: 'kt_select2_edit_party_id', hiddenId: 'hidden_edit_party_id', prop: 'edit_party_id', decorate: 'party' },
        { id: 'kt_select2_edit_consite_or_sub_id', hiddenId: 'hidden_edit_consite_or_sub_id', prop: 'edit_consite_or_sub_id', decorate: 'consite' },
        { id: 'kt_select2_edit_country_id', hiddenId: 'hidden_edit_country_id', prop: 'edit_country_id' },
        { id: 'kt_select2_edit_island_id', hiddenId: 'hidden_edit_island_id', prop: 'edit_island_id' },
        { id: 'kt_select2_edit_property_id', hiddenId: 'hidden_edit_property_id', prop: 'edit_properties_id' },
        { id: 'kt_select2_edit_current_country_id', hiddenId: 'hidden_edit_current_country_id', prop: 'edit_current_country_id' },
        { id: 'kt_select2_edit_current_island_id', hiddenId: 'hidden_edit_current_island_id', prop: 'edit_current_island_id' },
        { id: 'kt_select2_edit_current_property_id', hiddenId: 'hidden_edit_current_property_id', prop: 'edit_current_properties_id' },
        { id: 'kt_select2_edit_contact_directory_id', hiddenId: 'hidden_edit_contact_directory_id', prop: 'edit_contact_directory_id' },
    ];

    function safe(fn){ try { return fn(); } catch(e){ console.warn('[DirectoryEdit] ', e); } }
    const elementIsVisible = el => el && (el.offsetWidth || el.offsetHeight || el.getClientRects().length);

    function decorateOptions(cfg, base){
        if (cfg.decorate === 'party') {
            base.templateResult = function (data) {
                if(!data.id) return data.text; const logo=$(data.element).data('logo')||placeholderImg;
                return $('<span class="d-flex align-items-center"><img src="'+logo+'" class="rounded me-2" style="width:24px;height:24px;object-fit:cover;" onerror="this.src=\''+placeholderImg+'\'" /><span>'+data.text+'</span></span>');
            };
            base.templateSelection = function (data) {
                if(!data.id) return data.text; const logo=$(data.element).data('logo')||placeholderImg;
                return $('<span class="d-flex align-items-center"><img src="'+logo+'" class="rounded me-2" style="width:20px;height:20px;object-fit:cover;" onerror="this.src=\''+placeholderImg+'\'" /><span>'+data.text+'</span></span>');
            };
            base.escapeMarkup = m=>m;
        } else if (cfg.decorate === 'consite') {
            base.templateResult = function (data) {
                if(!data.id) return data.text; const $el=$(data.element); const type=$el.data('type'); const parent=$el.data('parent');
                if(type==='sub') return $('<span><span class="badge badge-light me-2">'+parent+'</span>'+$el.text()+'</span>');
                return data.text;
            };
            base.templateSelection = function (data) {
                if(!data.id) return data.text; const $el=$(data.element); const type=$el.data('type'); const parent=$el.data('parent');
                if(type==='sub') return parent+' - '+$el.text();
                return data.text;
            };
        }
        return base;
    }

    function initSingle(id){
        const cfg = selectsConfig.find(c=>c.id===id); if(!cfg) return; const el=document.getElementById(cfg.id); if(!el) return;
        if(!elementIsVisible(el)){ return; }
        const $select = $(el); const hidden=document.getElementById(cfg.hiddenId);
        if($select.data('select2')){ // sync value only
            const val=@this.get(cfg.prop); if(val){ $select.val(val).trigger('change.select2'); }
            return;
        }
        let options={ dropdownParent: $('#kt_modal_edit_user .modal-content'), placeholder:$select.data('placeholder')||'Select...', allowClear:true, width:'100%'};
        options = decorateOptions(cfg, options);
        safe(()=> $select.select2(options));
        const current=@this.get(cfg.prop);
        if(current){ if(!$select.find('option[value="'+current+'"]').length){ $select.append(new Option(current,current,true,true)); } $select.val(current).trigger('change.select2'); }
        $select.off('change.dir').on('change.dir', function(){ const v=this.value||''; if(hidden){ hidden.value=v; hidden.dispatchEvent(new Event('input',{bubbles:true})); } @this.set(cfg.prop, v||null); });
    }

    function initAll(){ selectsConfig.forEach(c=> initSingle(c.id)); }
    function integrity(){ selectsConfig.forEach(c=>{ const el=document.getElementById(c.id); if(el && elementIsVisible(el) && !$(el).data('select2')) initSingle(c.id); }); }

    let integrityTimer=null;
    function startIntegrity(){ if(!integrityTimer) integrityTimer=setInterval(()=>safe(integrity),800); }
    function stopIntegrity(){ if(integrityTimer){ clearInterval(integrityTimer); integrityTimer=null; } }

    const observer = new MutationObserver(()=> setTimeout(()=> safe(integrity),120));

    function onModalShown(){ safe(initAll); startIntegrity(); observer.observe(modalEl,{subtree:true,childList:true}); }
    function onModalHidden(){ stopIntegrity(); observer.disconnect(); selectsConfig.forEach(c=>{ const $s=$('#'+c.id); if($s.data('select2')){ safe(()=> $s.off('change.dir').select2('destroy')); } }); mainContent?.removeAttribute('inert'); }

    if(modalEl){ modalEl.addEventListener('shown.bs.modal', onModalShown); modalEl.addEventListener('hidden.bs.modal', onModalHidden); }

    Livewire.on('showEditDirectoryModal', () => { mainContent?.setAttribute('inert',''); bootstrap.Modal.getOrCreateInstance(modalEl).show(); setTimeout(()=> integrity(),250); });
    Livewire.on('closeEditDirectoryModal', () => { bootstrap.Modal.getInstance(modalEl)?.hide(); });
    Livewire.on('reinit-edit-select2', () => setTimeout(()=> integrity(),150));
    Livewire.hook('message.processed', () => setTimeout(()=> integrity(),180));

    // Ensure select2 library then init (modal may open later)
    if(window.__ensureSelect2){ window.__ensureSelect2(()=> {/* ready */}); }
})();
</script>
@endpush