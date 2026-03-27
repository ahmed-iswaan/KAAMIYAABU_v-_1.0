<?php

namespace App\Livewire\CallCenter;

use App\Models\Directory;
use App\Models\Election;
use App\Models\ElectionDirectoryCallStatus;
use App\Models\ElectionDirectoryCallSubStatus;
use App\Models\SubConsite;
use App\Models\VoterProvisionalUserPledge;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CallCenterBeta extends Component
{
    use WithPagination, AuthorizesRequests, WithFileUploads;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';
    public string $filterSubConsiteId = '';
    public string $filterStatus = 'pending'; // pending|completed|all
    public int $perPage = 10;

    public bool $hideWithoutPhone = true;

    public ?string $activeElectionId = null;

    /**
     * Active Sub Status options for mapping UUID => name.
     * Format: [id => name]
     */
    public array $activeSubStatuses = [];

    /**
     * Cache image urls per-directory for the current request to avoid repeat filesystem checks.
     * @var array<string, string|null>
     */
    private array $imageUrlMemo = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'filterSubConsiteId' => ['except' => ''],
        'filterStatus' => ['except' => 'pending'],
        'perPage' => ['except' => 25],
        'hideWithoutPhone' => ['except' => true],
    ];

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterSubConsiteId(): void { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }
    public function updatedPerPage(): void { $this->resetPage(); }
    public function updatedHideWithoutPhone(): void { $this->resetPage(); }

    public function mount(): void
    {
        $this->activeElectionId = Election::query()
            ->where('status', Election::STATUS_ACTIVE)
            ->value('id');

        $this->activeSubStatuses = \App\Models\SubStatus::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->mapWithKeys(fn($s) => [(string) $s->id => (string) $s->name])
            ->all();

        // Permission gate: if user cannot show directories without phone, force filter ON.
        if (!auth()->user()?->can('call-center-show-without-phone')) {
            $this->hideWithoutPhone = true;
        }
    }

    protected function allowedSubConsiteIds(): array
    {
        return Auth::user()?->subConsites()->pluck('sub_consites.id')->all() ?? [];
    }

    /**
     * Resolve image URLs for a list of directories in bulk (prefer stored profile_picture, then nid-images).
     *
     * @param iterable<int, \App\Models\Directory> $dirs
     * @return array<string, string|null>
     */
    private function directoryImageUrlsFor(iterable $dirs): array
    {
        $out = [];

        $nidsToCheck = [];
        foreach ($dirs as $dir) {
            $id = (string) $dir->id;

            // 1) Stored profile picture
            if (!empty($dir->profile_picture)) {
                $out[$id] = asset('storage/' . ltrim($dir->profile_picture, '/'));
                continue;
            }

            // 2) Try memoized result
            if (array_key_exists($id, $this->imageUrlMemo)) {
                $out[$id] = $this->imageUrlMemo[$id];
                continue;
            }

            $nid = trim((string) ($dir->id_card_number ?? ''));
            if ($nid !== '') {
                $nidsToCheck[$id] = $nid;
            } else {
                $out[$id] = null;
                $this->imageUrlMemo[$id] = null;
            }
        }

        if (count($nidsToCheck)) {
            foreach ($nidsToCheck as $id => $nid) {
                $found = null;
                foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
                    $relative = "nid-images/{$nid}.{$ext}";
                    if (is_file(public_path($relative))) {
                        $found = asset($relative);
                        break;
                    }
                }
                $out[$id] = $found;
                $this->imageUrlMemo[$id] = $found;
            }
        }

        return $out;
    }

    public function render()
    {
        $this->authorize('call-center-render');

        // Hard enforce on every request as well, in case querystring tries to bypass.
        if (!auth()->user()?->can('call-center-show-without-phone')) {
            $this->hideWithoutPhone = true;
        }

        $allowed = $this->allowedSubConsiteIds();

        $subConsites = SubConsite::query()
            ->whereIn('id', $allowed)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        // Build one base query used for both totals and list (keep it in sync with list filters)
        $baseQuery = Directory::query()
            ->select([
                'id',
                'name',
                'id_card_number',
                'serial',
                'phones',
                'profile_picture',
                'address',
                'street_address',
                'properties_id',
                'current_address',
                'current_street_address',
                'current_properties_id',
                'sub_consite_id',
            ])
            ->where('status', 'Active')
            ->whereIn('sub_consite_id', $allowed)
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                    ->from('voter_provisional_user_pledges as vpup')
                    ->whereColumn('vpup.directory_id', 'directories.id');
            })
            ->when($this->filterSubConsiteId, fn($q) => $q->where('sub_consite_id', $this->filterSubConsiteId))
            ->when($this->hideWithoutPhone, function ($q) {
                $q->whereNotNull('phones')
                    ->whereRaw("TRIM(phones) <> ''")
                    ->whereRaw("TRIM(phones) <> '[]'")
                    ->whereRaw("TRIM(phones) <> '[ ]'")
                    ->whereRaw("TRIM(phones) <> '[null]'")
                    ->whereRaw("TRIM(phones) <> 'null'")
                    ->whereRaw("TRIM(phones) <> '{}' ")
                    ->whereRaw("phones REGEXP '[0-9]'");
            })
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

        // Summary totals (do NOT apply filterStatus; keep pending/completed counts always visible)
        $totalsPending = 0;
        $totalsCompleted = 0;
        $totalsCompletedByMe = 0;
        $totalsCompletedToday = 0;
        $totalsAttemptsToday = 0;
        $totalsAttemptsTotal = 0;
        $totalsCompletedTodayByMe = 0;
        $totalsAttemptsTodayByMe = 0;
        $totalsAttemptsTotalByMe = 0;

        if ($this->activeElectionId && count($allowed)) {
            $myUserId = (string) \Illuminate\Support\Facades\Auth::id();

            $completedExists = ElectionDirectoryCallStatus::query()
                ->where('election_id', (string) $this->activeElectionId)
                ->where('status', ElectionDirectoryCallStatus::STATUS_COMPLETED)
                ->whereColumn('directory_id', 'directories.id');

            $totalsCompleted = (clone $baseQuery)->whereExists($completedExists)->count();
            $totalsPending = (clone $baseQuery)->whereNotExists($completedExists)->count();

            $totalsCompletedByMe = (clone $baseQuery)->whereExists(
                 ElectionDirectoryCallStatus::query()
                     ->where('election_id', (string) $this->activeElectionId)
                     ->where('status', ElectionDirectoryCallStatus::STATUS_COMPLETED)
                     ->where('updated_by', $myUserId)
                     ->whereColumn('directory_id', 'directories.id')
             )->count();

            $totalsCompletedToday = (clone $baseQuery)->whereExists(
                ElectionDirectoryCallStatus::query()
                    ->where('election_id', (string) $this->activeElectionId)
                    ->where('status', ElectionDirectoryCallStatus::STATUS_COMPLETED)
                    ->whereRaw('DATE(COALESCE(completed_at, updated_at)) = ?', [now()->toDateString()])
                    ->whereColumn('directory_id', 'directories.id')
            )->count();

            $totalsCompletedTodayByMe = (clone $baseQuery)->whereExists(
                ElectionDirectoryCallStatus::query()
                    ->where('election_id', (string) $this->activeElectionId)
                    ->where('status', ElectionDirectoryCallStatus::STATUS_COMPLETED)
                    ->where('updated_by', $myUserId)
                    ->whereRaw('DATE(COALESCE(completed_at, updated_at)) = ?', [now()->toDateString()])
                    ->whereColumn('directory_id', 'directories.id')
            )->count();

            // Attempts totals:
            // - Total attempts (Attempts) = count of attempt rows
            // - Attempts Today (Attempts) = count of attempt rows updated today
            $attemptsBase = (clone $baseQuery)
                ->join('election_directory_call_sub_statuses as edcss', 'edcss.directory_id', '=', 'directories.id')
                ->where('edcss.election_id', (string) $this->activeElectionId);

            $totalsAttemptsTotal = (clone $attemptsBase)->count('edcss.id');

            $totalsAttemptsToday = (clone $attemptsBase)
                ->whereDate('edcss.updated_at', now()->toDateString())
                ->count('edcss.id');

            // My attempts (by updated_by on attempt rows)
            $totalsAttemptsTotalByMe = (clone $attemptsBase)
                ->where('edcss.updated_by', $myUserId)
                ->count('edcss.id');

            $totalsAttemptsTodayByMe = (clone $attemptsBase)
                ->where('edcss.updated_by', $myUserId)
                ->whereDate('edcss.updated_at', now()->toDateString())
                ->count('edcss.id');
        }

        // Apply status filter to LIST (totals intentionally ignore filterStatus)
        if ($this->activeElectionId) {
            if ($this->filterStatus === 'completed') {
                $baseQuery->whereExists(
                    ElectionDirectoryCallStatus::query()
                        ->where('election_id', (string) $this->activeElectionId)
                        ->where('status', ElectionDirectoryCallStatus::STATUS_COMPLETED)
                        ->whereColumn('directory_id', 'directories.id')
                );
            } elseif ($this->filterStatus === 'pending') {
                $baseQuery->whereNotExists(
                    ElectionDirectoryCallStatus::query()
                        ->where('election_id', (string) $this->activeElectionId)
                        ->where('status', ElectionDirectoryCallStatus::STATUS_COMPLETED)
                        ->whereColumn('directory_id', 'directories.id')
                );
            }
        }

        $directories = $baseQuery
            ->with([
                'subConsite:id,code,name',
                'property:id,name',
                'currentProperty:id,name',
            ])
            ->when($this->activeElectionId, function ($q) {
                // Not attempted first:
                // attempted_flag = 0 when NO rows exist in election_directory_call_sub_statuses for this directory + election
                // attempted_flag = 1 when at least one attempt exists
                $attemptExists = ElectionDirectoryCallSubStatus::query()
                    ->where('election_id', (string) $this->activeElectionId)
                    ->whereColumn('directory_id', 'directories.id');

                $q->orderByRaw('CASE WHEN EXISTS (' . $attemptExists->toSql() . ') THEN 1 ELSE 0 END asc', $attemptExists->getBindings());
            })
            ->orderByRaw("COALESCE(NULLIF(address,''), 'zzz') asc")
            ->orderBy('name')
            ->paginate($this->perPage);

        $directoryImageUrls = $this->directoryImageUrlsFor($directories->getCollection());

        $dirIds = $directories->getCollection()->pluck('id')->map(fn($v) => (string) $v)->all();
        $listStatuses = [];
        $listSubStatuses = [];

        if ($this->activeElectionId && count($dirIds)) {
            $listStatuses = ElectionDirectoryCallStatus::query()
                ->where('election_id', (string) $this->activeElectionId)
                ->whereIn('directory_id', $dirIds)
                ->get(['directory_id', 'status'])
                ->mapWithKeys(fn($r) => [(string) $r->directory_id => (string) ($r->status ?? '')])
                ->all();

            // latest attempt sub-status: pick greatest attempt per directory
            $rows = ElectionDirectoryCallSubStatus::query()
                ->where('election_id', (string) $this->activeElectionId)
                ->whereIn('directory_id', $dirIds)
                ->orderBy('attempt')
                ->get(['directory_id', 'attempt', 'sub_status_id']);

            foreach ($rows as $r) {
                $did = (string) $r->directory_id;
                $listSubStatuses[$did] = [
                    'attempt' => (int) $r->attempt,
                    'sub_status_id' => (string) ($r->sub_status_id ?? ''),
                ];
            }
        }

        return view('livewire.call-center.call-center-beta', [
            'directories' => $directories,
            'subConsites' => $subConsites,
            'listStatuses' => $listStatuses,
            'listSubStatuses' => $listSubStatuses,
            'activeSubStatuses' => $this->activeSubStatuses,
            'directoryImageUrls' => $directoryImageUrls,
            'totalsPending' => $totalsPending,
            'totalsCompleted' => $totalsCompleted,
            'totalsCompletedByMe' => $totalsCompletedByMe,
            'totalsCompletedToday' => $totalsCompletedToday,
            'totalsAttemptsToday' => $totalsAttemptsToday,
            'totalsAttemptsTotal' => $totalsAttemptsTotal,
            'totalsCompletedTodayByMe' => $totalsCompletedTodayByMe,
            'totalsAttemptsTodayByMe' => $totalsAttemptsTodayByMe,
            'totalsAttemptsTotalByMe' => $totalsAttemptsTotalByMe,
        ])->layout('layouts.master');
    }

    /** Import modal */
    public bool $showImportModal = false;
    public $importCsvFile = null; // Livewire\Features\SupportFileUploads\TemporaryUploadedFile
    public array $importErrors = [];
    public int $importSuccessCount = 0;

    public function openImportModal(): void
    {
        $this->showImportModal = true;
        $this->importCsvFile = null;
        $this->importErrors = [];
        $this->importSuccessCount = 0;
    }

    public function closeImportModal(): void
    {
        $this->showImportModal = false;
    }

    public function downloadImportSampleCsv(): StreamedResponse
    {
        $filename = 'call_center_beta_import_sample_' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            // Columns: user email, directory nid, q3 option, attempt phone, attempt status (sub status name), attempt note, directory status
            fputcsv($out, [
                'user_email',
                'directory_nid',
                'q3_support',
                'attempt_phone_number',
                'attempt_status',
                'attempt_note',
            ]);

            fputcsv($out, [
                'agent@example.com',
                'A123456',
                'aanekey',
                '7777777',
                'Unreachable',
                'No answer',
            ]);

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function normalizeImportHeader(string $h): string
    {
        $h = trim($h);
        // Strip UTF-8 BOM if present (common in Excel-saved CSVs)
        $h = preg_replace('/^\xEF\xBB\xBF/', '', $h) ?? $h;

        $h = mb_strtolower(trim($h));
        $h = str_replace([' ', '-', '\t'], '_', $h);
        $h = preg_replace('/_+/', '_', $h) ?: $h;
        return $h;
    }

    private function headerAliases(): array
    {
        return [
            // canonical => aliases
            'user_email' => ['email', 'useremail', 'user_email', 'user email', 'agent_email', 'agent email'],
            'directory_nid' => ['nid', 'nid_number', 'nid number', 'id_card_number', 'id card number', 'idcard', 'id_card'],
            'q3_support' => ['q3', 'q3 option', 'q3_option', 'support', 'support_option'],
            'attempt_phone_number' => ['attempt_phone', 'phone', 'phone_number', 'attempt phone number', 'attempt_phone'],
            'attempt_status' => ['sub_status', 'sub status', 'attempt sub status', 'attempt_sub_status', 'substatus'],
            'attempt_note' => ['note', 'notes', 'attempt_notes'],
        ];
    }

    private function mapSubStatusNameToPhoneStatus(string $subStatusName): string
    {
        $n = mb_strtolower(trim($subStatusName));
        if ($n === '') return \App\Models\DirectoryPhoneStatus::STATUS_NOT_CALLED;

        if (str_contains($n, 'wrong number')) {
            return \App\Models\DirectoryPhoneStatus::STATUS_WRONG_NUMBER;
        }
        if (str_contains($n, 'call back') || str_contains($n, 'callback')) {
            return \App\Models\DirectoryPhoneStatus::STATUS_CALLBACK;
        }
        if (str_contains($n, 'busy')) {
            return \App\Models\DirectoryPhoneStatus::STATUS_BUSY;
        }
        if (str_contains($n, 'switched off') || str_contains($n, 'switched_off')) {
            return \App\Models\DirectoryPhoneStatus::STATUS_SWITCHED_OFF;
        }
        if (str_contains($n, 'no answer') || str_contains($n, 'no_answer') || str_contains($n, 'unreachable')) {
            return \App\Models\DirectoryPhoneStatus::STATUS_NO_ANSWER;
        }

        return \App\Models\DirectoryPhoneStatus::STATUS_NOT_CALLED;
    }

    public function importDirectoryStatusAndAttempt(): void
    {
        $this->importErrors = [];
        $this->importSuccessCount = 0;

        if (!$this->activeElectionId) {
            $this->importErrors[] = 'No active election found.';
            return;
        }

        if (!$this->importCsvFile) {
            $this->importErrors[] = 'Please choose a CSV file.';
            return;
        }

        $this->validate([
            'importCsvFile' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $path = $this->importCsvFile->getRealPath();
        if (!$path || !is_readable($path)) {
            $this->importErrors[] = 'Unable to read uploaded file.';
            return;
        }

        // Build sub-status lookup by name (case-insensitive)
        $subStatusByName = DB::table('sub_statuses')
            ->select('id', 'name')
            ->where('active', true)
            ->get()
            ->mapWithKeys(fn ($r) => [mb_strtolower(trim((string) $r->name)) => ['id' => (string) $r->id, 'name' => (string) $r->name]])
            ->all();

        $completeOnAttemptStatus = array_flip([
            'phone hung up',
            'wrong number',
            'would decide after speaking with mayor',
            'deceased',
        ]);

        $validQ3 = array_flip(['aanekey','noonekay','neyngey','vote_laan_nudhaanan']);

        $fh = fopen($path, 'r');
        if ($fh === false) {
            $this->importErrors[] = 'Unable to open uploaded file.';
            return;
        }

        // Read header
        $header = fgetcsv($fh);
        if (!is_array($header)) {
            fclose($fh);
            $this->importErrors[] = 'CSV header missing.';
            return;
        }

        $headerMap = [];
        foreach ($header as $i => $col) {
            $headerMap[$this->normalizeImportHeader((string) $col)] = $i;
        }

        // Resolve aliases to canonical names
        $aliases = $this->headerAliases();
        foreach ($aliases as $canonical => $list) {
            if (isset($headerMap[$canonical])) continue;
            foreach ($list as $a) {
                $aNorm = $this->normalizeImportHeader((string) $a);
                if (isset($headerMap[$aNorm])) {
                    $headerMap[$canonical] = $headerMap[$aNorm];
                    break;
                }
            }
        }

        $required = ['user_email', 'directory_nid'];
        foreach ($required as $col) {
            if (!array_key_exists($col, $headerMap)) {
                $found = implode(', ', array_slice(array_keys($headerMap), 0, 50));
                fclose($fh);
                $this->importErrors[] = "Missing required column: {$col}. Found headers: {$found}";
                return;
            }
        }

        $line = 1;
        while (($row = fgetcsv($fh)) !== false) {
            $line++;
            if (!is_array($row) || (count($row) === 1 && trim((string)($row[0] ?? '')) === '')) {
                continue;
            }

            $get = function (string $col) use ($row, $headerMap) {
                $idx = $headerMap[$col] ?? null;
                return $idx === null ? '' : trim((string) ($row[$idx] ?? ''));
            };

            $userEmail = $get('user_email');
            $nid = $get('directory_nid');
            $q3 = $get('q3_support');
            $attemptPhone = $get('attempt_phone_number');
            $attemptStatusName = $get('attempt_status');
            $attemptNote = $get('attempt_note');

            if ($userEmail === '' || $nid === '') {
                $this->importErrors[] = "Line {$line}: user_email and directory_nid are required.";
                continue;
            }

            $hasQ3 = trim((string) $q3) !== '';
            $hasAttempt = (trim((string) $attemptPhone) !== '' || trim((string) $attemptStatusName) !== '' || trim((string) $attemptNote) !== '');

            // If neither Q3 nor attempt info is provided, do nothing
            if (!$hasQ3 && !$hasAttempt) {
                continue;
            }

            // Validate Q3 if present
            if ($hasQ3) {
                $q3Norm = mb_strtolower(trim($q3));
                if (!isset($validQ3[$q3Norm])) {
                    $this->importErrors[] = "Line {$line}: invalid q3_support '{$q3}'. Allowed: aanekey, noonekay, neyngey, vote_laan_nudhaanan.";
                    continue;
                }
                $q3 = $q3Norm;
            }

            // Validate attempt fields if any attempt info is present: require both phone and status
            $subStatusId = null;
            $attemptStatusNorm = '';
            $attemptStatusDisplayName = '';
            if ($hasAttempt) {
                if (trim((string) $attemptPhone) === '' || trim((string) $attemptStatusName) === '') {
                    $this->importErrors[] = "Line {$line}: attempt_phone_number and attempt_status are required when importing an attempt.";
                    continue;
                }

                $attemptStatusNorm = mb_strtolower(trim($attemptStatusName));
                $sub = $subStatusByName[$attemptStatusNorm] ?? null;
                $subStatusId = is_array($sub) ? ($sub['id'] ?? null) : null;
                $attemptStatusDisplayName = is_array($sub) ? (string)($sub['name'] ?? '') : '';
                if (!$subStatusId) {
                    $this->importErrors[] = "Line {$line}: attempt_status '{$attemptStatusName}' not found in Sub Statuses.";
                    continue;
                }
            }

            $userId = (int) DB::table('users')->where('email', $userEmail)->value('id');
            if ($userId <= 0) {
                $this->importErrors[] = "Line {$line}: user not found for email {$userEmail}.";
                continue;
            }

            $directoryId = DB::table('directories')->where('id_card_number', $nid)->value('id');
            if (!$directoryId) {
                $this->importErrors[] = "Line {$line}: directory not found for NID {$nid}.";
                continue;
            }

            // Complete rule
            $shouldComplete = $hasQ3 || ($hasAttempt && isset($completeOnAttemptStatus[$attemptStatusNorm]));

            try {
                DB::transaction(function () use (
                    $directoryId,
                    $userId,
                    $q3,
                    $hasQ3,
                    $hasAttempt,
                    $attemptPhone,
                    $subStatusId,
                    $attemptNote,
                    $shouldComplete,
                    $attemptStatusDisplayName,
                    $attemptStatusNorm
                ) {
                    $electionId = (string) $this->activeElectionId;

                    // Upsert Q3 support (call_center_forms)
                    if ($hasQ3) {
                        $existingFormId = DB::table('call_center_forms')
                            ->where('election_id', $electionId)
                            ->where('directory_id', (string) $directoryId)
                            ->value('id');

                        if ($existingFormId) {
                            DB::table('call_center_forms')
                                ->where('id', $existingFormId)
                                ->update([
                                    'q3_support' => $q3,
                                    'updated_by' => $userId,
                                    'updated_at' => now(),
                                ]);
                        } else {
                            DB::table('call_center_forms')->insert([
                                'id' => (string) Str::uuid(),
                                'election_id' => $electionId,
                                'directory_id' => (string) $directoryId,
                                'q3_support' => $q3,
                                'created_by' => $userId,
                                'updated_by' => $userId,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }

                    // Insert attempt only if provided
                    if ($hasAttempt) {
                        $nextAttempt = (int) DB::table('election_directory_call_sub_statuses')
                            ->where('election_id', $electionId)
                            ->where('directory_id', (string) $directoryId)
                            ->max('attempt');
                        $nextAttempt = $nextAttempt + 1;

                        DB::table('election_directory_call_sub_statuses')->insert([
                            'id' => (string) Str::uuid(),
                            'election_id' => $electionId,
                            'directory_id' => (string) $directoryId,
                            'phone_number' => $attemptPhone,
                            'attempt' => $nextAttempt,
                            'sub_status_id' => (string) $subStatusId,
                            'notes' => $attemptNote,
                            'updated_by' => $userId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        // Update per-phone status table to match attempt substatus selection
                        $mappedPhoneStatus = $this->mapSubStatusNameToPhoneStatus($attemptStatusDisplayName !== '' ? $attemptStatusDisplayName : $attemptStatusNorm);
                        $phoneNorm = \App\Models\DirectoryPhoneStatus::normalizePhone((string) $attemptPhone);
                        if ($phoneNorm !== '') {
                            \App\Models\DirectoryPhoneStatus::query()->updateOrCreate(
                                [
                                    'directory_id' => (string) $directoryId,
                                    'phone' => $phoneNorm,
                                ],
                                [
                                    'status' => $mappedPhoneStatus,
                                    'sub_status_id' => (string) $subStatusId,
                                    'notes' => $attemptNote ?: null,
                                    'last_called_at' => now(),
                                    'last_called_by' => $userId,
                                ]
                            );
                        }
                    }

                    // Upsert completed status if rule triggers
                    if ($shouldComplete) {
                        $existingStatusId = DB::table('election_directory_call_statuses')
                            ->where('election_id', $electionId)
                            ->where('directory_id', (string) $directoryId)
                            ->value('id');

                        $update = [
                            'status' => ElectionDirectoryCallStatus::STATUS_COMPLETED,
                            'notes' => null,
                            'updated_by' => $userId,
                            'updated_at' => now(),
                            'completed_at' => now(),
                        ];

                        if ($existingStatusId) {
                            DB::table('election_directory_call_statuses')->where('id', $existingStatusId)->update($update);
                        } else {
                            DB::table('election_directory_call_statuses')->insert($update + [
                                'id' => (string) Str::uuid(),
                                'election_id' => $electionId,
                                'directory_id' => (string) $directoryId,
                                'created_at' => now(),
                            ]);
                        }
                    }
                });

                $this->importSuccessCount++;
            } catch (\Throwable $e) {
                $this->importErrors[] = "Line {$line}: " . $e->getMessage();
            }
        }

        fclose($fh);

        if ($this->importSuccessCount > 0) {
            // Refresh list
            $this->dispatch('$refresh');
        }
    }

    /**
     * Websocket listeners (via Laravel Echo + Livewire).
     * When any user updates a directory in Call Center Beta, other users will refresh.
     */
    public function getListeners(): array
    {
        return [
            'echo:elections.voters,VoterDataChanged' => 'handleVoterDataChanged',
            'reverb-voter-update' => 'handleVoterDataChanged',
            'window:voter-data-updated' => 'handleVoterDataChanged',
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

        \Log::info('CallCenterBeta realtime payload received: ' . json_encode($payload, JSON_UNESCAPED_UNICODE));

        $directoryId = (string)($payload['directory_id'] ?? ($payload['directoryId'] ?? ''));
        $electionId = (string)($payload['election_id'] ?? ($payload['electionId'] ?? ''));

        // Refresh only if current election matches
        if ($this->activeElectionId && $electionId && $electionId !== (string)$this->activeElectionId) {
            return;
        }

        // Do NOT reset pagination here; it forces users back to page 1.
        // Just refresh the dataset while keeping the current page.
        $this->dispatch('$refresh');
    }
}
