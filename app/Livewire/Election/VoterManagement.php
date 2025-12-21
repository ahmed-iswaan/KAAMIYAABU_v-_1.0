<?php

namespace App\Livewire\Election;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Election;
use App\Models\Directory;
use App\Models\VoterOpinion;
use App\Models\VoterRequest;
use App\Models\OpinionType;
use App\Models\RequestType; // added
use Illuminate\Support\Facades\Auth;
use App\Models\VoterRequestResponse; // new for responses
use App\Models\VoterNote; // added for saving notes
use App\Models\VoterPledge; // added
use App\Events\VoterDataChanged; // new event
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\EventLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // NEW logging

class VoterManagement extends Component
{
    use WithPagination,AuthorizesRequests;

    public $search='';
    public $perPage=10;
    public $electionId=null;
    public $pageTitle='Voters';
    public $elections=[]; // cached list
    public $viewingVoter = null; // selected voter for modal view
    public $voterOpinions = [];
    public $voterRequests = [];
    public $voterNotes = []; // notes collection
    public $notesLimit = 3; // how many notes to display initially
    // Opinion form state
    public $showAddOpinionModal = false;
    public $opinion_type_id = '';
    public $opinion_rating = null; // rating optional again
    public $opinion_note = '';
    public $opinionTypes = [];
    // NEW: opinion status (success / failed_attempt / follow_up)
    public $opinion_status = 'success';
    // Request form state (new)
    public $showAddRequestModal = false;
    public $request_type_id='';
    public $request_amount=null;
    public $request_note='';
    public $requestTypes = [];
    // Note form state (new)
    public $showAddNoteModal = false; // notes modal flag
    public $note_text = ''; // new note text
    // Track active tab
    public $activeTab = 'details';
    // Master/detail for opinions
    public $selectedOpinionId = null;
    public $selectedOpinion = null; // hydrates chosen opinion model (from collection)
    // Request master/detail
    public $selectedRequestId = null; // newly added
    public $selectedRequest = null;   // newly added
    // Response form state for a selected voter request
    public $request_response_text = '';
    public $request_response_status_after = '';
    // Pledge state (new)
    public $provisionalPledge=null; // current provisional pledge model
    public $finalPledge=null; // current final pledge model
    public $provisional_status='';
    public $final_status='';
    // Final pledge filter (new)
    public $finalPledgeFilters = []; // multiple selected final pledge statuses (include 'pending' for none)

    // Force re-render (keyed) for modal content on realtime updates
    public $modalRefreshTick = 0; // increment to change wire:key

    // Mapping for opinion rating labels (5->1) Strong Yes .. Strong No
    public $ratingLabels = [
        5 => 'Strong Yes',
        4 => 'Yes',
        3 => 'Neutral',
        2 => 'No',
        1 => 'Strong No',
    ];
    public $ratingColors = [
        5 => 'success',
        4 => 'primary',
        3 => 'secondary',
        2 => 'warning',
        1 => 'danger',
    ];

    protected $rules = [
        'opinion_type_id' => 'required|exists:opinion_types,id',
        'opinion_rating' => 'nullable|integer|min:1|max:5', // reverted to nullable
        'opinion_note' => 'nullable|string|max:2000',
        'opinion_status' => 'required|in:success,failed_attempt,follow_up',
        // request rules
        'request_type_id' => 'required|exists:request_types,id',
        'request_amount' => 'nullable|numeric|min:0',
        'request_note' => 'nullable|string|max:2000',
        // note rules
        'note_text' => 'required|string|max:4000'
    ];

    protected $queryString=['electionId'=>['except'=>null],'search'=>['except'=>'']]; // removed single filter from query string

    // Reset page when filters change
    public function updatingSearch(){ $this->resetPage(); }
    public function updatingPerPage(){ $this->resetPage(); }
    public function updatingFinalPledgeFilters(){ $this->resetPage(); }

    // Add helper to clear all final pledge filters
    public function clearFinalPledgeFilters(){
        $this->finalPledgeFilters = [];
        session()->put('voter.finalPledgeFilters', $this->finalPledgeFilters);
        $this->resetPage();
    }

