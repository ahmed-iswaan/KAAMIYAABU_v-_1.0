<div>
    @section('title', $pageTitle)
    @include('livewire.directory.add-directory-modal')
    @include('livewire.directory.directory-edit')
    
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
                            <div class="d-flex align-items-center gap-2 mt-2">
                                <span class="badge badge-light-success">Active: {{ $totalActive }}</span>
                                <span class="badge badge-light-danger">Inactive: {{ $totalInactive }}</span>
                            </div>
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
                                    <input type="text" wire:model.live.debounce.500ms="search" class="form-control form-control-solid w-250px ps-13" placeholder="Search by Name, Email or ID Card">
                                </div>
                                <!--end::Search-->
                            </div>
                            <!--begin::Card title-->
                            <!--begin::Card toolbar-->
                            <div class="card-toolbar d-flex align-items-center gap-3">
                                <!-- SubConsite Filter -->
                                <div class="w-250px">
                                    <select class="form-select form-select-solid" wire:model.live="filter_sub_consite_id">
                                        <option value="">All SubConsite</option>
                                        @foreach($consites as $c)
                                            @if($c->subConsites && $c->subConsites->count())
                                                <optgroup label="{{ $c->name }}">
                                                    @foreach($c->subConsites as $sub)
                                                        <option value="{{ $sub->id }}">{{ $sub->code }} - {{ $sub->name }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <!-- Gender Filter -->
                                <div class="w-200px">
                                    <select class="form-select form-select-solid" wire:model.live="filter_gender">
                                        <option value="">All Genders</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other / Unspecified</option>
                                    </select>
                                </div>
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
                                    <button type="button" class="btn btn-primary" wire:click.prevent="openAddModal">
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
                                    <th class="min-w-200px">Name</th>
                                    <th class="min-w-120px">ID Card</th>
                                    <th class="min-w-150px">Phones</th>
                                    <th class="min-w-100px">Party / SubConsite</th>
                                    <th class="min-w-200px">Permanent Location</th>
                                    <th class="min-w-200px">Current Location</th>
                                    <th class="min-w-80px">Status</th>
                                    <th class="text-end min-w-100px">Actions</th>
                                  </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                  @foreach($directory as $entry)
                                    <tr>
                                      <td class="d-flex align-items-center">
                                        @php
                                            $imgUrl = null;
                                            if (!empty($entry->profile_picture)) {
                                                $imgUrl = asset('storage/' . ltrim($entry->profile_picture, '/'));
                                            } else {
                                                $nid = trim((string) ($entry->id_card_number ?? ''));
                                                if ($nid !== '') {
                                                    foreach (['jpg','jpeg','png','webp'] as $__ext) {
                                                        $__rel = "nid-images/{$nid}.{$__ext}";
                                                        if (is_file(public_path($__rel))) { $imgUrl = asset($__rel); break; }
                                                    }
                                                }
                                            }
                                        @endphp

                                        @if($imgUrl)
                                          <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                                            <a href="#">
                                              <div class="symbol-label">
                                                <img src="{{ $imgUrl }}" alt="{{ $entry->name }}" class="w-100">
                                              </div>
                                            </a>
                                          </div>
                                        @else
                                          <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                                            <div class="symbol-label fs-3 bg-light-primary text-primary">
                                              {{ Str::substr($entry->name, 0, 1) }}
                                            </div>
                                          </div>
                                        @endif
                                        <div class="d-flex flex-column">
                                          <span class="text-gray-800 text-hover-primary mb-1">{{ ucwords(strtolower($entry->name)) }}</span>
                                          <small class="text-muted">Gender: {{ $entry->gender ?? 'N/A' }} | DOB: {{ $entry->date_of_birth ?? 'N/A' }}</small>
                                        </div>
                                      </td>
                                      <td>{{ $entry->id_card_number ?? '—' }}</td>
                                      <td>
                                        @if(is_array($entry->phones))
                                            @foreach($entry->phones as $p)
                                                <div><i class="ki-duotone ki-call fs-6 me-1"></i>{{ $p }}</div>
                                            @endforeach
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                        <div class="mt-1 small text-muted">Email: {{ $entry->email ?? '—' }}</div>
                                      </td>
                                      <td>
                                        @php $party = $entry->party; @endphp
                                        <div class="d-flex align-items-center">
                                            @if($party && $party->logo)
                                                <div class="symbol symbol-circle symbol-30px overflow-hidden me-2">
                                                    <img src="{{ asset('storage/' . $party->logo) }}" alt="{{ $party->short_name ?? $party->name }}" class="w-30px" />
                                                </div>
                                            @elseif($party)
                                                <div class="symbol symbol-circle symbol-30px bg-light fw-bold text-uppercase me-2">{{ Str::substr($party->short_name ?? $party->name,0,2) }}</div>
                                            @endif
                                            <div>
                                                <div>{{ $party->short_name ?? $party->name ?? '' }}</div>
                                                <small class="text-muted">{{ optional($entry->subConsite)->code ?? '' }}  </small>
                                            </div>
                                        </div>
                                      </td>
                                      <td>
                                        <div>{{ optional($entry->property)->name }}</div>
                                        <div>{{ $entry->street_address ?? '' }} {{ $entry->address ? ' / '.$entry->address : '' }}</div>
                                        <div class="fw-semibold fs-7 text-muted">{{ $entry->island?->atoll?->code }}. {{ $entry->island?->name }}, {{ $entry->country?->name }}</div>
                                      </td>
                                      <td>
                                        <div>{{ optional($entry->currentProperty)->name }}</div>
                                        <div>{{ $entry->current_street_address ?? '' }} {{ $entry->current_address ? ' / '.$entry->current_address : '' }}</div>
                                        <div class="fw-semibold fs-7 text-muted">{{ $entry->currentIsland?->atoll?->code }}. {{ $entry->currentIsland?->name }}, {{ $entry->currentCountry?->name }}</div>
                                      </td>
                                      <td><div class="badge {{ $entry->status==='Active' ? 'badge-light-success' : 'badge-light-danger' }} fw-bold">{{ $entry->status }}</div></td>
                                      <td class="text-end position-relative" >
                                            <a href="#" class="btn btn-light btn-active-light-primary btn-flex btn-center btn-sm" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions
                                            <i class="ki-duotone ki-down fs-5 ms-1"></i></a>
                                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">
                                                <div class="menu-item px-3">
                                                    <a href="#"
                                                    class="menu-link px-3"
                                                    wire:click.prevent="openEditModal('{{ $entry->id }}')">
                                                    Edit
                                                 </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                  @endforeach
                                </tbody>
                              </table>
                            </div>

                          <div class="row">
                            <div class="col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start">
                              <!-- per-page selector placeholder -->
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

   </div><!-- close content wrapper -->

   @stack('scripts')
</div><!-- close livewire root -->


