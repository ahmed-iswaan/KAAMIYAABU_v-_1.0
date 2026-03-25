<div class="card shadow-sm">
    <div class="card-header">
        <h3 class="card-title">Import Provisional Pledges (CSV)</h3>
    </div>

    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div class="text-muted">
                Upload a CSV with columns: <code>NID</code>, <code>Pledge</code>, <code>user_id</code>.
                (In <code>user_id</code> you can put the user's <strong>email</strong> or numeric id.)
                Allowed pledge values: <code>yes</code>, <code>no</code>, <code>neutral</code>, <code>pending</code>.
            </div>
            <div class="flex-grow-0">
                <a class="btn btn-light-primary" href="{{ asset('templates/provisional_pledges_import_sample.csv') }}" download>
                    Download Sample CSV
                </a>
            </div>
        </div>

        <div class="row g-3 align-items-end">
            <div class="col-md-8">
                <input type="file" class="form-control" wire:model="importFile" accept=".csv" />
                @error('importFile')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4">
                <button type="button" class="btn btn-primary w-100" wire:click="import" wire:loading.attr="disabled" wire:target="import,importFile">
                    <span wire:loading.remove wire:target="import">Import</span>
                    <span wire:loading wire:target="import">Importing...</span>
                </button>
            </div>
        </div>

        @if(!empty($importErrors))
            <div class="alert alert-danger mt-4">
                <div class="fw-semibold mb-2">Import errors:</div>
                <ul class="mb-0">
                    @foreach($importErrors as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(($importedCount ?? 0) > 0)
            <div class="alert alert-success mt-4">
                Imported {{ $importedCount }} rows.
            </div>
        @endif
    </div>
</div>