    // Called by Apply button when using deferred checkbox binding
    public function applyFinalPledgeFilters(){
        $this->finalPledgeFilters = array_values(array_unique(array_filter($this->finalPledgeFilters)));
        session()->put('voter.finalPledgeFilters', $this->finalPledgeFilters);
        $this->resetPage();
    }

    public function mount()
    {
        $this->elections = Election::orderBy('start_date','desc')->get(['id','name','status']);
        if(!$this->electionId && $this->elections->count()){
            $this->electionId = $this->elections->first()->id;
        }
        $this->opinionTypes = OpinionType::where('active',true)->orderBy('name')->get(['id','name']);
        $this->requestTypes = RequestType::where('active',true)->orderBy('name')->get(['id','name']); // load request types
        $saved = session('voter.finalPledgeFilters');
        if(is_array($saved)) { $this->finalPledgeFilters = $saved; }
    }

    public function updatedElectionId(){ $this->resetPage(); }

    public function setActiveTab($tab){ if(in_array($tab,['details','opinions','notes','requests'])) $this->activeTab = $tab; }

    public function viewVoter($id)
    {
        $this->authorize('voters-viewVoter');
        $this->viewingVoter = Directory::with([
            'party:id,short_name,logo,name',
            'subConsite:id,code,name',
            'property:id,name',
            'island:id,name,atoll_id',
            'island.atoll:id,code',
            'country:id,name'
        ])->find($id);

        if($this->viewingVoter){
            $this->notesLimit = 3; // reset notes limit when opening a voter
            $this->loadVoterRelations();
            $this->activeTab = 'details';
            $this->dispatch('show-view-voter-modal');
        }
    }

    private function loadVoterRelations(){
        if(!$this->viewingVoter) return;
        $id = $this->viewingVoter->id;
        $this->voterOpinions = VoterOpinion::with(['type:id,name','takenBy:id,name'])
            ->where('directory_id',$id)
            ->when($this->electionId, fn($q)=>$q->where('election_id',$this->electionId))
            ->latest()->get();
        $this->voterRequests = VoterRequest::with([
                'type:id,name','author:id,name','responses.responder:id,name'
            ])
            ->where('directory_id',$id)
            ->when($this->electionId, fn($q)=>$q->where('election_id',$this->electionId))
            ->latest()->get();
        $this->voterNotes = \App\Models\VoterNote::with(['author:id,name'])
            ->where('directory_id',$id)
            ->when($this->electionId, fn($q)=>$q->where('election_id',$this->electionId))
            ->latest()->get();
        // load pledges
        if($this->electionId){
            $pledges = VoterPledge::where('directory_id',$id)->where('election_id',$this->electionId)->get();
            $this->provisionalPledge = $pledges->firstWhere('type',VoterPledge::TYPE_PROVISIONAL);
            $this->finalPledge = $pledges->firstWhere('type',VoterPledge::TYPE_FINAL);
            $this->provisional_status = $this->provisionalPledge->status ?? '';
            $this->final_status = $this->finalPledge->status ?? '';
        }
        // Select first opinion if none selected or selected no longer exists
        if(!$this->selectedOpinionId || !$this->voterOpinions->firstWhere('id',$this->selectedOpinionId)){
            $this->selectedOpinionId = optional($this->voterOpinions->first())->id;
        }
        // Ensure request selection
        if(!$this->selectedRequestId || !$this->voterRequests->firstWhere('id',$this->selectedRequestId)){
            $this->selectedRequestId = optional($this->voterRequests->first())->id;
        }
        $this->syncSelectedOpinionSelection();
        $this->syncSelectedRequestSelection(); // new call
    }

    // Renamed to avoid Livewire treating it as a lifecycle hook (hydrateSelectedOpinion)
    private function syncSelectedOpinionSelection(){
        $this->selectedOpinion = $this->voterOpinions->firstWhere('id',$this->selectedOpinionId);
    }

    private function syncSelectedRequestSelection(){
        $this->selectedRequest = $this->voterRequests->firstWhere('id',$this->selectedRequestId);
        // reset response form when switching
        $this->request_response_text='';
        $this->request_response_status_after='';
    }

    public function selectOpinion($id){
        if($this->voterOpinions){
            $this->selectedOpinionId = $id;
            $this->syncSelectedOpinionSelection();
        }
    }

    public function selectRequest($id){
        if($this->voterRequests){
            $this->selectedRequestId = $id;
            $this->syncSelectedRequestSelection();
        }
    }

