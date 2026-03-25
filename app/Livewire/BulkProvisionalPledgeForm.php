<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Directory;
use App\Models\EventLog;
use App\Models\Election;
use App\Models\User;

class BulkProvisionalPledgeForm extends Component
{
    use WithFileUploads;

    public string $electionId;

    /**
     * Selected directory from the table click (single target for this modal).
     */
    public ?string $directoryId = null;

    /** Read-only display */
    public string $directoryName = '';
    public string $directoryNid = '';

    /**
     * Each row: ['user_id' => (int|null), 'status' => 'pending|yes|no|neutral']
     */
    public array $rows = [
        ['user_id' => null, 'status' => 'pending'],
    ];

    /** Options for users */
    public array $userOptions = [];

    /** Import file (CSV). XLSX requires PHP zip extension + extra package; CSV is supported out-of-box. */
    public $importFile = null;

    public array $importErrors = [];
    public int $importedCount = 0;

    public function mount(?string $electionId = null, array $selectedDirectoryIds = []): void
    {
        // Always use ACTIVE election
        $this->electionId = Election::where('status', Election::STATUS_ACTIVE)->value('id')
            ?? (string) ($electionId ?? '');

        // Take first selected directory as the fixed target
        $selectedDirectoryIds = array_values(array_unique(array_filter($selectedDirectoryIds)));
        $this->directoryId = $selectedDirectoryIds[0] ?? null;

        if ($this->directoryId) {
            $dir = Directory::query()->whereKey($this->directoryId)->first(['id', 'name', 'id_card_number']);
            $this->directoryName = (string) ($dir->name ?? '');
            $this->directoryNid = (string) ($dir->id_card_number ?? '');
        }

        // Load users list (only users with role: Provisional Pledge OR Admin)
        $this->userOptions = User::query()
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['Provisional Pledge', 'Admin']);
            })
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name'])
            ->map(fn ($u) => ['id' => (int) $u->id, 'label' => (string) $u->name])
            ->toArray();

        // Initialize default row
        $this->rows = [['user_id' => null, 'status' => 'pending']];
    }

    public function addRow(): void
    {
        $this->rows[] = ['user_id' => null, 'status' => 'pending'];
    }

    public function removeRow(int $index): void
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);

        if (count($this->rows) === 0) {
            $this->rows = [['user_id' => null, 'status' => 'pending']];
        }
    }

    public function save(): void
    {
        $this->validate([
            'electionId' => ['required', 'uuid'],
            'directoryId' => ['required', 'uuid', 'exists:directories,id'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.user_id' => ['required', 'integer', 'distinct', 'exists:users,id'],
            'rows.*.status' => ['required', 'in:yes,no,neutral,pending'],
        ], [
            'rows.*.user_id.distinct' => 'Duplicate user in rows is not allowed.',
        ]);

        $actorId = Auth::id();
        $now = now();

        DB::transaction(function () use ($actorId, $now) {
            $payload = [];

            foreach ($this->rows as $row) {
                $payload[] = [
                    'election_id' => $this->electionId,
                    'user_id' => (int) $row['user_id'],
                    'directory_id' => (string) $this->directoryId,
                    'status' => ($row['status'] === 'pending') ? null : $row['status'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('voter_provisional_user_pledges')->upsert(
                $payload,
                ['election_id', 'user_id', 'directory_id'],
                ['status', 'updated_at']
            );

            // Optional: one event log entry
            try {
                EventLog::create([
                    'user_id' => $actorId,
                    'event_tab' => 'Voter',
                    'event_entry_id' => (string) $this->directoryId,
                    'event_type' => 'Bulk Provisional Pledge (Admin)',
                    'description' => 'Bulk provisional pledges saved',
                    'event_data' => [
                        'election_id' => $this->electionId,
                        'directory_id' => (string) $this->directoryId,
                        'rows_count' => count($this->rows),
                    ],
                    'ip_address' => request()->ip(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('Bulk provisional pledge EventLog failed', ['error' => $e->getMessage()]);
            }
        });

        $this->rows = [['user_id' => null, 'status' => 'pending']];

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Saved',
            'text' => 'Bulk provisional pledges saved.',
            'showConfirmButton' => false,
            'timer' => 1200,
        ]);

        $this->dispatch('bulk-prov-pledges-saved');
    }

    public function import(): void
    {
        $this->authorizeCanImport();

        $this->importErrors = [];
        $this->importedCount = 0;

        $this->validate([
            'importFile' => ['required', 'file', 'max:5120'], // 5MB
        ]);

        $path = $this->importFile->getRealPath();
        if (! $path || ! is_file($path)) {
            $this->importErrors[] = 'Upload failed: file was not available on server.';
            return;
        }

        $ext = strtolower((string) $this->importFile->getClientOriginalExtension());
        if ($ext !== 'csv') {
            $this->importErrors[] = 'Only CSV import is enabled on this server. (XLSX requires PHP zip extension.)';
            return;
        }

        $rows = $this->parseCsv($path);
        if (count($rows) === 0) {
            $this->importErrors[] = 'No data rows found in file.';
            return;
        }

        // Expect headers: NID, Pledge, user_id (case-insensitive)
        $now = now();
        $electionId = Election::where('status', Election::STATUS_ACTIVE)->value('id');
        if (! $electionId) {
            $this->importErrors[] = 'No active election found.';
            return;
        }

        $payload = [];
        $lineNo = 1; // data line counter (after header)

        foreach ($rows as $r) {
            $lineNo++;
            $nid = trim((string) ($r['nid'] ?? $r['NID'] ?? $r['id_card_number'] ?? ''));
            $pledge = strtolower(trim((string) ($r['pledge'] ?? $r['Pledge'] ?? $r['status'] ?? '')));
            $userId = (int) ($r['user_id'] ?? $r['User_ID'] ?? $r['user'] ?? 0);

            if ($nid === '') {
                $this->importErrors[] = "Line {$lineNo}: NID is required.";
                continue;
            }

            if (! in_array($pledge, ['yes','no','neutral','pending',''], true)) {
                $this->importErrors[] = "Line {$lineNo}: Invalid pledge '{$pledge}'. Allowed: yes, no, neutral, pending.";
                continue;
            }

            if ($userId <= 0 || ! DB::table('users')->where('id', $userId)->exists()) {
                $this->importErrors[] = "Line {$lineNo}: Invalid user_id '{$userId}'.";
                continue;
            }

            $dirId = Directory::where('id_card_number', $nid)->value('id');
            if (! $dirId) {
                $this->importErrors[] = "Line {$lineNo}: NID '{$nid}' not found.";
                continue;
            }

            $payload[] = [
                'election_id' => $electionId,
                'user_id' => $userId,
                'directory_id' => (string) $dirId,
                'status' => ($pledge === '' || $pledge === 'pending') ? null : $pledge,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (count($payload) === 0) {
            if (count($this->importErrors) === 0) {
                $this->importErrors[] = 'No valid rows to import.';
            }
            return;
        }

        DB::transaction(function () use ($payload) {
            DB::table('voter_provisional_user_pledges')->upsert(
                $payload,
                ['election_id', 'user_id', 'directory_id'],
                ['status', 'updated_at']
            );
        });

        $this->importedCount = count($payload);

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Imported',
            'text' => "Imported {$this->importedCount} provisional pledges." ,
            'showConfirmButton' => false,
            'timer' => 1500,
        ]);

        $this->dispatch('bulk-prov-pledges-saved');
    }

    private function authorizeCanImport(): void
    {
        // Same permission as bulk provisional pledge entry
        if (! Auth::user()?->can('voters-bulkProvisionalPledge')) {
            abort(403);
        }
    }

    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) return [];

        $header = null;
        $out = [];

        while (($data = fgetcsv($handle)) !== false) {
            // skip empty lines
            if (! is_array($data) || count(array_filter($data, fn($v) => trim((string)$v) !== '')) === 0) {
                continue;
            }

            if ($header === null) {
                $header = array_map(fn($h) => strtolower(trim((string) $h)), $data);
                continue;
            }

            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = $data[$i] ?? null;
            }
            $out[] = $row;
        }

        fclose($handle);
        return $out;
    }

    public function render()
    {
        return view('livewire.bulk-provisional-pledge-form');
    }
}
