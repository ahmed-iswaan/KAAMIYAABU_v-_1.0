<?php

namespace App\Livewire\CallCenter;

use App\Models\CallCenterForm;
use App\Models\Directory;
use App\Models\DirectoryPhoneStatus;
use App\Models\Election;
use App\Models\ElectionDirectoryCallStatus;
use App\Models\ElectionDirectoryCallSubStatus;
use App\Models\RequestType;
use App\Models\SubConsite;
use App\Models\SubStatus;
use App\Models\VoterNote;
use App\Models\VoterRequest;
use App\Models\EventLog;
use App\Events\VoterDataChanged;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class CallCenter extends Component
{
    use WithPagination, AuthorizesRequests;

    protected $paginationTheme = 'bootstrap';

    /**
     * Active Sub Status options for the attempts dropdown.
     * Format: [id => name]
     */
    public array $activeSubStatuses = [];

    public string $search = '';
    public string $filterSubConsiteId = '';
    public int $perPage = 25;

    // Modal state
    public bool $showDetailsModal = false;
    public ?Directory $selectedDirectory = null;

    /**
     * Keep a primitive ID for realtime comparisons (safer than relying on a hydrated model).
     */
    public ?string $selectedDirectoryId = null;

    public ?string $activeElectionId = null;

    // Election-level directory completion status
    public string $directoryCallStatus = ElectionDirectoryCallStatus::STATUS_NOT_STARTED;

    // Notes/Requests for selected directory
    public array $selectedNotes = [];
    public array $selectedRequests = [];

    // Call Status (per number)
    public array $phoneCallStatuses = []; // [normalizedPhone => status]
    public array $phoneCallNotes = [];    // [normalizedPhone => notes]
    public array $phoneCallSubStatuses = []; // [normalizedPhone => sub_status_id]

    // Create note/request forms
    public string $newNoteText = '';
    public string $newRequestTypeId = '';
    public ?float $newRequestAmount = null;
    public string $newRequestNote = '';

    // Call center form (Dhivehi)
    public array $ccForm = [
        'q1_performance' => null,
        'q2_reason' => null,
        'q3_support' => null,
        'q4_voting_area' => null,
        'q4_other_text' => null,
        'q5_help_needed' => null,
        'q6_message_to_mayor' => null,
    ];

    public array $requestTypes = [];

    // History (from event_logs)
    public array $selectedHistory = [];

    // Persist active tab inside modal to prevent resets on auto-save
    public string $activeModalTab = 'cc_notes';

    /**
     * Keep the Bootstrap tab state in sync with Livewire so re-renders don't reset the active tab.
     */
    public function setActiveModalTab(string $tab): void
    {
        $allowed = ['cc_notes', 'cc_requests', 'cc_status_attempts', 'cc_call_status', 'cc_form', 'cc_history'];
        if (!in_array($tab, $allowed, true)) return;

        // Permission gates for tab access
        if ($tab === 'cc_notes' && !auth()->user()?->can('call-center-notes')) return;
        if ($tab === 'cc_call_status' && !auth()->user()?->can('call-center-call-status')) return;
        if ($tab === 'cc_history' && !auth()->user()?->can('call-center-history')) return;

        $this->activeModalTab = $tab;
    }

    // Sub-status attempts (per election + directory)
    // attempt => ['sub_status_id' => ?, 'notes' => ?, 'phone_number' => ?]
    public array $subStatusAttempts = []; // attempt => ['sub_status_id' => ?, 'notes' => ?]

    // Progressive UI: how many attempt blocks are currently visible in the modal
    public int $visibleAttempts = 0;

    /**
     * Increment to force modal inner fragment re-render without touching the Bootstrap modal root.
     */
    public int $modalRenderTick = 0;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterSubConsiteId' => ['except' => ''],
        'perPage' => ['except' => 25],
    ];

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterSubConsiteId(): void { $this->resetPage(); }
    public function updatedPerPage(): void { $this->resetPage(); }

    public function mount(): void
    {
        $this->activeElectionId = Election::query()
            ->where('status', Election::STATUS_ACTIVE)
            ->value('id');

        $this->requestTypes = RequestType::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($t) => ['id' => (string) $t->id, 'name' => (string) $t->name])
            ->all();

        $this->activeSubStatuses = SubStatus::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->mapWithKeys(fn($s) => [(string) $s->id => (string) $s->name])
            ->all();

        $this->resetSubStatusAttempts();
    }

    protected function allowedSubConsiteIds(): array
    {
        return Auth::user()?->subConsites()->pluck('sub_consites.id')->all() ?? [];
    }

    private function directoryImageUrl(Directory $dir): ?string
    {
        // 1) Stored profile picture
        if (!empty($dir->profile_picture)) {
            return asset('storage/' . ltrim($dir->profile_picture, '/'));
        }

        // 2) Fallback: public/nid-images/{NID}.{ext}
        $nid = trim((string) ($dir->id_card_number ?? ''));
        if ($nid === '') return null;

        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            $relative = "nid-images/{$nid}.{$ext}";
            if (is_file(public_path($relative))) {
                return asset($relative);
            }
        }

        return null;
    }

    private function loadPhoneCallStatusesForSelected(): void
    {
        $this->phoneCallStatuses = [];
        $this->phoneCallNotes = [];
        $this->phoneCallSubStatuses = [];

        if (!$this->selectedDirectory) return;

        $directory = Directory::with('phoneStatuses')->find($this->selectedDirectory->id);
        if (!$directory) return;

        foreach (($directory->phones ?? []) as $p) {
            $norm = DirectoryPhoneStatus::normalizePhone($p);
            if (!$norm) continue;
            $this->phoneCallStatuses[$norm] = DirectoryPhoneStatus::STATUS_NOT_CALLED;
            $this->phoneCallNotes[$norm] = '';
            $this->phoneCallSubStatuses[$norm] = '';
        }

        foreach ($directory->phoneStatuses as $row) {
            $norm = DirectoryPhoneStatus::normalizePhone($row->phone);
            if (!$norm) continue;
            $this->phoneCallStatuses[$norm] = $row->status ?: DirectoryPhoneStatus::STATUS_NOT_CALLED;
            $this->phoneCallNotes[$norm] = (string)($row->notes ?? '');
            $this->phoneCallSubStatuses[$norm] = (string)($row->sub_status_id ?? '');
        }
    }

    private function loadElectionDirectoryCallStatusForSelected(): void
    {
        $this->directoryCallStatus = ElectionDirectoryCallStatus::STATUS_NOT_STARTED;

        if (!$this->selectedDirectory || !$this->activeElectionId) return;

        $row = ElectionDirectoryCallStatus::query()
            ->where('election_id', (string) $this->activeElectionId)
            ->where('directory_id', (string) $this->selectedDirectory->id)
            ->first();

        if ($row) {
            $this->directoryCallStatus = $row->status ?: ElectionDirectoryCallStatus::STATUS_NOT_STARTED;
        }
    }

    public function updatedDirectoryCallStatus($value): void
    {
        // Auto-save when changed
        $this->saveDirectoryCallStatus();
    }

    private function refreshHistory(): void
    {
        $this->selectedHistory = [];

        if (!$this->selectedDirectory) return;

        $dirId = (string) $this->selectedDirectory->id;

        $this->selectedHistory = EventLog::query()
            ->where('event_tab', 'Call Center')
            ->where('event_entry_id', $dirId)
            ->latest()
            ->limit(10)
            ->get(['id', 'user_id', 'event_type', 'description', 'event_data', 'ip_address', 'created_at'])
            ->map(function ($e) {
                return [
                    'id' => (string) $e->id,
                    'event_type' => (string) $e->event_type,
                    'description' => (string) ($e->description ?? ''),
                    'event_data' => $e->event_data,
                    'ip_address' => (string) ($e->ip_address ?? ''),
                    'created_at_human' => $e->created_at?->diffForHumans(),
                    'created_at' => $e->created_at?->format('Y-m-d H:i'),
                    'user_name' => optional($e->user)->name ?? null,
                ];
            })
            ->all();
    }

    private function logEvent(string $type, ?string $description = null, array $data = []): void
    {
        try {
            if (!$this->selectedDirectory) return;

            EventLog::create([
                'user_id' => auth()->id(),
                'event_tab' => 'Call Center',
                'event_entry_id' => (string) $this->selectedDirectory->id,
                'event_type' => $type,
                'description' => $description,
                'event_data' => $data ?: null,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            // Never break call-center flow if logging fails.
        }
    }

    public function saveDirectoryCallStatus(): void
    {
        $this->authorize('call-center-render');

        if (!$this->selectedDirectory || !$this->activeElectionId) return;

        $status = $this->directoryCallStatus;
        if (!in_array($status, ElectionDirectoryCallStatus::STATUSES, true)) {
            $status = ElectionDirectoryCallStatus::STATUS_NOT_STARTED;
        }

        $row = ElectionDirectoryCallStatus::query()->firstOrNew([
            'election_id' => (string) $this->activeElectionId,
            'directory_id' => (string) $this->selectedDirectory->id,
        ]);

        $prev = $row->exists ? ($row->status ?: ElectionDirectoryCallStatus::STATUS_NOT_STARTED) : null;

        $row->status = $status;
        $row->updated_by = auth()->id();
        $row->completed_at = $status === ElectionDirectoryCallStatus::STATUS_COMPLETED ? now() : null;
        $row->save();

        $this->logEvent(
            'Directory Status Updated',
            'Per-election directory call status updated',
            [
                'election_id' => (string) $this->activeElectionId,
                'directory_id' => (string) $this->selectedDirectory->id,
                'previous_status' => $prev,
                'new_status' => $status,
            ]
        );
        $this->refreshHistory();

        // Realtime notify
        VoterDataChanged::dispatch(
            'call_center_directory_status_updated',
            (string) $this->selectedDirectory->id,
            (string) $this->activeElectionId,
            ['status' => (string) $row->status]
        );

        // Do not force a full component re-render (prevents active tab from resetting)
    }

    public function markAsCompleted(): void
    {
        $this->authorize('call-center-mark-completed');

        $this->directoryCallStatus = ElectionDirectoryCallStatus::STATUS_COMPLETED;
        $this->saveDirectoryCallStatus();
    }

    public function undoDirectoryStatus(): void
    {
        $this->authorize('call-center-undo-status');

        if (!$this->selectedDirectory || !$this->activeElectionId) return;

        $row = ElectionDirectoryCallStatus::query()
            ->where('election_id', (string) $this->activeElectionId)
            ->where('directory_id', (string) $this->selectedDirectory->id)
            ->first();

        $prev = $row?->status;

        if ($row) {
            $row->delete();
        }

        // Reset local state
        $this->directoryCallStatus = ElectionDirectoryCallStatus::STATUS_NOT_STARTED;

        $this->logEvent(
            'Directory Status Undone',
            'Directory status reset to pending (status row deleted)',
            [
                'election_id' => (string) $this->activeElectionId,
                'directory_id' => (string) $this->selectedDirectory->id,
                'previous_status' => (string) ($prev ?? ''),
                'new_status' => ElectionDirectoryCallStatus::STATUS_NOT_STARTED,
            ]
        );
        $this->refreshHistory();

        VoterDataChanged::dispatch(
            'call_center_directory_status_undone',
            (string) $this->selectedDirectory->id,
            (string) $this->activeElectionId,
            ['status' => ElectionDirectoryCallStatus::STATUS_NOT_STARTED]
        );

        // Note: list statuses are computed in render(); no component property to update here.
    }

    public function updatePhoneCallStatus(string $phone, string $status): void
    {
        $this->authorize('call-center-render');

        if (!$this->selectedDirectory) return;

        $norm = DirectoryPhoneStatus::normalizePhone($phone);
        if (!$norm) return;

        if (!in_array($status, DirectoryPhoneStatus::STATUSES, true)) {
            $status = DirectoryPhoneStatus::STATUS_NOT_CALLED;
        }

        $notes = (string)($this->phoneCallNotes[$norm] ?? '');

        $row = DirectoryPhoneStatus::firstOrNew([
            'directory_id' => (string) $this->selectedDirectory->id,
            'phone' => $norm,
        ]);

        $prevStatus = $row->exists ? ($row->status ?: DirectoryPhoneStatus::STATUS_NOT_CALLED) : null;
        $prevNotes = $row->exists ? (string)($row->notes ?? '') : null;

        $row->status = $status;
        $row->notes = $notes !== '' ? $notes : null;
        $row->last_called_at = now();
        $row->last_called_by = auth()->id();
        $row->save();

        $this->phoneCallStatuses[$norm] = $status;

        $this->logEvent(
            'Phone Call Status Updated',
            'Directory phone status updated',
            [
                'directory_id' => (string) $this->selectedDirectory->id,
                'phone' => $norm,
                'previous_status' => $prevStatus,
                'new_status' => $status,
                'previous_notes' => $prevNotes,
                'new_notes' => $notes,
            ]
        );
        $this->refreshHistory();

        VoterDataChanged::dispatch(
            'call_center_phone_status_updated',
            (string) $this->selectedDirectory->id,
            (string) $this->activeElectionId,
            ['phone' => (string) $norm]
        );

        // Do not force $refresh
    }

    public function updatePhoneCallStatusFromSubStatus(string $phone, string $subStatusId): void
    {
        $this->authorize('call-center-render');

        if (!$this->selectedDirectory) return;

        $norm = DirectoryPhoneStatus::normalizePhone($phone);
        if (!$norm) return;

        $subStatusId = trim((string) $subStatusId);

        $row = DirectoryPhoneStatus::firstOrNew([
            'directory_id' => (string) $this->selectedDirectory->id,
            'phone' => $norm,
        ]);

        $row->sub_status_id = $subStatusId !== '' ? $subStatusId : null;
        $row->last_called_at = now();
        $row->last_called_by = auth()->id();

        $mapped = $this->mapSubStatusToPhoneCallStatus($subStatusId);
        if (!$mapped && $subStatusId !== '') {
            $mapped = DirectoryPhoneStatus::STATUS_COMPLETED;
        }
        $row->status = $mapped ?: DirectoryPhoneStatus::STATUS_NOT_CALLED;

        $notes = (string)($this->phoneCallNotes[$norm] ?? '');
        $row->notes = $notes !== '' ? $notes : null;

        $row->save();

        // Keep ALL local state in sync for immediate UI updates
        $this->phoneCallSubStatuses[$norm] = (string)($row->sub_status_id ?? '');
        $this->phoneCallStatuses[$norm] = (string)($row->status ?? DirectoryPhoneStatus::STATUS_NOT_CALLED);
        $this->phoneCallNotes[$norm] = (string)($row->notes ?? '');

        $this->logEvent(
            'Phone Call Sub-Status Updated',
            'Directory phone sub-status updated',
            [
                'directory_id' => (string) $this->selectedDirectory->id,
                'phone' => $norm,
                'sub_status_id' => $row->sub_status_id,
                'status' => $row->status,
            ]
        );
        $this->refreshHistory();

        VoterDataChanged::dispatch(
            'call_center_phone_sub_status_updated',
            (string) $this->selectedDirectory->id,
            (string) $this->activeElectionId,
            ['phone' => (string) $norm]
        );
    }

    public function saveCallCenterForm(): void
    {
        $this->authorize('call-center-render');

        if (!$this->selectedDirectory || !$this->activeElectionId) return;

        $this->validate([
            'ccForm.q1_performance' => ['nullable', 'in:kamudhey,kamunudhey,neyngey,mixed'],
            'ccForm.q2_reason' => ['nullable', 'string', 'max:4000'],
            'ccForm.q3_support' => ['nullable', 'in:aanekey,noonekay,neyngey'],
            'ccForm.q4_voting_area' => ['nullable', 'in:male,vilimale,hulhumale_phase1,hulhumale_phase2,other,unknown'],
            'ccForm.q4_other_text' => ['nullable', 'string', 'max:255'],
            'ccForm.q5_help_needed' => ['nullable', 'in:yes,no,maybe'],
            'ccForm.q6_message_to_mayor' => ['nullable', 'string', 'max:4000'],
        ]);

        $row = CallCenterForm::query()->firstOrNew([
            'election_id' => (string) $this->activeElectionId,
            'directory_id' => (string) $this->selectedDirectory->id,
        ]);

        $prev = $row->exists ? $row->only([
            'q1_performance','q2_reason','q3_support','q4_voting_area','q4_other_text','q5_help_needed','q6_message_to_mayor'
        ]) : null;

        if (!$row->exists) {
            $row->created_by = auth()->id();
        }

        $row->fill([
            'q1_performance' => $this->ccForm['q1_performance'] ?? null,
            'q2_reason' => $this->ccForm['q2_reason'] ?? null,
            'q3_support' => $this->ccForm['q3_support'] ?? null,
            'q4_voting_area' => $this->ccForm['q4_voting_area'] ?? null,
            'q4_other_text' => $this->ccForm['q4_other_text'] ?? null,
            'q5_help_needed' => $this->ccForm['q5_help_needed'] ?? null,
            'q6_message_to_mayor' => $this->ccForm['q6_message_to_mayor'] ?? null,
            'updated_by' => auth()->id(),
        ]);

        $row->save();

        // Auto-complete directory status when the required form answers exist.
        $q1 = trim((string)($this->ccForm['q1_performance'] ?? ''));
        $q2 = trim((string)($this->ccForm['q2_reason'] ?? ''));
        $q3 = trim((string)($this->ccForm['q3_support'] ?? ''));

        // Q2 is only required when Q1 is NOT "kamudhey" (per Blade condition)
        $formIsComplete = ($q1 !== '')
            && ($q3 !== '')
            && ($q1 === 'kamudhey' || $q2 !== '');

        if ($formIsComplete) {
            $this->directoryCallStatus = ElectionDirectoryCallStatus::STATUS_COMPLETED;
            $this->saveDirectoryCallStatus();
        }

        $this->logEvent(
            'Call Center Form Saved',
            'Call center form auto-saved',
            [
                'election_id' => (string) $this->activeElectionId,
                'directory_id' => (string) $this->selectedDirectory->id,
                'previous' => $prev,
                'current' => $row->only([
                    'q1_performance','q2_reason','q3_support','q4_voting_area','q4_other_text','q5_help_needed','q6_message_to_mayor'
                ]),
            ]
        );
        $this->refreshHistory();

        VoterDataChanged::dispatch(
            'call_center_form_updated',
            (string) $this->selectedDirectory->id,
            (string) $this->activeElectionId
        );

        // Do not force $refresh
    }

    /**
     * Livewire hook: auto-save the call center form when any ccForm field changes.
     *
     * Blade shows "Auto-save enabled" but we must still trigger persistence.
     */
    public function updatedCcForm($value, string $key): void
    {
        // Don't save while modal not bound to a directory/election.
        if (!$this->selectedDirectory || !$this->activeElectionId) {
            return;
        }

        // If voting area is not 'other', clear stale free-text.
        if ($key === 'q4_voting_area' && ($value ?? null) !== 'other') {
            $this->ccForm['q4_other_text'] = null;
        }

        // Ignore "clearing" events coming from Livewire hydration/re-render for selects
        // (this was causing Q1/Q3 to appear auto-filled/changed unexpectedly).
        if (in_array($key, ['q1_performance', 'q3_support'], true) && ($value === '' || $value === null)) {
            return;
        }

        try {
            $this->saveCallCenterForm();
        } catch (\Throwable $e) {
            // Never break UI updates because of a save failure.
            \Log::error('CallCenter form auto-save failed', [
                'directory_id' => $this->selectedDirectory?->id,
                'election_id' => $this->activeElectionId,
                'field' => $key,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function resetSubStatusAttempts(): void
    {
        $this->subStatusAttempts = [];
        foreach (\App\Models\ElectionDirectoryCallSubStatus::ATTEMPTS as $a) {
            $this->subStatusAttempts[(string)$a] = ['sub_status_id' => '', 'notes' => '', 'phone_number' => ''];
        }

        $this->visibleAttempts = 0;
    }

    private function loadSubStatusAttemptsForSelected(): void
    {
        $this->resetSubStatusAttempts();

        if (!$this->selectedDirectory || !$this->activeElectionId) return;

        $rows = ElectionDirectoryCallSubStatus::query()
            ->where('election_id', (string) $this->activeElectionId)
            ->where('directory_id', (string) $this->selectedDirectory->id)
            ->get(['attempt', 'sub_status_id', 'notes', 'phone_number']);

        $maxAttemptWithData = 0;
        foreach ($rows as $r) {
            $a = (int) $r->attempt;
            $maxAttemptWithData = max($maxAttemptWithData, $a);
            $this->subStatusAttempts[(string)$a] = [
                'sub_status_id' => (string) ($r->sub_status_id ?? ''),
                'notes' => (string) ($r->notes ?? ''),
                'phone_number' => (string) ($r->phone_number ?? ''),
            ];
        }

        // Ensure every visible attempt has a default phone
        $defaultPhone = $this->defaultAttemptPhone();
        for ($a = 1; $a <= $maxAttemptWithData; $a++) {
            if (empty($this->subStatusAttempts[(string)$a]['phone_number']) && $defaultPhone !== '') {
                $this->subStatusAttempts[(string)$a]['phone_number'] = $defaultPhone;
            }
        }

        // Show attempts that already exist (and none if there are no saved attempts yet)
        $this->visibleAttempts = $maxAttemptWithData;
    }

    private function defaultAttemptPhone(): string
    {
        if (!$this->selectedDirectory) return '';
        $phones = is_array($this->selectedDirectory->phones ?? null) ? array_values(array_filter($this->selectedDirectory->phones)) : [];
        $first = $phones[0] ?? '';
        return DirectoryPhoneStatus::normalizePhone((string) $first) ?: '';
    }

    public function addAttempt(): void
    {
        $this->authorize('call-center-render');
        if (!$this->selectedDirectory || !$this->activeElectionId) return;

        if ($this->visibleAttempts < 10) {
            $this->visibleAttempts++;

            // Default the phone number for the newly shown attempt
            $a = $this->visibleAttempts;
            if (isset($this->subStatusAttempts[(string)$a])) {
                $defaultPhone = $this->defaultAttemptPhone();
                if (($this->subStatusAttempts[(string)$a]['phone_number'] ?? '') === '' && $defaultPhone !== '') {
                    $this->subStatusAttempts[(string)$a]['phone_number'] = $defaultPhone;
                }
            }
        }

        if ($this->visibleAttempts > 10) $this->visibleAttempts = 10;
    }

    public function updateSubStatusAttemptPhone(string $attempt, string $phone): void
    {
        $this->authorize('call-center-render');

        $attemptInt = (int) $attempt;
        if (!in_array($attemptInt, ElectionDirectoryCallSubStatus::ATTEMPTS, true)) return;

        $norm = DirectoryPhoneStatus::normalizePhone($phone);
        $this->subStatusAttempts[(string)$attemptInt]['phone_number'] = $norm ?: '';

        // Persist immediately if attempt already has data
        $subStatusId = (string)($this->subStatusAttempts[(string)$attemptInt]['sub_status_id'] ?? '');
        $notes = (string)($this->subStatusAttempts[(string)$attemptInt]['notes'] ?? '');
        if (trim($subStatusId) !== '' || trim($notes) !== '') {
            $this->updateSubStatusAttemptStatus((string)$attemptInt, $subStatusId);
        }
    }

    public function updateSubStatusAttemptStatus(string $attempt, string $status): void
    {
        $this->authorize('call-center-render');

        if (!$this->selectedDirectory || !$this->activeElectionId) return;

        $attemptInt = (int) $attempt;
        if (!in_array($attemptInt, ElectionDirectoryCallSubStatus::ATTEMPTS, true)) return;

        // We store the selected sub_status_id (uuid) in the DB column `sub_status_id`
        $subStatusId = trim($status);
        if ($subStatusId === '') {
            $subStatusId = '';
        }

        $notes = (string)($this->subStatusAttempts[(string)$attemptInt]['notes'] ?? '');
        $phone = (string)($this->subStatusAttempts[(string)$attemptInt]['phone_number'] ?? '');
        $phone = DirectoryPhoneStatus::normalizePhone($phone) ?: '';

        $row = ElectionDirectoryCallSubStatus::query()->firstOrNew([
            'election_id' => (string) $this->activeElectionId,
            'directory_id' => (string) $this->selectedDirectory->id,
            'attempt' => $attemptInt,
        ]);

        $prev = $row->exists ? ['sub_status_id' => $row->sub_status_id, 'notes' => $row->notes, 'phone_number' => $row->phone_number] : null;

        // If user clears sub-status and notes, delete row if exists
        if ($subStatusId === '' && trim($notes) === '') {
            if ($row->exists) {
                $row->delete();
            }

            $this->subStatusAttempts[(string)$attemptInt]['sub_status_id'] = '';
            $this->subStatusAttempts[(string)$attemptInt]['notes'] = '';

            $this->logEvent('Attempt Sub-Status Cleared', 'Attempt sub-status cleared', [
                'election_id' => (string) $this->activeElectionId,
                'directory_id' => (string) $this->selectedDirectory->id,
                'attempt' => $attemptInt,
                'previous' => $prev,
            ]);
            $this->refreshHistory();
            return;
        }

        $row->phone_number = $phone !== '' ? $phone : null;
        $row->sub_status_id = $subStatusId !== '' ? $subStatusId : null;
        $row->notes = trim($notes) !== '' ? $notes : null;
        $row->updated_by = auth()->id();
        $row->save();

        $this->subStatusAttempts[(string)$attemptInt]['phone_number'] = (string)($row->phone_number ?? '');
        $this->subStatusAttempts[(string)$attemptInt]['sub_status_id'] = (string)($row->sub_status_id ?? '');
        $this->subStatusAttempts[(string)$attemptInt]['notes'] = (string)($row->notes ?? '');

        // Ensure the attempt is visible once user starts using it
        $this->visibleAttempts = max($this->visibleAttempts, $attemptInt);

        // Sync directory phone status + notes when an attempt is saved
        if ($phone !== '') {
            // Persist both the selected sub_status_id and a mapped legacy status for the phone
            $this->phoneCallSubStatuses[$phone] = $subStatusId;

            $mapped = $this->mapSubStatusToPhoneCallStatus($subStatusId);
            if (!$mapped && $subStatusId !== '') {
                $mapped = DirectoryPhoneStatus::STATUS_COMPLETED;
            }

            if ($mapped) {
                $attemptNote = trim($notes);
                if ($attemptNote !== '') {
                    $existing = trim((string)($this->phoneCallNotes[$phone] ?? ''));
                    $this->phoneCallNotes[$phone] = $existing === '' ? $attemptNote : ($existing . " | " . $attemptNote);
                }

                // Save legacy status + notes
                $this->updatePhoneCallStatus($phone, $mapped);
                $this->phoneCallStatuses[$phone] = $mapped;
            }

            // Save sub_status_id regardless of mapping
            $this->updatePhoneCallStatusFromSubStatus($phone, $subStatusId);
        }

        $this->logEvent('Attempt Sub-Status Updated', 'Attempt sub-status updated', [
            'election_id' => (string) $this->activeElectionId,
            'directory_id' => (string) $this->selectedDirectory->id,
            'attempt' => $attemptInt,
            'previous' => $prev,
            'current' => ['sub_status_id' => $row->sub_status_id, 'notes' => $row->notes, 'phone_number' => $row->phone_number],
        ]);
        $this->refreshHistory();

        VoterDataChanged::dispatch(
            'call_center_attempt_updated',
            (string) $this->selectedDirectory->id,
            (string) $this->activeElectionId,
            ['attempt' => (int) $attemptInt]
        );
    }

    private function mapSubStatusToPhoneCallStatus(string $subStatusId): ?string
    {
        $subStatusId = trim($subStatusId);
        if ($subStatusId === '') return null;

        // Prefer cached [id => name] list, but fall back to DB lookup to be robust.
        $name = trim((string)($this->activeSubStatuses[$subStatusId] ?? ''));
        if ($name === '') {
            $name = (string) SubStatus::query()->whereKey($subStatusId)->value('name');
            $name = trim($name);
        }

        $key = strtolower($name);

        return match (true) {
            $key === 'wrong number' => DirectoryPhoneStatus::STATUS_WRONG_NUMBER,
            $key === 'unreachable' => DirectoryPhoneStatus::STATUS_NO_ANSWER,
            $key === 'no answer' => DirectoryPhoneStatus::STATUS_NO_ANSWER,
            $key === 'busy' => DirectoryPhoneStatus::STATUS_BUSY,
            $key === 'switched off' => DirectoryPhoneStatus::STATUS_SWITCHED_OFF,
            $key === 'call back' => DirectoryPhoneStatus::STATUS_CALLBACK,
            $key === 'do not call' => DirectoryPhoneStatus::STATUS_WRONG_NUMBER,
            default => null,
        };
    }

    public function openDirectory(string $directoryId): void
    {
        $this->authorize('call-center-render');

        $directory = Directory::query()
            ->with(['party:id,short_name,name', 'subConsite:id,code,name', 'phoneStatuses'])
            ->find($directoryId);

        if (!$directory) return;

        $this->selectedDirectory = $directory;
        $this->selectedDirectoryId = (string) $directory->id;
        $this->showDetailsModal = true;

        // Default to Form tab when modal opens
        $this->activeModalTab = 'cc_form';

        // Load modal data
        $this->loadElectionDirectoryCallStatusForSelected();
        $this->loadSubStatusAttemptsForSelected();
        $this->loadPhoneCallStatusesForSelected();
        $this->loadNotesForSelected();
        $this->loadRequestsForSelected();
        $this->loadCallCenterFormForSelected();
        $this->refreshHistory();

        // Reset create forms
        $this->newNoteText = '';
        $this->newRequestTypeId = '';
        $this->newRequestAmount = null;
        $this->newRequestNote = '';

        // Ensure a sane visible attempt count
        if (($this->visibleAttempts ?? 0) < 0) {
            $this->visibleAttempts = 0;
        }

        $this->modalPresentUsers = [];

        // Ask browser to join presence channel for this directory
        $this->dispatch('cc-presence-join', electionId: (string) $this->activeElectionId, directoryId: (string) $this->selectedDirectoryId);

        $this->dispatch('open-call-center-directory-modal');
    }

    public function closeDirectoryModal(): void
    {
        // Ask browser to leave the presence channel
        if ($this->activeElectionId && $this->selectedDirectoryId) {
            $this->dispatch('cc-presence-leave', electionId: (string) $this->activeElectionId, directoryId: (string) $this->selectedDirectoryId);
        }

        $this->showDetailsModal = false;
        $this->selectedDirectory = null;
        $this->selectedDirectoryId = null;

        $this->selectedNotes = [];
        $this->selectedRequests = [];
        $this->selectedHistory = [];

        $this->resetSubStatusAttempts();
        $this->phoneCallStatuses = [];
        $this->phoneCallNotes = [];
        $this->resetCallCenterFormState();

        $this->modalPresentUsers = [];

        $this->dispatch('close-call-center-directory-modal');
    }

    public function updateSubStatusAttemptNotes(string $attempt): void
    {
        $this->authorize('call-center-render');

        $attemptInt = (int) $attempt;
        if (!in_array($attemptInt, ElectionDirectoryCallSubStatus::ATTEMPTS, true)) return;

        $subStatusId = (string) ($this->subStatusAttempts[(string)$attemptInt]['sub_status_id'] ?? '');
        $this->updateSubStatusAttemptStatus((string)$attemptInt, $subStatusId);
    }

    public function clearAttempt(string $attempt): void
    {
        $this->authorize('call-center-render');

        $attemptInt = (int) $attempt;
        if (!in_array($attemptInt, ElectionDirectoryCallSubStatus::ATTEMPTS, true)) return;

        // Clearing by calling the same saver with empty values.
        $this->subStatusAttempts[(string)$attemptInt]['sub_status_id'] = '';
        $this->subStatusAttempts[(string)$attemptInt]['notes'] = '';
        $this->updateSubStatusAttemptStatus((string)$attemptInt, '');
    }

    public function submitNote(): void
    {
        $this->authorize('call-center-render');

        if (!$this->selectedDirectory || !$this->activeElectionId) return;

        $this->validate([
            'newNoteText' => ['required', 'string', 'max:4000'],
        ]);

        VoterNote::query()->create([
            'directory_id' => (string) $this->selectedDirectory->id,
            'election_id' => (string) $this->activeElectionId,
            'note' => $this->newNoteText,
            'created_by' => auth()->id(),
        ]);

        $this->newNoteText = '';

        // Reload notes list for the modal
        $this->loadNotesForSelected();

        // Force inner modal fragment to re-render
        $this->modalRenderTick++;

        $this->logEvent('Note Created', 'Call center note created');
        $this->refreshHistory();

        VoterDataChanged::dispatch(
            'call_center_note_created',
            (string) $this->selectedDirectory->id,
            (string) $this->activeElectionId
        );
    }

    public function submitRequest(): void
    {
        $this->authorize('call-center-render');

        if (!$this->selectedDirectory || !$this->activeElectionId) return;

        $this->validate([
            'newRequestTypeId' => ['required'],
            'newRequestNote' => ['nullable', 'string', 'max:1000'],
        ]);

        $req = VoterRequest::query()->create([
            'directory_id' => (string) $this->selectedDirectory->id,
            'election_id' => (string) $this->activeElectionId,
            'request_type_id' => $this->newRequestTypeId,
            'amount' => $this->newRequestAmount, // kept for compatibility even if hidden in UI
            'note' => $this->newRequestNote !== '' ? $this->newRequestNote : null,
            'created_by' => auth()->id(),
        ]);

        // Reset form inputs
        $this->newRequestTypeId = '';
        $this->newRequestAmount = null;
        $this->newRequestNote = '';

        // Reload requests list for the modal
        $this->loadRequestsForSelected();

        // Force inner modal fragment to re-render
        $this->modalRenderTick++;

        $this->logEvent('Request Created', 'Call center request created', ['request_id' => (string) $req->id]);
        $this->refreshHistory();

        VoterDataChanged::dispatch(
            'call_center_request_created',
            (string) $this->selectedDirectory->id,
            (string) $this->activeElectionId
        );
    }

    private function loadNotesForSelected(): void
    {
        $this->selectedNotes = [];

        if (!$this->selectedDirectory || !$this->activeElectionId) return;

        $this->selectedNotes = VoterNote::query()
            ->with(['author:id,name'])
            ->where('directory_id', (string) $this->selectedDirectory->id)
            ->where('election_id', (string) $this->activeElectionId)
            ->latest()
            ->limit(200)
            ->get(['id', 'note', 'created_at', 'created_by'])
            ->map(fn($n) => [
                'id' => (string) $n->id,
                'note' => (string) $n->note,
                'created_at_human' => $n->created_at?->diffForHumans(),
                'author' => $n->author?->name ?? null,
            ])
            ->all();
    }

    private function loadRequestsForSelected(): void
    {
        $this->selectedRequests = [];

        if (!$this->selectedDirectory || !$this->activeElectionId) return;

        $this->selectedRequests = VoterRequest::query()
            ->with(['type:id,name', 'author:id,name'])
            ->where('directory_id', (string) $this->selectedDirectory->id)
            ->where('election_id', (string) $this->activeElectionId)
            ->latest()
            ->limit(200)
            ->get(['id', 'request_type_id', 'request_number', 'status', 'amount', 'note', 'created_by', 'created_at'])
            ->map(fn($r) => [
                'id' => (string) $r->id,
                'type' => $r->type?->name ?? null,
                'request_number' => (string) ($r->request_number ?? ''),
                'status' => (string) ($r->status ?? ''),
                'amount' => $r->amount,
                'note' => (string) ($r->note ?? ''),
                'author' => $r->author?->name ?? null,
                'created_at_human' => $r->created_at?->diffForHumans(),
            ])
            ->all();
    }

    private function resetCallCenterFormState(): void
    {
        $this->ccForm = [
            'q1_performance' => null,
            'q2_reason' => null,
            'q3_support' => null,
            'q4_voting_area' => null,
            'q4_other_text' => null,
            'q5_help_needed' => null,
            'q6_message_to_mayor' => null,
        ];
    }

    /**
     * Load call center form for the currently selected directory.
     */
    private function loadCallCenterFormForSelected(): void
    {
        $this->resetCallCenterFormState();

        if (!$this->selectedDirectory || !$this->activeElectionId) return;

        $row = CallCenterForm::query()
            ->where('election_id', (string) $this->activeElectionId)
            ->where('directory_id', (string) $this->selectedDirectory->id)
            ->first();

        if (!$row) return;

        $this->ccForm = [
            'q1_performance' => $row->q1_performance,
            'q2_reason' => $row->q2_reason,
            'q3_support' => $row->q3_support,
            'q4_voting_area' => $row->q4_voting_area,
            'q4_other_text' => $row->q4_other_text,
            'q5_help_needed' => $row->q5_help_needed,
            'q6_message_to_mayor' => $row->q6_message_to_mayor,
        ];
    }

    /**
     * Websocket listeners (via Laravel Echo + Livewire).
     * When any user updates a voter in Call Center, other users will refresh.
     */
    // NOTE: Use dynamic listeners so we can safely add fallbacks.
    public function getListeners(): array
    {
        return [
            'echo:elections.voters,VoterDataChanged' => 'handleVoterDataChanged',
            'reverb-voter-update' => 'handleVoterDataChanged',
            'window:voter-data-updated' => 'handleVoterDataChanged',
            'presence-sync' => 'syncPresenceUsers',
        ];
    }

    /**
     * Receive presence members list from the browser.
     */
    public function syncPresenceUsers($users = []): void
    {
        \Log::info('CallCenter presence sync received', [
            'selectedDirectoryId' => (string)($this->selectedDirectoryId ?? ''),
            'count' => is_array($users) ? count($users) : null,
            'sample' => is_array($users) ? array_slice($users, 0, 2) : $users,
        ]);

        if (!is_array($users)) {
            $users = [];
        }

        // Normalize
        $this->modalPresentUsers = collect($users)
            ->map(fn($u) => [
                'id' => (string)($u['id'] ?? ''),
                'name' => (string)($u['name'] ?? ''),
                'profile_picture' => $u['profile_picture'] ?? null,
            ])
            ->filter(fn($u) => $u['id'] !== '')
            ->values()
            ->all();

        $this->modalRenderTick++;
    }

    public function handleVoterDataChanged($payload = null): void
    {
        // Normalize payload into array
        if ($payload === null) {
            $payload = [];
        } elseif (is_string($payload)) {
            $decoded = json_decode($payload, true);
            $payload = json_last_error() === JSON_ERROR_NONE ? $decoded : ['raw' => $payload];
        } elseif (!is_array($payload)) {
            $payload = (array) $payload;
        }

        \Log::info('CallCenter realtime payload received: ' . json_encode($payload, JSON_UNESCAPED_UNICODE));

        $voterId = (string)($payload['voter_id'] ?? ($payload['voterId'] ?? ''));
        $electionId = (string)($payload['election_id'] ?? ($payload['electionId'] ?? ''));

        // If broadcast payload is empty/missing ids, still refresh the currently open modal.
        // (Some Echo drivers + Livewire echo listener combos don't pass broadcastWith() payload.)
        $shouldRefreshOpenModal = ($this->showDetailsModal && $this->selectedDirectoryId)
            && ($voterId === '' || $voterId === (string) $this->selectedDirectoryId);

        // Refresh list totals/table when current election matches (or election id not provided)
        if ($this->activeElectionId && $electionId && $electionId !== (string)$this->activeElectionId) {
            return;
        }

        if ($shouldRefreshOpenModal) {
            // Refresh the model itself so bindings in the modal update
            $this->selectedDirectory = Directory::query()
                ->with(['party:id,short_name,name', 'subConsite:id,code,name', 'phoneStatuses'])
                ->find($this->selectedDirectoryId);

            $this->loadElectionDirectoryCallStatusForSelected();
            $this->loadSubStatusAttemptsForSelected();
            $this->loadPhoneCallStatusesForSelected();
            $this->loadNotesForSelected();
            $this->loadRequestsForSelected();
            $this->loadCallCenterFormForSelected();
            $this->refreshHistory();

            $this->modalRenderTick++;
        }

        // Trigger re-render so list badges/totals update. Avoid full refresh while modal is open.
        if (!$this->showDetailsModal) {
            $this->dispatch('$refresh');
        }
    }

    public function render()
    {
        $this->authorize('call-center-render');

        $allowed = $this->allowedSubConsiteIds();

        // Totals for all directories visible to this user (respect filters/search)
        $allDirIdsQuery = Directory::query()
            ->where('status', 'Active')
            ->whereIn('sub_consite_id', $allowed)
            ->when($this->filterSubConsiteId, fn($q) => $q->where('sub_consite_id', $this->filterSubConsiteId))
            ->when($this->search, function ($q) {
                $term = trim($this->search);
                $q->where(function ($qq) use ($term) {
                    $qq->where('name', 'like', '%' . $term . '%')
                        ->orWhere('id_card_number', 'like', '%' . $term . '%')
                        ->orWhere('serial', 'like', '%' . $term . '%')
                        ->orWhere('phones', 'like', '%' . $term . '%')
                        ->orWhere('address', 'like', '%' . $term . '%');
                });
            });

        $allDirectoryIds = $allDirIdsQuery->pluck('id')->map(fn($v) => (string)$v)->all();
        $totalAll = count($allDirectoryIds);
        $totalCompleted = 0;
        $totalCompletedByMe = 0;
        $totalPending = $totalAll;

        if ($this->activeElectionId && $totalAll) {
            $statuses = ElectionDirectoryCallStatus::query()
                ->where('election_id', (string) $this->activeElectionId)
                ->whereIn('directory_id', $allDirectoryIds)
                ->get(['directory_id', 'status', 'updated_by']);

            $totalCompleted = $statuses->where('status', 'completed')->count();
            $totalCompletedByMe = $statuses
                ->where('status', 'completed')
                ->where('updated_by', auth()->id())
                ->count();

            $totalPending = max(0, $totalAll - $totalCompleted);
        }

        $subConsites = SubConsite::query()
            ->whereIn('id', $allowed)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $directories = Directory::query()
            ->with(['party:id,short_name,name', 'subConsite:id,code,name'])
            ->where('status', 'Active')
            ->whereIn('sub_consite_id', $allowed)
            ->when($this->filterSubConsiteId, fn($q) => $q->where('sub_consite_id', $this->filterSubConsiteId))
            ->when($this->search, function ($q) {
                $term = trim($this->search);
                $q->where(function ($qq) use ($term) {
                    $qq->where('name', 'like', '%' . $term . '%')
                        ->orWhere('id_card_number', 'like', '%' . $term . '%')
                        ->orWhere('serial', 'like', '%' . $term . '%')
                        ->orWhere('phones', 'like', '%' . $term . '%')
                        ->orWhere('address', 'like', '%' . $term . '%');
                });
            })
            ->orderByRaw("COALESCE(NULLIF(address,''), 'zzz') asc")
            ->orderBy('name')
            ->paginate($this->perPage);

        $dirIds = $directories->getCollection()->pluck('id')->map(fn($v) => (string)$v)->all();

        $listStatuses = [];
        $listSubStatuses = [];

        if ($this->activeElectionId && count($dirIds)) {
            $listStatuses = ElectionDirectoryCallStatus::query()
                ->where('election_id', (string) $this->activeElectionId)
                ->whereIn('directory_id', $dirIds)
                ->get(['directory_id', 'status', 'updated_by'])
                ->mapWithKeys(fn($r) => [
                    (string)$r->directory_id => [
                        'status' => (string)($r->status ?? ''),
                        'updated_by' => (string)($r->updated_by ?? ''),
                    ],
                ])
                ->all();

            // latest attempt sub-status: pick greatest attempt per directory
            $rows = ElectionDirectoryCallSubStatus::query()
                ->where('election_id', (string) $this->activeElectionId)
                ->whereIn('directory_id', $dirIds)
                ->orderBy('attempt')
                ->get(['directory_id', 'attempt', 'sub_status_id']);

            foreach ($rows as $r) {
                $did = (string)$r->directory_id;
                $listSubStatuses[$did] = [
                    'attempt' => (int) $r->attempt,
                    'sub_status_id' => (string) ($r->sub_status_id ?? ''),
                ];
            }
        }

        return view('livewire.call-center.call-center', [
            'directories' => $directories,
            'subConsites' => $subConsites,
            'directoryImageUrls' => $directories->getCollection()->mapWithKeys(fn($d) => [$d->id => $this->directoryImageUrl($d)]),
            'listStatuses' => $listStatuses,
            'listSubStatuses' => $listSubStatuses,
            'activeSubStatuses' => $this->activeSubStatuses,
            'totalsAll' => $totalAll,
            'totalsCompleted' => $totalCompleted,
            'totalsPending' => $totalPending,
            'totalsCompletedByMe' => $totalCompletedByMe,
        ])->layout('layouts.master');
    }
}