    public function openAddOpinion(){
        if(!$this->viewingVoter) return;
        $this->resetOpinionForm();
        $this->activeTab = 'opinions';
        $this->showAddOpinionModal = true;
        $this->dispatch('show-add-opinion-modal');
    }

    public function openAddRequest(){
        if(!$this->viewingVoter) return;
        $this->resetRequestForm();
        $this->activeTab = 'requests';
        $this->showAddRequestModal = true;
        $this->dispatch('show-add-request-modal');
    }

    public function openAddNote(){
        if(!$this->viewingVoter) return;
        $this->note_text='';
        $this->resetValidation();
        $this->activeTab = 'notes';
        $this->showAddNoteModal = true;
        $this->dispatch('show-add-note-modal');
    }

    // Open pledge modal without closing main modal
    public function openPledgeModal(){
        if(!$this->viewingVoter) return; $this->dispatch('show-pledge-modal');
    }
    public function closePledgeModal(){ $this->dispatch('hide-pledge-modal'); }

    public function resetOpinionForm(){
        $this->opinion_type_id='';
        $this->opinion_rating=null; // optional
        $this->opinion_note='';
        $this->opinion_status='success';
        $this->resetValidation();
    }

    public function resetRequestForm(){
        $this->request_type_id='';
        $this->request_amount=null;
        $this->request_note='';
        $this->resetValidation();
    }

    public function saveOpinion(){
        if(!$this->viewingVoter || !$this->electionId) return;
        $this->validate([
            'opinion_type_id' => 'required|exists:opinion_types,id',
            'opinion_rating' => 'nullable|integer|min:1|max:5',
            'opinion_note' => 'nullable|string|max:2000',
            'opinion_status' => 'required|in:success,failed_attempt,follow_up',
        ]);
        $opinion = VoterOpinion::create([
            'directory_id' => $this->viewingVoter->id,
            'election_id' => $this->electionId,
            'opinion_type_id' => $this->opinion_type_id,
            'rating' => $this->opinion_rating,
            'note' => $this->opinion_note,
            'status' => $this->opinion_status,
            'taken_by' => Auth::id(),
        ]);
        $this->loadVoterRelations();
        $this->selectedOpinionId = $opinion->id; // focus new
        $this->syncSelectedOpinionSelection();
        $this->activeTab = 'opinions';
        $this->dispatch('opinion-saved');
        $this->showAddOpinionModal = false;
        VoterDataChanged::dispatch('opinion_created', $this->viewingVoter->id, $this->electionId, ['opinion_id'=>$opinion->id]);
    }

    public function saveRequest(){
        if(!$this->viewingVoter || !$this->electionId) return;
        $this->validate([
            'request_type_id' => 'required|exists:request_types,id',
            'request_amount' => 'nullable|numeric|min:0',
            'request_note' => 'nullable|string|max:2000',
        ]);
        $req = VoterRequest::create([
            'directory_id' => $this->viewingVoter->id,
            'election_id' => $this->electionId,
            'request_type_id' => $this->request_type_id,
            'amount' => $this->request_amount,
            'note' => $this->request_note,
            'created_by' => Auth::id(),
            // request_number auto-generated in model boot
        ]);
        $this->loadVoterRelations();
        $this->selectedRequestId = $req->id; // focus new
        $this->syncSelectedRequestSelection();
        $this->activeTab = 'requests';
        $this->dispatch('request-saved');
        $this->showAddRequestModal = false;
        VoterDataChanged::dispatch('request_created', $this->viewingVoter->id, $this->electionId, ['request_id'=>$req->id]);
    }

    // Add a response to the currently selected voter request
    public function saveRequestResponse(){
        if(!$this->selectedRequest || !$this->electionId) return;
        $this->validate([
            'request_response_text' => 'required|string|max:4000',
            'request_response_status_after' => 'nullable|in:pending,in_progress,fulfilled,rejected'
        ]);
        $req = VoterRequest::find($this->selectedRequest->id);
        if(!$req) return;
        VoterRequestResponse::create([
            'voter_request_id' => $req->id,
            'responded_by' => Auth::id(),
            'response' => $this->request_response_text,
            'status_after' => $this->request_response_status_after ?: $req->status,
        ]);
        // optionally update status
        if($this->request_response_status_after && $this->request_response_status_after !== $req->status){
            $req->status = $this->request_response_status_after;
            $req->save();
        }
        $keepId = $req->id;
        $this->loadVoterRelations(); // reload including responses
        $this->selectedRequestId = $keepId;
        $this->syncSelectedRequestSelection();
        $this->dispatch('request-response-saved');
        VoterDataChanged::dispatch('request_response_created', $this->viewingVoter->id, $this->electionId, ['request_id'=>$req->id]);
    }

