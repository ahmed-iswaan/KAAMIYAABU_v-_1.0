<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Directory;
use App\Models\EventLog;
use App\Models\Election;
use App\Models\User;

class BulkProvisionalPledgeForm extends Component
{
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

    public function render()
    {
        return view('livewire.bulk-provisional-pledge-form');
    }
}
