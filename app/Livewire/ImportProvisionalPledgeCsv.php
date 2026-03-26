<?php

namespace App\Livewire;

use App\Models\Directory;
use App\Models\Election;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImportProvisionalPledgeCsv extends Component
{
    use WithFileUploads;

    public $importFile = null;

    public array $importErrors = [];
    public int $importedCount = 0;

    public function import(): void
    {
        if (! Auth::user()?->can('voters-importProvisionalPledgesCsv')) {
            abort(403);
        }

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
            $this->importErrors[] = 'Only CSV import is enabled on this server.';
            return;
        }

        $rows = $this->parseCsv($path);
        if (count($rows) === 0) {
            $this->importErrors[] = 'No data rows found in file.';
            return;
        }

        $now = now();
        $electionId = Election::where('status', Election::STATUS_ACTIVE)->value('id');
        if (! $electionId) {
            $this->importErrors[] = 'No active election found.';
            return;
        }

        $payload = [];
        $lineNo = 1;

        foreach ($rows as $r) {
            $lineNo++;
            $nid = trim((string) ($r['nid'] ?? $r['id_card_number'] ?? ''));
            $pledgeRaw = trim((string) ($r['pledge'] ?? $r['status'] ?? ''));
            $pledgeNorm = strtolower(trim($pledgeRaw));

            // Accept human-friendly pledge values and map to stored codes
            // Yes -> yes
            // No -> no
            // Undecided -> neutral
            // Not voting -> not_voting
            $pledgeMap = [
                'yes' => 'yes',
                'no' => 'no',
                'undecided' => 'neutral',
                'neutral' => 'neutral',
                'not voting' => 'not_voting',
                'not_voting' => 'not_voting',
            ];
            $pledge = $pledgeMap[$pledgeNorm] ?? null;

            // Accept either numeric user id OR email in the user_id column
            $userRaw = trim((string) ($r['user_id'] ?? $r['user'] ?? $r['email'] ?? ''));

            if ($nid === '') {
                $this->importErrors[] = "Line {$lineNo}: NID is required.";
                continue;
            }

            if ($pledgeNorm === '') {
                $this->importErrors[] = "Line {$lineNo}: Pledge is required.";
                continue;
            }

            if ($pledge === null) {
                $this->importErrors[] = "Line {$lineNo}: Invalid pledge '{$pledgeRaw}'. Allowed: Yes, No, Undecided, Not voting.";
                continue;
            }

            $userId = $this->resolveUserId($userRaw);
            if (! $userId) {
                $this->importErrors[] = "Line {$lineNo}: Invalid user (use user id or email): '{$userRaw}'.";
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
                'status' => $pledge,
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
            'text' => "Imported {$this->importedCount} provisional pledges.",
            'showConfirmButton' => false,
            'timer' => 1500,
        ]);

        $this->dispatch('bulk-prov-pledges-saved');
    }

    private function resolveUserId(string $raw): ?int
    {
        $raw = trim($raw);
        if ($raw === '') return null;

        // Numeric id
        if (ctype_digit($raw)) {
            $id = (int) $raw;
            return DB::table('users')->where('id', $id)->exists() ? $id : null;
        }

        // Email
        if (filter_var($raw, FILTER_VALIDATE_EMAIL)) {
            $id = DB::table('users')->where('email', $raw)->value('id');
            return $id ? (int) $id : null;
        }

        return null;
    }

    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) return [];

        $header = null;
        $out = [];

        while (($data = fgetcsv($handle)) !== false) {
            if (! is_array($data) || count(array_filter($data, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            if ($header === null) {
                $header = array_map(fn ($h) => strtolower(trim((string) $h)), $data);
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
        return view('livewire.import-provisional-pledge-csv');
    }
}
