<div>
    <!--begin::Modal - Edit Role-->
    <div class="modal fade" id="kt_modal_edit_roles" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Edit Role</h2>
                    <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </button>
                </div>

                <div class="modal-body">
                    <form wire:submit.prevent="updateRole">
                        <div class="d-flex flex-column px-5">
                            @if (session()->has('error'))
                            <div class="py-5">
                                <div class="d-flex align-items-center rounded py-5 px-5 bg-light-warning">
                                    <i class="ki-duotone ki-information-5 fs-3x text-warning me-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                    <div class="text-gray-700 fw-bold fs-6">
                                      {{ session('error') }}
                                    </div>
                                </div>
                             </div>
                             @endif

                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Role Name</label>
                                <input type="text" wire:model="editrole_name" class="form-control form-control-solid" placeholder="Enter Role Name">
                                @error('editrole_name')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="fv-row mb-8">
                                <label class="fs-6 fw-semibold mb-2">Description</label>
                                <textarea class="form-control form-control-solid" wire:model="editrole_description" rows="3" placeholder="Enter Description"></textarea>
                            </div>

                            <div class="mb-5">
                                <label class="required fw-semibold fs-6 mb-5">Permissions</label>
                                <br>
                                @error('editselectedPermissions')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                                @foreach ($permission as $perm)
                                <div class="d-flex fv-row">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input me-3" type="checkbox" wire:model="editselectedPermissions" value="{{$perm->id}}" id="edit_role_{{$perm->id}}">
                                        <label class="form-check-label" for="edit_role_{{$perm->id}}">
                                            <div class="fw-bold text-gray-800">{{ $perm->name }}</div>
                                        </label>
                                    </div>
                                </div>
                                <div class="separator separator-dashed my-5"></div>
                                @endforeach
                            </div>
                        </div>

                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <span class="indicator-label">Update</span>
                                <span wire:loading wire:target="updateRole" class="indicator-progress">Please wait...
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

        const modalElement = document.getElementById('kt_modal_edit_roles');

        if (!modalElement) {
            // console.error('❌ Modal element not found');
            return;
        }

        // console.log('✅ Modal element found');

        const modal = new bootstrap.Modal(modalElement);

        Livewire.on('showModalEditRole', () => {
            // console.log('✅ showModal event received');
            modal.show();
        });

        Livewire.on('closeModalEditRole', () => {
            // console.log('✅ closeModal event received');
            modal.hide();
        });

        // Test modal manually
        // console.log('✅ Running manual modal test...');

    });
</script>
