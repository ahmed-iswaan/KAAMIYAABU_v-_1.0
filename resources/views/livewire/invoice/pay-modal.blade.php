<div class="modal fade" tabindex="-1" id="kt_modal_pay" wire:ignore.self>
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light px-5">
                <h3 class="modal-title">
                    🧾 Invoice Payment Summary
                </h3>
                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>

<div class="modal-body px-5 pt-4 pb-2">
    @if($invoicesToPay)
        <div class="mb-4">
            <label class="form-label">Enter Payment Amount (MVR)</label>
            <input type="number" min="0" step="0.01" class="form-control" wire:model.live.debounce.500ms="paymentAmount" />
        </div>

        @error('paymentAmount')
            <div class="text-danger mb-2">{{ $message }}</div>
        @enderror

        @if ($unpayableInvoices)
            <div class="alert alert-warning">No amount left for invoice(s): {{ implode(', ', $unpayableInvoices) }}</div>
        @endif

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="bg-secondary text-white">
                    <tr>
                        <th>#</th>
                        <th>Invoice</th>
                        <th>Customer / Property</th>
                        <th class="text-end">Amount (MVR)</th>
                        <th class="text-end">Applied</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($paymentPreview as $i => $p)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $p['invoice']->number }}</td>
                            <td>{{ $p['invoice']->directory->name }} / {{ $p['invoice']->property->name }}</td>
                            <td class="text-end">{{ number_format($p['invoice']->total_amount, 2) }}</td>
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

@php
    $hasZeroApplied = collect($paymentPreview)->contains(fn($p) => $p['applied'] == 0);
    $totalApplied   = collect($paymentPreview)->sum('applied');
@endphp

<div class="modal-footer bg-light px-5">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
    <button type="button" class="btn btn-primary"
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

            Livewire.on('showPayModal',  () => bsModal.show());
            Livewire.on('closePayModal', () => bsModal.hide());
        });
    </script>
 @endpush
