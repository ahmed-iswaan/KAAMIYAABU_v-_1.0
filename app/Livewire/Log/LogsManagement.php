<?php

namespace App\Livewire\Log;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class LogsManagement extends Component
{
    use AuthorizesRequests;

    public function render()
    {
        $this->authorize('log-render');

        return view('livewire.log.logs-management');
    }
}