    public function saveNote(){
        if(!$this->viewingVoter || !$this->electionId) return;
        $this->validate([
            'note_text' => 'required|string|max:4000'
        ]);
        VoterNote::create([
            'directory_id' => $this->viewingVoter->id,
            'election_id' => $this->electionId,
            'note' => $this->note_text,
            'created_by' => Auth::id(),
        ]);
        $this->note_text='';
        $this->loadVoterRelations();
        // keep current limit (new note appears at top); do not reset
        $this->activeTab='notes';
        $this->dispatch('note-saved');
        $this->showAddNoteModal = false;
        VoterDataChanged::dispatch('note_created', $this->viewingVoter->id, $this->electionId);
        \Log::info('Note broadcast dispatched', ['voter'=>$this->viewingVoter->id,'election'=>$this->electionId]);
    }

    public function loadMoreNotes(){
        $this->notesLimit += 5; // increment by 5 each click
    }

    public function closeAddOpinion(){
        $this->showAddOpinionModal=false;
        $this->dispatch('hide-add-opinion-modal');
    }

    public function closeAddRequest(){
        $this->showAddRequestModal=false;
        $this->dispatch('hide-add-request-modal');
    }

    public function closeAddNote(){
        $this->showAddNoteModal=false;
        $this->dispatch('hide-add-note-modal');
    }

    public function closeViewVoter()
    {
        $this->viewingVoter = null;
        $this->voterOpinions = [];
        $this->voterRequests = [];
        $this->voterNotes = []; // reset notes
        $this->showAddOpinionModal = false;
        $this->showAddRequestModal = false;
        $this->showAddNoteModal = false;
        $this->activeTab = 'details';
        $this->selectedOpinionId = null;
        $this->selectedOpinion = null;
        $this->selectedRequestId = null; // reset request selection
        $this->selectedRequest = null;   // reset request model
        $this->note_text=''; // reset note text
        $this->request_response_text='';
        $this->request_response_status_after='';
        $this->notesLimit = 3; // reset
        $this->provisionalPledge=null; $this->finalPledge=null; $this->provisional_status=''; $this->final_status='';
        $this->dispatch('hide-view-voter-modal');
    }

    public function openRequestDetail($id){
        if($this->voterRequests){
            $this->selectedRequestId = $id;
            $this->syncSelectedRequestSelection();
            // Offcanvas removed: no dispatch
        }
    }

    public function closeRequestDetail(){
        // Simply clear selection if needed (optional)
        // $this->selectedRequestId = null; $this->selectedRequest = null;
        // No offcanvas to hide now
    }

