<div>
    <div class="modal fade" wire:ignore.self id="kt_modal_payment_view" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-light">
                    <h3 class="modal-title fw-bold text-primary">
                        📄 Payment Slip
                    </h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"></i>
                    </div>
                </div>

                <div class="modal-body bg-white">
                    @if($selectedPayment)
                        <div class="p-5">
                            <!-- Header -->
                            <div class="text-center mb-5 border-bottom pb-4">
                                @if(file_exists(public_path('images/logo.png')))
                                    <img src="{{ asset('images/logo.png') }}" alt="Logo" class="mb-3" style="height: 60px;">
                                @endif
                                <h4 class="fw-bold mb-0">{{ config('app.name') }}</h4>
                                <small class="text-muted">Payment Slip</small>
                            </div>

                            <!-- Payment Info -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <p><strong>Payment No:</strong> {{ $selectedPayment->number }}</p>
                                    <p><strong>Date:</strong> {{ $selectedPayment->date->format('d M Y') }}</p>
                                    <p><strong>Status:</strong>
                                        <span class="badge 
                                            {{ $selectedPayment->status === 'Approved' ? 'bg-success' : 
                                               ($selectedPayment->status === 'Pending' ? 'bg-warning text-dark' : 'bg-danger') }}">
                                            {{ ucfirst($selectedPayment->status) }}
                                        </span>
                                    </p>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <p><strong>Payer:</strong> {{ $selectedPayment->directory->name ?? 'N/A' }}</p>
                                    <p><strong>Reference:</strong> {{ $selectedPayment->ref ?? 'N/A' }}</p>
                                    <p><strong>Bank:</strong> {{ $selectedPayment->bank ?? 'N/A' }}</p>
                                </div>
                            </div>

                            <!-- Payment Slip Upload -->
                            @if($selectedPayment->payment_slip)
                                <div class="mb-5">
                                    <h5 class="fw-bold mb-3">Attached Slip</h5>
                                    @if(Str::endsWith(strtolower($selectedPayment->payment_slip), ['.jpg','.jpeg','.png']))
                                        <div class="text-center">
                                            <img src="{{ asset('storage/'.$selectedPayment->payment_slip) }}" 
                                                 alt="Payment Slip" class="img-fluid rounded shadow" style="max-height: 400px;">
                                        </div>
                                    @elseif(Str::endsWith(strtolower($selectedPayment->payment_slip), ['.pdf']))
                                        <div class="text-center">
                                            <a href="{{ asset('storage/'.$selectedPayment->payment_slip) }}" 
                                               target="_blank" class="btn btn-outline-primary">
                                               📑 View Payment Slip (PDF)
                                            </a>
                                        </div>
                                    @else
                                        <p class="text-muted">Payment slip not viewable.</p>
                                    @endif
                                </div>
                            @else
                                <div class="alert alert-secondary">No payment slip uploaded.</div>
                            @endif

                            <!-- Amount & Invoices -->
                            <div class="card border-0 shadow-sm mb-5">
                                <div class="card-body text-center">
                                    <h2 class="fw-bold text-success">
                                        ${{ number_format($selectedPayment->amount, 2) }}
                                    </h2>
                                    <p class="text-muted mb-0">Total Amount Paid</p>
                                </div>
                            </div>

                            <h5 class="fw-bold mb-3">Invoices Covered</h5>
                            <table class="table table-striped table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Invoice Number</th>
                                        <th class="text-end">Applied Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($selectedPayment->invoices as $invoice)
                                        <tr>
                                            <td>{{ $invoice->number }}</td>
                                            <td class="text-end">${{ number_format($invoice->pivot->applied_amount, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center text-muted">No invoices applied.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>

                            <!-- Notes -->
                            @if($selectedPayment->note || $selectedPayment->cancel_note)
                                <div class="alert alert-secondary mt-4">
                                    @if($selectedPayment->note)
                                        <p class="mb-1"><strong>Note:</strong> {{ $selectedPayment->note }}</p>
                                    @endif
                                    @if($selectedPayment->cancel_note)
                                        <p class="mb-0 text-danger"><strong>Cancellation Reason:</strong> {{ $selectedPayment->cancel_note }}</p>
                                    @endif
                                </div>
                            @endif

                            <!-- Footer -->
                            <div class="text-center mt-5 border-top pt-3">
                                <small class="text-muted">This slip is system-generated and does not require a signature.</small>
                            </div>
                        </div>
                    @else
                        <p class="text-center">No payment selected.</p>
                    @endif
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="window.print()">Print Slip</button>
                </div>
            </div>
        </div>
    </div>
</div>


@push('scripts')
<script>
    function waitForLivewire(cb) {
        if (window.Livewire) return cb();
        setTimeout(() => waitForLivewire(cb), 200);
    }
    waitForLivewire(() => {
        const modalEl = document.getElementById('kt_modal_payment_view');
        if (!modalEl) return;
        const bsModal = new bootstrap.Modal(modalEl);

        Livewire.on('showPaymentModal',  () => bsModal.show());
        Livewire.on('closePaymentModal', () => bsModal.hide());
    });
</script>
@endpush
