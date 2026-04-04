<?php

namespace App\Livewire\Election;

use App\Models\Election;
use App\Models\VotedRepresentative;
use App\Models\VotingBox;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class VotedRepresentativesList extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public ?string $electionId = null;
    public string $search = '';
    public int $perPage = 25;

    public string $votingBoxId = '';

    public function mount(): void
    {
        $latest = Election::orderBy('start_date', 'desc')->first(['id']);
        $this->electionId = $latest?->id ? (string) $latest->id : null;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedVotingBoxId(): void
    {
        $this->resetPage();
    }

    protected function allowedSubConsiteIds(): array
    {
        return Auth::user()?->subConsites()
            ->newQuery()
            ->pluck('sub_consites.id')
            ->all() ?? [];
    }

    protected function allowedVotingBoxIds(): array
    {
        return Auth::user()?->votingBoxes()
            ->newQuery()
            ->pluck('voting_boxes.id')
            ->all() ?? [];
    }

    private function directoryImageUrl($dir): ?string
    {
        if (!$dir) return null;

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

    public function render()
    {
        // Access is controlled by route middleware: permission:votedRepresentative-list-render

        $allowed = $this->allowedSubConsiteIds();
        $allowedVotingBoxes = $this->allowedVotingBoxIds();

        $votingBoxes = Auth::user()?->votingBoxes()
            ->orderBy('name')
            ->get(['voting_boxes.id', 'voting_boxes.name']) ?? collect();

        $query = VotedRepresentative::query()
            ->with([
                'user:id,name,profile_picture',
                // Include serial so it can be shown in the list
                'directory:id,name,profile_picture,id_card_number,serial,sub_consite_id,voting_box_id',
                'directory.subConsite:id,name',
                'directory.votingBox:id,name',
            ])
            ->when($this->electionId, fn ($q) => $q->where('election_id', $this->electionId))
            ->join('directories as d', 'd.id', '=', 'voted_representatives.directory_id')
            ->where('d.status', 'Active')
            ->when(!empty($allowed), fn ($q) => $q->whereIn('d.sub_consite_id', $allowed))
            // Only restrict by allowed voting boxes if the user actually has any assigned boxes.
            // Otherwise, keep old behavior (do not block the entire list).
            ->when($this->votingBoxId !== '' && !empty($allowedVotingBoxes), fn ($q) => $q->whereIn('d.voting_box_id', $allowedVotingBoxes))
            ->when($this->votingBoxId !== '', fn ($q) => $q->where('d.voting_box_id', $this->votingBoxId))
            ->select('voted_representatives.*')
            ->orderByDesc('voted_representatives.voted_at');

        if (trim($this->search) !== '') {
            $s = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($s) {
                $q->where('d.name', 'like', $s)
                    ->orWhere('d.id_card_number', 'like', $s);
            });
        }

        $paginator = $query->paginate($this->perPage);

        $directoryImageUrls = $paginator->getCollection()->mapWithKeys(function ($row) {
            $dir = $row->directory;
            return [$row->id => $this->directoryImageUrl($dir)];
        });

        return view('livewire.election.voted-representatives-list', [
            'rows' => $paginator,
            'directoryImageUrls' => $directoryImageUrls,
            'votingBoxes' => $votingBoxes,
        ])->layout('layouts.master');
    }
}