    public function getVotersProperty()
    {
        if(!$this->electionId){
            return Directory::query()
                ->select(['id','name'])
                ->whereRaw('1=0')
                ->paginate($this->perPage);
        }

        $electionId = $this->electionId; // capture for closures

        $directoryQuery = Directory::query()
            ->with([
                'party:id,short_name,logo,name',
                'subConsite:id,code,name',
                'property:id,name',
                'island:id,name,atoll_id',
                'island.atoll:id,code',
                'country:id,name'
            ])
            ->select([
                'directories.id','name','profile_picture','id_card_number','gender','date_of_birth','phones','email','party_id','sub_consite_id','properties_id','street_address','address','country_id','island_id','status','created_at'
            ])
            // Only Active directories
            ->where('status','Active')
            ->addSelect([
                'opinions_count' => \App\Models\VoterOpinion::selectRaw('COUNT(*)')
                    ->whereColumn('directory_id','directories.id')
                    ->where('election_id',$electionId),
                'voter_requests_count' => \App\Models\VoterRequest::selectRaw('COUNT(*)')
                    ->whereColumn('directory_id','directories.id')
                    ->where('election_id',$electionId),
                'voter_notes_count' => \App\Models\VoterNote::selectRaw('COUNT(*)')
                    ->whereColumn('directory_id','directories.id')
                    ->where('election_id',$electionId),
                'final_pledge_status' => \App\Models\VoterPledge::select('status')
                    ->whereColumn('directory_id','directories.id')
                    ->where('election_id',$electionId)
                    ->where('type', \App\Models\VoterPledge::TYPE_FINAL)
                    ->limit(1),
                'provisional_pledge_status' => \App\Models\VoterPledge::select('status')
                    ->whereColumn('directory_id','directories.id')
                    ->where('election_id',$electionId)
                    ->where('type', \App\Models\VoterPledge::TYPE_PROVISIONAL)
                    ->limit(1),
                'latest_opinion_status' => \App\Models\VoterOpinion::select('status')
                    ->whereColumn('directory_id','directories.id')
                    ->where('election_id',$electionId)
                    ->latest()
                    ->limit(1),
            ]);

        // Limit to sub consites that the current user has access to
        $user = Auth::user();
        if ($user) {
            $directoryQuery->whereIn('sub_consite_id', $user->subConsites()->select('sub_consites.id'));
        }

        if($this->search){
            $term = '%'.$this->search.'%';
            $directoryQuery->where(function($qq) use ($term){
                $qq->where('name','like',$term)
                   ->orWhere('email','like',$term)
                   ->orWhere('id_card_number','like',$term);
            });
        }

        // Multi final pledge filter logic
        if(!empty($this->finalPledgeFilters)){
            $selected = array_filter($this->finalPledgeFilters); // remove empties
            if(!empty($selected)){
                $includePending = in_array('pending',$selected,true);
                $statuses = array_values(array_filter($selected, fn($s)=> $s !== 'pending'));
                $directoryQuery->where(function($q) use ($includePending,$statuses,$electionId){
                    if($statuses){
                        $q->whereExists(function($sub) use ($statuses,$electionId){
                            $sub->selectRaw(1)->from('voter_pledges')
                                ->whereColumn('voter_pledges.directory_id','directories.id')
                                ->where('voter_pledges.election_id',$electionId)
                                ->where('voter_pledges.type', \App\Models\VoterPledge::TYPE_FINAL)
                                ->whereIn('voter_pledges.status',$statuses);
                        });
                        if($includePending){
                            $q->orWhereNotExists(function($sub) use ($electionId){
                                $sub->selectRaw(1)->from('voter_pledges')
                                    ->whereColumn('voter_pledges.directory_id','directories.id')
                                    ->where('voter_pledges.election_id',$electionId)
                                    ->where('voter_pledges.type', \App\Models\VoterPledge::TYPE_FINAL);
                            });
                        }
                    } elseif($includePending){
                        $q->whereNotExists(function($sub) use ($electionId){
                            $sub->selectRaw(1)->from('voter_pledges')
                                ->whereColumn('voter_pledges.directory_id','directories.id')
                                ->where('voter_pledges.election_id',$electionId)
                                ->where('voter_pledges.type', \App\Models\VoterPledge::TYPE_FINAL);
                        });
                    }
                });
            }
        }

        return $directoryQuery->latest()->paginate($this->perPage);
    }

    public function render()
    {
         $this->authorize('voters-render');

        $voters = $this->voters;

        // Total voters (Active) participating in the selected election
        $totalVoters = 0;
        if ($this->electionId) {
            $totalVoters = Directory::where('status','Active')
                ->whereIn('sub_consite_id', function($q){
                    $q->select('sub_consite_id')->from('participants')->where('election_id', $this->electionId);
                })
                ->count();
        }
        $this->calculatePledgeTotals();
        return view('livewire.election.voter-management', [
            'voters' => $voters,
            'pageTitle' => $this->pageTitle,
            'elections' => $this->elections,
            'totalVoters' => $totalVoters,
            'totalsProv' => $this->totalsProv,
            'totalsFinal' => $this->totalsFinal,
        ])->layout('layouts.master');
    }

