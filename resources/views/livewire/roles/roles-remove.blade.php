                          <!--begin::Modal - Add task-->
                                <div class="modal fade" id="kt_modal_remove_role" tabindex="-1" aria-hidden="true" wire:ignore.self>
                                    <!--begin::Modal dialog-->
                                    <div class="modal-dialog modal-dialog-centered mw-650px">
                                        <!--begin::Modal content-->
                                        <div class="modal-content" style="background-color: #fff5f8;">

                                            <!--begin::Alert-->
                                    <div class="alert alert-dismissible bg-light-danger d-flex flex-center flex-column py-10 px-10 px-lg-20 mb-10">
                                        <!--begin::Close-->
                                        <button type="button" class="position-absolute top-0 end-0 m-2 btn btn-icon btn-icon-danger" data-bs-dismiss="modal">
                                            <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                                        </button>
                                        <!--end::Close-->

                                        <!--begin::Icon-->
                                        <i class="ki-duotone ki-information-5 fs-5tx text-danger mb-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                        <!--end::Icon-->

                                        <!--begin::Wrapper-->
                                        <div class="text-center">
                                            <!--begin::Title-->
                                            <h1 class="fw-bold mb-5">Are you sure?</h1>
                                            <!--end::Title-->

                                            <!--begin::Separator-->
                                            <div class="separator separator-dashed border-danger opacity-25 mb-5"></div>
                                            <!--end::Separator-->

                                            <!--begin::Content-->
                                            <div class="mb-9 text-gray-900">
                                                You are about to delete the role <strong>{{$editname}}</strong> from the system.

                                                ❌ This role will be permanently removed.

                                                🚫 Users assigned to this role will lose associated permissions.

                                                🔄 This action cannot be undone!
                                            </div>
                                            <!--end::Content-->

                                                <!--begin::Buttons-->
                                                <div class="d-flex flex-center flex-wrap">
                                                    <a href="#" class="btn btn-outline btn-outline-danger btn-active-danger m-2" data-bs-dismiss="modal">Cancel</a>
                                                    <a href="#" class="btn btn-danger m-2" wire:click="RemoveUserRole">Remove</a>
                                                </div>
                                                <!--end::Buttons-->
                                            </div>
                                            <!--end::Wrapper-->
                                        </div>
                                        <!--end::Alert-->

                                        </div>
                                        <!--end::Modal content-->
                                    </div>
                                    <!--end::Modal dialog-->
                                </div>

                                <!--end::Modal - Add task-->
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

                                        const modalElement = document.getElementById('kt_modal_remove_role');

                                        if (!modalElement) {
                                            // console.error('❌ Modal element not found');
                                            return;
                                        }

                                        // console.log('✅ Modal element found');

                                        const modal = new bootstrap.Modal(modalElement);

                                        Livewire.on('showModalremove', () => {
                                            // console.log('✅ showModal event received');
                                            modal.show();
                                        });

                                        Livewire.on('closeModalremove', () => {
                                            // console.log('✅ closeModal event received');
                                            modal.hide();
                                        });

                                        // Test modal manually
                                        // console.log('✅ Running manual modal test...');

                                    });
                                </script>


