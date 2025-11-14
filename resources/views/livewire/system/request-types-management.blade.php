<div class="content fs-6 d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="toolbar" id="kt_toolbar">
        <div class="container-fluid d-flex flex-stack flex-wrap flex-sm-nowrap">
            <div class="d-flex flex-column align-items-start justify-content-center flex-wrap me-2">
                <h1 class="text-dark fw-bold my-1 fs-2">Request Types</h1>
                <ul class="breadcrumb fw-semibold fs-base my-1">
                    <li class="breadcrumb-item text-muted"><a href="#" class="text-muted text-hover-primary">System</a></li>
                    <li class="breadcrumb-item text-dark">Request Types</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="post fs-6 d-flex flex-column-fluid" id="kt_post">
        <div class="container-xxl">
            <div class="card">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            <input type="text" wire:model.live.debounce.500ms="search" class="form-control form-control-solid w-250px ps-13" placeholder="Search request types">
                        </div>
                    </div>
                    <div class="card-toolbar">
                        <div class="d-flex justify-content-end" data-kt-rt-table-toolbar="base">
                            <div class="d-flex align-items-center gap-2">
                                <input type="text" class="form-control form-control-sm" placeholder="Name" wire:model.defer="name">
                                <input type="text" class="form-control form-control-sm" placeholder="Description" wire:model.defer="description">
                                <button type="button" class="btn btn-sm btn-primary" wire:click="create" wire:loading.attr="disabled">
                                    <i class="ki-duotone ki-plus fs-2"></i>Add
                                    <span class="spinner-border spinner-border-sm ms-2" wire:loading wire:target="create"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body py-4">
                    @error('name') <div class="alert alert-danger py-2 px-3 mb-4">{{ $message }}</div> @enderror
                    @error('description') <div class="alert alert-danger py-2 px-3 mb-4">{{ $message }}</div> @enderror
                    @error('editName') <div class="alert alert-danger py-2 px-3 mb-4">{{ $message }}</div> @enderror
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th class="min-w-200px">Name</th>
                                    <th class="min-w-250px">Description</th>
                                    <th class="min-w-100px">Status</th>
                                    <th class="text-end min-w-150px">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 fw-semibold">
                                @forelse($types as $t)
                                    <tr @class(['table-active'=>$editId===$t->id])>
                                        <td>
                                            @if($editId === $t->id)
                                                <input type="text" class="form-control form-control-sm" wire:model.defer="editName" />
                                            @else
                                                <span class="text-gray-800 text-hover-primary fw-bold">{{ $t->name }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($editId === $t->id)
                                                <input type="text" class="form-control form-control-sm" wire:model.defer="editDescription" />
                                            @else
                                                <span class="text-muted">{{ $t->description ?: '—' }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $t->active ? 'badge-light-success' : 'badge-light-danger' }}">{{ $t->active ? 'Active' : 'Inactive' }}</span>
                                        </td>
                                        <td class="text-end">
                                            @if($editId === $t->id)
                                                <button class="btn btn-sm btn-success" wire:click="update" wire:loading.attr="disabled">Save</button>
                                                <button class="btn btn-sm btn-light" wire:click="cancelEdit" wire:loading.attr="disabled">Cancel</button>
                                            @else
                                                <button class="btn btn-sm btn-light-primary" wire:click="edit('{{ $t->id }}')">Edit</button>
                                                <button class="btn btn-sm {{ $t->active ? 'btn-danger' : 'btn-success' }}" wire:click="toggle('{{ $t->id }}')" wire:loading.attr="disabled">{{ $t->active ? 'Deactivate' : 'Activate' }}</button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-10">No request types found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-4">{{ $types->links('vendor.pagination.new') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