    private function calculatePledgeTotals(): void
    {
        Log::info('PledgeTotals: start', ['electionId'=>$this->electionId]);
        if (! $this->electionId) {
            $this->totalsProv = ['yes'=>0,'no'=>0,'neutral'=>0,'pending'=>0];
            $this->totalsFinal = ['yes'=>0,'no'=>0,'neutral'=>0,'pending'=>0];
            Log::info('PledgeTotals: no electionId');
            return;
        }

        $user = Auth::user();
        $allowedSubconsiteIds = $user ? $user->subConsites()->pluck('sub_consites.id')->all() : [];
        Log::info('PledgeTotals: allowed subconsite ids', ['count'=>count($allowedSubconsiteIds),'ids'=>$allowedSubconsiteIds]);
        if (empty($allowedSubconsiteIds)) {
            $this->totalsProv = ['yes'=>0,'no'=>0,'neutral'=>0,'pending'=>0];
            $this->totalsFinal = ['yes'=>0,'no'=>0,'neutral'=>0,'pending'=>0];
            Log::warning('PledgeTotals: user has no assigned sub consites');
            return;
        }

        $dirIds = Directory::query()
            ->select('directories.id')
            ->where('status','Active')
            ->whereIn('sub_consite_id', $allowedSubconsiteIds)
            ->whereIn('sub_consite_id', function($q){
                $q->select('sub_consite_id')->from('participants')->where('election_id', $this->electionId);
            })
            ->pluck('id')
            ->all();
        Log::info('PledgeTotals: filtered directory ids', ['count'=>count($dirIds),'sample'=>array_slice($dirIds,0,10)]);

        if (empty($dirIds)) {
            $this->totalsProv = ['yes'=>0,'no'=>0,'neutral'=>0,'pending'=>0];
            $this->totalsFinal = ['yes'=>0,'no'=>0,'neutral'=>0,'pending'=>0];
            Log::warning('PledgeTotals: no directories matched filters');
            return;
        }

        // Provisional totals over filtered directories
        $provRows = DB::table('voter_pledges')
            ->where('election_id', $this->electionId)
            ->where('type', \App\Models\VoterPledge::TYPE_PROVISIONAL)
            ->whereIn('directory_id', $dirIds)
            ->selectRaw('LOWER(COALESCE(status, "pending")) as s, COUNT(*) as c')
            ->groupBy('s')
            ->get();
        $tp = ['yes'=>0,'no'=>0,'neutral'=>0,'pending'=>0];
        $countWithProvPledge = 0;
        foreach ($provRows as $r) {
            $key = in_array($r->s, ['yes','no','neutral','pending'], true) ? $r->s : 'pending';
            $tp[$key] = ($tp[$key] ?? 0) + (int)$r->c;
            $countWithProvPledge += (int)$r->c; // counts rows that have a pledge (including null)
        }
        // Pending = directories without any provisional pledge row
        $tp['pending'] = max(0, count($dirIds) - $countWithProvPledge);

        // Final totals over filtered directories (Yes/No/Neutral, Pending = no row)
        $finalRows = DB::table('voter_pledges')
            ->where('election_id', $this->electionId)
            ->where('type', \App\Models\VoterPledge::TYPE_FINAL)
            ->whereIn('directory_id', $dirIds)
            ->selectRaw('LOWER(COALESCE(status, "pending")) as s, COUNT(*) as c')
            ->groupBy('s')
            ->get();
        $tf = ['yes'=>0,'no'=>0,'neutral'=>0,'pending'=>0];
        $countWithFinalPledge = 0;
        foreach ($finalRows as $r) {
            $key = in_array($r->s, ['yes','no','neutral','pending'], true) ? $r->s : 'pending';
            $tf[$key] = ($tf[$key] ?? 0) + (int)$r->c;
            $countWithFinalPledge += (int)$r->c;
        }
        $tf['pending'] = max(0, count($dirIds) - $countWithFinalPledge);
        $this->totalsFinal = $tf;

        $this->totalsProv = $tp;
        Log::info('PledgeTotals: computed', ['prov'=>$tp,'final'=>$tf]);
    }

    public function setProvisionalPledge($status)
    {
        if(!$this->viewingVoter || !$this->electionId) return;
        if(!in_array($status, VoterPledge::STATUSES)) return;
        $pledge = VoterPledge::updateOrCreate([
            'directory_id' => $this->viewingVoter->id,
            'election_id'  => $this->electionId,
            'type'         => VoterPledge::TYPE_PROVISIONAL,
        ],[
            'status'      => $status,
            'created_by'  => Auth::id(),
        ]);
        $this->provisionalPledge = $pledge;
        $this->provisional_status = $pledge->status;
        $this->dispatch('pledge-updated');
        // NEW: broadcast so other windows update
        VoterDataChanged::dispatch('provisional_pledge_updated', $this->viewingVoter->id, $this->electionId, [
            'status' => $pledge->status,
            'type' => 'provisional'
        ]);
    }

