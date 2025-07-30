<div class="modal fade" tabindex="-1" id="kt_modal_pay" wire:ignore.self>
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light px-5 py-3 border-bottom">
                <h3 class="modal-title text-dark fw-bold">
                    Invoice Payment Summary
                </h3>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </button>
            </div>

            <div class="modal-body px-5 pt-4 pb-2">

                <div class="d-flex flex-wrap">
                    <!--begin::Stat-->
                    <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                        <!--begin::Number-->
                        <div class="d-flex align-items-center">
                            <i class="ki-duotone ki-wallet fs-3 text-success me-2">
                               <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                            </i>
                            <div class="fs-4 fw-bold counted" data-kt-countup="true" data-kt-countup-value="{{ number_format($availableCredit, 2) }}"
                                 data-kt-initialized="1">{{ number_format($availableCredit, 2) }} MVR</div>
                        </div>
                        <!--end::Number-->
                        <!--begin::Label-->
                        <div class="fw-semibold fs-6 text-gray-400">Available Credit</div>
                        <!--end::Label-->
                    </div>
                    <!--end::Stat-->
                    <!--begin::Stat-->
                    <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                        <!--begin::Number-->
                        <div class="d-flex align-items-center">
                            <i class="ki-duotone ki-credit-cart fs-3 text-success me-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <div class="fs-4 fw-bold counted" data-kt-countup="true" data-kt-countup-value="{{ number_format($creditUsed, 2) }}"
                                data-kt-initialized="1">{{ number_format($creditUsed, 2) }} MVR</div>
                        </div>
                        <!--end::Number-->
                        <!--begin::Label-->
                        <div class="fw-semibold fs-6 text-gray-400">Credit Used</div>
                        <!--end::Label-->
                    </div>
                    <!--end::Stat-->
                    <!--begin::Stat-->
                    <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                        <!--begin::Number-->
                        <div class="d-flex align-items-center">
                             <i class="ki-duotone ki-bill fs-3 text-danger me-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                                <span class="path5"></span>
                                <span class="path6"></span>
                            </i>
                            <div class="fs-4 fw-bold counted" data-kt-countup="true" data-kt-countup-value="{{ number_format($overpaidAmount, 2) }}"
                                 data-kt-initialized="1">{{ number_format($overpaidAmount, 2) }} MVR</div>
                        </div>
                        <!--end::Number-->
                        <!--begin::Label-->
                        <div class="fw-semibold fs-6 text-gray-400">Overpaid to Credit</div>
                        <!--end::Label-->
                    </div>
                    <!--end::Stat-->

                                        <!--begin::Stat-->
                    <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                        <!--begin::Number-->
                        <div class="d-flex align-items-center">
                            <i class="ki-duotone ki-check-circle fs-3 text-success me-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <div class="fs-4 fw-bold counted" data-kt-countup="true" data-kt-countup-value="{{ number_format($totalApplied, 2) }} MVR"
                                data-kt-initialized="1">{{ number_format($totalApplied, 2) }} MVR</div>
                        </div>
                        <!--end::Number-->
                        <!--begin::Label-->
                        <div class="fw-semibold fs-6 text-gray-400">Applied</div>
                        <!--end::Label-->
                    </div>
                    <!--end::Stat-->
                </div>

               <div class="separator my-2"></div>

                @if($invoicesToPay)
                    {{-- Header Info --}}
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payer</label>
                            <!-- Select2 visible dropdown -->
                            <select class="form-select" wire:model.live="selectedCustomerId">
                                <option value="">Select Payer</option>
                                @foreach($directories as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                                <label class="form-label fw-semibold">Payment Method</label>
                                <select class="form-select" wire:model.live="paymentMethod">
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                    <option value="bank transfer">Bank Transfer</option>
                                </select>
                        </div>

                        <div class="col-md-6">
                                <label class="form-label fw-semibold">Payment Date</label>
                                <input type="date" class="form-control" wire:model="paymentDate" />
                        </div>


                         {{-- Amount --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Enter Payment Amount (MVR)</label>
                            <input type="number" min="0" step="0.01" class="form-control"
                                wire:model.live.debounce.500ms="paymentAmount" />
                            @error('paymentAmount')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        @if ($availableCredit > 0)
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Use Credit (Max: {{ number_format($availableCredit, 2) }} MVR)
                            </label>
                            <input
                                type="number"
                                class="form-control"
                                min="0"
                                max="{{ $availableCredit }}"
                                step="0.01"
                                wire:model.live.debounce.500ms="creditUsed"
                            />
                            @error('creditUsed')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    @endif



                            {{-- Payment Options --}}
                            @if ($paymentMethod !== 'cash')
                                <div class="row g-4 mb-4">
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

                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Payment Slip (Image or PDF)</label>
                                        <input type="file" class="form-control" wire:model="paymentSlip" accept="image/*,application/pdf" />
                                    </div>
                                </div>
                            @endif




                        <div class="mb-4">
                            <label class="form-label fw-semibold">Note</label>
                            <textarea class="form-control" wire:model.lazy="paymentNote" rows="2"></textarea>
                        </div>



                        {{-- Warning --}}
                        @if($unpayableInvoices)
                            <div class="alert alert-warning mb-4">
                                ⚠️ No amount left for invoice(s):
                                <strong>{{ implode(', ', $unpayableInvoices) }}</strong>
                            </div>
                        @endif

                        {{-- Table --}}
                        <div class="table-responsive mb-3">
                            <table class="table align-middle table-row-bordered table-row-dashed fs-6 gy-5">
                                <thead class="text-start text-gray-400 fw-bold fs-7 text-uppercase">
                                    <tr>
                                        <th class="min-w-50px">#</th>
                                        <th class="min-w-150px">Invoice</th>
                                        <th class="text-end min-w-125px">Total (MVR)</th>
                                        <th class="text-end min-w-100px">Applied</th>
                                    </tr>
                                </thead>
                                <tbody class="fw-semibold text-gray-700">
                                    @foreach($paymentPreview as $i => $p)
                                        @php
                                            $inv = $p['invoice'];
                                            $balance = $inv->total_amount - $inv->paid_amount;
                                        @endphp
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="text-dark fw-bold">{{ $inv->number }}</span>
                                                    <span class="text-muted fs-8">
                                                        {{ $inv->directory->name ?? 'N/A' }} /
                                                        {{ $inv->property->name ?? 'N/A' }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <div class="fw-bold text-dark">{{ number_format($inv->total_amount, 2) }}</div>
                                                <div class="text-success fs-8">Paid: {{ number_format($inv->paid_amount, 2) }}</div>
                                                <div class="text-danger fs-8">Balance: {{ number_format($balance, 2) }}</div>
                                            </td>
                                            <td class="text-end fw-bold {{ $p['applied'] == 0 ? 'text-danger' : 'text-success' }}">
                                                {{ number_format($p['applied'], 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="bg-light text-dark fw-bold">
                                        <td colspan="3" class="text-end">Total to Apply:</td>
                                        <td class="text-end text-primary">
                                            {{ number_format(collect($paymentPreview)->sum('applied'), 2) }} MVR
                                        </td>
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
                $totalApplied = collect($paymentPreview)->sum('applied');
                $hasZeroApplied = collect($paymentPreview)->contains(fn($p) => $p['applied'] == 0);
            @endphp

            <div class="modal-footer">
                <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary ms-2"
                            wire:click="submitPayment"
                            @if ($paymentAmount < 0 || $totalApplied == 0 || $hasZeroApplied)
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
