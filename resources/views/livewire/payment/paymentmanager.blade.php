@section('title', $pageTitle)

<!--begin::Content-->
	<div class="content fs-6 d-flex flex-column flex-column-fluid" id="kt_content">
		<!--begin::Toolbar-->
			<div class="toolbar" id="kt_toolbar">
				<div class="container-fluid d-flex flex-stack flex-wrap flex-sm-nowrap">
					<!--begin::Info-->
						<div class="d-flex flex-column align-items-start justify-content-center flex-wrap me-2">
							<!--begin::Title-->
								<h1 class="text-dark fw-bold my-1 fs-2">{{$pageTitle}}
								<small class="text-muted fs-6 fw-normal ms-1"></small></h1>
								<!--end::Title-->
                         <ul class="breadcrumb fw-semibold fs-base my-1">
							<li class="breadcrumb-item text-muted">
								<a href="#" class="text-muted text-hover-primary">Payments Management</a>
							</li>
							<li class="breadcrumb-item text-dark">Payments</li>
							</ul>
						</div>
						<!--end::Info-->

					</div>
				</div>
			<!--end::Toolbar-->

            <div class="post fs-6 d-flex flex-column-fluid" id="kt_post">
                <!--begin::Container-->
                <div class="container-xxl">
                    <!--begin::Card-->
                    <div class="card">
                        <!--begin::Card header-->
                        <div class="card-header border-0 pt-6">
                            <!--begin::Card title-->
                            <div class="card-title">
                                <!--begin::Search-->
                                <div class="d-flex align-items-center position-relative my-1">
                                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <input type="text" wire:model.live.debounce.500ms="search" class="form-control form-control-solid w-250px ps-13" placeholder="Search by name or ref...">
                                </div>
                                <!--end::Search-->
                            </div>
                            <!--begin::Card title-->
                            <!--begin::Card toolbar-->
                            <div class="card-toolbar">
                                <!--begin::Toolbar-->
                                <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                                    <!--begin::Export-->
                                    <button type="button" class="btn btn-light-primary me-3" data-bs-toggle="modal" data-bs-target="#kt_modal_export_users">
                                    <i class="ki-duotone ki-exit-up fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>Export</button>
                                    <!--end::Export-->
                                </div>
                                <!--end::Toolbar-->
                                <!--begin::Group actions-->
                                <div class="d-flex justify-content-end align-items-center d-none" data-kt-user-table-toolbar="selected">
                                    <div class="fw-bold me-5">
                                    <span class="me-2" data-kt-user-table-select="selected_count"></span>Selected</div>
                                    <button type="button" class="btn btn-danger" data-kt-user-table-select="delete_selected">Delete Selected</button>
                                </div>
                                <!--end::Group actions-->

      

                            </div>
                            <!--end::Card toolbar-->
                        </div>
                        <!--end::Card header-->
                        <!--begin::Card body-->
                        <div class="card-body py-4">
                          <!--begin::Table-->
                          <div id="kt_table_directories_wrapper" class="dataTables_wrapper dt-bootstrap4 no-footer">
                            <div class="table-responsive">
                              <table class="table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer" id="kt_table_directories">
                                <thead>
                                  <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th>Payment #</th>
                                        <th>Date</th>
                                        <th>Payer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Payment Method</th>
                                        <th class="text-end">Actions</th>
                                  </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                                  @forelse ($payments as $payment)
                            <tr>
                                <td>{{ $payment->number }}</td>
                                <td>{{ $payment->date->format('Y-m-d') }}</td>
                                <td class="d-flex align-items-center">
                                        @if($payment->directory?->profile_picture)
                                          <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                                            <a href="#">
                                              <div class="symbol-label">
                                                <img src="{{ asset('storage/' . $payment->directory?->profile_picture) }}"
                                                    alt="{{ $payment->directory?->name }}" class="w-100">
                                              </div>
                                            </a>
                                          </div>
                                        @else
                                          <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                                            <div class="symbol-label fs-3 bg-light-warning text-warning">
                                              {{ Str::substr($payment->directory?->name, 0, 1) }}
                                            </div>
                                          </div>
                                        @endif
                                        <div class="d-flex flex-column">
                                          <span class="text-gray-800 text-hover-primary mb-1">
                                           @if($payment->directory?->type->name === 'Individual')
                                                @if(strtolower($payment->directory?->gender) === 'male')
                                                    <i class="bi bi-gender-male fs-5 text-primary"></i>
                                                @elseif(strtolower($payment->directory?->gender) === 'female')
                                                    <i class="bi bi-gender-female fs-5" style="color: #FF69B4;"></i>
                                                @else
                                                    <i class="bi bi-question-circle fs-5 text-muted"></i>
                                                @endif

                                            @elseif($payment->directory?->type->name === 'Company')
                                                <i class="bi bi-building fs-5 text-secondary"></i>

                                            @else
                                                <i class="bi bi-person-fill fs-5 text-gray-600"></i>
                                            @endif


                                             {{ ucwords(strtolower($payment->directory?->name)) }}</span>
                                          <small class="text-muted">{{ optional($payment->directory?->registrationType)->name }} : {{ $payment->directory?->registration_number }}</small>
                                        </div>
                                      </td>
                                                                          @php
                                        $badgeClass = match(strtolower($payment->status)) {
                                            'approved' => 'success',
                                            'pending'  => 'warning',
                                            'cancelled' => 'danger',
                                            default    => 'secondary',
                                        };
                                    @endphp
                                <td class="text-{{ $badgeClass }}">MVR {{ number_format($payment->amount, 2) }}</td>
                                <td>

                                    <span class="badge badge-{{ $badgeClass }}">
                                        {{ ucfirst($payment->status) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
										<span class="icon-wrapper">
                                         @if($payment->method == 'cash')
										<i class="ki-duotone ki-bill fs-2 text-primary me-4">
										<span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                        <span class="path5"></span>
                                        <span class="path6"></span>
										</i>
                                        @elseif($payment->method == 'card')
                                        <i class="ki-duotone ki-two-credit-cart fs-2 text-primary me-4">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                        <span class="path5"></span>
                                        </i>
                                        @else
                                        <i class="ki-duotone ki-bank fs-2 text-primary me-4">
										<span class="path1"></span>
                                        <span class="path2"></span>
										</i>
                                        @endif
										</span>
									{{ ucfirst($payment->method) }}
                                    								</div>
                                    <small class="text-muted">{{ $payment->ref }}</small>

                                </td>
                                <td class="text-end">
                                    <a href="#" wire:click="viewPayment('{{ $payment->id }}')" class="btn btn-sm btn-light btn-active-light-primary">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No payments found.</td>
                            </tr>
                        @endforelse
                                </tbody>
                              </table>
                            </div>

                          <div class="row">
                            <div class="col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start">
                              <!-- You can add per-page selector here -->
                            </div>
                            <div class="col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end">
                              {{ $payments->links('vendor.pagination.new') }}
                            </div>
                          </div>
                        </div>
                        <!--end::Table-->
                      </div>

                        <!--end::Card body-->
                    </div>
                    <!--end::Card-->
                </div>
                <!--end::Container-->
            </div>
    @include('livewire.payment.payment-view')
   </div>


