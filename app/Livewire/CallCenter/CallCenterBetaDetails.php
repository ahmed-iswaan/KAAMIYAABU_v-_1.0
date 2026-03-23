<?php

namespace App\Livewire\CallCenter;

use App\Models\CallCenterForm;
use App\Models\Directory;
use App\Models\DirectoryPhoneStatus;
use App\Models\Election;
use App\Models\ElectionDirectoryCallStatus;
use App\Models\ElectionDirectoryCallSubStatus;
use App\Models\SubStatus;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CallCenterBetaDetails extends Component
{
    use AuthorizesRequests;

    public string $directoryId;

    public ?string $activeElectionId = null;

    /**
     * Active Sub Status options for mapping UUID => name.
     * Format: [id => name]
     */
    public array $activeSubStatuses = [];

    public array $subStatusAttempts = []; // attempt => ['sub_status_id' => ?, 'notes' => ?, 'phone_number' => ?]
    public int $visibleAttempts = 0;

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

    public array $modalPresentUsers = [];
    public int $modalRenderTick = 0;

    /**
     * Phone status map keyed by normalized phone number.
     * Format: [normPhone => ['status'=>..., 'sub_status_id'=>..., 'notes'=>...]]
     */
    public array $phoneStatuses = [];

    public function mount(string $directory): void
    {
        $this->authorize('call-center-render');

        $this->directoryId = $directory;

        $this->activeElectionId = Election::query()
            ->where('status', Election::STATUS_ACTIVE)
            ->value('id');

        $this->activeSubStatuses = SubStatus::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->mapWithKeys(fn($s) => [(string) $s->id => (string) $s->name])
            ->all();

        $this->ensurePermittedDirectory($this->directoryId);
        $this->loadCallCenterForm();
        $this->loadSubStatusAttempts();

        // Load phone statuses for initial render
        $dir = Directory::query()->with(['phoneStatuses'])->find($this->directoryId);
        $map = [];
        if ($dir) {
            foreach ($dir->phoneStatuses as $row) {
                $norm = DirectoryPhoneStatus::normalizePhone($row->phone);
                if (!$norm) continue;
                $map[$norm] = [
                    'status' => (string) ($row->status ?? DirectoryPhoneStatus::STATUS_NOT_CALLED),
                    'sub_status_id' => (string) ($row->sub_status_id ?? ''),
                    'notes' => (string) ($row->notes ?? ''),
                ];
            }
        }
        $this->phoneStatuses = $map;

        $this->modalPresentUsers = [];
        $this->modalRenderTick = 0;
    }

    protected function allowedSubConsiteIds(): array
    {
        return Auth::user()?->subConsites()->pluck('sub_consites.id')->all() ?? [];
    }

    protected function ensurePermittedDirectory(string $directoryId): void
    {
        $allowed = $this->allowedSubConsiteIds();

        $ok = Directory::query()
            ->where('id', $directoryId)
            ->where('status', 'Active')
            ->whereIn('sub_consite_id', $allowed)
            ->exists();

        abort_if(!$ok, 403);
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

    private function loadCallCenterForm(): void
    {
        $this->resetCallCenterFormState();

        if (!$this->activeElectionId || !$this->directoryId) return;

        $row = CallCenterForm::query()
            ->where('election_id', (string) $this->activeElectionId)
            ->where('directory_id', (string) $this->directoryId)
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

    public function saveCallCenterForm(): void
    {
        $this->authorize('call-center-render');

        if (!$this->activeElectionId || !$this->directoryId) return;

        $this->validate([
            'ccForm.q1_performance' => ['nullable', 'in:kamudhey,kamunudhey,neyngey,mixed'],
            'ccForm.q2_reason' => ['nullable', 'string', 'max:4000'],
            'ccForm.q3_support' => ['nullable', 'in:aanekey,noonekay,neyngey,vote_laan_nudhaanan'],
            'ccForm.q4_voting_area' => ['nullable', 'in:male,vilimale,hulhumale_phase1,hulhumale_phase2,other,unknown'],
            'ccForm.q4_other_text' => ['nullable', 'string', 'max:255'],
            'ccForm.q5_help_needed' => ['nullable', 'in:yes,no,maybe'],
            'ccForm.q6_message_to_mayor' => ['nullable', 'string', 'max:4000'],
        ]);

        $row = CallCenterForm::query()->firstOrNew([
            'election_id' => (string) $this->activeElectionId,
            'directory_id' => (string) $this->directoryId,
        ]);

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

        // Auto-complete directory status when required form answers exist (Q1 + Q3)
        $q1 = trim((string)($this->ccForm['q1_performance'] ?? ''));
        $q3 = trim((string)($this->ccForm['q3_support'] ?? ''));
        $formIsComplete = ($q1 !== '') && ($q3 !== '');

        if ($formIsComplete) {
            ElectionDirectoryCallStatus::query()->updateOrCreate([
                'election_id' => (string) $this->activeElectionId,
                'directory_id' => (string) $this->directoryId,
            ], [
                'status' => ElectionDirectoryCallStatus::STATUS_COMPLETED,
                'updated_by' => auth()->id(),
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Livewire hook: auto-save call center form any time a field changes.
     */
    public function updatedCcForm($value, string $key): void
    {
        if (!$this->activeElectionId || !$this->directoryId) return;

        if ($key === 'q4_voting_area' && ($value ?? null) !== 'other') {
            $this->ccForm['q4_other_text'] = null;
        }

        if (in_array($key, ['q1_performance', 'q3_support'], true) && ($value === '' || $value === null)) {
            return;
        }

        try {
            $this->saveCallCenterForm();
            // Broadcast event for real-time updates in list view
            event(new \App\Events\VoterDataChanged(
                'form-updated',
                (string) $this->directoryId,
                (string) $this->activeElectionId,
                [
                    'ccForm' => $this->ccForm,
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()?->name,
                ]
            ));
        } catch (\Throwable $e) {
            \Log::error('CallCenterBetaDetails form auto-save failed', [
                'directory_id' => $this->directoryId,
                'election_id' => $this->activeElectionId,
                'field' => $key,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function resetSubStatusAttempts(): void
    {
        $this->subStatusAttempts = [];
        for ($a = 1; $a <= 10; $a++) {
            $this->subStatusAttempts[(string)$a] = ['sub_status_id' => '', 'notes' => '', 'phone_number' => ''];
        }
    }

    private function loadSubStatusAttempts(): void
    {
        $this->resetSubStatusAttempts();

        if (!$this->activeElectionId || !$this->directoryId) return;

        $rows = ElectionDirectoryCallSubStatus::query()
            ->where('election_id', (string) $this->activeElectionId)
            ->where('directory_id', (string) $this->directoryId)
            ->orderBy('attempt')
            ->get(['attempt', 'sub_status_id', 'phone_number', 'notes']);

        $maxAttemptWithData = 0;
        foreach ($rows as $r) {
            $a = (int) $r->attempt;
            if ($a < 1 || $a > 10) continue;

            $this->subStatusAttempts[(string)$a] = [
                'sub_status_id' => (string) ($r->sub_status_id ?? ''),
                'phone_number' => (string) ($r->phone_number ?? ''),
                'notes' => (string) ($r->notes ?? ''),
            ];

            $maxAttemptWithData = max($maxAttemptWithData, $a);
        }

        $this->visibleAttempts = max($maxAttemptWithData, 1);
    }

    /**
     * Check if an attempt is considered 'submitted' (has a selected sub status).
     */
    private function attemptIsSubmitted(int $attempt): bool
    {
        $ss = (string)($this->subStatusAttempts[(string)$attempt]['sub_status_id'] ?? '');
        return trim($ss) !== '';
    }

    public function addAttempt(): void
    {
        $this->authorize('call-center-render');
        if (!$this->directoryId || !$this->activeElectionId) return;

        // Must submit the current last attempt before adding a new one
        $current = (int) ($this->visibleAttempts ?? 1);
        if ($current >= 1 && $current <= 10 && !$this->attemptIsSubmitted($current)) {
            $this->dispatch('swal', [
                'title' => 'Submit current attempt first',
                'text' => 'Please select a Sub status and click Save for the current attempt before adding a new attempt.',
                'icon' => 'warning',
                'buttonsStyling' => false,
                'confirmButtonText' => 'Ok',
                'confirmButton' => 'btn btn-primary',
            ]);
            return;
        }

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

    private function defaultAttemptPhone(): string
    {
        $directory = Directory::query()->findOrFail($this->directoryId);
        $phones = is_array($directory->phones) ? array_values(array_filter($directory->phones)) : [];
        if (!count($phones) && is_string($directory->phones ?? null)) {
            $phones = array_values(array_filter(array_map('trim', preg_split('/[\s,\/]+/', (string) $directory->phones))));
        }
        $phonesNorm = array_map(fn($p) => DirectoryPhoneStatus::normalizePhone((string)$p), $phones);
        $phonesNorm = array_values(array_filter($phonesNorm));
        return $phonesNorm[0] ?? '';
    }

    public function updateSubStatusAttemptStatus(string $attempt): void
    {
        $this->authorize('call-center-render');

        $attemptInt = (int) $attempt;
        if (!in_array($attemptInt, ElectionDirectoryCallSubStatus::ATTEMPTS, true)) return;
        if (!$this->activeElectionId || !$this->directoryId) return;

        $status = (string)($this->subStatusAttempts[(string)$attemptInt]['sub_status_id'] ?? '');
        $notes = (string)($this->subStatusAttempts[(string)$attemptInt]['notes'] ?? '');
        $phone = (string)($this->subStatusAttempts[(string)$attemptInt]['phone_number'] ?? '');

        // Ensure we always store a normalized phone (and default to first phone if none selected)
        $phoneNorm = DirectoryPhoneStatus::normalizePhone($phone);
        if ($phoneNorm === '') {
            $phoneNorm = $this->defaultAttemptPhone();
        }
        $this->subStatusAttempts[(string)$attemptInt]['phone_number'] = $phoneNorm;

        ElectionDirectoryCallSubStatus::query()->updateOrCreate([
            'election_id' => (string) $this->activeElectionId,
            'directory_id' => (string) $this->directoryId,
            'attempt' => $attemptInt,
        ], [
            'sub_status_id' => $status ?: null,
            'phone_number' => $phoneNorm !== '' ? $phoneNorm : null,
            'notes' => $notes ?: null,
            'updated_by' => auth()->id(),
        ]);

        // Update phone status if phone number is set
        if ($phoneNorm !== '') {
            // $status is expected to be a SubStatus UUID. Some old values may be legacy codes.
            $subStatusId = $status;
            $subStatusName = '';
            if ($subStatusId !== '') {
                $subStatusName = strtolower(trim((string)($this->activeSubStatuses[$subStatusId] ?? '')));
                if ($subStatusName === '') {
                    $subStatusName = strtolower(trim((string) SubStatus::query()->whereKey($subStatusId)->value('name')));
                }
            }

            // Map attempt sub-status name -> per-phone legacy status code (same intent as main call center)
            $mappedStatus = DirectoryPhoneStatus::STATUS_NOT_CALLED;
            if ($subStatusName !== '') {
                if (str_contains($subStatusName, 'wrong number')) {
                    $mappedStatus = DirectoryPhoneStatus::STATUS_WRONG_NUMBER;
                } elseif (str_contains($subStatusName, 'call back') || str_contains($subStatusName, 'callback')) {
                    $mappedStatus = DirectoryPhoneStatus::STATUS_CALLBACK;
                } elseif (str_contains($subStatusName, 'busy')) {
                    $mappedStatus = DirectoryPhoneStatus::STATUS_BUSY;
                } elseif (str_contains($subStatusName, 'switched off') || str_contains($subStatusName, 'switched_off')) {
                    $mappedStatus = DirectoryPhoneStatus::STATUS_SWITCHED_OFF;
                } elseif (str_contains($subStatusName, 'no answer') || str_contains($subStatusName, 'no_answer') || str_contains($subStatusName, 'unreachable')) {
                    $mappedStatus = DirectoryPhoneStatus::STATUS_NO_ANSWER;
                }
            }

            // Keep the exact selection (UUID) on the phone row so UI shows same substatus label
            DirectoryPhoneStatus::query()->updateOrCreate(
                [
                    'directory_id' => (string) $this->directoryId,
                    'phone' => $phoneNorm,
                ],
                [
                    'status' => $mappedStatus,
                    'sub_status_id' => $subStatusId ?: null,
                    'notes' => $notes ?: null,
                    'last_called_at' => now(),
                    'last_called_by' => auth()->id(),
                ]
            );

            // Reload phoneStatuses for the directory (store as normalized map for the Blade)
            $directory = Directory::query()
                ->with(['phoneStatuses'])
                ->findOrFail($this->directoryId);

            $map = [];
            foreach ($directory->phoneStatuses as $row) {
                $norm = DirectoryPhoneStatus::normalizePhone($row->phone);
                if (!$norm) continue;
                $map[$norm] = [
                    'status' => (string) ($row->status ?? DirectoryPhoneStatus::STATUS_NOT_CALLED),
                    'sub_status_id' => (string) ($row->sub_status_id ?? ''),
                    'notes' => (string) ($row->notes ?? ''),
                ];
            }
            $this->phoneStatuses = $map;
        }

        // Broadcast event for real-time updates in list view
        event(new \App\Events\VoterDataChanged(
            'attempt-updated',
            (string) $this->directoryId,
            (string) $this->activeElectionId,
            [
                'attempt' => $attemptInt,
                'sub_status_id' => $status,
                'phone_number' => $phoneNorm,
                'notes' => $notes,
            ]
        ));

        // Update visible attempts if needed
        $this->visibleAttempts = max($this->visibleAttempts, $attemptInt);

        // Auto-complete directory if sub status matches required values
        $subStatusName = '';
        if ($status !== '') {
            $subStatusName = strtolower(trim((string)($this->activeSubStatuses[$status] ?? '')));
            if ($subStatusName === '') {
                $subStatusName = strtolower(trim((string) SubStatus::query()->whereKey($status)->value('name')));
            }
        }
        $autoCompleteNames = [
            'phone hung up',
            'wrong number',
            'would decide after speaking with mayor',
            'deceased',
        ];
        if (in_array($subStatusName, $autoCompleteNames, true)) {
            ElectionDirectoryCallStatus::query()->updateOrCreate([
                'election_id' => (string) $this->activeElectionId,
                'directory_id' => (string) $this->directoryId,
            ], [
                'status' => ElectionDirectoryCallStatus::STATUS_COMPLETED,
                'updated_by' => auth()->id(),
                'completed_at' => now(),
            ]);
        }

        // Force reload of phoneStatuses for the directory
        $this->render();
    }

    public function clearAttempt(string $attempt): void
    {
        $this->authorize('call-center-clear-attempt');

        $attemptInt = (int) $attempt;
        if (!in_array($attemptInt, ElectionDirectoryCallSubStatus::ATTEMPTS, true)) return;
        if (!$this->activeElectionId || !$this->directoryId) return;

        ElectionDirectoryCallSubStatus::query()->where([
            'election_id' => (string) $this->activeElectionId,
            'directory_id' => (string) $this->directoryId,
            'attempt' => $attemptInt,
        ])->delete();

        $this->subStatusAttempts[(string)$attemptInt]['sub_status_id'] = '';
        $this->subStatusAttempts[(string)$attemptInt]['notes'] = '';
        $this->subStatusAttempts[(string)$attemptInt]['phone_number'] = '';
    }

    /**
     * Websocket listeners (via Laravel Echo + Livewire).
     * When any user updates a voter in Call Center, other users will refresh.
     */
    public function getListeners(): array
    {
        return [
            'echo:elections.voters,VoterDataChanged' => 'handleVoterDataChanged',
            'reverb-voter-update' => 'handleVoterDataChanged',
            'window:voter-data-updated' => 'handleVoterDataChanged',
            'presence-sync' => 'syncPresenceUsers',
        ];
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

        \Log::info('CallCenterBetaDetails realtime payload received: ' . json_encode($payload, JSON_UNESCAPED_UNICODE));

        $directoryId = (string)($payload['directory_id'] ?? ($payload['directoryId'] ?? ''));
        $electionId = (string)($payload['election_id'] ?? ($payload['electionId'] ?? ''));

        // Refresh only if current directory matches
        if ($this->activeElectionId && $electionId && $electionId !== (string)$this->activeElectionId) {
            return;
        }
        if ($directoryId === '' || $directoryId === (string) $this->directoryId) {
            $this->loadSubStatusAttempts();

            // also refresh phone statuses so UI updates in realtime
            $directory = Directory::query()->with(['phoneStatuses'])->find($this->directoryId);
            if ($directory) {
                $map = [];
                foreach ($directory->phoneStatuses as $row) {
                    $norm = DirectoryPhoneStatus::normalizePhone($row->phone);
                    if (!$norm) continue;
                    $map[$norm] = [
                        'status' => (string) ($row->status ?? DirectoryPhoneStatus::STATUS_NOT_CALLED),
                        'sub_status_id' => (string) ($row->sub_status_id ?? ''),
                        'notes' => (string) ($row->notes ?? ''),
                    ];
                }
                $this->phoneStatuses = $map;
            }

            $this->render();
        }
    }

    public function syncPresenceUsers($users = []): void
    {
        if (!is_array($users)) {
            $users = [];
        }
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

    public function render()
    {
        $this->authorize('call-center-render');

        $directory = Directory::query()
            ->with([
                'subConsite:id,code,name',
                'property:id,name',
                'currentProperty:id,name',
                'party:id,short_name,name',
                'phoneStatuses',
            ])
            ->findOrFail($this->directoryId);

        $callStatus = null;
        if ($this->activeElectionId) {
            $callStatus = ElectionDirectoryCallStatus::query()
                ->where('election_id', (string) $this->activeElectionId)
                ->where('directory_id', (string) $directory->id)
                ->first();
        }

        $lastAttempt = null;
        $allAttempts = [];
        if ($this->activeElectionId) {
            $rows = ElectionDirectoryCallSubStatus::query()
                ->where('election_id', (string) $this->activeElectionId)
                ->where('directory_id', (string) $directory->id)
                ->orderBy('attempt')
                ->get(['attempt', 'sub_status_id', 'phone_number', 'notes', 'updated_at', 'updated_by']);

            foreach ($rows as $r) {
                $attemptData = [
                    'attempt' => (int) $r->attempt,
                    'sub_status_id' => (string) ($r->sub_status_id ?? ''),
                    'phone_number' => (string) ($r->phone_number ?? ''),
                    'notes' => (string) ($r->notes ?? ''),
                    'updated_at' => $r->updated_at,
                    'updated_by' => (string) ($r->updated_by ?? ''),
                ];
                $allAttempts[] = $attemptData;
                $lastAttempt = $attemptData;
            }
        }

        // Directory image (match classic logic, but single item)
        $imgUrl = null;
        if (!empty($directory->profile_picture)) {
            $imgUrl = asset('storage/' . ltrim($directory->profile_picture, '/'));
        } else {
            $nid = trim((string) ($directory->id_card_number ?? ''));
            if ($nid !== '') {
                foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
                    $relative = "nid-images/{$nid}.{$ext}";
                    if (is_file(public_path($relative))) {
                        $imgUrl = asset($relative);
                        break;
                    }
                }
            }
        }

        $phones = is_array($directory->phones) ? array_values(array_filter($directory->phones)) : [];
        if (!count($phones) && is_string($directory->phones ?? null)) {
            $phones = array_values(array_filter(array_map('trim', preg_split('/[\s,\/]+/', (string) $directory->phones))));
        }

        // map phone statuses for display
        $phoneStatusMap = [];
        foreach ($directory->phoneStatuses as $row) {
            $norm = DirectoryPhoneStatus::normalizePhone($row->phone);
            if (!$norm) continue;
            $phoneStatusMap[$norm] = [
                'status' => (string) ($row->status ?? DirectoryPhoneStatus::STATUS_NOT_CALLED),
                'sub_status_id' => (string) ($row->sub_status_id ?? ''),
                'notes' => (string) ($row->notes ?? ''),
                'last_called_at' => $row->last_called_at,
            ];
        }

        return view('livewire.call-center.call-center-beta-details', [
            'directory' => $directory,
            'imgUrl' => $imgUrl,
            'callStatus' => $callStatus,
            'lastAttempt' => $lastAttempt,
            'allAttempts' => $allAttempts,
            'phones' => $phones,
            'phoneStatusMap' => $phoneStatusMap,
            'activeSubStatuses' => $this->activeSubStatuses,
            'visibleAttempts' => $this->visibleAttempts,
            'subStatusAttempts' => $this->subStatusAttempts,
            'phoneStatuses' => $this->phoneStatuses,
        ])->layout('layouts.master');
    }
}
