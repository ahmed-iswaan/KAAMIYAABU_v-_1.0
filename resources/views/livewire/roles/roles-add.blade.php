<div>
    <!--begin::Modal - Add role-->
    <div  class="modal fade" id="kt_modal_add_roles" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Add New Role</h2>
                    <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </button>
                </div>

                <div class="modal-body">
                    <form wire:submit.prevent="createRole">
                        <div class="d-flex flex-column px-5">
                            @if (session()->has('error'))
                            <div class="py-5">
                                <!--begin::Information-->
                                <div class="d-flex align-items-center rounded py-5 px-5 bg-light-warning ">
                                    <i class="ki-duotone ki-information-5 fs-3x text-warning me-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                    <!--begin::Description-->
                                    <div class="text-gray-700 fw-bold fs-6">
                                      {{ session('error') }}
                                    </div>
                                    <!--end::Description-->
                                </div>
                             </div>
                             @endif
                            <!-- Role Name< -->
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Role Name</label>
                                <input type="text" wire:model="role_name" class="form-control form-control-solid" placeholder="Enter Role Name">
                                @error('role_name')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>


                            <div class="fv-row mb-8 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid">
                                <!--begin::Label-->
                                <label class=" fs-6 fw-semibold mb-2">Description</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <textarea class="form-control form-control-solid" wire:model="role_description" rows="3" placeholder="Enter Description" name="role_description"></textarea>
                                <!--end::Input-->
                            <div class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback"></div></div>

                            <!-- Roles Selection -->
                            <div class="mb-5">
                                <label class="required fw-semibold fs-6 mb-5">Role</label>
                                <br>
                                @error('selectedPermission')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                                @foreach ($permission as $permission)
                                <div class="d-flex fv-row">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input me-3" type="checkbox" wire:model="selectedPermission" value="{{$permission->id}}" id="role_{{$permission->id}}">
                                        <label class="form-check-label" for="role_{{$permission->id}}">
                                            <div class="fw-bold text-gray-800">{{ $permission->name }}</div>
                                        </label>
                                    </div>
                                </div>
                                <div class="separator separator-dashed my-5"></div>
                            @endforeach

                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                            <button type="submit" class="btn btn-primary">
                                <span class="indicator-label">Submit</span>
                                <span wire:loading wire:target="createRole" class="indicator-progress">Please wait...
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
    function waitForLivewire(callback) {
        if (typeof window.Livewire !== 'undefined') {
            // console.log('✅ Livewire is now available');
            callback();
        } else {
            // console.log('⏳ Waiting for Livewire...');
            setTimeout(() => waitForLivewire(callback), 500);
        }
    }

    waitForLivewire(() => {
        // console.log('✅ Livewire is finally loaded!');

        const modalElement = document.getElementById('kt_modal_add_roles');

        if (!modalElement) {
            // console.error('❌ Modal element not found');
            return;
        }

        // console.log('✅ Modal element found');

        const modal = new bootstrap.Modal(modalElement);

        Livewire.on('showModal', () => {
            // console.log('✅ showModal event received');
            modal.show();
        });

        Livewire.on('closeModal', () => {
            // console.log('✅ closeModal event received');
            modal.hide();
        });

        // Test modal manually
        // console.log('✅ Running manual modal test...');

    });
</script>





