<div>
    {{-- Create Invoice Modal (always in DOM) --}}
    <div class="modal fade" wire:ignore.self id="kt_modal_create_invoice" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <form wire:submit.prevent="save" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Invoice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    {{-- Header fields --}}
                    <div class="row gx-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Property</label>
                            <select wire:model.defer="invoice.property_id" class="form-select">
                                <option value="">— Choose —</option>
                                @foreach($properties as $id=>$name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('invoice.property_id') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Directory</label>
                            <select wire:model.defer="invoice.directories_id" class="form-select">
                                <option value="">— Choose —</option>
                                @foreach($directories as $id=>$name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('invoice.directories_id') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" wire:model.defer="invoice.date" class="form-control"/>
                            @error('invoice.date') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Due Date</label>
                            <input type="date" wire:model.defer="invoice.due_date" class="form-control"/>
                            @error('invoice.due_date') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Type / Status --}}
                    <div class="row gx-4 mb-4">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Type</label>
                            <select wire:model.defer="invoice.invoice_type" class="form-select">
                                @foreach($types as $t)
                                    <option value="{{ $t->value }}">{{ ucfirst($t->value) }}</option>
                                @endforeach
                            </select>
                            @error('invoice.invoice_type') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <select wire:model.defer="invoice.status" class="form-select">
                                @foreach($statuses as $s)
                                    <option value="{{ $s->value }}">{{ ucfirst($s->value) }}</option>
                                @endforeach
                            </select>
                            @error('invoice.status') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Fine Details Section --}}
                    <h5 class="mt-4">Fine Details</h5>
                    <div class="row gx-4 mb-4">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Fine Rate</label>
                            <input type="number" step="0.01" wire:model.defer="invoice.fine_rate" class="form-control"/>
                            @error('invoice.fine_rate') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Interval</label>
                            <select wire:model.defer="invoice.fine_interval" class="form-select">
                                @foreach($intervals as $i)
                                    <option value="{{ $i->value }}">{{ ucfirst($i->value) }}</option>
                                @endforeach
                            </select>
                            @error('invoice.fine_interval') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Grace Period</label>
                            <input type="number" wire:model.defer="invoice.fine_grace_period" class="form-control"/>
                            @error('invoice.fine_grace_period') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    {{-- End Fine Details Section --}}

                    {{-- Line Items --}}
                    <h5 class="mt-4">Invoice Lines</h5>
                    <div class="table-responsive">
                        <table class="table align-middle gs-0 gy-4">
                            <thead>
                                <tr class="fw-bold text-muted bg-light">
                                    <th class="ps-4 rounded-start">Category</th>
                                    <th>Description</th>
                                    <th>Qty</th>
                                    <th>Unit Price</th>
                                    <th class="text-center rounded-end"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lines as $idx => $line)
                                <tr>
                                    <td class="ps-4">
                                        <select wire:model.defer="lines.{{ $idx }}.category_id" class="form-select form-select-sm">
                                            <option value="">— none —</option>
                                            @foreach($categories as $catId=>$catName)
                                                <option value="{{ $catId }}">{{ $catName }}</option>
                                            @endforeach
                                        </select>
                                        @error("lines.{$idx}.category_id")<div class="text-danger fs-7">{{ $message }}</div>@enderror
                                    </td>
                                    <td>
                                        <input type="text" wire:model.defer="lines.{{ $idx }}.description" class="form-control form-control-sm"/>
                                        @error("lines.{$idx}.description")<div class="text-danger fs-7">{{ $message }}</div>@enderror
                                    </td>
                                    <td>
                                        <input type="number" wire:model.defer="lines.{{ $idx }}.quantity" min="1" class="form-control form-control-sm"/>
                                        @error("lines.{$idx}.quantity")<div class="text-danger fs-7">{{ $message }}</div>@enderror
                                    </td>
                                    <td>
                                        <input type="number" wire:model.defer="lines.{{ $idx }}.unit_price" step="0.01" class="form-control form-control-sm"/>
                                        @error("lines.{$idx}.unit_price")<div class="text-danger fs-7">{{ $message }}</div>@enderror
                                    </td>
                                    <td class="text-center">
                                        @if(!$loop->first) {{-- Only show remove button if it's not the first line --}}
                                        <button type="button" class="btn btn-sm btn-icon btn-light btn-active-light-danger" wire:click="removeLine({{ $idx }})">
                                            <i class="ki-duotone ki-trash fs-5 m-0">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                                <span class="path3"></span>
                                                <span class="path4"></span>
                                                <span class="path5"></span>
                                            </i>
                                        </button>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" wire:click="addLine">
                        <i class="ki-duotone ki-plus fs-2"></i> Add Line
                    </button>

                    {{-- Messages --}}
                    <h5 class="mt-4">Messages</h5>
                    <div class="mb-3">
                        <label class="form-label">Message on Statement</label>
                        <textarea wire:model.defer="invoice.message_on_statement" class="form-control" rows="2"></textarea>
                        @error('invoice.message_on_statement') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message to Customer</label>
                        <textarea wire:model.defer="invoice.message_to_customer" class="form-control" rows="3"></textarea>
                        @error('invoice.message_to_customer') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    {{-- End Messages --}}

                </div> {{-- End modal-body --}}
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Invoice</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function waitForLivewire(cb) {
            if (window.Livewire) return cb();
            setTimeout(() => waitForLivewire(cb), 200);
        }
        waitForLivewire(() => {
            const modalEl = document.getElementById('kt_modal_create_invoice');
            if (!modalEl) return;
            const bsModal = new bootstrap.Modal(modalEl);

            Livewire.on('showInvoiceModal',  () => bsModal.show());
            Livewire.on('closeInvoiceModal', () => bsModal.hide());
        });
    </script>
    @endpush
</div>
