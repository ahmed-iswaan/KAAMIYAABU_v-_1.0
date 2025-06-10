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
								<a href="#" class="text-muted text-hover-primary">Role Management</a>
							</li>
							<li class="breadcrumb-item text-dark">{{$pageTitle}}</li>
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
                                    <input type="text" wire:model.live.debounce.500ms="search" class="form-control form-control-solid w-250px ps-13" placeholder="Search roles, details, or permissions...">
                                </div>
                                <!--end::Search-->
                            </div>
                            <!--begin::Card title-->
                            <!--begin::Card toolbar-->
                            <div class="card-toolbar">
                                <!--begin::Toolbar-->
                                <div class="d-flex justify-content-end" data-kt-role-table-toolbar="base">
                                    <!--begin::Export-->
                                    <button type="button" class="btn btn-light-primary me-3" data-bs-toggle="modal" data-bs-target="#kt_modal_export_roles">
                                    <i class="ki-duotone ki-exit-up fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>Export</button>
                                    <!--end::Export-->
                                    <!--begin::Add role-->
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_roles">
                                    <i class="ki-duotone ki-plus fs-2"></i>Add New</button>
                                    <!--end::Add role-->
                                </div>
                                <!--end::Toolbar-->
                                <!--begin::Group actions-->
                                <div class="d-flex justify-content-end align-items-center d-none" data-kt-role-table-toolbar="selected">
                                    <div class="fw-bold me-5">
                                    <span class="me-2" data-kt-role-table-select="selected_count"></span>Selected</div>
                                    <button type="button" class="btn btn-danger" data-kt-role-table-select="delete_selected">Delete Selected</button>
                                </div>
                                <!--end::Group actions-->

                                @include('livewire.roles.roles-export')
                                @include('livewire.roles.roles-add')
                                @include('livewire.roles.roles-edit')
                                @include('livewire.roles.roles-remove')

                            </div>
                            <!--end::Card toolbar-->
                        </div>
                        <!--end::Card header-->
                        <!--begin::Card body-->
                        <div class="card-body py-4">
                            <!--begin::Table-->
                            <div id="kt_table_roles_wrapper" class="dataTables_wrapper dt-bootstrap4 no-footer"><div class="table-responsive"><table class="table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer" id="kt_table_roles">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-125px sorting" tabindex="0" aria-controls="kt_table_roles" rowspan="1" colspan="1" aria-label="role: activate to sort column ascending" style="width: 278.312px;">Roles</th>
                                        <th class="min-w-125px sorting" tabindex="0" aria-controls="kt_table_roles" rowspan="1" colspan="1" aria-label="Role: activate to sort column ascending" style="width: 161.828px;">Details</th>
                                        <th class="min-w-125px sorting" tabindex="0" aria-controls="kt_table_roles" rowspan="1" colspan="1" aria-label="Joined Date: activate to sort column ascending" style="width: 210.328px;">Created Date</th>
                                        <th class="text-end min-w-100px sorting_disabled" rowspan="1" colspan="1" aria-label="Actions" style="width: 132.484px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @foreach($roles as $role)
                                    <tr class="even">

                                        <td>

                                            {{ $role->name }}

                                        </td>
                                        <td>
                                            <div class="badge badge-light fw-bold">
                                                {{ $role->details }}

                                            </div>
                                        </td>
                                        <td data-order="2023-12-20T20:43:00+05:00">{{ \Carbon\Carbon::parse($role->created_at)->format('d M Y, g:i a') }}</td>
                                        <td class="text-end">
                                            <a href="#" class="btn btn-light btn-active-light-primary btn-flex btn-center btn-sm" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions
                                            <i class="ki-duotone ki-down fs-5 ms-1"></i></a>
                                            <!--begin::Menu-->
                                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">
                                                <!--begin::Menu item-->
                                                <div class="menu-item px-3">
                                                    <a href="#"
                                                    class="menu-link px-3"
                                                    wire:click="editRole({{ $role->id }})">
                                                    Edit
                                                 </a>
                                                </div>
                                                <!--end::Menu item-->

                                                    <!--begin::Menu item-->
                                                        <div class="menu-item px-3">
                                                            <a href="#" class="menu-link px-3" wire:click="removeRole({{ $role->id }})" >Remove</a>
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
                            </div>
                            <div class="col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end">
                                {{ $roles->links('vendor.pagination.new') }}
                            </div></div></div>
                            <!--end::Table-->
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Card-->
                </div>
                <!--end::Container-->
            </div>
 </div>
