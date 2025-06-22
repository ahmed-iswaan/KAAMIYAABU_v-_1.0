@section('title', $pageTitle)
@push('styles')
<link
  rel="stylesheet"
  href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
/>
@endpush
<!--begin::Content-->
<div class="content fs-6 d-flex flex-column flex-column-fluid" id="kt_content">
    <!--begin::Toolbar-->
    <div class="toolbar" id="kt_toolbar">
        <div class="container-fluid d-flex flex-stack flex-wrap flex-sm-nowrap">
            <!--begin::Info-->
            <div class="d-flex flex-column align-items-start justify-content-center flex-wrap me-2">
                <!--begin::Title-->
                <h1 class="text-dark fw-bold my-1 fs-2">
                    {{ $pageTitle }}
                </h1>
                <!--end::Title-->
                <ul class="breadcrumb fw-semibold fs-base my-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="#" class="text-muted text-hover-primary">Property Management</a>
                    </li>
                    <li class="breadcrumb-item text-dark">Properties</li>
                </ul>
            </div>
            <!--end::Info-->
        </div>
    </div>
    <!--end::Toolbar-->

    <!--begin::Post-->
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
                            <input
                                type="text"
                                wire:model.live.debounce.500ms="search"
                                class="form-control form-control-solid w-250px ps-13"
                                placeholder="Search Properties by Name or Reg. No." />
                        </div>
                        <!--end::Search-->
                    </div>
                    <!--end::Card title-->
                    <!--begin::Card toolbar-->
                    <div class="card-toolbar">
                        <div class="d-flex justify-content-end" data-kt-property-table-toolbar="base">
                            <button
                                type="button"
                                class="btn btn-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#kt_modal_add_property">
                                <i class="ki-duotone ki-plus fs-2"></i>Add Property
                            </button>
                        </div>
                    </div>
                    <!--end::Card toolbar-->
                </div>
                <!--end::Card header-->

                 @include('livewire.property.property-add')

                <!--begin::Card body-->
                <div class="card-body py-4">
                    <!--begin::Table-->
                    <div id="kt_table_properties_wrapper" class="dataTables_wrapper dt-bootstrap4 no-footer">
                        <div class="table-responsive">
                            <table
                                class="table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer"
                                id="kt_table_properties">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-125px">Name</th>
                                         <th class="min-w-125px">No</th>
                                        <th class="min-w-100px">Type</th>
                                        <th class="min-w-100px">Area (sq ft)</th>
                                        <th class="min-w-125px">Island</th>
                                        <th class="text-end min-w-100px sorting_disabled" rowspan="1" colspan="1" aria-label="Actions" style="width: 132.484px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @foreach($properties as $property)
                                        <tr>
                                          <td>
                                            <div class="d-flex align-items-center">
                                                <!--begin::Thumbnail-->
                                                <a href="{{ route('properties.view', $property->id) }}" class="symbol symbol-50px">
                                                    <span class="symbol-label">
                                                    <span class="badge badge-light">
                                                      @if($property->propertyType->name === 'Residential')
                                                       <i class="ki-duotone ki-home fs-1">
                                                       </i> 
                                                      @elseif($property->propertyType->name === 'Guest Houses')
                                                             <i class="ki-duotone ki-frame fs-1 text-success">
                                                              <span class="path1"></span>
                                                              <span class="path2"></span>
                                                              <span class="path3"></span>
                                                              <span class="path4"></span>
                                                          </i>
                                                      @else
                                                      @endif
                                                    </span></span>
                                                </a>
                                                <!--end::Thumbnail-->

                                                <div class="ms-5">
                                                    <!--begin::Title-->
                                                    <a href="{{ route('properties.view', $property->id) }}" class="text-gray-800 text-hover-primary fs-5 fw-bold" data-kt-ecommerce-product-filter="product_name">{{ $property->name }}</a>
                                                     <div class="fw-semibold fs-7 text-muted">{{ $property->register_number }}</div>
                                                    <!--end::Title-->
                                                </div>
                                            </div>
                                        </td>
                                            <td>
                                              
                                                <a href="#" class="text-gray-800 text-hover-primary mb-1">{{ $property->number }}</a>
                                            </td>
                                          
                                          <td>
                                          
                                              @if($property->propertyType->name === 'Residential')
                                                  <span class="badge badge-light">
                                                      <i class="ki-duotone ki-home fs-6 me-2">
                                                          </i> {{ $property->propertyType->name }}
                                                  </span>

                                              @elseif($property->propertyType->name === 'Guest Houses')
                                                  <span class="badge bg-light-success text-success rounded-pill">
                                                      <i class="ki-duotone ki-shop fs-6 text-success me-2">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                            <span class="path3"></span>
                                                            <span class="path4"></span>
                                                            <span class="path5"></span>
                                                          </i> {{ $property->propertyType->name }}
                                                  </span>
                                             @elseif($property->propertyType->name === 'Agricultural')
                                                  <span class="badge badge-light-success">
                                                      <i class="ki-duotone ki-tree fs-6 text-success me-2">
                                                          <span class="path1"></span>
                                                          <span class="path2"></span>
                                                          <span class="path3"></span>
                                                          </i> {{ $property->propertyType->name }}
                                                  </span>
                                              @elseif($property->propertyType->name === 'Commercial')
                                                  <span class="badge bg-light-info text-info rounded-pill">
                                                      <i class="ki-duotone ki-bank text-info fs-6 me-2">
                                                          <span class="path1"></span>
                                                          <span class="path2"></span>
                                                      </i> {{ $property->propertyType->name }}
                                                  </span>

                                              @elseif($property->propertyType->name === 'Industrial')
                                                  <span class="badge bg-light-danger text-danger rounded-pill">
                                                      <i class="ki ki-factory fs-5 me-1"></i> {{ $property->propertyType->name }}
                                                  </span>

                                              @else
                                                  <span class="badge bg-light-secondary text-secondary rounded-pill">
                                                      <i class="ki ki-question fs-5 me-1"></i> {{ $property->propertyType->name }}
                                                  </span>
                                              @endif
                                          </td>


                                            <td>{{ number_format($property->square_feet, 2) }}</td>
                                            <td>
                                              {{ optional($property->island->atoll)->code }}. {{ optional($property->island)->name }}
                                                @if($property->wards)
                                                    / {{ $property->wards->name }}
                                                @endif
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
                                                   wire:click="openEditModal({{ $property->id }})">
                                                    Edit
                                                 </a>
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

                        <!--begin::Pagination-->
                        <div class="row">
                            <div class="col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start">
                                <!-- Optional: per-page selector -->
                            </div>
                            <div class="col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end">
                                {{ $properties->links('vendor.pagination.new') }}
                            </div>
                        </div>
                        <!--end::Pagination-->
                    </div>
                    <!--end::Table-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
        <!--end::Container-->
    </div>
    <!--end::Post-->
</div>
<!--end::Content-->

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endpush


<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('kt_modal_add_property');
        const bsModal = new bootstrap.Modal(modalEl);

        window.addEventListener('showAddPropertyModal', () => bsModal.show());
        window.addEventListener('closeAddPropertyModal', () => bsModal.hide());
    });
</script>