<div>
    <div class="modal fade" wire:ignore.self id="kt_modal_payment_view" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content shadow-lg border-0">
              <div class="modal-header">
                                               @if($selectedPayment)  
                <h2 class="modal-title">Payment Details </h2>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"></i>
                    </div>
                </div>

                                    <div class="d-flex flex-column flex-xl-row gap-7 gap-lg-10">
										<!--begin::Order details-->
										<div class="card card-flush py-4 flex-row-fluid">
											<!--begin::Card body-->
											<div class="card-body pt-0">
												<div class="table-responsive">
													<!--begin::Table-->
													<table class="table align-middle table-row-bordered mb-0 fs-6 gy-5 min-w-300px">
														<tbody class="fw-semibold text-gray-600">
                                                            <tr>
																<td class="text-muted">
																	<div class="d-flex align-items-center">
																	<i class="ki-duotone ki-devices fs-2 me-2">
																		<span class="path1"></span>
																		<span class="path2"></span>
																		<span class="path3"></span>
																		<span class="path4"></span>
																		<span class="path5"></span>
																	</i>Number</div>
																</td>
																<td class="fw-bold text-end"> {{ $selectedPayment->number }} </td>
															</tr>
                                                            <tr>
																<td class="text-muted">
																	<div class="d-flex align-items-center">
																	<i class="ki-duotone ki-profile-circle fs-2 me-2">
																		<span class="path1"></span>
																		<span class="path2"></span>
																		<span class="path3"></span>
																	</i>Payer</div>
																</td>
																<td class="fw-bold text-end">
																	<div class="d-flex align-items-center justify-content-end">
                                                                        @if($selectedPayment->directory->profile_picture)
                                                                        <div class="symbol symbol-circle symbol-25px overflow-hidden me-3">
                                                                            <a href="#">
                                                                            <div class="symbol-label">
                                                                                <img src="{{ asset('storage/' . $selectedPayment->directory->profile_picture) }}"
                                                                                    alt="{{ $selectedPayment->directory->name }}" class="w-100">
                                                                            </div>
                                                                            </a>
                                                                        </div>
                                                                        @else
                                                                        <div class="symbol symbol-circle symbol-25px overflow-hidden me-3">
                                                                            <div class="symbol-label fs-3 bg-light-warning text-warning">
                                                                            {{ Str::substr($selectedPayment->directory->name, 0, 1) }}
                                                                            </div>
                                                                        </div>
                                                                        @endif
																		<!--end::Avatar-->
																		<!--begin::Name-->
                                                                         {{ ucwords(strtolower($selectedPayment->directory->name)) }}
																		<!--end::Name-->
																	</div>
																</td>
															</tr>
															<tr>
																<td class="text-muted">
																	<div class="d-flex align-items-center">
																	<i class="ki-duotone ki-calendar-add fs-2 me-2">
															            <span class="path1"></span>
                                                                        <span class="path2"></span>
                                                                        <span class="path3"></span>
                                                                        <span class="path4"></span>
                                                                        <span class="path5"></span>
                                                                        <span class="path6"></span>
																	</i>Payment Date</div>
																</td>
																<td class="fw-bold text-end">{{ $selectedPayment->date->format('d M Y') }}</td>
															</tr>
                                                            @if($selectedPayment->ref)
                                                            <tr>
																<td class="text-muted">
																	<div class="d-flex align-items-center">
																	<i class="ki-duotone ki-text-number fs-2 me-2">
																	    <span class="path1"></span>
                                                                        <span class="path2"></span>
                                                                        <span class="path3"></span>
                                                                        <span class="path4"></span>
                                                                        <span class="path5"></span>
                                                                        <span class="path6"></span>
																	</i>Reference</div>
																</td>
																<td class="fw-bold text-end">{{ $selectedPayment->ref ?? 'N/A' }}</td>
															</tr>
                                                            @endif
                                                            @if($selectedPayment->bank)
                                                            <tr>
																<td class="text-muted">
																	<div class="d-flex align-items-center">
																	<i class="ki-duotone ki-bank fs-2 me-2">
																		<span class="path1"></span>
																		<span class="path2"></span>
																	</i>Bank</div>
																</td>
																<td class="fw-bold text-end">{{ $selectedPayment->bank ?? 'N/A' }}
                                                                @if($selectedPayment->bank == 'BML')
																<img src="assets/media/svg/card-logos/bml.svg" class="w-50px ms-2">
                                                                @elseif($selectedPayment->bank == 'MIB')
                                                                <img src="assets/media/svg/card-logos/mib.svg" class="w-50px ms-2">
                                                                @else
                                                                @endif

                                                                </td>
                                                                
															</tr>
                                                             @endif
                                                            <tr>
																<td class="text-muted">
																	<div class="d-flex align-items-center">
																	<i class="ki-duotone ki-status fs-2 me-2">
																		<span class="path1"></span>
																		<span class="path2"></span>
                                                                        <span class="path3"></span>
																	</i>Status</div>
																</td>
																<td class="fw-bold text-end">
                                                                                                 <span class="badge
                                            {{ $selectedPayment->status === 'Approved' ? 'badge-light-success' : 
                                               ($selectedPayment->status === 'Pending' ? 'badge-light-warning ' : 'badge-light-danger') }}">
                                            {{ ucfirst($selectedPayment->status) }}
                                        </span>
                                                                </td>
															</tr>
 
															<tr>
																<td class="text-muted">
																	<div class="d-flex align-items-center">
																	<i class="ki-duotone ki-wallet fs-2 me-2">
																		<span class="path1"></span>
																		<span class="path2"></span>
																		<span class="path3"></span>
																		<span class="path4"></span>
																	</i>Payment Method</div>
																</td>
																<td class="fw-bold text-end">{{ ucfirst($selectedPayment->method) }}</td>
															</tr>
                                                            <tr>
																<td class="text-muted">
																	<div class="d-flex align-items-center">
																	<i class="ki-duotone ki-calendar fs-2 me-2">
																		<span class="path1"></span>
																		<span class="path2"></span>
																	</i>Created At</div>
																</td>
																<td class="fw-bold text-end">{{ ucfirst($selectedPayment->created_at) }}</td>
															</tr>
                                                            <tr>
																<td class="text-muted">
																	<div class="d-flex align-items-center">
																	<i class="ki-duotone ki-calendar fs-2 me-2">
																		<span class="path1"></span>
																		<span class="path2"></span>
																	</i>Updated At</div>
																</td>
																<td class="fw-bold text-end">{{ ucfirst($selectedPayment->updated_at) }}</td>
															</tr>
                                                            <tr>
																<td class="text-muted">
																	<div class="d-flex align-items-center">
																	<i class="ki-duotone ki-graph-up fs-2 me-2">
                                                                        <span class="path1"></span>
                                                                        <span class="path2"></span>
                                                                        <span class="path3"></span>
                                                                        <span class="path4"></span>
                                                                        <span class="path5"></span>
                                                                        <span class="path6"></span>
																	</i>Overpaid Amount</div>
																</td>
																<td class="fw-bold text-end text-success">MVR {{ number_format($selectedPayment->overpaid_amount, 2) }}</td>
															</tr>
                                                            <tr>
																<td class="text-muted">
																	<div class="d-flex align-items-center">
																	<i class="ki-duotone ki-credit-cart fs-2 me-2">
                                                                        <span class="path1"></span>
                                                                        <span class="path2"></span>
																	</i>Credit Used</div>
																</td>
																<td class="fw-bold text-end text-success">MVR {{ number_format($selectedPayment->credit_used, 2) }}</td>
															</tr>
															<tr>
																<td class="text-muted">
																	<div class="d-flex align-items-center">
																	<i class="ki-duotone ki-bill fs-2 me-2">
                                                                        <span class="path1"></span>
                                                                        <span class="path2"></span>
                                                                        <span class="path3"></span>
                                                                        <span class="path4"></span>
                                                                        <span class="path5"></span>
                                                                        <span class="path6"></span>
																	</i>Total Amount Paid</div>
																</td>
																<td class="fw-bold text-end text-success">MVR {{ number_format($selectedPayment->amount, 2) }}</td>
															</tr>
														</tbody>
													</table>
													<!--end::Table-->
												</div>
											</div>
											<!--end::Card body-->
										</div>
										<!--end::Order details-->
	
									</div>
                                    
                                @else
                                    <p class="text-center">No payment selected.</p>
                                @endif
                <div class="modal-body bg-white">
                    @if($selectedPayment)  
                        <div class="p-5">

                            <h5 class="fw-bold mb-3">Invoices Covered</h5>
                            <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                                <thead >
                                    <tr>
                                        <th>Invoice Number</th>
                                        <th class="text-end">Applied Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($selectedPayment->invoices as $invoice)
                                        <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                            <td>{{ $invoice->number }}</td>
                                            <td class="text-end">MVR {{ number_format($invoice->pivot->applied_amount, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                            <td colspan="2" class="text-center text-muted">No invoices applied.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        
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

                        </div>
                    @else
                        <p class="text-center">No payment selected.</p>
                    @endif
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    @if($selectedPayment && $selectedPayment->payment_slip)
                        <a target="_blank" class="btn btn-primary" href="{{ asset('storage/'.$selectedPayment->payment_slip) }}">
                            Download Slip
                        </a>
                    @endif
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
