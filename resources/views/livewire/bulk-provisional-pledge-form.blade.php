<div class="card shadow-sm">
    <div class="card-header">
        <h3 class="card-title">Bulk Provisional Pledge Entry</h3>
    </div>

    <div class="card-body">
        {{-- Read-only Target Info Box --}}
        <div class="card card-bordered bg-light mb-5">
            <div class="card-body py-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <div class="text-muted small">Target</div>
                        <div class="fw-bold text-gray-900 fs-5">{{ $directoryName ?: '—' }}</div>
                    </div>
                    <div class="text-end">
                        <div class="text-muted small">NID</div>
                        <div class="fw-bold text-gray-900 fs-5">{{ $directoryNid ?: '—' }}</div>
                    </div>
                </div>
                @error('directoryId')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <form wire:submit.prevent="save">
            @error('rows')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror

            <div class="row fw-semibold text-gray-600 mb-2">
                <div class="col-md-6">User</div>
                <div class="col-md-4">Status</div>
                <div class="col-md-2 text-end">&nbsp;</div>
            </div>

            <div class="d-flex flex-column gap-3">
                @foreach($rows as $i => $row)
                    <div class="row align-items-start" wire:key="bulk-prov-row-{{ $i }}">
                        <div class="col-md-6">
                            <select class="form-select form-select-solid" wire:model="rows.{{ $i }}.user_id">
                                <option value="">Select user...</option>
                                @foreach($userOptions as $u)
                                    <option value="{{ $u['id'] }}">{{ $u['label'] }}</option>
                                @endforeach
                            </select>
                            @error("rows.$i.user_id")
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <select class="form-select form-select-solid" wire:model="rows.{{ $i }}.status">
                                <option value="pending">Pending</option>
                                <option value="yes">Yes</option>
                                <option value="no">No</option>
                                <option value="neutral">Undecided</option>
                            </select>
                            @error("rows.$i.status")
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-2 text-end">
                            <button type="button" class="btn btn-light-danger" wire:click="removeRow({{ $i }})" @disabled(count($rows) === 1)>
                                Remove
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="d-flex justify-content-between align-items-center mt-6">
                <button type="button" class="btn btn-light-primary" wire:click="addRow">
                    Add Row
                </button>

                <button type="submit" class="btn btn-primary">
                    Save All
                </button>
            </div>
        </form>
    </div>
</div>
