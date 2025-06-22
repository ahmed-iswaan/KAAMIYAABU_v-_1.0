<?php

namespace App\Livewire\Waste;

use Livewire\Component;

class WasteManagement extends Component
{
    public function render()
    {
        return view('livewire.waste.waste-management')->layout('layouts.master');
    }
}
