<?php

namespace App\Livewire\Property;

use Livewire\Component;
use App\Models\Property;

class ViewProperty extends Component
{
    public Property $property;
    public $pageTitle = 'Property Management';

    /**
     * Laravel will automatically resolve the Property by its UUID.
     */
    public function mount(Property $property)
    {
        $this->property = $property;
    }

    public function render()
    {
       return view('livewire.property.view-property', [
            'pageTitle'  => $this->pageTitle,
        ])->layout('layouts.master');
    }
}
