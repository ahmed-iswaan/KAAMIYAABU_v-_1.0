<?php

namespace App\Livewire\Election;

use Livewire\Component;
use App\Models\Directory;
use App\Models\SubConsite;
use App\Models\VotedRepresentative;
use App\Models\Election;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\EventLog;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Events\RepresentativeVotedChanged;
use App\Models\VotingBox;

class Representatives extends Component
{
    use AuthorizesRequests;

    public $searchMode = 'serial'; // nid | serial
    public $searchNid = '';
    public $searchSerial = '';
    public $foundUser = null;
    public $electionId = null;
    public $elections = [];
    public $message = '';
    public $alreadyVoted = false;

    public $historyOpen = false;
    public $historyLimit = 10;

    public $searchResults = []; // serial search results (when multiple)

    protected function swal(string $icon, string $title, string $text = '', array $extra = []): void
    {
        $this->dispatch('swal', array_merge([
            'icon' => $icon,
            'title' => $title,
            'text' => $text,
            'showConfirmButton' => true,
        ], $extra));
    }

    public function mount()
    {
        $this->elections = Election::orderBy('start_date','desc')->get(['id','name','status']);
        // Always use the first election if available
        if($this->elections->count()){
            $this->electionId = $this->elections->first()->id;
        }
    }

    public function openHistory(): void
    {
        $this->historyOpen = true;
        $this->historyLimit = 10;
    }

    public function closeHistory(): void
    {
        $this->historyOpen = false;
    }

    public function loadMoreHistory(): void
    {
        $this->historyLimit += 10;
    }

    protected function allowedVotingBoxIds(): array
    {
 return Auth::user()?->votingBoxes()->pluck('voting_boxes.id')->all() ?? [];
    }

    public function updatedSearchMode(): void
    {
        // When switching modes, clear inputs and results
        $this->reset(['foundUser', 'message', 'alreadyVoted', 'searchNid', 'searchSerial', 'searchResults']);

        if ($this->searchMode === 'nid') {
            $this->dispatch('nid:reset');
        }
    }

    public function search()
    {
        if (($this->searchMode ?? 'nid') === 'serial') {
            return $this->searchBySerial();
        }

        return $this->searchByNid();
    }

    public function searchBySerial(): void
    {
        $this->reset(['foundUser','message','alreadyVoted','searchResults']);

        $serial = trim((string) $this->searchSerial);
        if ($serial === '') {
            $this->swal('error', 'Invalid Serial', 'Please enter the Serial number.');
            return;
        }

        $allowedVotingBoxIds = $this->allowedVotingBoxIds();
        if (empty($allowedVotingBoxIds)) {
            $this->swal('error', 'Permission denied', 'You do not have voting box permission to view representatives.');
            return;
        }

        // DEBUG: comment/remove after confirming IDs
        // $this->swal('info', 'DEBUG voting boxes', 'Allowed: '.implode(',', $allowedVotingBoxIds));

        $results = Directory::with([
                'subConsite:id,code,name',
                'property:id,name',
                'country:id,name',
                'island:id,name,atoll_id',
                'island.atoll:id,code',
                'currentProperty:id,name',
                'currentCountry:id,name',
                'currentIsland:id,name,atoll_id',
                'currentIsland.atoll:id,code',
            ])
            ->where('serial', $serial)
            ->where('status', 'Active')
            ->whereIn('voting_box_id', $allowedVotingBoxIds)
            ->orderBy('name')
            ->get();

        // DEBUG
        // if ($results->isNotEmpty()) {
        //     $this->swal('info', 'DEBUG result box', 'Result voting_box_id: '.($results->first()->voting_box_id ?? 'null'));
        // }

        if ($results->isEmpty()) {
            $this->swal('warning', 'Not found', 'No directory found for that Serial (within your permitted voting boxes).');
            return;
        }

        // If exactly one match, behave like previous flow
        if ($results->count() === 1) {
            $user = $results->first();
            $this->foundUser = $user;
            $this->alreadyVoted = VotedRepresentative::where('election_id', $this->electionId)
                ->where('directory_id', $user->id)
                ->exists();

            if ($this->alreadyVoted) {
                $this->swal('info', 'Already voted', 'This representative is already marked as voted.', ['showConfirmButton' => true]);
            }
        } else {
            $this->searchResults = $results->values()->all();
            $this->swal('info', 'Multiple matches', 'Select the correct representative from the list.');
        }

        // Reset serial input after submit (keep results)
        $this->reset('searchSerial');
    }

