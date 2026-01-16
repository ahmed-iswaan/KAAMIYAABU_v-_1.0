<?php

namespace App\Livewire\Election;

use App\Models\Directory;
use App\Models\Election;
use App\Models\EventLog;
use App\Models\VotedRepresentative;
use App\Models\VoterPledge;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ConsiteFocals extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $electionId;

    public $search = '';
    public $subConsiteId = '';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'representative-voted-changed' => 'handleRepresentativeVotedChanged',
    ];

    protected function swal(string $icon, string $title, string $text = '', array $extra = []): void
    {
        $this->dispatch('swal', array_merge([
            'icon' => $icon,
            'title' => $title,
            'text' => $text,
            'showConfirmButton' => true,
        ], $extra));
    }

    public function mount(): void
    {
        $latest = Election::orderBy('start_date', 'desc')->first(['id']);
        $this->electionId = $latest?->id;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSubConsiteId(): void
    {
        $this->resetPage();
    }

    public function updatedElectionId(): void
    {
        // election is fixed to latest; ignore user changes
    }

    protected function allowedSubConsiteIds(): array
    {
        return Auth::user()?->subConsites()->pluck('sub_consites.id')->all() ?? [];
    }

    // View-only page: no actions.

    public function handleRepresentativeVotedChanged(array $payload = []): void
    {
        // If an election filter is active, only refresh for that election
        $electionId = $payload['election_id'] ?? null;
        if ($this->electionId && $electionId && (string)$this->electionId !== (string)$electionId) {
            return;
        }

        // Refresh the not-voted list
        $this->resetPage();
    }

    public function markAsVoted(string $directoryId): void
    {
        // Require explicit permission for voting action
        $this->authorize('votedRepresentative-markAsVoted');

        if (!$this->electionId) {
            $this->swal('error', 'No election', 'No election selected.');
            return;
        }

        $allowed = $this->allowedSubConsiteIds();
        if (empty($allowed)) {
            $this->swal('error', 'Permission denied', 'You do not have sub consite permission.');
            return;
        }

        $dir = Directory::query()
            ->select(['id', 'name', 'id_card_number', 'sub_consite_id', 'status'])
            ->where('id', $directoryId)
            ->first();

        if (!$dir || $dir->status !== 'Active') {
            $this->swal('warning', 'Not found', 'Directory not found or inactive.');
            return;
        }

        if (!$dir->sub_consite_id || !in_array($dir->sub_consite_id, $allowed, true)) {
            $this->swal('error', 'Permission denied', 'You do not have permission to vote for this directory.');
            return;
        }

        if (VotedRepresentative::where('election_id', $this->electionId)->where('directory_id', $dir->id)->exists()) {
            $this->swal('info', 'Already voted', 'This directory is already marked as voted.');
            return;
        }

        DB::transaction(function () use ($dir) {
            $vr = VotedRepresentative::create([
                'election_id' => $this->electionId,
                'directory_id' => $dir->id,
                'user_id' => Auth::id(),
                'voted_at' => now(),
            ]);

            EventLog::create([
                'user_id' => Auth::id(),
                'event_tab' => 'Election',
                'event_entry_id' => $dir->id,
                'event_type' => 'Representative Marked Voted',
                'description' => 'Marked representative as voted for election (consites focals)',
                'event_data' => [
                    'election_id' => $this->electionId,
                    'directory_id' => $dir->id,
                    'voted_representative_id' => $vr->id,
                ],
                'ip_address' => request()->ip(),
            ]);

            DB::afterCommit(function () use ($dir) {
                event(new \App\Events\RepresentativeVotedChanged(
                    'marked_voted',
                    $dir->id,
                    $this->electionId,
                    [
                        'sub_consite_id' => (string) ($dir->sub_consite_id ?? ''),
                    ]
                ));
            });
        });

        $this->swal('success', 'Saved', 'Marked as voted!', ['showConfirmButton' => false, 'timer' => 1200]);
        $this->resetPage();
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

    public function exportNotVotedCsv()
    {
        $this->authorize('consites-focals-exportNotVotedCsv');

        if (!$this->electionId) {
            abort(400, 'Election is required');
        }

        $allowed = $this->allowedSubConsiteIds();
        if (empty($allowed)) {
            abort(403);
        }

        $file = 'consites-focals-not-voted-election-'.$this->electionId.'-'.now()->format('Ymd_His').'.csv';

        $q = Directory::query()
            ->leftJoin('sub_consites', 'sub_consites.id', '=', 'directories.sub_consite_id')
            ->where('directories.status', 'Active')
            ->whereIn('directories.sub_consite_id', $allowed)
            ->when($this->subConsiteId, fn($qq) => $qq->where('directories.sub_consite_id', $this->subConsiteId))
            ->when($this->search, function ($qq) {
                $sRaw = trim((string) $this->search);
                $s = $sRaw;

                $serialOnly = null;
                if (preg_match('/^s\s*(\d+)$/i', $sRaw, $m)) {
                    $serialOnly = $m[1];
                }

                $qq->where(function ($w) use ($s, $serialOnly) {
                    $w->where('directories.name', 'like', "%{$s}%")
                        ->orWhere('directories.id_card_number', 'like', "%{$s}%")
                        ->orWhere('directories.serial', 'like', "%{$s}%")
                        ->orWhere('directories.address', 'like', "%{$s}%")
                        ->orWhere('directories.street_address', 'like', "%{$s}%")
                        ->orWhereRaw("JSON_SEARCH(directories.phones, 'one', ?) IS NOT NULL", [$s])
                        ->orWhere('directories.phones', 'like', "%{$s}%");

                    if ($serialOnly !== null) {
                        $w->orWhere('directories.serial', $serialOnly);
                    }
                });
            })
            ->whereNotExists(function ($qq) {
                $qq->selectRaw(1)
                    ->from('voted_representatives')
                    ->whereColumn('voted_representatives.directory_id', 'directories.id')
                    ->where('voted_representatives.election_id', $this->electionId);
            })
            ->orderBy('sub_consites.code')
            ->orderBy('directories.name')
            ->select([
                'directories.name',
                'directories.id_card_number',
                'directories.serial',
                'sub_consites.code as sub_consite_code',
                'sub_consites.name as sub_consite_name',
                'directories.phones',
                'directories.street_address',
                'directories.address',
            ]);

        return response()->streamDownload(function () use ($q) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($out, [
                'Name',
                'NID',
                'Serial',
                'SubConsite Code',
                'SubConsite Name',
                'Phones',
                'Street Address',
                'Address',
            ]);

            $q->chunk(1000, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    $phones = $r->phones;
                    if (is_array($phones)) {
                        $phones = implode(', ', array_filter($phones));
                    } elseif (is_string($phones)) {
                        $decoded = json_decode($phones, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $phones = implode(', ', array_filter($decoded));
                        }
                    }

                    fputcsv($out, [
                        $r->name,
                        $r->id_card_number,
                        $r->serial,
                        $r->sub_consite_code,
                        $r->sub_consite_name,
                        $phones,
                        $r->street_address,
                        $r->address,
                    ]);
                }
            });

            fclose($out);
        }, $file, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function render()
    {
        $this->authorize('consites-focals-render');

        $allowed = $this->allowedSubConsiteIds();

        $subConsites = Auth::user()?->subConsites()->orderBy('code')->get(['sub_consites.id', 'code', 'name']);

        // Summary counts (scoped to allowed sub consites and optional selected filter)
        $baseDirs = Directory::query()
            ->where('status', 'Active')
            ->whereIn('sub_consite_id', $allowed)
            ->when($this->subConsiteId, fn($q) => $q->where('sub_consite_id', $this->subConsiteId));

        $totalDirectories = (clone $baseDirs)->count();

        $votedCount = (clone $baseDirs)
            ->whereExists(function ($q) {
                $q->selectRaw(1)
                  ->from('voted_representatives')
                  ->whereColumn('voted_representatives.directory_id', 'directories.id')
                  ->where('voted_representatives.election_id', $this->electionId);
            })
            ->count();

        $notVotedCount = max(0, $totalDirectories - $votedCount);

        $directories = Directory::query()
            ->select([
                'id',
                'name',
                'profile_picture',
                'id_card_number',
                'serial',
                'sub_consite_id',
                'address','street_address',
                'phones',
            ])
            ->addSelect([
                'final_pledge_status' => VoterPledge::select('status')
                    ->whereColumn('directory_id', 'directories.id')
                    ->where('election_id', $this->electionId)
                    ->where('type', VoterPledge::TYPE_FINAL)
                    ->limit(1),
            ])
            ->with(['subConsite:id,code,name'])
            ->where('status', 'Active')
            ->whereIn('sub_consite_id', $allowed)
            ->when($this->subConsiteId, fn($q) => $q->where('sub_consite_id', $this->subConsiteId))
            ->when($this->search, function ($q) {
                $sRaw = trim($this->search);
                $s = $sRaw;

                // Allow searching serial using prefix like "S2" or "s12" => match serial "2"/"12"
                $serialOnly = null;
                if (preg_match('/^s\s*(\d+)$/i', $sRaw, $m)) {
                    $serialOnly = $m[1];
                }

                $q->where(function ($qq) use ($s, $serialOnly) {
                    $qq
                        ->where('name', 'like', "%{$s}%")
                        ->orWhere('id_card_number', 'like', "%{$s}%")
                        ->orWhere('serial', 'like', "%{$s}%")
                        ->orWhere('address', 'like', "%{$s}%")
                        ->orWhere('street_address', 'like', "%{$s}%")
                        ->orWhereRaw("JSON_SEARCH(phones, 'one', ?) IS NOT NULL", [$s])
                        ->orWhere('phones', 'like', "%{$s}%");

                    if ($serialOnly !== null) {
                        $qq->orWhere('serial', $serialOnly);
                    }
                });
            })
            ->whereNotExists(function ($q) {
                $q->selectRaw(1)
                  ->from('voted_representatives')
                  ->whereColumn('voted_representatives.directory_id', 'directories.id')
                  ->where('voted_representatives.election_id', $this->electionId);
            })
            ->orderByRaw("CASE
                WHEN final_pledge_status IN ('strong_yes','yes') THEN 0
                WHEN final_pledge_status = 'neutral' THEN 1
                WHEN final_pledge_status IN ('no','strong_no') THEN 2
                WHEN final_pledge_status IS NULL OR final_pledge_status = '' THEN 3
                ELSE 4
            END")
            ->orderBy('name')
            ->paginate(25);

        return view('livewire.election.consite-focals', [
            'directories' => $directories,
            'subConsites' => $subConsites,
            'directoryImageUrls' => $directories->getCollection()->mapWithKeys(fn($d) => [$d->id => $this->directoryImageUrl($d)]),
            'totalDirectories' => $totalDirectories,
            'votedCount' => $votedCount,
            'notVotedCount' => $notVotedCount,
        ]);
    }
}
