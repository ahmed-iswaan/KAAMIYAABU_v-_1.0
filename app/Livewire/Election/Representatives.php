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

class Representatives extends Component
{
     use AuthorizesRequests;

    public $searchNid = '';
    public $foundUser = null;
    public $electionId = null;
    public $elections = [];
    public $message = '';
    public $alreadyVoted = false;

    public $historyOpen = false;
    public $historyLimit = 10;

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

    protected function allowedSubConsiteIds(): array
    {
        return Auth::user()?->subConsites()->pluck('sub_consites.id')->all() ?? [];
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

        $allowedSubConsiteIds = Auth::user()?->subConsites()->pluck('sub_consites.id')->all() ?? [];
        if (empty($allowedSubConsiteIds)) {
            $this->swal('error', 'Permission denied', 'You do not have sub consite permission to view representatives.');
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
            ->first();

        if (!$user) {
            $this->swal('warning', 'Not found', 'No directory found for that NID.');
            return;
        }

        if (!$user->sub_consite_id || !in_array($user->sub_consite_id, $allowedSubConsiteIds, true)) {
            $this->swal('error', 'Permission denied', 'You do not have consites permission to view this directory.');
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

        // Enforce sub consite permission again for safety
        $allowedSubConsiteIds = Auth::user()?->subConsites()->pluck('sub_consites.id')->all() ?? [];
        if (empty($allowedSubConsiteIds) || !$this->foundUser->sub_consite_id || !in_array($this->foundUser->sub_consite_id, $allowedSubConsiteIds, true)) {
            $this->swal('error', 'Permission denied', 'You do not have consites permission to mark this directory.');
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

        $allowedSubConsiteIds = $this->allowedSubConsiteIds();
        if (empty($allowedSubConsiteIds)) {
            $this->swal('error', 'Permission denied', 'You do not have sub consite permission.');
            return;
        }

        $vr = VotedRepresentative::with(['directory:id,name,sub_consite_id', 'user:id,name'])
            ->where('id', $votedRepresentativeId)
            ->where('election_id', $this->electionId)
            ->first();

        if (!$vr) {
            $this->swal('warning', 'Not found', 'History record not found.');
            return;
        }

        if (!$vr->directory?->sub_consite_id || !in_array($vr->directory->sub_consite_id, $allowedSubConsiteIds, true)) {
            $this->swal('error', 'Permission denied', 'You do not have consites permission to undo this record.');
            return;
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

    public function render()
    {
        $this->authorize('votedRepresentative-render');

        $history = collect();
        $historyTotal = 0;
        if ($this->electionId) {
            $baseQuery = VotedRepresentative::query()->where('election_id', $this->electionId);
            $historyTotal = (clone $baseQuery)->count();

            $history = VotedRepresentative::with([
                    'directory:id,name,id_card_number,sub_consite_id',
                    'directory.subConsite:id,code,name',
                    'user:id,name',
                ])
                ->where('election_id', $this->electionId)
                ->orderByDesc('voted_at')
                ->limit($this->historyLimit)
                ->get();
        }

        return view('livewire.election.representatives', [
            'history' => $history,
            'historyTotal' => $historyTotal,
        ]);
    }
}