    public function selectSerialResult(string $directoryId): void
    {
        $this->reset(['foundUser','alreadyVoted']);

        $allowedVotingBoxIds = $this->allowedVotingBoxIds();
        if (empty($allowedVotingBoxIds)) {
            $this->swal('error', 'Permission denied', 'You do not have voting box permission to view representatives.');
            return;
        }

        $user = Directory::with([
                'subConsite:id,code,name',
                'property:id,name',
                'country:id,name',
                'island:id,name,atoll_id',
                'island.atoll:id,code',
                'currentProperty:id,name',
                'currentCountry:id,name',
                'currentIsland:id,name,atoll_id',
                'currentIsland.atoll:id,code',
            ])
            ->where('id', $directoryId)
            ->where('status', 'Active')
            ->whereIn('voting_box_id', $allowedVotingBoxIds)
            ->first();

        if (!$user) {
            $this->swal('warning', 'Not found', 'Selected directory not found (or not permitted).');
            return;
        }

        $this->searchResults = [];
        $this->foundUser = $user;
        $this->alreadyVoted = VotedRepresentative::where('election_id', $this->electionId)
            ->where('directory_id', $user->id)
            ->exists();
    }

    public function searchByNid()
    {
        $this->reset(['foundUser','message','alreadyVoted']);

        $input = trim($this->searchNid);
        if (!$input) {
            $this->swal('error', 'Invalid NID', 'Please enter the 6 digits of the NID.');
            return;
        }

        // If user enters only 6 digits, prepend 'A'. Accept full NID (A123456) as well.
        if (preg_match('/^\d{6}$/', $input)) {
            $nid = 'A' . $input;
        } else {
            $formatted = strtoupper($input);
            if (preg_match('/^A\d{6}$/', $formatted)) {
                $nid = $formatted;
            } else {
                $this->swal('error', 'Invalid NID', 'Please enter 6 digits (e.g. 123456).');
                return;
            }
        }

        $allowedVotingBoxIds = $this->allowedVotingBoxIds();
        if (empty($allowedVotingBoxIds)) {
            $this->swal('error', 'Permission denied', 'You do not have voting box permission to view representatives.');
            return;
        }

        $user = Directory::with([
                'subConsite:id,code,name',
                'property:id,name',
                'country:id,name',
                'island:id,name,atoll_id',
                'island.atoll:id,code',
                'currentProperty:id,name',
                'currentCountry:id,name',
                'currentIsland:id,name,atoll_id',
                'currentIsland.atoll:id,code',
            ])
            ->where('id_card_number', $nid)
            ->where('status', 'Active')
            ->whereIn('voting_box_id', $allowedVotingBoxIds)
            ->first();

        if (!$user) {
            $this->swal('warning', 'Not found', 'No directory found for that NID.');
            return;
        }

        if (!$user->voting_box_id || !in_array($user->voting_box_id, $allowedVotingBoxIds, true)) {
            $this->swal('error', 'Permission denied', 'You do not have voting box permission to view this directory.');
            return;
        }

        $this->foundUser = $user;
        $this->alreadyVoted = VotedRepresentative::where('election_id', $this->electionId)
            ->where('directory_id', $user->id)
            ->exists();

        if ($this->alreadyVoted) {
            $this->swal('info', 'Already voted', 'This representative is already marked as voted.', ['showConfirmButton' => true]);
        }

        // Reset search input fields after submit (keep results)
        $this->reset('searchNid');
        $this->dispatch('nid:reset');
    }

    public function markAsVoted(): void
    {
        if(!$this->foundUser || !$this->electionId) {
            $this->swal('error', 'No selection', 'Search and select a representative first.');
            return;
        }

        // Enforce voting box permission again for safety
        $allowedVotingBoxIds = Auth::user()?->votingBoxes()->pluck('voting_boxes.id')->all() ?? [];
        if (empty($allowedVotingBoxIds) || !$this->foundUser->voting_box_id || !in_array($this->foundUser->voting_box_id, $allowedVotingBoxIds, true)) {
            $this->swal('error', 'Permission denied', 'You do not have voting box permission to mark this directory.');
            return;
        }

        if(VotedRepresentative::where('election_id', $this->electionId)->where('directory_id', $this->foundUser->id)->exists()) {
            $this->alreadyVoted = true;
            $this->swal('info', 'Already marked', 'This representative is already marked as voted.');
            return;
        }

        DB::transaction(function () {
            $vr = VotedRepresentative::create([
                'election_id' => $this->electionId,
                'directory_id' => $this->foundUser->id,
                'user_id' => Auth::id(),
                'voted_at' => now(),
            ]);

            EventLog::create([
                'user_id' => Auth::id(),
                'event_tab' => 'Election',
                'event_entry_id' => $this->foundUser->id,
                'event_type' => 'Representative Marked Voted',
                'description' => 'Marked representative as voted for election',
                'event_data' => [
                    'election_id' => $this->electionId,
                    'directory_id' => $this->foundUser->id,
                    'voted_representative_id' => $vr->id,
                ],
                'ip_address' => request()->ip(),
            ]);

            DB::afterCommit(function () {
                event(new \App\Events\RepresentativeVotedChanged(
                    'marked_voted',
                    $this->foundUser->id,
                    $this->electionId,
                    [
                        'sub_consite_id' => (string) ($this->foundUser->sub_consite_id ?? ''),
                    ]
                ));
            });
        });

        $this->alreadyVoted = true;
        $this->swal('success', 'Saved', 'Marked as voted!', ['showConfirmButton' => false, 'timer' => 1500]);
    }

