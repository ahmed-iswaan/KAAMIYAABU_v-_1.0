<div class="modal fade" tabindex="-1" id="kt_modal_pay" wire:ignore.self>
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light px-5 py-3 border-bottom">
                <h3 class="modal-title text-dark fw-bold">
                    🧾 Invoice Payment Summary
                </h3>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </button>
            </div>

            <div class="modal-body px-5 pt-4 pb-2">
                @if($invoicesToPay)
                    {{-- Header Info --}}
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payer</label>
                            <!-- Select2 visible dropdown -->
                            <select class="form-select"  wire:model.live="selectedCustomerId">
                                <option value="">Select Payer</option>
                                @foreach($directories as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Date</label>
                            <input type="date" class="form-control" wire:model="paymentDate" />
                        </div>
                    </div>

                    {{-- Payment Options --}}
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Payment Method</label>
                            <select class="form-select" wire:model="paymentMethod">
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="transfer">Bank Transfer</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Bank</label>
                            <select class="form-select" wire:model="paymentBank">
                                <option value="">Select Bank</option>
                                <option value="MIB">MIB</option>
                                <option value="BML">BML</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Reference #</label>
                            <input type="text" class="form-control" wire:model.lazy="paymentRef" />
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Payment Slip (Image or PDF)</label>
                        <input type="file" class="form-control" wire:model="paymentSlip" accept="image/*,application/pdf" />
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Note</label>
                        <textarea class="form-control" wire:model.lazy="paymentNote" rows="2"></textarea>
                    </div>

                    {{-- Amount --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Enter Payment Amount (MVR)</label>
                        <input type="number" min="0" step="0.01" class="form-control" wire:model.live.debounce.500ms="paymentAmount" />
                        @error('paymentAmount')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Warning --}}
                    @if ($unpayableInvoices)
                        <div class="alert alert-warning mb-4">
                            ⚠️ No amount left for invoice(s): <strong>{{ implode(', ', $unpayableInvoices) }}</strong>
                        </div>
                    @endif

                    {{-- Table --}}
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="bg-secondary text-white">
                                <tr>
                                    <th>#</th>
                                    <th>Invoice</th>
                                    <th>Customer / Property</th>
                                    <th class="text-end">Total (MVR)</th>
                                    <th class="text-end">Applied</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($paymentPreview as $i => $p)
                                    @php
                                            $inv = $p['invoice'];
                                            $balance = $inv->total_amount - $inv->paid_amount;
                                        @endphp
                                
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $p['invoice']->number }}</td>
                                        <td>{{ $p['invoice']->directory->name }} / {{ $p['invoice']->property->name }}</td>
                                        <td class="text-end">
                                            <div>Total: {{ number_format($inv->total_amount, 2) }}</div>
                                            <div class="text-success small">Paid: {{ number_format($inv->paid_amount, 2) }}</div>
                                            <div class="text-danger small">Balance: {{ number_format($balance, 2) }}</div>
                                        </td>
                                        <td class="text-end fw-bold {{ $p['applied'] == 0 ? 'text-danger' : 'text-success' }}">
                                            {{ number_format($p['applied'], 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-light fw-bold">
                                    <td colspan="4" class="text-end">Total to Apply:</td>
                                    <td class="text-end text-primary">{{ number_format(collect($paymentPreview)->sum('applied'), 2) }} MVR</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <p class="text-muted">No invoices selected.</p>
                @endif
            </div>

            {{-- Modal Footer --}}
            @php
                $totalApplied   = collect($paymentPreview)->sum('applied');
                $hasZeroApplied = collect($paymentPreview)->contains(fn($p) => $p['applied'] == 0);
            @endphp

            <div class="modal-footer bg-light px-5 flex-column align-items-stretch">
                <div class="d-flex justify-content-between w-100 text-muted mb-2">
                    <div>💳 Available Credit: <strong class="text-info">{{ number_format($availableCredit, 2) }} MVR</strong></div>
                    <div>🔄 Credit Used: <strong class="text-warning">{{ number_format($creditUsed, 2) }} MVR</strong></div>
                    <div>💰 Overpaid to Credit: <strong class="text-success">{{ number_format($overpaidAmount, 2) }} MVR</strong></div>
                    <div>🧾 Applied: <strong class="text-primary">{{ number_format($totalApplied, 2) }} MVR</strong></div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary ms-2"
                            wire:click="submitPayment"
                            @if ($paymentAmount <= 0 || $totalApplied == 0 || $hasZeroApplied)
                                disabled
                            @endif>
                        Make Payment
                    </button>
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
        const modalEl = document.getElementById('kt_modal_pay');
        if (!modalEl) return;
        const bsModal = new bootstrap.Modal(modalEl);

        Livewire.on('showPayModal', () => {
            bsModal.show();

            // ✅ Reinitialize Select2
            setTimeout(() => {
                $('#select_customer').select2({
                    dropdownParent: $('#kt_modal_pay'),
                    placeholder: 'Select Customer',
                    allowClear: true
                }).on('change', function (e) {
                    Livewire.find(modalEl.getAttribute('wire:id'))
                            .set('selectedCustomerId', $(this).val());
                });
            }, 100);
        });

        Livewire.on('closePayModal', () => bsModal.hide());
    });
</script>
@endpush


