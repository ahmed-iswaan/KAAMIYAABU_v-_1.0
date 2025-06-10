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
								<a href="#" class="text-muted text-hover-primary">Directory Management</a>
							</li>
							<li class="breadcrumb-item text-dark">Directory</li>
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
                                    <input type="text" wire:model.live.debounce.500ms="search" class="form-control form-control-solid w-250px ps-13" placeholder="Search Users by Name, Email, or Employee ID">
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
                                    <!--begin::Add user-->
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_user">
                                    <i class="ki-duotone ki-plus fs-2"></i>Add</button>
                                    <!--end::Add user-->
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
                                    <th class="min-w-125px">Name</th>
                                    <th class="min-w-125px">Type</th>
                                    <th class="min-w-125px">Reg. No.</th>
                                    <th class="min-w-125px">Contact</th>
                                    <th class="min-w-125px">Location</th>
                                    <th class="text-end min-w-100px">Actions</th>
                                  </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                  @foreach($directory as $entry)
                                    <tr>
                                      <td class="d-flex align-items-center">
                                        @if($entry->profile_picture)
                                          <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                                            <a href="#">
                                              <div class="symbol-label">
                                                <img src="{{ asset('storage/'.$entry->profile_picture) }}"
                                                    alt="{{ $entry->name }}" class="w-100">
                                              </div>
                                            </a>
                                          </div>
                                        @else
                                          <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                                            <div class="symbol-label fs-3 bg-light-warning text-warning">
                                              {{ Str::substr($entry->name, 0, 1) }}
                                            </div>
                                          </div>
                                        @endif
                                        <div class="d-flex flex-column">
                                          <span class="text-gray-800 text-hover-primary mb-1">{{ $entry->name }}</span>
                                          <small class="text-muted">{{ $entry->description }}</small>
                                        </div>
                                      </td>

                                      <td>{{ $entry->type->name }}</td>
                                      <td>{{ $entry->registration_number ?? '' }}</td>
                                      <td>
                                        <div>{{ $entry->contact_person ?? '' }}</div>
                                        <div>{{ $entry->phone ?? '' }}</div>
                                      </td>
                                      <td>
                                        {{ optional($entry->country)->name ?? '' }}
                                        -
                                        {{ optional($entry->island)->name ?? '' }}
                                      </td>
                                   <td class="text-end">
                                            <a href="#" class="btn btn-light btn-active-light-primary btn-flex btn-center btn-sm" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions
                                            <i class="ki-duotone ki-down fs-5 ms-1"></i></a>
                                            <!--begin::Menu-->
                                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">
                                                <!--begin::Menu item-->
                                                <div class="menu-item px-3">
                                                    <a href="#"
                                                    class="menu-link px-3"
                                                    wire:click="editUser({{ $entry->id }})">
                                                    Edit
                                                 </a>
                                                </div>
                                                <!--end::Menu item-->
                                         
                                                    <!--begin::Menu item-->
                                                        <div class="menu-item px-3">
                                                            <a href="#" class="menu-link px-3" wire:click="removeRole({{ $entry->id }})" >Remove Role</a>
                                                        </div>
                                                   <!--end::Menu item-->
                                              

                                            </div>
                                            <!--end::Menu-->
                                        </td>
                                    </tr>
                                  @endforeach
                                </tbody>
                              </table>
                            </div>

    <div class="row">
      <div class="col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start">
        <!-- You can add per-page selector here -->
      </div>
      <div class="col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end">
        {{ $directory->links('vendor.pagination.new') }}
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

   </div>