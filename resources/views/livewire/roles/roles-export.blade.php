<div>
    <!-- Modal -->
    <div class="modal fade" id="kt_modal_export_roles" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Export Roles</h2>
                    <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </button>
                </div>

                <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                    <form wire:submit.prevent="exportRoles" class="form">
                        <div class="text-center">
                            <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                            <button type="submit" class="btn btn-primary">
                                <span class="indicator-label">Export CSV</span>
                                <span wire:loading wire:target="exportRoles" class="indicator-progress">
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
    // Listen for the 'closeModal' event and close the modal
    document.addEventListener('livewire:init', () => {
        Livewire.on('closeModal', () => {
            // Get the modal element
            var modalElement = document.getElementById('kt_modal_export_roles');
            var modal = new bootstrap.Modal(modalElement);

            // Hide the modal
            modal.hide();

            // Manually remove the modal backdrop
            var backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.classList.remove('fade', 'show');
                backdrop.remove();
            }

            // Reset the body's overflow property to restore scrolling
            document.body.style.overflow = '';
        });
    });
</script>


