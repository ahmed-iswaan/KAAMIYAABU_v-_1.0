<div class="content fs-6 d-flex flex-column flex-column-fluid" id="kt_content">
    <!-- Toolbar -->
    <div class="toolbar" id="kt_toolbar">
        <div class="container-fluid d-flex flex-stack flex-wrap flex-sm-nowrap">
            <div class="d-flex flex-column align-items-start justify-content-center flex-wrap me-2">
                <h1 class="text-dark fw-bold my-1 fs-2">Waste Management</h1>
                <ul class="breadcrumb fw-semibold fs-base my-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="/waste" class="text-muted text-hover-primary">Waste Management</a>
                    </li>
                    <li class="breadcrumb-item text-dark">Listing</li>
                </ul>
            </div>
            <div class="d-flex align-items-center flex-nowrap text-nowrap py-1">
                <button type="button" class="btn btn-light-primary me-3" data-bs-toggle="modal" data-bs-target="#kt_customers_export_modal">
                    <i class="ki-duotone ki-exit-up fs-2"></i>Export
                </button>
                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_register" id="kt_toolbar_primary_button">Register New</a>
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    @include('livewire.waste.register-form')

    <!-- Post -->
    <div class="post fs-6 d-flex flex-column-fluid" id="kt_post">
        <div class="container-xxl">
            <!-- Card -->
            <div class="card">
                <!-- Card Header -->
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5"></i>
                            <input type="text"
                                   wire:model.debounce.500ms.live="search"
                                   class="form-control form-control-solid w-250px ps-13"
                                   placeholder="Search Application Number or Name" />
                        </div>
                    </div>
                    <div class="card-toolbar">
                        <div class="d-flex justify-content-end" data-kt-customer-table-toolbar="base">
                            <div class="w-150px me-3">
                                <select class="form-select form-select-solid"
                                        wire:model.live="statusFilter"
                                        data-hide-search="true"
                                        data-placeholder="Status">
                                    <option value="">Select Status</option>
                                    <option value="all">All</option>
									<option value="pending">Pending</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
									<option value="terminated">Terminated</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="card-body pt-0">
					<!-- Loading Message -->
				<div wire:loading wire:target="search, statusFilter" class="table-loading-message text-center py-4">
					Loading...
				</div>

                  <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_customers_table">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                <th class="min-w-125px">Application Number</th>
                                <th class="min-w-125px">Applicant</th>
                                <th class="min-w-125px">Property/Land</th>
                                <th class="min-w-125px">Status</th>
                                <th class="min-w-125px">Created Date</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-600">
                            @forelse ($registrations as $registration)
                                <tr>
                                    <td>
                                        <a href="#" class="text-gray-800 text-hover-primary mb-1">
                                            {{ $registration->number}}
                                        </a>
										<div class="fw-semibold fs-7 text-muted">{{ $registration->register_number }}</div>
                                    </td>
									<td class="d-flex align-items-center">
										  @if($registration->directory->profile_picture)
                                          <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                                            <a href="#">
                                              <div class="symbol-label">
                                                <img src="{{ asset('storage/'.$registration->directory->profile_picture) }}"
                                                    alt="{{ $registration->directory->name }}" class="w-100">
                                              </div>
                                            </a>
                                          </div>
                                        @else
                                          <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                                            <div class="symbol-label fs-3 bg-light-warning text-warning">
                                              {{ Str::substr($registration->directory->name, 0, 1) }}
                                            </div>
                                          </div>
                                        @endif
                                        <div class="d-flex flex-column">
                                          <span class="text-gray-800 text-hover-primary mb-1">
                                           @if($registration->directory->type->name === 'Individual')
                                                @if(strtolower($registration->directory->gender) === 'male')
                                                    <i class="bi bi-gender-male fs-5 text-primary"></i>
                                                @elseif(strtolower($registration->directory->gender) === 'female')
                                                    <i class="bi bi-gender-female fs-5" style="color: #FF69B4;"></i>
                                                @else
                                                    <i class="bi bi-question-circle fs-5 text-muted"></i>
                                                @endif

                                            @elseif($registration->directory->type->name === 'Company')
                                                <i class="bi bi-building fs-5 text-secondary"></i>

                                            @else
                                                <i class="bi bi-person-fill fs-5 text-gray-600"></i>
                                            @endif


                                             {{ ucwords(strtolower($registration->directory->name)) }}</span>
                                          <small class="text-muted">{{ optional($registration->directory->registrationType)->name }} : {{ $registration->directory->registration_number }}</small>
                                        </div>
									</td>
                                    <td>
                                        <a href="#" class="text-gray-600 text-hover-primary mb-1">
                                            {{ $registration->property->name ?? '-' }}
                                        </a>
										<div class="fw-semibold fs-7 text-muted">
											{{ optional($registration->property->island->atoll)->code }}. {{ optional($registration->property->island)->name }}
										</div>
                                    </td>
                                    <td>
                                        <div class="badge badge-light-{{ $registration->status == 'active' ? 'success' : 'danger' }}">
                                            {{ ucfirst($registration->status) }}
                                        </div>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($registration->created_at)->format('d M Y, h:i a') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No waste registrations found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
</div>
     				                        <div class="row">
                            <div class="col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start">
                            </div>
                            <div class="col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end">
                                {{ $registrations->links('vendor.pagination.new') }}
                            </div></div></div>
                            <!--end::Table-->
                        </div>
                </div>

            </div>
        </div>
    </div>
</div>
