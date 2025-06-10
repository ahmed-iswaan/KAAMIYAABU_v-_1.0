<!-- resources/views/livewire/user/user-edit-modal.blade.php -->

<div>
    <!--begin::Modal - Edit User-->
    <div class="modal fade" id="kt_modal_view_user" tabindex="-1" aria-hidden="true" wire:ignore.self>
      <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">

          <!-- Modal header -->
          <div class="modal-header" id="kt_modal_view_user_header">
            <h2 class="fw-bold">Edit User</h2>
            <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
              <i class="ki-duotone ki-cross fs-1">
                <span class="path1"></span><span class="path2"></span>
              </i>
            </button>
          </div>

          <!-- Modal body -->
          <div class="modal-body px-5 my-7">
            <form wire:submit.prevent="updateUser" id="kt_modal_view_user_form">

              <div 
                class="d-flex flex-column scroll-y px-5 px-lg-10" 
                id="kt_modal_view_user_scroll"
                data-kt-scroll="true"
                data-kt-scroll-activate="true"
                data-kt-scroll-max-height="auto"
                data-kt-scroll-dependencies="#kt_modal_view_user_header"
                data-kt-scroll-wrappers="#kt_modal_view_user_scroll"
                data-kt-scroll-offset="300px"
                style="max-height: 558px;"
              >

@php
    // 1) Decide which URL to show
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

  @error('edit_profile_picture')
    <div class="text-danger mt-2">{{ $message }}</div>
  @enderror
</div>





                <div class="separator mb-7"></div>

                <!-- Full Name -->
                <div class="fv-row mb-5">
                  <label class="fw-semibold fs-6 mb-2">Full Name</label>
                  <input 
                    type="text" 
                    wire:model.defer="editname" 
                    class="form-control form-control-solid"
                  >
                  @error('editname') 
                    <div class="text-danger">{{ $message }}</div> 
                  @enderror
                </div>

                <!-- Email -->
                <div class="fv-row mb-5">
                  <label class="fw-semibold fs-6 mb-2">Email</label>
                  <input 
                    type="email" 
                    wire:model.defer="editemail" 
                    class="form-control form-control-solid"
                  >
                  @error('editemail') 
                    <div class="text-danger">{{ $message }}</div> 
                  @enderror
                </div>

                <!-- Staff ID -->
                <div class="fv-row mb-5">
                  <label class="fw-semibold fs-6 mb-2">Staff ID</label>
                  <input 
                    type="text" 
                    wire:model.defer="editstaff_id" 
                    class="form-control form-control-solid"
                  >
                  @error('editstaff_id') 
                    <div class="text-danger">{{ $message }}</div> 
                  @enderror
                </div>

                <!-- Job Title -->
                <div class="fv-row mb-5">
                  <label class="fw-semibold fs-6 mb-2">Job Title</label>
                  <input 
                    type="text" 
                    wire:model.defer="editjob_title" 
                    class="form-control form-control-solid"
                  >
                  @error('editjob_title') 
                    <div class="text-danger">{{ $message }}</div> 
                  @enderror
                </div>

                <div class="separator mb-7"></div>

                <!-- Roles -->
                <div class="mb-5">
                  <label class="fw-semibold fs-6 mb-3">Roles</label>
                  @error('editselectedRoles') 
                    <div class="text-danger mb-2">{{ $message }}</div> 
                  @enderror
                  @foreach($roles_list as $role)
                    <div class="form-check form-check-custom form-check-solid mb-3">
                      <input
                        class="form-check-input me-3"
                        type="checkbox"
                        wire:model="editselectedRoles"
                        value="{{ $role->name }}"
                        id="edit_role_{{ $role->id }}"
                      >
                      <label class="form-check-label" for="edit_role_{{ $role->id }}">
                        <div class="fw-bold">{{ $role->name }}</div>
                        <div class="text-gray-600">{{ $role->details }}</div>
                      </label>
                    </div>
                  @endforeach
                </div>

                <div class="separator mb-7"></div>

                <!-- Read-only Details -->
                <div class="pb-5 fs-6 text-gray-700">
                  <div class="fw-bold">Last Login</div>
                  <div class="mb-5">
                    @php
                      $last = $editlastlogin 
                        ? \Carbon\Carbon::parse($editlastlogin) 
                        : null;
                    @endphp
                    {{ $last
                        ? ($last->diffInSeconds() < 60
                            ? 'Just now'
                            : $last->diffForHumans()
                          )
                        : 'Never logged in'
                    }}
                  </div>

                  <div class="fw-bold">Joined Date</div>
                  <div>
                    {{ \Carbon\Carbon::parse($created_at ?? now())
                        ->format('d M Y, g:i a')
                    }}
                  </div>
                </div>

              </div>

              <!-- Actions -->
              <div class="text-center pt-10">
                <button 
                  type="button" 
                  class="btn btn-light me-3" 
                  data-bs-dismiss="modal"
                >
                  Discard
                </button>
                <button 
                  type="submit" 
                  class="btn btn-primary" 
                  wire:loading.attr="disabled"
                >
                  <span class="indicator-label">Submit</span>
                  <span 
                    wire:loading 
                    wire:target="updateUser" 
                    class="indicator-progress"
                  >
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
    <!--end::Modal - Edit User-->

    <!-- Livewire + Bootstrap modal hooks -->
    <script>
document.addEventListener('DOMContentLoaded', () => {
  if (!window.Livewire) {
    console.warn('Livewire JS not loaded yet!');
    return;
  }
  const el = document.getElementById('kt_modal_view_user');
  const bsModal = new bootstrap.Modal(el);
  Livewire.on('showModaledit',  () => bsModal.show());
  Livewire.on('closeModaledit', () => bsModal.hide());
});

    </script>
</div>
