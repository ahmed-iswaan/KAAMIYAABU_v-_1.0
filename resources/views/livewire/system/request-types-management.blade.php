<div class="content fs-6 d-flex flex-column flex-column-fluid" id="kt_content">
    @pushonce('styles')
    <style>
        .craft-filters .dropdown-menu.craft-filters__menu{max-width:400px;width:100%;max-height:70vh;overflow-y:auto;}
        @media (max-width: 600px){ .craft-filters .dropdown-menu.craft-filters__menu{position:fixed !important; top:70px !important; right:12px !important; left:12px !important; width:auto; } }
    </style>
    @endpushonce
    @push('scripts')
    <script>
    document.addEventListener('alpine:init', () => {
        // nothing needed here
    });
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.craft-filters').forEach(wrapper => {
            const btn = wrapper.querySelector('button');
            btn?.addEventListener('click', () => {
                setTimeout(()=>{
                    const panel = wrapper.querySelector('.craft-filters__menu');
                    if(!panel) return;
                    panel.style.visibility='hidden'; panel.style.display='block';
                    const rect = panel.getBoundingClientRect();
                    const vw = window.innerWidth; const vh = window.innerHeight;
                    let changed=false;
                    if(rect.right > vw){ panel.style.left='auto'; panel.style.right='0'; changed=true; }
                    if(rect.left < 0){ panel.style.left='0'; panel.style.right='auto'; changed=true; }
                    if(rect.bottom > vh){ panel.style.maxHeight = (vh - rect.top - 20)+ 'px'; changed=true; }
                    panel.style.visibility=''; if(!panel.classList.contains('show')) panel.style.display='';
                },10);
            });
        });
        window.addEventListener('resize', () => {
            document.querySelectorAll('.craft-filters__menu[style]')?.forEach(p=>{p.style.maxHeight='70vh';});
        });
    });
    </script>
    @endpush
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
            <div class="card craft-card">
                <div class="card-header border-0 pt-6 pb-0 craft-card__header">
                    <h3 class="card-title fw-bold mb-0">Request Types</h3>
                    <div class="ms-auto d-flex align-items-center gap-2">
                        <div class="dropdown craft-filters position-relative" x-data="{open:false}" @click.outside="open=false">
                            <button class="btn btn-sm btn-primary d-flex align-items-center gap-2" @click="open=!open" type="button">
                                <i class="ki-duotone ki-filter fs-2"></i><span>Manage</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-0 shadow w-400px show craft-filters__menu" x-show="open" x-transition.origin.top.right style="display:none;">
                                <div class="border rounded-3 overflow-hidden">
                                    <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center bg-light">
                                        <span class="fw-semibold">Add / Search</span>
                                        <button type="button" class="btn btn-sm btn-icon" @click="open=false"><i class="ki-duotone ki-cross fs-2"></i></button>
                                    </div>
                                    <div class="px-4 py-4 d-flex flex-column gap-4">
                                        <div class="d-flex flex-column gap-2">
                                            <label class="form-label fw-semibold mb-1">Search</label>
                                            <input type="text" wire:model.live.debounce.500ms="search" class="form-control form-control-sm form-control-solid" placeholder="Search request types" />
                                        </div>
                                        <div class="d-flex flex-column gap-2">
                                            <label class="form-label fw-semibold mb-1">New Request Type</label>
                                            <input type="text" class="form-control form-control-sm form-control-solid" placeholder="Name" wire:model.defer="name">
                                            <input type="text" class="form-control form-control-sm form-control-solid" placeholder="Description" wire:model.defer="description">
                                            <button type="button" class="btn btn-sm btn-primary mt-2" wire:click="create" wire:loading.attr="disabled">
                                                <i class="ki-duotone ki-plus fs-2"></i><span>Add</span>
                                                <span class="spinner-border spinner-border-sm ms-2" wire:loading wire:target="create"></span>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="px-4 py-3 border-top d-flex justify-content-end bg-light-subtle">
                                        <button type="button" class="btn btn-sm btn-light" @click="open=false">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body py-4 craft-card__body">
                    @error('name') <div class="alert alert-danger py-2 px-3 mb-4">{{ $message }}</div> @enderror
                    @error('description') <div class="alert alert-danger py-2 px-3 mb-4">{{ $message }}</div> @enderror
                    @error('editName') <div class="alert alert-danger py-2 px-3 mb-4">{{ $message }}</div> @enderror
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 craft-table">
                            <thead>
                                <tr class="text-gray-600 fw-semibold">
                                    <th class="min-w-200px">Name</th>
                                    <th class="min-w-250px">Description</th>
                                    <th class="min-w-100px">Status</th>
                                    <th class="text-end min-w-180px">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-700">
                                @forelse($types as $t)
                                    <tr @class(['table-active'=>$editId===$t->id])>
                                        <td class="fw-semibold">
                                            @if($editId === $t->id)
                                                <input type="text" class="form-control form-control-sm" wire:model.defer="editName" />
                                            @else
                                                <span class="text-gray-800 text-hover-primary">{{ $t->name }}</span>
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
                                            <span class="badge {{ $t->active ? 'badge-light-success' : 'badge-light-danger' }} fw-semibold px-3 py-2 fs-8">{{ $t->active ? 'Active' : 'Inactive' }}</span>
                                        </td>
                                        <td class="text-end">
                                            @if($editId === $t->id)
                                                <div class="d-flex flex-wrap gap-2 justify-content-end">
                                                    <button class="btn btn-sm btn-success" wire:click="update" wire:loading.attr="disabled">Save</button>
                                                    <button class="btn btn-sm btn-light" wire:click="cancelEdit" wire:loading.attr="disabled">Cancel</button>
                                                </div>
                                            @else
                                                <div class="d-flex flex-wrap gap-2 justify-content-end">
                                                    <button class="btn btn-sm btn-light-primary" wire:click="edit('{{ $t->id }}')">Edit</button>
                                                    <button class="btn btn-sm {{ $t->active ? 'btn-danger' : 'btn-success' }}" wire:click="toggle('{{ $t->id }}')" wire:loading.attr="disabled">{{ $t->active ? 'Deactivate' : 'Activate' }}</button>
                                                </div>
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