    public function setFinalPledge($status)
    {
        if(!$this->viewingVoter || !$this->electionId) return;
        if(!in_array($status, VoterPledge::STATUSES)) return;
        $pledge = VoterPledge::updateOrCreate([
            'directory_id' => $this->viewingVoter->id,
            'election_id'  => $this->electionId,
            'type'         => VoterPledge::TYPE_FINAL,
        ],[
            'status'      => $status,
            'created_by'  => Auth::id(),
        ]);
        $this->finalPledge = $pledge;
        $this->final_status = $pledge->status;
        $this->dispatch('pledge-updated');
        // NEW: broadcast so other windows update
        VoterDataChanged::dispatch('final_pledge_updated', $this->viewingVoter->id, $this->electionId, [
            'status' => $pledge->status,
            'type' => 'final'
        ]);
    }

    public function setOpinionRating($value){
        if($value === null || $value === 'null' || $value === ''){ $this->opinion_rating = null; return; }
        $v = (int)$value; if($v>=1 && $v<=5){ $this->opinion_rating = $v; }
    }

    public function getLatestOpinionStatusProperty(){
        return $this->voterOpinions[0]->status ?? null; // collection already ordered latest first
    }

    public function getListeners(){
        return [
            'echo:elections.voters,VoterDataChanged' => 'handleRealtimeUpdate',
            'reverb-voter-update' => 'handleRealtimeUpdate',
            'window:voter-data-updated' => 'handleRealtimeUpdate', // NEW fallback browser event
        ];
    }

    private function refreshViewingVoter($id){
        $this->viewingVoter = Directory::with([
            'party:id,short_name,logo,name',
            'subConsite:id,code,name',
            'property:id,name',
            'island:id,name,atoll_id',
            'island.atoll:id,code',
            'country:id,name'
        ])->find($id);
    }

    public function handleRealtimeUpdate($payload = null){
        // Normalize payload into array
        if($payload === null){
            $payload = [];
        } elseif(is_string($payload)) {
            $decoded = json_decode($payload, true);
            if(json_last_error() === JSON_ERROR_NONE) { $payload = $decoded; }
            else { $payload = ['raw' => $payload]; }
        } elseif(!is_array($payload)) {
            $payload = (array)$payload;
        }
        \Log::info('Realtime payload received', $payload);
        if(isset($payload['election_id']) && $this->electionId && (string)$payload['election_id'] !== (string)$this->electionId){
            return; // different election
        }
        $incomingVoterId = $payload['voter_id'] ?? null;
        $changeType = $payload['change_type'] ?? '';
        // Refresh pagination dataset (list) only; pagination object is computed so triggering re-render is enough
        $this->resetPage();
        if($this->viewingVoter && $incomingVoterId && (string)$incomingVoterId === (string)$this->viewingVoter->id){
            $this->refreshViewingVoter($this->viewingVoter->id);
            $this->loadVoterRelations();
            $this->modalRefreshTick++;
            \Log::info('Modal refreshed for voter', ['voter'=>$this->viewingVoter->id,'tick'=>$this->modalRefreshTick,'changeType'=>$changeType]);
            $this->dispatch('voter-modal-refreshed');
        }
        // Removed $this->dispatch('$refresh'); to avoid full component remount which interrupted open modal state.
    }

    public function openPledgeFor($directoryId, $type = null)
    {
        if ($type === 'provisional') {
            $this->authorize('voters-openProvisionalPledge');
        } elseif ($type === 'final') {
            $this->authorize('voters-openFinalPledge');
        }
        $this->viewingVoter = \App\Models\Directory::with(['party:id,short_name,logo,name','subConsite:id,code,name'])
            ->find($directoryId);
        if (! $this->viewingVoter) { return; }
        $t = in_array($type, ['provisional','final']) ? $type : null;
        $this->pledgeType = $t;
        if ($t === 'provisional') {
            $this->dispatch('show-provisional-pledge-modal');
        } elseif ($t === 'final') {
            $this->dispatch('show-final-pledge-modal');
        }
    }

