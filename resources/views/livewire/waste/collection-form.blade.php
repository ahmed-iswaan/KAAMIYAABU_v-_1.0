<!--begin::Modal-->
<div wire:ignore.self class="modal fade" id="changeStatusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <!--begin::Header-->
            <div class="modal-header">
                <h5 class="modal-title">Waste Collection</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <!--end::Header-->

            @php
            $status = $selectedTask->status ?? 'unknown';
            $statusColors = [
                'pending' => 'bg-warning',
                'in_progress' => 'bg-info',
                'completed' => 'bg-success',
                'cancelled' => 'bg-danger',
            ];
            $statusColor = $statusColors[$status] ?? 'bg-secondary';
         @endphp

            <!--begin::Body-->
            <div class="modal-body">
                    <div class="card-header ribbon ribbon-end">
                     <div class="ribbon-label {{ $statusColor }}">
                            {{ ucwords(str_replace('_', ' ', $status)) }}
                     </div>
                    <h2 class="card-title">{{ $selectedTask->property->name ?? 'N/A' }} - {{ $selectedTask->register->register_number ?? '-' }}</h2>
            </div>
                @if ($modalError)
                    <div class="alert alert-danger">{{ $modalError }}</div>
                @elseif ($selectedTask)

                    <div class="mb-3">
                        <div class="separator separator-content border- my-10"><span class="w-250px fw-bold">Waste Collection</span></div>
                        @foreach ($wasteInputs as $index => $input)
                            <div class="d-flex align-items-center mb-2">
                                <label class="me-2 w-50">{{ $wasteTypes->firstWhere('id', $input['waste_type_id'])->name ?? 'Type' }}</label>
                                <input type="number" step="0.01" min="0" wire:model.defer="wasteInputs.{{ $index }}.amount"
                                    class="form-control form-control form-control-transparent-sm" placeholder="Amount ({{ $wasteTypes->firstWhere('id', $input['waste_type_id'])->unit ?? '' }})">
                            </div>
                        @endforeach
                    </div>

                    <div class="mb-3">
                        <div class="separator separator-content border- my-10"><span class="w-250px fw-bold">New Status</span></div>
                        <select class="form-select" wire:model.defer="newStatus">
                            <option value="">Select status</option>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                @endif
            </div>
            <!--end::Body-->

            <!--begin::Footer-->
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                @if ($selectedTask)
                    <button class="btn btn-primary" wire:click="updateStatus">Update</button>
                @endif
            </div>
            <!--end::Footer-->
        </div>
    </div>
</div>
<!--end::Modal-->

@push('scripts')
<script>
    window.addEventListener('hide-change-status-modal', () => {
        const modalEl = document.getElementById('changeStatusModal');
        const modal = bootstrap.Modal.getInstance(modalEl);

        if (modal) {
            modal.hide();
        }

        // Manually remove backdrop (in case it was left behind)
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());

        // Also remove 'modal-open' class from body to enable scrolling again
        document.body.classList.remove('modal-open');
    });
</script>

@push('scripts')
<script>
    function setDeviceLocationToLivewire() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    Livewire.dispatch('set-device-location', [
                        position.coords.latitude,
                        position.coords.longitude
                    ]);
                },
                (error) => {
                    console.warn('Location access denied or failed:', error);
                }
            );
        } else {
            console.warn('Geolocation not supported.');
        }
    }

    window.addEventListener('show-change-status-modal', () => {
        const modal = new bootstrap.Modal(document.getElementById('changeStatusModal'));
        modal.show();
        setDeviceLocationToLivewire(); // auto-fetch when modal opens
    });
</script>
@endpush

@endpush

