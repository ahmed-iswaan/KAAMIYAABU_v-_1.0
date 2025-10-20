@section('title', $formId ? 'Edit Form' : 'Create Form')
@push('styles')
<style>
    @font-face {
        font-family: 'Faruma';
        src: url('/fonts/Faruma.ttf') format('truetype'); /* fixed path & format to existing font */
        font-weight: 400; font-style: normal; font-display: swap;
    }
    /* Enhanced Dhivehi (dv) handling */
    .dv-mode { direction: rtl; }
    .dv-mode .dv-input, .dv-mode .dv-text { font-family: 'Faruma','Tahoma',sans-serif !important; text-align: right; }
    .dv-mode input.form-control, .dv-mode textarea.form-control, .dv-mode select.form-select { direction: rtl; text-align: right; }
    .dv-mode .dv-input::placeholder { text-align: right; }
    .dv-mode .badge, .dv-mode .btn, .dv-mode .nav-link { font-family: inherit; }
</style>
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    window.addEventListener('swal', e => {
        const { type='success', title='', text='' } = e.detail || {};
        Swal.fire({icon: type, title: title, text: text, confirmButtonColor: '#0d6efd'});
    });
</script>
@endpush
<div class="content fs-6 d-flex flex-column flex-column-fluid {{ in_array(strtolower($language),['dv','dhivehi','dv-mv','mv']) ? 'dv-mode' : '' }}" id="kt_content" x-data="{tab: 'builder'}" wire:key="form-builder" dir="{{ in_array(strtolower($language),['dv','dhivehi','dv-mv','mv']) ? 'rtl' : 'ltr' }}">
    <!-- Toolbar -->
    <div class="toolbar" id="kt_toolbar">
        <div class="container-fluid d-flex flex-stack flex-wrap flex-sm-nowrap">
            <div class="d-flex flex-column align-items-start justify-content-center flex-wrap me-2 mb-4 mb-sm-0">
                <h1 class="text-dark fw-bold my-1 fs-2">Form Builder</h1>
                <ul class="breadcrumb fw-semibold fs-base my-1">
                    <li class="breadcrumb-item text-muted"><a href="#" class="text-muted text-hover-primary">Operations</a></li>
                    <li class="breadcrumb-item text-muted"><a href="{{ route('forms.index') }}" class="text-muted text-hover-primary">Forms</a></li>
                    <li class="breadcrumb-item text-dark">{{ $formId ? 'Edit' : 'Create' }}</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('forms.index') }}" class="btn btn-light me-2">All Forms</a>
                <select wire:model="language" class="form-select form-select-sm form-select-solid w-125px">
                    <option value="en">English</option>
                    <option value="dv">Dhivehi</option>
                </select>
                <button wire:click="save" class="btn btn-primary"><i class="ki-duotone ki-save-2 fs-2 me-1"></i>Save</button>
            </div>
        </div>
    </div>

    <!-- Post -->
    <div class="post fs-6 d-flex flex-column-fluid" id="kt_post">
        <div class="container-xxl">

            <!-- Tabs Card -->
            <div class="card mb-7">
                <div class="card-header border-0 pt-6 pb-0 flex-wrap">
                    <!-- Updated nav: removed flex-nowrap overflow-auto to prevent scrollbar, allow wrapping -->
                    <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x fs-6 flex-wrap gap-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a href="#" @click.prevent="tab='builder'" :class="tab==='builder' ? 'nav-link fw-semibold active' : 'nav-link fw-semibold'">Builder</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="#" @click.prevent="tab='preview'" :class="tab==='preview' ? 'nav-link fw-semibold active' : 'nav-link fw-semibold'">Preview</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="#" @click.prevent="tab='settings'" :class="tab==='settings' ? 'nav-link fw-semibold active' : 'nav-link fw-semibold'">Settings</a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Builder Content -->
            <div class="row g-7" x-show="tab==='builder'" x-cloak>
                <!-- Left Column: Meta + Sections List -->
                <div class="col-lg-3">
                    <div class="card mb-7">
                        <div class="card-header border-0 pt-6 pb-0">
                            <h3 class="card-title fw-bold fs-6 mb-0">Form Details</h3>
                        </div>
                        <div class="card-body pt-5">
                            <div class="mb-5">
                                <label class="form-label required">Title</label>
                                <input type="text" class="form-control form-control-solid {{ $language==='dv' ? 'dv-input' : '' }}" wire:model="title">
                                @error('title')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-5">
                                <label class="form-label">Description</label>
                                <textarea class="form-control form-control-solid {{ $language==='dv' ? 'dv-input' : '' }}" rows="3" wire:model="description"></textarea>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Status</label>
                                <select class="form-select form-select-solid" wire:model="status">
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card" x-data="{open:true}">
                        <div class="card-header border-0 pt-6 pb-0 d-flex justify-content-between align-items-center">
                            <h3 class="card-title fw-bold fs-6 mb-0">Sections</h3>
                            <div class="d-flex align-items-center gap-2">
                                <button class="btn btn-sm btn-light-primary d-flex align-items-center" wire:click="addSection" title="Add Section">
                                    <i class="ki-duotone ki-plus fs-2"><span class="path1"></span><span class="path2"></span></i>
                                    <span class="ms-1 d-none d-md-inline">Add</span>
                                </button>
                            </div>
                        </div>
                        <div class="card-body pt-5">
                            <!-- Removed fixed max-height/scroll to avoid nested scrollbars -->
                            <div class="d-flex flex-column">
                                @forelse($sections as $idx=>$section)
                                    <div class="border border-dashed rounded px-4 py-3 mb-3 bg-light d-flex flex-column position-relative">
                                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                            <span class="fw-semibold text-gray-800 flex-grow-1 fs-7 text-truncate dv-text" title="{{ $section['title'] ?: 'Untitled Section' }}">{{ $section['title'] ?: 'Untitled Section' }}</span>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-icon btn-light" title="Add Question" wire:click="addQuestion({{ $idx }})">
                                                    <i class="ki-duotone ki-plus fs-2"><span class="path1"></span><span class="path2"></span></i>
                                                    <span class="visually-hidden">Add Question</span>
                                                </button>
                                                <button type="button" class="btn btn-icon btn-light-danger" title="Remove Section" wire:click="removeSection({{ $idx }})">
                                                    <i class="ki-duotone ki-trash fs-2">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                        <span class="path3"></span>
                                                        <span class="path4"></span>
                                                        <span class="path5"></span>
                                                    </i>
                                                    <span class="visually-hidden">Remove Section</span>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="text-muted fs-8">Questions: {{ collect($questions)->filter(fn($q)=> (($q['section_index'] ?? null)===$idx) || (($q['section_id'] ?? null)===($section['id'] ?? null)) )->count() }}</div>
                                    </div>
                                @empty
                                    <div class="text-muted small">No sections yet.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Middle Column: Questions Builder -->
                <div class="col-lg-6">
                    @foreach($sections as $sIdx=>$section)
                        <div class="card mb-7 shadow-sm" x-data="{collapsed:false}">
                            <div class="card-header border-0 pt-6 pb-0 flex-wrap justify-content-between align-items-start gap-4">
                                <div class="flex-grow-1 me-3">
                                    <input type="text" class="form-control form-control-solid mb-2 {{ $language==='dv' ? 'dv-input' : '' }}" placeholder="Section title" wire:model="sections.{{ $sIdx }}.title">
                                    <textarea class="form-control form-control-solid {{ $language==='dv' ? 'dv-input' : '' }}" rows="2" placeholder="Section description" wire:model="sections.{{ $sIdx }}.description"></textarea>
                                </div>
                                <div class="d-flex flex-column align-items-end gap-2 mt-0">
                                    <div class="btn-toolbar" role="toolbar">
                                        <div class="btn-group me-2 mb-2" role="group">
                                            <button class="btn btn-sm btn-light" wire:click="addQuestion({{ $sIdx }}, 'short_text')" title="Short Text">Short</button>
                                            <button class="btn btn-sm btn-light" wire:click="addQuestion({{ $sIdx }}, 'long_text')" title="Long Text">Long</button>
                                            <button class="btn btn-sm btn-light" wire:click="addQuestion({{ $sIdx }}, 'number')" title="Number">#</button>
                                        </div>
                                        <div class="btn-group mb-2" role="group">
                                            <button class="btn btn-sm btn-light" wire:click="addQuestion({{ $sIdx }}, 'select')" title="Select">Select</button>
                                            <button class="btn btn-sm btn-light" wire:click="addQuestion({{ $sIdx }}, 'checkbox')" title="Checkbox">Checkbox</button>
                                            <button class="btn btn-sm btn-light" wire:click="addQuestion({{ $sIdx }}, 'radio')" title="Radio">Radio</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-5">
                                <div class="d-flex flex-column gap-5">
                                    @php $qRendered = 0; @endphp
                                    @foreach($questions as $uid=>$q)
                                        @if(($q['section_index'] ?? null) === $sIdx || ($q['section_id'] ?? null) === ($section['id'] ?? null))
                                            @php $qRendered++; @endphp
                                            <div class="border rounded p-5 bg-light position-relative" x-data="{open:true}">
                                                <div class="position-absolute top-0 end-0 mt-2 me-2 d-flex gap-1">
                                                    <button class="btn btn-icon btn-sm btn-light" type="button" title="Toggle Required" wire:click="toggleRequired('{{ $uid }}')">
                                                        @if($q['is_required'])<i class="ki-duotone ki-check-circle fs-2 text-success"></i>@else<i class="ki-duotone ki-circle fs-2 text-gray-500"></i>@endif
                                                    </button>
                                                    <button class="btn btn-icon btn-sm btn-light-danger" title="Delete Question" wire:click="deleteQuestion('{{ $uid }}')">
                                                        <i class="ki-duotone ki-trash fs-2">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                            <span class="path3"></span>
                                                            <span class="path4"></span>
                                                            <span class="path5"></span>
                                                        </i>
                                                        <span class="visually-hidden">Delete Question</span>
                                                    </button>
                                                </div>
                                                <div class="mb-4 pe-10">
                                                    <span class="badge badge-light-primary text-uppercase me-2">{{ $q['type'] }}</span>
                                                    @if($q['is_required'])<span class="badge badge-light-danger">Required</span>@endif
                                                </div>
                                                <input type="text" class="form-control form-control-solid mb-3 {{ $language==='dv' ? 'dv-input' : '' }}" placeholder="Question" wire:model="questions.{{ $uid }}.question_text">
                                                <textarea class="form-control form-control-solid mb-3 {{ $language==='dv' ? 'dv-input' : '' }}" rows="2" placeholder="Help text" wire:model="questions.{{ $uid }}.help_text"></textarea>

                                                @if(in_array($q['type'],['select','multiselect','radio','checkbox']))
                                                    <div class="border rounded p-4 bg-white mb-3">
                                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                                            <strong class="fs-7">Options</strong>
                                                            <button class="btn btn-sm btn-light-primary" wire:click="addOption('{{ $uid }}')">
                                                                <i class="ki-duotone ki-plus fs-2 me-1"><span class="path1"></span><span class="path2"></span></i>Add
                                                            </button>
                                                        </div>
                                                        <div class="d-flex flex-column gap-3">
                                                            @foreach($q['options'] as $optIdx=>$opt)
                                                                <div class="d-flex align-items-center gap-3">
                                                                    <input type="text" class="form-control form-control-solid {{ $language==='dv' ? 'dv-input' : '' }}" wire:model="questions.{{ $uid }}.options.{{ $optIdx }}.label" placeholder="Label">
                                                                    <input type="text" class="form-control form-control-solid {{ $language==='dv' ? 'dv-input' : '' }}" wire:model="questions.{{ $uid }}.options.{{ $optIdx }}.value" placeholder="Value">
                                                                    <button class="btn btn-icon btn-sm btn-light-danger" wire:click="removeOption('{{ $uid }}', {{ $optIdx }})" title="Remove Option">
                                                                        <i class="ki-duotone ki-trash fs-2">
                                                                            <span class="path1"></span>
                                                                            <span class="path2"></span>
                                                                            <span class="path3"></span>
                                                                            <span class="path4"></span>
                                                                            <span class="path5"></span>
                                                                        </i>
                                                                        <span class="visually-hidden">Remove Option</span>
                                                                    </button>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    @endforeach
                                    @if($qRendered === 0)
                                        <div class="text-muted fs-7">No questions yet.</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Right Column: Preview -->
                <div class="col-lg-3">
                    <div class="card" x-data="{open:true}">
                        <div class="card-header border-0 pt-6 pb-0">
                            <h3 class="card-title fw-bold fs-6 mb-0">Quick Preview</h3>
                        </div>
                        <div class="card-body pt-5">
                            <div class="mb-5">
                                <div class="fw-bold mb-1 {{ $language==='dv' ? 'dv-text' : '' }}">{{ $title }}</div>
                                <div class="text-muted fs-8 {{ $language==='dv' ? 'dv-text' : '' }}">{{ $description }}</div>
                            </div>
                            @foreach($sections as $sIdx=>$section)
                                <div class="mb-5">
                                    <div class="fw-semibold text-gray-800 {{ $language==='dv' ? 'dv-text' : '' }}">{{ $section['title'] }}</div>
                                    <div class="text-muted fs-8 mb-2 {{ $language==='dv' ? 'dv-text' : '' }}">{{ $section['description'] }}</div>
                                    <ol class="fs-8 ps-4 mb-0 {{ $language==='dv' ? 'dv-text' : '' }}">
                                        @foreach($questions as $uid=>$q)
                                            @if(($q['section_index'] ?? null) === $sIdx || ($q['section_id'] ?? null) === ($section['id'] ?? null))
                                                <li class="mb-1 {{ $language==='dv' ? 'dv-text' : '' }}">{{ $q['question_text'] }} @if($q['is_required'])<span class="text-danger">*</span>@endif</li>
                                            @endif
                                        @endforeach
                                    </ol>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview Tab -->
            <div x-show="tab==='preview'" x-cloak>
                <div class="card mb-7">
                    <div class="card-header border-0 pt-6 pb-0">
                        <h3 class="card-title fw-bold fs-6 mb-0">Full Preview</h3>
                    </div>
                    <div class="card-body pt-5">
                        <div class="mb-10">
                            <h3 class="fw-bold mb-3 {{ $language==='dv' ? 'dv-text' : '' }}">{{ $title }}</h3>
                            <p class="text-muted mb-0 {{ $language==='dv' ? 'dv-text' : '' }}">{{ $description }}</p>
                        </div>
                        <div class="d-flex flex-column gap-10">
                            @foreach($sections as $sIdx=>$section)
                                <div class="border rounded p-6 bg-light-subtle">
                                    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-4">
                                        <div>
                                            <h5 class="fw-semibold mb-1 {{ $language==='dv' ? 'dv-text' : '' }}">{{ $section['title'] }}</h5>
                                            <div class="text-muted fs-8 {{ $language==='dv' ? 'dv-text' : '' }}">{{ $section['description'] }}</div>
                                        </div>
                                        <span class="badge badge-light">Questions: {{ collect($questions)->filter(fn($q)=> (($q['section_index'] ?? null)===$sIdx) || (($q['section_id'] ?? null)===($section['id'] ?? null)) )->count() }}</span>
                                    </div>
                                    <div class="d-flex flex-column gap-5">
                                        @foreach($questions as $uid=>$q)
                                            @if(($q['section_index'] ?? null) === $sIdx || ($q['section_id'] ?? null) === ($section['id'] ?? null))
                                                <div class="p-4 bg-white rounded border">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div class="fw-semibold {{ $language==='dv' ? 'dv-text' : '' }}">{{ $q['question_text'] }} @if($q['is_required'])<span class="text-danger">*</span>@endif</div>
                                                        <span class="badge badge-light-primary text-uppercase">{{ $q['type'] }}</span>
                                                    </div>
                                                    @if(in_array($q['type'],['select','multiselect','radio','checkbox']))
                                                        <ul class="text-muted fs-8 mb-0 ps-4">
                                                            @foreach($q['options'] as $opt)
                                                                <li class="{{ $language==='dv' ? 'dv-text' : '' }}">{{ $opt['label'] }} <code class="text-gray-500">({{ $opt['value'] }})</code></li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <div class="text-muted fs-8 fst-italic {{ $language==='dv' ? 'dv-text' : '' }}">{{ $q['help_text'] }}</div>
                                                    @endif
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Tab -->
            <div x-show="tab==='settings'" x-cloak>
                <div class="card">
                    <div class="card-header border-0 pt-6 pb-0">
                        <h3 class="card-title fw-bold fs-6 mb-0">Settings</h3>
                    </div>
                    <div class="card-body pt-5">
                        <div class="row g-7">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Submission Limit</label>
                                <input type="number" class="form-control form-control-solid" placeholder="Unlimited" disabled>
                                <div class="text-muted fs-8 mt-2">(Coming soon)</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Allow Edit After Submit</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" disabled>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Thank You Message</label>
                                <textarea class="form-control form-control-solid" rows="3" placeholder="Coming soon" disabled></textarea>
                            </div>
                        </div>
                        <div class="alert alert-info d-flex align-items-center p-5 mt-10 mb-0">
                            <i class="ki-duotone ki-information fs-2hx text-info me-4"><span class="path1"></span><span class="path2"></span></i>
                            <div class="d-flex flex-column">
                                <span class="fw-semibold">Advanced settings will be available in a future update.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
