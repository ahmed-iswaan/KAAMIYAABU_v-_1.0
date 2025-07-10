<div>
    <!--begin::Modal - Add User-->
    <div class="modal fade" id="kt_modal_add_user" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Add User</h2>
                    <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </button>
                </div>

                <div class="modal-body">
                    <form wire:submit.prevent="createUser">
                        <div class="d-flex flex-column px-5">

                            @if(session()->has('error'))
                                <div class="py-5">
                                    <div class="d-flex align-items-center rounded py-5 px-5 bg-light-warning">
                                        <i class="ki-duotone ki-information-5 fs-3x text-warning me-5">
                                            <span class="path1"></span><span class="path2"></span><span
                                                class="path3"></span>
                                        </i>
                                        <div class="text-gray-700 fw-bold fs-6">
                                            {{ session('error') }}
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="d-block fw-semibold fs-6 mb-5">Profile Picture</label>
                                <!--end::Label-->

                                <!--begin::Image placeholder styles-->
                                <style>
                                    .image-input-placeholder {
                                        background-image: url('{{ asset("assets/media/svg/files/blank-image.svg") }}');
                                    }

                                    [data-bs-theme="dark"] .image-input-placeholder {
                                        background-image: url('{{ asset("assets/media/svg/files/blank-image-dark.svg") }}');
                                    }

                                </style>
                                <!--end::Image placeholder styles-->

                                <!--begin::Image input-->
                                <div class="image-input image-input-outline image-input-placeholder image-input-empty"
                                    data-kt-image-input="true" wire:ignore>
                                    <!-- Preview existing or uploaded avatar -->
                                    <div class="image-input-wrapper w-125px h-125px" style="background-image: 
@if($profile_picture)
                                    url('{{ $profile_picture->temporaryUrl() }}')
@elseif(isset($user) && $user->profile_picture)
                                    url('{{ asset('storage/'.$user->profile_picture) }}')
@else
                                    none
@endif
                                ;">
                                    </div>
                                    <!-- End preview -->

                                    <!-- Change Profile Picture button -->
                                    <label
                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="change" data-bs-toggle="tooltip"
                                        title="Change Uploade Picture">
                                        <i class="ki-duotone ki-pencil fs-7">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                        <!-- file input wired to Livewire -->
                                        <input type="file" wire:model="profile_picture" accept=".png, .jpg, .jpeg">
                                        <!-- this hidden input can be used if you implement a “remove” action -->
                                        <input type="hidden" wire:model="profile_picture_remove" value="1">
                                    </label>
                                    <!-- End change -->

                                    <!-- Cancel upload -->
                                    <span
                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="cancel" data-bs-toggle="tooltip"
                                        title="Cancel avatar">
                                        <i class="ki-duotone ki-cross fs-2">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </span>
                                    <!-- End cancel -->

                                    <!-- Remove avatar -->
                                    <span class="btn btn-icon btn-circle …" data-kt-image-input-action="remove"
                                        data-bs-toggle="tooltip" title="Remove avatar"
                                        onclick="@this.set('profile_picture', null)">
                                        <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span
                                                class="path2"></span></i>
                                    </span>
                                    <!-- End remove -->
                                </div>
                                <!--end::Image input-->

                                <!--begin::Hint-->
                                <div class="form-text">Allowed file types: png, jpg, jpeg. Max size: 1MB.</div>
                                <!--end::Hint-->
                            </div>


                            {{-- Name --}}
                            <div class="fv-row mb-5">
                                <label class="required fw-semibold fs-6 mb-2">Name</label>
                                <input type="text" wire:model.defer="name" class="form-control form-control-solid"
                                    placeholder="Enter full name">
                                @error('name')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Email --}}
                            <div class="fv-row mb-5">
                                <label class="required fw-semibold fs-6 mb-2">Email</label>
                                <input type="email" wire:model.defer="email" class="form-control form-control-solid"
                                    placeholder="Enter email address">
                                @error('email')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Password --}}
                            <div class="fv-row mb-5">
                                <label class="required fw-semibold fs-6 mb-2">Password</label>
                                <input type="password" wire:model.defer="password"
                                    class="form-control form-control-solid" placeholder="Enter password">
                                @error('password')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Confirm Password --}}
                            <div class="fv-row mb-5">
                                <label class="required fw-semibold fs-6 mb-2">Confirm Password</label>
                                <input type="password" wire:model.defer="password_confirmation"
                                    class="form-control form-control-solid" placeholder="Re-enter password">
                                @error('password_confirmation')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Staff ID --}}
                            <div class="fv-row mb-5">
                                <label class="required fw-semibold fs-6 mb-2">Staff ID</label>
                                <input type="text" wire:model.defer="staff_id" class="form-control form-control-solid"
                                    placeholder="Enter Staff ID">
                                @error('staff_id')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Job Title --}}
                            <div class="fv-row mb-5">
                                <label class="required fw-semibold fs-6 mb-2">Job Title</label>
                                <input type="text" wire:model.defer="job_title" class="form-control form-control-solid"
                                    placeholder="Enter Job Title">
                                @error('job_title')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Roles Selection --}}
                            <div class="mb-5">
                                <label class="required fw-semibold fs-6 mb-2">Role</label>
                                @error('selectedRoles')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                @foreach($roles_list as $i => $role)
                                    <div class="d-flex align-items-center mb-3">
                                        <input class="form-check-input me-3" type="checkbox" wire:model="selectedRoles"
                                            value="{{ $role->name }}" id="role_{{ $i }}__create">
                                        <label class="form-check-label" for="role_{{ $i }}__create">
                                            <div class="fw-bold text-gray-800">{{ $role->name }}</div>
                                            <div class="text-gray-600">{{ $role->details }}</div>
                                        </label>
                                    </div>
                                    @if(! $loop->last)
                                        <div class="separator separator-dashed my-2"></div>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                            <button type="submit" class="btn btn-primary">
                                <span class="indicator-label">Submit</span>
                                <span wire:loading wire:target="createUser" class="indicator-progress">
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
    // same Livewire modal wiring as before...
    function waitForLivewire(callback) {
        if (window.Livewire) return callback();
        setTimeout(() => waitForLivewire(callback), 200);
    }

    waitForLivewire(() => {
        const modalEl = document.getElementById('kt_modal_add_user');
        const modal = new bootstrap.Modal(modalEl);

        Livewire.on('showModal', () => modal.show());
        Livewire.on('closeModal', () => modal.hide());
    });

</script>
