<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Directory;
use Illuminate\Support\Facades\DB;

class DashboardOverview extends Component
{
    public $totalPopulation = 0;
    public $maleCount = 0;
    public $femaleCount = 0;

    // Per-island chart data
    public $islandLabels = [];
    public $islandMaleCounts = [];
    public $islandFemaleCounts = [];
    public $islandTotals = [];

    public function mount()
    {
        // Aggregate across all islands that have directory data (Active only)
        $rows = DB::table('directories')
            ->join('islands','directories.island_id','=','islands.id')
            ->select('islands.name as island_name',
                DB::raw("SUM(CASE WHEN directories.gender='male' THEN 1 ELSE 0 END) as male_count"),
                DB::raw("SUM(CASE WHEN directories.gender='female' THEN 1 ELSE 0 END) as female_count"),
                DB::raw('COUNT(*) as total_count')
            )
            ->where('directories.status','Active')
            ->whereNotNull('directories.island_id')
            ->groupBy('directories.island_id','islands.name')
            ->orderBy('islands.name')
            ->get();

        $this->totalPopulation = $rows->sum('total_count');
        $this->maleCount = $rows->sum('male_count');
        $this->femaleCount = $rows->sum('female_count');

        $this->islandLabels = $rows->pluck('island_name')->toArray();
        $this->islandMaleCounts = $rows->pluck('male_count')->toArray();
        $this->islandFemaleCounts = $rows->pluck('female_count')->toArray();
        $this->islandTotals = $rows->pluck('total_count')->toArray();
    }

    public function render()
    {
        return view('livewire.dashboard-overview', [
            'totalPopulation'   => $this->totalPopulation,
            'maleCount'         => $this->maleCount,
            'femaleCount'       => $this->femaleCount,
            'islandLabels'      => $this->islandLabels,
            'islandMaleCounts'  => $this->islandMaleCounts,
            'islandFemaleCounts'=> $this->islandFemaleCounts,
            'islandTotals'      => $this->islandTotals,
        ])->layout('layouts.master');
    }
}