    public function openProvisionalPledgeModal($directoryId = null)
    {
        $this->authorize('voters-openProvisionalPledge');
        if ($directoryId) {
            $this->viewingVoter = \App\Models\Directory::find($directoryId);
        }
        if (! $this->viewingVoter) { return; }
        $this->pledgeType = 'provisional';
        $this->dispatch('show-provisional-pledge-modal');
    }

    public function openFinalPledgeModal($directoryId = null)
    {
        $this->authorize('voters-openFinalPledge');
        if ($directoryId) {
            $this->viewingVoter = \App\Models\Directory::find($directoryId);
        }
        if (! $this->viewingVoter) { return; }
        $this->pledgeType = 'final';
        $this->dispatch('show-final-pledge-modal');
    }

    public function saveProvisionalPledge(): void
    {
        if (! $this->viewingVoter || ! $this->electionId) return;
        $dirId = $this->viewingVoter->id;
        $status = $this->provisional_status ?: null;

        // Read previous
        $prev = \App\Models\VoterPledge::where('directory_id',$dirId)
            ->where('election_id',$this->electionId)
            ->where('type', \App\Models\VoterPledge::TYPE_PROVISIONAL)
            ->value('status');

        $pledge = \App\Models\VoterPledge::firstOrNew([
            'directory_id' => $dirId,
            'election_id' => $this->electionId,
            'type' => \App\Models\VoterPledge::TYPE_PROVISIONAL,
        ]);
        $pledge->status = $status;
        if (! $pledge->exists) {
            $pledge->created_by = auth()->id();
        }
        $pledge->save();

        // Event log
        EventLog::create([
            'user_id' => auth()->id(),
            'event_tab' => 'Election',
            'event_entry_id' => $dirId,
            'event_type' => 'Provisional Pledge Updated',
            'description' => 'Provisional pledge changed for voter',
            'event_data' => [
                'election_id' => $this->electionId,
                'directory_id' => $dirId,
                'previous_status' => $prev,
                'new_status' => $status,
            ],
            'ip_address' => request()->ip(),
        ]);

        $this->loadVoterRelations();
        $this->dispatch('swal', [
            'title' => 'Saved',
            'text' => 'Provisional pledge updated.',
            'icon' => 'success',
            'buttonsStyling' => false,
            'confirmButtonText' => 'Ok',
            'confirmButton' => 'btn btn-primary',
        ]);
        $this->dispatch('hide-provisional-pledge-modal');
    }

    public function saveFinalPledge(): void
    {
        if (! $this->viewingVoter || ! $this->electionId) return;
        $dirId = $this->viewingVoter->id;
        $status = $this->final_status ?: null;

        // Read previous
        $prev = \App\Models\VoterPledge::where('directory_id',$dirId)
            ->where('election_id',$this->electionId)
            ->where('type', \App\Models\VoterPledge::TYPE_FINAL)
            ->value('status');

        $pledge = \App\Models\VoterPledge::firstOrNew([
            'directory_id' => $dirId,
            'election_id' => $this->electionId,
            'type' => \App\Models\VoterPledge::TYPE_FINAL,
        ]);
        $pledge->status = $status;
        if (! $pledge->exists) {
            $pledge->created_by = auth()->id();
        }
        $pledge->save();

        // Event log
        EventLog::create([
            'user_id' => auth()->id(),
            'event_tab' => 'Election',
            'event_entry_id' => $dirId,
            'event_type' => 'Final Pledge Updated',
            'description' => 'Final pledge changed for voter',
            'event_data' => [
                'election_id' => $this->electionId,
                'directory_id' => $dirId,
                'previous_status' => $prev,
                'new_status' => $status,
            ],
            'ip_address' => request()->ip(),
        ]);

        $this->loadVoterRelations();
        $this->dispatch('swal', [
            'title' => 'Saved',
            'text' => 'Final pledge updated.',
            'icon' => 'success',
            'buttonsStyling' => false,
            'confirmButtonText' => 'Ok',
            'confirmButton' => 'btn btn-success',
        ]);
        $this->dispatch('hide-final-pledge-modal');
    }
}
