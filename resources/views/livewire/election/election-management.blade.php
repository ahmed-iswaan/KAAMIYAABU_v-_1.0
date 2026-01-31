@section('title','Elections')
<div class="content fs-6 d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="toolbar" id="kt_toolbar">
        <div class="container-fluid d-flex flex-stack flex-wrap flex-sm-nowrap">
            <div class="d-flex flex-column align-items-start justify-content-center flex-wrap me-2 mb-4 mb-sm-0">
                <h1 class="text-dark fw-bold my-1 fs-2">Elections</h1>
                <ul class="breadcrumb fw-semibold fs-base my-1">
                    <li class="breadcrumb-item text-muted"><a href="#" class="text-muted text-hover-primary">Election</a></li>
                    <li class="breadcrumb-item text-dark">Manage</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-primary" wire:click="openCreate">Add Election</button>
            </div>
        </div>
    </div>

    <div class="post fs-6 d-flex flex-column-fluid" id="kt_post">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h3 class="fw-bold mb-0">Election List</h3>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7">
                            <thead>
                                <tr class="text-gray-600 fw-semibold">
                                    <th>Name</th>
                                    <th>Start</th>
                                    <th>End</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($elections as $e)
                                    @php
                                        $badge = match($e->status){
                                            'Active' => 'badge-light-success',
                                            'Completed' => 'badge-light-dark',
                                            default => 'badge-light-warning'
                                        };
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold">{{ $e->name }}</td>
                                        <td>{{ optional($e->start_date)->format('Y-m-d') }}</td>
                                        <td>{{ optional($e->end_date)->format('Y-m-d') }}</td>
                                        <td><span class="badge {{ $badge }}">{{ $e->status }}</span></td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-light" wire:click="openEdit('{{ $e->id }}')">Edit</button>
                                                @if($e->status !== 'Active')
                                                    <button type="button" class="btn btn-light-success" wire:click="setActive('{{ $e->id }}')">Activate</button>
                                                @else
                                                    <button type="button" class="btn btn-light-warning" wire:click="setInactive('{{ $e->id }}')">Deactivate</button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-10">No elections found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="text-muted small mt-3">
                        Note: Only one election can be <span class="fw-semibold">Active</span> at a time. Activating an election will automatically deactivate others.
                    </div>
                </div>
            </div>

            @if($showModal)
                <div class="modal fade show" tabindex="-1" style="display:block; background: rgba(0,0,0,.5);" role="dialog" aria-modal="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">{{ $mode === 'edit' ? 'Edit Election' : 'Add Election' }}</h5>
                                <button type="button" class="btn-close" aria-label="Close" wire:click="closeModal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Name</label>
                                    <input type="text" class="form-control" wire:model.defer="name" />
                                    @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <label class="form-label fw-semibold">Start Date</label>
                                        <input type="date" class="form-control" wire:model.defer="startDate" />
                                        @error('startDate')<div class="text-danger small">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-semibold">End Date</label>
                                        <input type="date" class="form-control" wire:model.defer="endDate" />
                                        @error('endDate')<div class="text-danger small">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <label class="form-label fw-semibold">Status</label>
                                    <select class="form-select" wire:model.defer="status">
                                        <option value="Upcoming">Upcoming</option>
                                        <option value="Active">Active</option>
                                        <option value="Completed">Completed</option>
                                    </select>
                                    @error('status')<div class="text-danger small">{{ $message }}</div>@enderror
                                    <div class="text-muted small mt-2">If you set status to Active, other elections will be set to Upcoming automatically.</div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" wire:click="closeModal">Cancel</button>
                                <button type="button" class="btn btn-primary" wire:click="save">Save</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
