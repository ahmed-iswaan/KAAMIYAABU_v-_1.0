<div class="container-fluid py-4">
    <div class="d-flex flex-wrap flex-stack mb-5">
        <div class="d-flex flex-column">
            <h1 class="d-flex align-items-center text-dark fw-bold my-1 fs-3">Voting Box Management</h1>
            <div class="text-muted">Add, edit and list voting boxes (including SubConsite mapping)</div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-primary" wire:click="create">Add New</button>
        </div>
    </div>

    <div class="row g-5">
        <div class="col-12 col-xl-5">
            <div class="card card-flush">
                <div class="card-header pt-5">
                    <div class="card-title">
                        <h3 class="fw-bold mb-0">Add / Edit</h3>
                    </div>
                </div>

                <div class="card-body">
                    @if($editingId === null)
                        <div class="text-muted">Click <b>Add New</b> or <b>Edit</b> from the list.</div>
                    @else
                        <div class="mb-4">
                            <label class="form-label">Box Name</label>
                            <input type="text" class="form-control form-control-solid" wire:model.defer="name" placeholder="e.g. 322 Hulhumale Dhekunu 1">
                            @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label">SubConsite</label>
                            <select class="form-select form-select-solid" wire:model.defer="sub_consite_id">
                                <option value="">-- None --</option>
                                @foreach($this->subConsites as $sc)
                                    <option value="{{ $sc->id }}">{{ $sc->code }} - {{ $sc->name }}</option>
                                @endforeach
                            </select>
                            @error('sub_consite_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary" wire:click="save">Save</button>
                            <button type="button" class="btn btn-light" wire:click="cancel">Cancel</button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="card card-flush">
                <div class="card-header pt-5">
                    <div class="card-title">
                        <h3 class="fw-bold mb-0">Voting Boxes</h3>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-8">
                            <input type="text" class="form-control form-control-solid" placeholder="Search box name..." wire:model.live.debounce.400ms="search">
                        </div>
                        <div class="col-4">
                            <select class="form-select form-select-solid" wire:model.live="perPage">
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle gs-0 gy-3">
                            <thead>
                            <tr class="fw-bold text-muted">
                                <th>Box</th>
                                <th>SubConsite</th>
                                <th class="text-end">Directories</th>
                                <th class="text-end">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($boxes as $b)
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-gray-900">{{ $b->name }}</div>
                                    </td>
                                    <td>
                                        @if($b->subConsite)
                                            <span class="badge badge-light-info">{{ $b->subConsite->code }}</span>
                                            <div class="text-muted fs-8">{{ $b->subConsite->name }}</div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <span class="badge badge-light-primary">{{ (int) ($b->directories_count ?? 0) }}</span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-light-primary" wire:click="edit('{{ $b->id }}')">Edit</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-10">No voting boxes found.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end">
                        {{ $boxes->links('vendor.pagination.new') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
