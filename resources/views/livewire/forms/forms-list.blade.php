@section('title', 'Forms')
@push('styles')
<style>
    @font-face {font-family:'Faruma';src:url('/fonts/faruma.woff2') format('woff2'),url('/fonts/faruma.woff') format('woff');font-weight:400;font-style:normal;font-display:swap;}
    .faruma {font-family:'Faruma','Tahoma',sans-serif !important; text-align:left; letter-spacing:.5px;}
</style>
@endpush
<div class="content fs-6 d-flex flex-column flex-column-fluid" id="kt_content">
    <!-- Toolbar -->
    <div class="toolbar" id="kt_toolbar">
        <div class="container-fluid d-flex flex-stack flex-wrap flex-sm-nowrap">
            <div class="d-flex flex-column align-items-start justify-content-center flex-wrap me-2 mb-4 mb-sm-0">
                <h1 class="text-dark fw-bold my-1 fs-2">Forms</h1>
                <ul class="breadcrumb fw-semibold fs-base my-1">
                    <li class="breadcrumb-item text-muted"><a href="#" class="text-muted text-hover-primary">Operations</a></li>
                    <li class="breadcrumb-item text-dark">Forms</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('forms.create') }}" class="btn btn-primary"><i class="ki-duotone ki-plus fs-2 me-1"></i>New Form</a>
            </div>
        </div>
    </div>

    <!-- Post / Main Content -->
    <div class="post fs-6 d-flex flex-column-fluid" id="kt_post">
        <div class="container-xxl">

            @if (session('message'))
                <div class="alert alert-success d-flex align-items-center p-5 mb-5">
                    <i class="ki-duotone ki-check-circle fs-2hx text-success me-4"><span class="path1"></span><span class="path2"></span></i>
                    <div class="d-flex flex-column">
                        <span class="fw-bold">{{ session('message') }}</span>
                    </div>
                </div>
            @endif

            <!-- Filters Card -->
            <div class="card mb-7">
                <div class="card-body py-5">
                    <div class="row g-5 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Search Title</label>
                            <input type="text" class="form-control form-control-solid" placeholder="Search..." wire:model.debounce.500ms="search">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select form-select-solid" wire:model="status">
                                <option value="">All Status</option>
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Language</label>
                            <select class="form-select form-select-solid" wire:model="language">
                                <option value="">All</option>
                                <option value="en">English</option>
                                <option value="dv">Dhivehi</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex justify-content-md-end">
                            <a href="{{ route('forms.index') }}" class="btn btn-light me-2">Reset</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Forms Table -->
            <div class="card">
                <div class="card-header border-0 pt-6 pb-0">
                    <div class="card-title">
                        <h3 class="fw-bold mb-0 fs-5">Form Records</h3>
                    </div>
                </div>
                <div class="card-body pt-5">
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle gs-0 gy-4">
                            <thead>
                                <tr class="text-start text-gray-500 fw-semibold fs-7 text-uppercase">
                                    <th>Title</th>
                                    <th>Language</th>
                                    <th>Status</th>
                                    <th>Sections</th>
                                    <th>Questions</th>
                                    <th>Updated</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="fw-semibold text-gray-700">
                            @forelse($forms as $form)
                                <tr>
                                    <td class="{{ $form->language==='dv' ? 'faruma' : '' }}">{{ $form->title }}</td>
                                    <td>
                                        <span class="badge badge-light-secondary fw-normal">{{ strtoupper($form->language) }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $statusClass = match($form->status){
                                                'published' => 'badge-light-success',
                                                'archived' => 'badge-light-secondary',
                                                'draft' => 'badge-light-warning',
                                                default => 'badge-light'
                                            };
                                        @endphp
                                        <span class="badge {{ $statusClass }} fw-normal">{{ ucfirst($form->status) }}</span>
                                    </td>
                                    <td>{{ $form->sections()->count() }}</td>
                                    <td>{{ $form->questions()->count() }}</td>
                                    <td class="text-muted">{{ $form->updated_at->diffForHumans() }}</td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end flex-shrink-0 gap-1">
                                            <!-- View Responses -->
                                            <a href="{{ route('forms.responses',$form->id) }}" class="btn btn-sm btn-light-success" title="View Responses">
                                                <i class="ki-duotone ki-chart fs-2"><span class="path1"></span><span class="path2"></span></i>
                                            </a>
                                            <!-- Edit -->
                                            <a href="{{ route('forms.edit',$form->id) }}" class="btn btn-sm btn-light-primary" title="Edit">
                                                <i class="ki-duotone ki-pencil fs-2"><span class="path1"></span><span class="path2"></span></i>
                                            </a>
                                            <!-- Duplicate -->
                                            <button type="button" class="btn btn-sm btn-light" wire:click="duplicate({{ $form->id }})" title="Duplicate">
                                                <i class="ki-duotone ki-copy fs-2"><span class="path1"></span><span class="path2"></span></i>
                                            </button>
                                            <!-- Delete -->
                                            <button type="button" class="btn btn-sm btn-light-danger" wire:click="confirmDelete({{ $form->id }})" title="Delete">
                                                <i class="ki-duotone ki-trash fs-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                    <span class="path3"></span>
                                                    <span class="path4"></span>
                                                    <span class="path5"></span>
                                                </i>
                                            </button>
                                        </div>
                                        @if($confirmingDeleteId === $form->id)
                                            <div class="mt-2 d-inline-flex align-items-center gap-2">
                                                <span class="text-danger small">Confirm?</span>
                                                <button class="btn btn-icon btn-sm btn-danger" wire:click="deleteForm" title="Yes">
                                                    <i class="ki-duotone ki-check fs-2"><span class="path1"></span><span class="path2"></span></i>
                                                </button>
                                                <button class="btn btn-icon btn-sm btn-light" wire:click="cancelDelete" title="No">
                                                    <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>
                                                </button>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-10">
                                        <div class="text-muted">No forms found.</div>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end py-5">
                    {{ $forms->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