    public function undoVoted(string $votedRepresentativeId): void
    {
        if (!$this->electionId) {
            $this->swal('error', 'No election', 'No election selected.');
            return;
        }

        $allowedVotingBoxIds = $this->allowedVotingBoxIds();
        if (empty($allowedVotingBoxIds)) {
            $this->swal('error', 'Permission denied', 'You do not have voting box permission.');
            return;
        }

        $vr = VotedRepresentative::with(['directory:id,name,sub_consite_id,voting_box_id', 'user:id,name'])
            ->where('id', $votedRepresentativeId)
            ->where('election_id', $this->electionId)
            ->first();

        if (!$vr) {
            $this->swal('warning', 'Not found', 'History record not found.');
            return;
        }

        // Allow undo if the current user is the one who marked it (common expected behavior),
        // otherwise require voting box permission.
        $dirVotingBoxId = $vr->directory?->voting_box_id;
        $isOwner = (int) $vr->user_id === (int) Auth::id();

        if (!$isOwner) {
            if (!$dirVotingBoxId || !in_array($dirVotingBoxId, $allowedVotingBoxIds, true)) {
                $this->swal('error', 'Permission denied', 'You do not have voting box permission to undo this record.');
                return;
            }
        }

        $dirId = $vr->directory_id;
        $vrId = $vr->id;
        $markedBy = $vr->user_id;
        $markedAt = $vr->voted_at;
        $subConsiteId = (string) ($vr->directory?->sub_consite_id ?? '');

        DB::transaction(function () use ($vr, $dirId, $subConsiteId, $vrId, $markedBy, $markedAt) {
            $vr->delete();

            DB::afterCommit(function () use ($dirId, $subConsiteId) {
                event(new \App\Events\RepresentativeVotedChanged(
                    'undo_voted',
                    $dirId,
                    $this->electionId,
                    [
                        'sub_consite_id' => $subConsiteId,
                    ]
                ));
            });

            EventLog::create([
                'user_id' => Auth::id(),
                'event_tab' => 'Election',
                'event_entry_id' => $dirId,
                'event_type' => 'Representative Undo Voted',
                'description' => 'Undid representative voted mark for election',
                'event_data' => [
                    'election_id' => $this->electionId,
                    'directory_id' => $dirId,
                    'voted_representative_id' => $vrId,
                    'previous_marked_by_user_id' => $markedBy,
                    'previous_marked_at' => (string) $markedAt,
                ],
                'ip_address' => request()->ip(),
            ]);
        });

        if ($this->foundUser && $this->foundUser->id === $dirId) {
            $this->alreadyVoted = false;
        }

        $this->swal('success', 'Undone', 'Voted record removed successfully.', ['showConfirmButton' => false, 'timer' => 1400]);
    }

    private function representativeImageUrl(?Directory $dir): ?string
    {
        if (!$dir) return null;

        // 1) Stored profile picture
        if (!empty($dir->profile_picture)) {
            return asset('storage/' . ltrim($dir->profile_picture, '/'));
        }

        // 2) Fallback: public/nid-images/{NID}.{ext}
        $nid = trim((string) ($dir->id_card_number ?? ''));
        if ($nid === '') return null;

        foreach (['jpg','jpeg','png','webp'] as $ext) {
            $relative = "nid-images/{$nid}.{$ext}";
            if (is_file(public_path($relative))) {
                return asset($relative);
            }
        }

        return null;
    }

    public function render()
    {
        $this->authorize('votedRepresentative-render');

        $history = collect();
        $historyTotal = 0;
        if ($this->electionId) {
            $baseQuery = VotedRepresentative::query()
                ->where('election_id', $this->electionId)
                ->where('user_id', Auth::id());

            $historyTotal = (clone $baseQuery)->count();

            $history = VotedRepresentative::with([
                    'directory:id,name,serial,id_card_number,sub_consite_id',
                    'directory.subConsite:id,code,name',
                    'user:id,name',
                ])
                ->where('election_id', $this->electionId)
                ->where('user_id', Auth::id())
                ->orderByDesc('voted_at')
                ->limit($this->historyLimit)
                ->get();
        }

        return view('livewire.election.representatives', [
            'history' => $history,
            'historyTotal' => $historyTotal,
            'representativeImageUrl' => $this->representativeImageUrl($this->foundUser),
        ]);
    }
}
