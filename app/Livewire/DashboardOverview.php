<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Directory;
use App\Models\DirectoryType;
use Carbon\Carbon;

class DashboardOverview extends Component
{
    public $totalPopulation;
    public $maleCount;
    public $femaleCount;
    public $ageMaleCounts   = [];
    public $ageFemaleCounts = [];

    public function mount()
    {
        // 1) Get “Individual” type
        $individualType = DirectoryType::firstWhere('name', 'Individual');

        // 2) All inland individuals in M. Mulah
        $records = Directory::where('location_type', 'inland')
            ->where('directory_type_id', $individualType->id)
            ->whereHas('island', fn($q) => $q->where('name', 'Mulah'))
            ->get();

        // 3) Totals
        $this->totalPopulation = $records->count();
        $this->maleCount       = $records->where('gender', 'male')->count();
        $this->femaleCount     = $records->where('gender', 'female')->count();

        // 4) Prepare empty buckets
        $labels        = ['0-17','18-25','26-35','36-45','46-64','65+'];
        $maleBuckets   = array_fill_keys($labels, 0);
        $femaleBuckets = array_fill_keys($labels, 0);

        // 5) Fill buckets by gender
        foreach ($records as $r) {
            if (! $r->date_of_birth) continue;
            $age = Carbon::parse($r->date_of_birth)->age;

            if     ($age < 18)   $key = '0-17';
            elseif ($age < 26)   $key = '18-25';
            elseif ($age < 36)   $key = '26-35';
            elseif ($age < 46)   $key = '36-45';
            elseif ($age < 65)   $key = '46-64';
            else                 $key = '65+';

            if ($r->gender === 'male') {
                $maleBuckets[$key]++;
            } else {
                $femaleBuckets[$key]++;
            }
        }

        $this->ageMaleCounts   = $maleBuckets;
        $this->ageFemaleCounts = $femaleBuckets;
    }

    public function render()
    {
        return view('livewire.dashboard-overview', [
            'totalPopulation'  => $this->totalPopulation,
            'maleCount'        => $this->maleCount,
            'femaleCount'      => $this->femaleCount,
            'ageMaleCounts'    => $this->ageMaleCounts,
            'ageFemaleCounts'  => $this->ageFemaleCounts,
        ])->layout('layouts.master');
    }
}
