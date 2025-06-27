<?php

namespace App\Livewire\Directory;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Directory;
use App\Models\DirectoryType;
use App\Models\RegistrationType;
use App\Models\Country;
use App\Models\Island;

class DirectoryManagement extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $perPage = 10;
    public $pageTitle = 'Directory';

    public function render()
    {
        $directory = Directory::where('name', 'like', '%' . $this->search . '%')
        ->orWhere('email', 'like', '%' . $this->search . '%')
        ->orWhere('registration_number', 'like', '%' . $this->search . '%')
        ->latest()
        ->paginate($this->perPage);

        return view('livewire.directory.directory-management', [
            'directory' => $directory,
            'pageTitle' => $this->pageTitle,
        ])->layout('layouts.master');
    }

   
}
