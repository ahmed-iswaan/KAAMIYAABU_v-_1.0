<?php
namespace App\Livewire\Election;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\VoterRequest;
use App\Models\RequestType;
use App\Models\Election;
use App\Models\Directory;
use App\Models\VoterRequestResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class RequestsManagement extends Component
{
    use WithPagination,AuthorizesRequests;

    public $search='';
    public $status='';
    public $requestTypeId=''; // clearer name avoids any potential internal collisions
    public $electionId=null;
    public $perPage=15;
    public $elections=[];
    public $requestTypes=[];
    // Added advanced filter fields
    public $amountMin='';
    public $amountMax='';
    public $dateFrom='';
    public $dateTo='';

    // Drawer/detail state
    public $showDrawer = false; // control detail drawer
    public $activeRequestId = null; // selected request id
    public $activeRequest = null; // hydrated request with relations
    public $response_text = '';
    public $response_status_after = '';

    protected $listeners = ['refreshRequests' => '$refresh'];

    protected $queryString=[
        'search'=>['except'=>''],
        'status'=>['except'=>''],
        'requestTypeId'=>['except'=>''],
        'electionId'=>['except'=>null],
    ];

    public function updatingSearch(){ $this->resetPage(); }
    public function updatingStatus(){ $this->resetPage(); }
    public function updatingRequestTypeId(){ $this->resetPage(); }
    public function updatingElectionId(){ $this->resetPage(); }
    // Reset page when advanced filters change
    public function updatingAmountMin(){ $this->resetPage(); }
    public function updatingAmountMax(){ $this->resetPage(); }
    public function updatingDateFrom(){ $this->resetPage(); }
    public function updatingDateTo(){ $this->resetPage(); }

    public function mount(){
        $this->elections = Election::orderBy('start_date','desc')->get(['id','name']);
        if(!$this->electionId && $this->elections->count()) $this->electionId = $this->elections->first()->id;
        $this->requestTypes = RequestType::where('active',true)->orderBy('name')->get(['id','name']);
    }

    public function resetFilters(){
        $this->status='';
        $this->requestTypeId='';
        $this->amountMin='';
        $this->amountMax='';
        $this->dateFrom='';
        $this->dateTo='';
        $this->resetPage();
    }

    public function getRequestsProperty(){
        $q = VoterRequest::query()->with([
            'type:id,name','author:id,name','voter:id,name,id_card_number,profile_picture','election:id,name'
        ]);
        if($this->electionId) $q->where('election_id',$this->electionId);
        if($this->status) $q->where('status',$this->status);
        if($this->requestTypeId) $q->where('request_type_id',$this->requestTypeId);
        // Advanced filters
        if($this->amountMin !== '' && $this->amountMin !== null) $q->where('amount','>=',$this->amountMin);
        if($this->amountMax !== '' && $this->amountMax !== null) $q->where('amount','<=',$this->amountMax);
        if($this->dateFrom) $q->whereDate('created_at','>=',$this->dateFrom);
        if($this->dateTo) $q->whereDate('created_at','<=',$this->dateTo);
        if($this->search){
            $term='%'.$this->search.'%';
            $q->where(function($qq) use($term){
                $qq->whereHas('voter', function($qqq) use($term){
                    $qqq->where('name','like',$term)->orWhere('id_card_number','like',$term);
                })->orWhere('request_number','like',$term);
            });
        }
        return $q->latest()->paginate($this->perPage);
    }

    public function openRequest($id){
        $this->activeRequestId = $id;
        $this->loadActiveRequest();
        if($this->activeRequest){
            $this->showDrawer = true;
            $this->dispatch('show-request-drawer');
        }
    }

    public function loadActiveRequest(){
        if(!$this->activeRequestId) return;
        $this->activeRequest = VoterRequest::with([
            'type:id,name',
            'author:id,name',
            // Expanded voter fields & relationships for address/phones display
            'voter:id,name,id_card_number,profile_picture,phones,email,address,street_address,current_address,current_street_address,country_id,current_country_id,properties_id,current_properties_id',
            'voter.country:id,name',
            'voter.currentCountry:id,name',
            'voter.property:id,name',
            'voter.currentProperty:id,name',
            'responses.responder:id,name'
        ])->find($this->activeRequestId);
    }

    public function closeDrawer(){
        $this->showDrawer=false;
        $this->activeRequestId=null;
        $this->activeRequest=null;
        $this->response_text='';
        $this->response_status_after='';
        $this->dispatch('hide-request-drawer');
    }

    public function saveResponse(){
        if(!$this->activeRequest) return;
        $this->validate([
            'response_text' => 'required|string|max:4000',
            'response_status_after' => ['nullable', Rule::in(['pending','in_progress','fulfilled','rejected'])]
        ]);
        $resp = VoterRequestResponse::create([
            'voter_request_id' => $this->activeRequest->id,
            'responded_by' => Auth::id(),
            'response' => $this->response_text,
            'status_after' => $this->response_status_after ?: $this->activeRequest->status,
        ]);
        // Update status if changed
        if($this->response_status_after && $this->response_status_after !== $this->activeRequest->status){
            $this->activeRequest->status = $this->response_status_after;
            $this->activeRequest->save();
        }
        $this->response_text='';
        $this->response_status_after='';
        $this->loadActiveRequest();
        $this->dispatch('response-saved');
    }

    public function updateStatusInline($id, $newStatus){
        if(!in_array($newStatus,['pending','in_progress','fulfilled','rejected'])) return;
        $req = VoterRequest::find($id);
        if(!$req) return;
        $req->status = $newStatus;
        $req->save();
        if($this->activeRequestId === $id){
            $this->loadActiveRequest();
        }
    }

    public function render(){
        $this->authorize('requests-voters-render');
        return view('livewire.election.requests-management', [
            'requests' => $this->requests,
        ])->layout('layouts.master');
    }
}
