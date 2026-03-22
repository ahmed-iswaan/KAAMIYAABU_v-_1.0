<div>
    @php
        $fallback = asset('assets/media/avatars/blank.png');
        $dirStatusLabelMap = [
            'not_started' => 'Pending',
            'in_progress' => 'In progress',
            'callback' => 'Call back',
            'unreachable' => 'Unreachable',
            'wrong_number' => 'Wrong number',
            'do_not_call' => 'Do not call',
            'completed' => 'Completed',
        ];

        $callStatusValue = (string)($callStatus?->status ?? 'not_started');
        $callStatusLabel = $dirStatusLabelMap[$callStatusValue] ?? $callStatusValue;
        $callStatusClass = $callStatusValue === 'completed' ? 'badge badge-success' : 'badge badge-warning';

        $attemptText = '—';
        if (is_array($lastAttempt)) {
            $attemptNo = (int)($lastAttempt['attempt'] ?? 0);
            $subId = (string)($lastAttempt['sub_status_id'] ?? '');
            $subLabel = $activeSubStatuses[$subId] ?? $subId;
            $attemptText = $attemptNo > 0 ? "Attempt {$attemptNo} • {$subLabel}" : ($subLabel ?: '—');
        }
        $attemptClass = is_array($lastAttempt) ? 'badge badge-danger' : 'badge badge-light';

        $loc = $directory->permanentLocationString();
        if (!$loc || $loc === 'N/A') $loc = $directory->currentLocationString();
    @endphp

    <div class="content fs-6 d-flex flex-column flex-column-fluid" id="kt_content">
        <div class="toolbar" id="kt_toolbar">
            <div class="container-fluid d-flex flex-stack flex-wrap flex-sm-nowrap">
                <div class="d-flex flex-column align-items-start justify-content-center flex-wrap me-2 mb-4 mb-sm-0">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <a href="{{ route('call-center.beta') }}" class="btn btn-sm btn-light">Back</a>
                        <h1 class="text-dark fw-bold my-1 fs-2 mb-0">Directory Details</h1>
                    </div>
                    <ul class="breadcrumb fw-semibold fs-base my-1">
                        <li class="breadcrumb-item text-muted"><a href="#" class="text-muted text-hover-primary">Operations</a></li>
                        <li class="breadcrumb-item text-muted"><a href="{{ route('call-center.beta') }}" class="text-muted text-hover-primary">Call Center Beta</a></li>
                        <li class="breadcrumb-item text-dark">Details</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="post fs-6 d-flex flex-column-fluid" id="kt_post">
            <div class="container-fluid">

                <div class="card border border-gray-200 shadow-sm mb-6">
                    <div class="card-body p-5">
                        <div class="d-flex flex-column flex-md-row align-items-start gap-5">
                            <div class="symbol symbol-80px symbol-circle flex-shrink-0">
                                <img src="{{ $imgUrl ?: $fallback }}" alt="{{ $directory->name }}" class="w-80px h-80px object-fit-cover" />
                            </div>

                            <div class="flex-grow-1">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                    <div>
                                        <div class="fw-bold text-gray-900 fs-3">{{ $directory->name }}</div>
                                        <div class="text-muted">{{ $directory->subConsite?->code }} @if($directory->subConsite?->name) - {{ $directory->subConsite->name }} @endif</div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="{{ $callStatusClass }}">{{ $callStatusLabel }}</span>
                                        <span class="{{ $attemptClass }}">{{ $attemptText }}</span>
                                    </div>
                                </div>

                                <div class="separator my-4"></div>

                                <div class="row g-4">
                                    <div class="col-6 col-lg-3">
                                        <div class="text-muted fs-8">NID</div>
                                        <div class="fw-semibold text-gray-800">{{ $directory->id_card_number ?: '-' }}</div>
                                    </div>
                                    <div class="col-6 col-lg-3">
                                        <div class="text-muted fs-8">Serial</div>
                                        <div class="fw-semibold text-gray-800">{{ $directory->serial ?: '-' }}</div>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <div class="text-muted fs-8">Block / Address</div>
                                        <div class="fw-semibold text-gray-800">{{ $loc ?: '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if(isset($modalPresentUsers) && count($modalPresentUsers) > 0)
                    <div class="mb-4">
                        <div class="fw-semibold mb-2">Currently viewing:</div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($modalPresentUsers as $user)
                                <div class="d-flex align-items-center gap-2">
                                    @if($user['profile_picture'])
                                        <img src="{{ $user['profile_picture'] }}" alt="{{ $user['name'] }}" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;">
                                    @else
                                        <span class="symbol symbol-32px symbol-circle bg-light"><span class="fw-bold text-primary">{{ substr($user['name'],0,1) }}</span></span>
                                    @endif
                                    <span class="fw-semibold">{{ $user['name'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(isset($directory->phones) && is_array($directory->phones) && count($directory->phones))
                    <div class="mb-4">
                        <div class="fw-semibold mb-2">Phone Numbers & Status:</div>
                        <div class="d-flex flex-column gap-2">
                            @php
                                $statusLabelMap = [
                                    'not_called' => 'Pending',
                                    'completed' => 'Completed',
                                    'wrong_number' => 'Wrong number',
                                    'no_answer' => 'No answer',
                                    'busy' => 'Busy',
                                    'switched_off' => 'Switched off',
                                    'callback' => 'Call back',
                                ];
                            @endphp
                            @foreach($directory->phones as $phone)
                                @php
                                    $norm = \App\Models\DirectoryPhoneStatus::normalizePhone((string) $phone);
                                    $status = 'not_called';
                                    $subId = '';
                                    $subName = '';
                                    $notes = '';
                                    $badgeColor = 'secondary';
                                    if(isset($phoneStatuses[$norm])) {
                                        $status = $phoneStatuses[$norm]['status'] ?? 'not_called';
                                        $subId = $phoneStatuses[$norm]['sub_status_id'] ?? '';
                                        $notes = $phoneStatuses[$norm]['notes'] ?? '';
                                    }
                                    $subName = $subId ? (($activeSubStatuses[$subId] ?? '') ?: $subId) : '';
                                    $displayText = $subId ? $subName : ($statusLabelMap[$status] ?? $status);
                                    $badgeColor = match(true){
                                        $subId !== '' => 'primary',
                                        $status === 'completed' => 'success',
                                        $status === 'wrong_number' => 'danger',
                                        $status === 'no_answer' => 'warning',
                                        $status === 'busy' => 'warning',
                                        $status === 'switched_off' => 'danger',
                                        $status === 'callback' => 'info',
                                        default => 'secondary'
                                    };
                                @endphp
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-bold">{{ $phone }}</span>
                                    <span class="badge badge-light-{{ $badgeColor }}">{{ $displayText }}</span>
                                    @if($norm && !empty($notes))
                                        <span class="text-muted small ms-1 fst-italic">{{ $notes }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Attempts History + Call Center Form -->
                <div class="row g-6 align-items-start">
                    <div class="col-12 col-xl-5">
                        <!-- Attempts History Card -->
                        <div class="card border border-gray-200 shadow-sm mb-6">
                            <div class="card-header border-0 pt-6 d-flex justify-content-between align-items-center">
                                <div class="card-title">
                                    <h3 class="fw-bold m-0">Attempts History</h3>
                                </div>
                                <div class="card-toolbar">
                                    @if($activeElectionId)
                                        <button type="button" class="btn btn-sm btn-icon btn-light-primary w-25px h-25px"
                                                wire:click="addAttempt"
                                                @disabled($visibleAttempts >= 10)
                                                title="Add Attempt">
                                            <i class="ki-duotone ki-plus fs-2"><span class="path1"></span><span class="path2"></span></i>
                                        </button>
                                    @endif
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="text-muted fs-7 mb-5">Track specific call attempts below.</div>

                                @php
                                    $subStatusOptions = ['' => '—'] + ($activeSubStatuses ?? []);
                                    $phonesForAttempts = is_array($directory->phones ?? null) ? array_values(array_filter($directory->phones)) : [];
                                    $phonesForAttemptsNorm = array_map(fn($p) => \App\Models\DirectoryPhoneStatus::normalizePhone((string)$p), $phonesForAttempts);
                                    $phonesForAttemptsNorm = array_values(array_filter($phonesForAttemptsNorm));
                                    $defaultAttemptPhone = $phonesForAttemptsNorm[0] ?? '';
                                @endphp

                                <div id="ccAttemptsBox" class="vstack gap-3">
                                    @for($a = ($visibleAttempts ?? 0); $a >= 1; $a--)
                                        @php
                                            $ss = $subStatusAttempts[(string)$a]['sub_status_id'] ?? '';
                                            $isSet = !empty($ss);
                                            $selPhone = $subStatusAttempts[(string)$a]['phone_number'] ?? '';
                                            if (empty($selPhone) && !empty($defaultAttemptPhone)) {
                                                $selPhone = $defaultAttemptPhone;
                                            }
                                        @endphp

                                        <div wire:key="beta-attempt-{{ $directory->id }}-{{ $a }}" class="p-4 rounded border {{ $isSet ? 'border-primary border-dashed bg-light-primary' : 'border-gray-300 border-dashed bg-light' }}">
                                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                                <div class="fw-bold {{ $isSet ? 'text-primary' : 'text-gray-700' }}">Attempt {{ $a }}</div>
                                                @if($isSet)
                                                    <span class="badge badge-light-primary fw-bold">{{ $subStatusOptions[$ss] ?? $ss }}</span>
                                                @endif
                                            </div>

                                            <div class="row g-3 align-items-end">
                                                <div class="col-12 col-md-3">
                                                    <label class="form-label fw-semibold fs-7 mb-1">Phone</label>
                                                    <select class="form-select form-select-sm" @disabled(!$activeElectionId || !count($phonesForAttemptsNorm))
                                                            wire:model.live="subStatusAttempts.{{ $a }}.phone_number">
                                                        @if(!count($phonesForAttemptsNorm))
                                                            <option value="">No phone numbers</option>
                                                        @else
                                                            @foreach($phonesForAttemptsNorm as $idx => $p)
                                                                <option value="{{ $p }}">{{ $phonesForAttempts[$idx] ?? $p }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                </div>

                                                <div class="col-12 col-md-3">
                                                    <label class="form-label fw-semibold fs-7 mb-1">Sub status</label>
                                                    <select class="form-select form-select-sm" @disabled(!$activeElectionId)
                                                            wire:model.live="subStatusAttempts.{{ $a }}.sub_status_id">
                                                        @foreach($subStatusOptions as $val => $label)
                                                            <option value="{{ $val }}">{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="col-12 col-md-6">
                                                     <label class="form-label fw-semibold fs-7 mb-1">Notes</label>
                                                     <input type="text" class="form-control form-control-sm" @disabled(!$activeElectionId)
                                                            wire:model.live.debounce.200ms="subStatusAttempts.{{ $a }}.notes"
                                                            placeholder="What happened on attempt {{ $a }}?" />
                                                 </div>
                                             </div>

                                            @if($activeElectionId)
                                                <div class="mt-3 d-flex justify-content-end gap-2">
                                                    <button type="button" class="btn btn-sm btn-light-primary"
                                                            wire:click="updateSubStatusAttemptStatus('{{ $a }}')"
                                                            wire:loading.attr="disabled"
                                                            wire:target="updateSubStatusAttemptStatus">
                                                        <span wire:loading.remove wire:target="updateSubStatusAttemptStatus">Save</span>
                                                        <span wire:loading wire:target="updateSubStatusAttemptStatus" class="spinner-border spinner-border-sm"></span>
                                                    </button>

                                                    @can('call-center-clear-attempt')
                                                        <button type="button" class="btn btn-sm btn-icon btn-light-danger w-25px h-25px"
                                                                wire:click="clearAttempt('{{ $a }}')" title="Clear attempt">
                                                            <i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                                                        </button>
                                                    @endcan
                                                </div>
                                            @endif
                                        </div>
                                    @endfor

                                    @if(($visibleAttempts ?? 0) === 0)
                                        <div class="text-center text-muted py-5 border border-dashed rounded bg-light">
                                            <i class="ki-duotone ki-call fs-1 text-gray-400 mb-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span><span class="path7"></span><span class="path8"></span></i>
                                            <div>No attempts added yet.</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-7">
                        <div class="card border border-gray-200 shadow-sm mb-6">
                            <div class="card-header border-0 pt-6">
                                <div class="card-title">
                                    <h3 class="fw-bold m-0">Call Center Form</h3>
                                </div>
                                <div class="card-toolbar">
                                    @if(!$activeElectionId)
                                        <span class="badge badge-warning">No active election</span>
                                    @else
                                        <span class="badge badge-primary">Auto-save enabled</span>
                                    @endif
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="callcenter-dv-form" dir="rtl" lang="dv">
                                    <style>
                                        .callcenter-dv-form{font-family:Faruma, 'MV Faseyha', 'Noto Sans Thaana', Arial, sans-serif; font-size: 15px; line-height: 1.5;}
                                        .callcenter-dv-form .form-label{font-family:inherit; font-size: 15px;}
                                        .callcenter-dv-form .form-control,.callcenter-dv-form .form-select{font-family:inherit;text-align:right; font-size: 15px;}
                                        .callcenter-dv-form .text-muted.small{font-size: 13px;}
                                    </style>

                                    <div class="alert alert-primary d-flex align-items-center p-5">
                                        <div class="d-flex flex-column">
                                            <span class="fw-semibold">މި ގުޅާލީ ކުރިއަށް އޮތް ލޯކަލް ކައުންސިލް އިންތިހާބުގައި މާލެ ސިޓީ މޭޔަރ ކަމަށް މިހާރު މާލޭގެ މޭޔަރ އާދަމް އާޒިމް ކުރިމަތި ލައްވާ ފައިވާތީ ތިޔަ ބޭފުޅާގެ ހިޔާލު ހޯދާލުމަށް އާދަމް އާޒިމްގެ ކެމްޕެއިން އޮފީހުން. ފަސޭހަ ފުޅު ވަގުތެއްތޯ؟</span>
                                        </div>
                                    </div>

                                    <div class="mb-5">
                                        <label class="form-label fw-semibold">
                                            1. މިހާރު މާލޭގެ މޭޔަރ ގޮތުގައި އާދަމް އާޒިމް ކުރައްވަމުން ގެންދަވާ މަސައްކަތާއި މެދު ދެކެވަޑައިގަންނަވަނީ ކިހިނެއްތޯ؟
                                        </label>
                                        <select class="form-select" wire:model.live="ccForm.q1_performance" @disabled(!$activeElectionId)>
                                            <option value="">—</option>
                                            <option value="kamudhey">ކަމުދޭ</option>
                                            <option value="kamunudhey">ކަމުނުދޭ</option>
                                            <option value="neyngey">ނޭނގޭ</option>
                                            <option value="mixed">މިކްސް އޮޕީނިއަން</option>
                                        </select>
                                    </div>

                                    @php($showQ2Reason = !empty($ccForm['q1_performance']) && (($ccForm['q1_performance'] ?? null) !== 'kamudhey'))
                                    <div class="mb-5" wire:key="ccForm-q2-reason" style="{{ $showQ2Reason ? '' : 'display:none;' }}">
                                        <label class="form-label fw-semibold">
                                            2. އެއީ ކިހިނެއްތޯ ވީ؟
                                            <span class="text-muted small">(1 ގަި ކަމުނުދޭ / ނޭނގޭ / މިކްސް އޮޕީނިއަން ބުނެފިނަމަ)</span>
                                        </label>

                                        <textarea
                                            class="form-control"
                                            rows="3"
                                            wire:model.defer="ccForm.q2_reason"
                                            placeholder="..."
                                            @disabled(!$activeElectionId || !$showQ2Reason)
                                        ></textarea>
                                    </div>

                                    <div class="mb-5">
                                        <label class="form-label fw-semibold">
                                            3. މާލޭގެ މޭޔަރ ކަމަށް އިތުރު ދައުރަކަށް އާދަމް އާޒިމް ކުރިމަތި ލެއްވުމަށް ތާއީދު ކުރައްވަންތޯ؟
                                        </label>
                                        <select class="form-select" wire:model.live="ccForm.q3_support" @disabled(!$activeElectionId)>
                                            <option value="">—</option>
                                            <option value="aanekey">އާނއެކޭ</option>
                                            <option value="noonekay">ނޫނެކޭ</option>
                                            <option value="neyngey">ނޭނގޭ</option>
                                            <option value="vote_laan_nudhaanan">ވޯޓު ލާން ނޫދާނަން</option>
                                        </select>
                                    </div>

                                    @if(($ccForm['q3_support'] ?? null) === 'aanekey')
                                        <div class="mb-5">
                                            <label class="form-label fw-semibold">
                                                4. ވޯޓްލާ ދުވަހު ހުންނަވާނީ ކޮން ސަރަހައްދުގަތޯ؟
                                            </label>
                                            <select class="form-select" wire:model.live="ccForm.q4_voting_area" @disabled(!$activeElectionId)>
                                                <option value="">—</option>
                                                <option value="male">މާލެ</option>
                                                <option value="vilimale">ވިލިމާލެ</option>
                                                <option value="hulhumale_phase1">ހުޅުމާލެ ފޭސް 1</option>
                                                <option value="hulhumale_phase2">ހުޅުމާލެ ފޭސް 2</option>
                                                <option value="other">ނޫން (އެހެންނިހެން)</option>
                                                <option value="unknown">ނޭނގޭ</option>
                                            </select>

                                            @php($showQ4OtherText = (($ccForm['q4_voting_area'] ?? null) === 'other'))
                                            <div class="mt-3" wire:key="ccForm-q4-other-text" style="{{ $showQ4OtherText ? '' : 'display:none;' }}">
                                                <label class="form-label fw-semibold">ނޫން ނަމަ ކޮންތާކުތޯ؟</label>
                                                <input type="text" class="form-control" wire:model.defer="ccForm.q4_other_text" placeholder="..." @disabled(!$activeElectionId || !$showQ4OtherText) />
                                            </div>
                                        </div>

                                        @if(!in_array(($ccForm['q4_voting_area'] ?? null), ['other','unknown'], true))
                                            <div class="mb-5">
                                                <label class="form-label fw-semibold">5. ވޯޓުލާން ދިއުމަށް އެހީތެރިކަމެއް ބޭނުންފުޅުވޭތޯ؟</label>
                                                <select class="form-select" wire:model.live="ccForm.q5_help_needed" @disabled(!$activeElectionId)>
                                                    <option value="">—</option>
                                                    <option value="yes">އާނ</option>
                                                    <option value="no">ނޫން</option>
                                                    <option value="maybe">މަބީ</option>
                                                </select>
                                            </div>
                                        @else
                                            <input type="hidden" wire:model.lazy="ccForm.q5_help_needed" />
                                        @endif
                                    @endif

                                </div>
                            </div>
                        </div>
                    </div>
                </div>



            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Replace with your actual directory UUID variable
    const directoryId = @json($directory->id);
    const userId = @json(auth()->id());
    const userName = @json(auth()->user()?->name);
    const userProfile = @json(auth()->user()?->profile_picture ?? null);

    if (window.Echo && directoryId) {
        window.Echo.join(`directory.presence.${directoryId}`)
            .here(users => {
                // Map users to expected format
                const mapped = users.map(u => ({
                    id: u.id,
                    name: u.name,
                    profile_picture: u.profile_picture ?? null,
                }));
                window.Livewire.emit('presence-sync', mapped);
            })
            .joining(user => {
                // Optionally update when a user joins
                window.Livewire.emit('presence-sync', []); // triggers refresh
            })
            .leaving(user => {
                window.Livewire.emit('presence-sync', []); // triggers refresh
            });
    }
</script>
@endpush
