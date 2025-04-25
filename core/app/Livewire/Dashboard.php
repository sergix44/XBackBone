<?php

namespace App\Livewire;

use Livewire\Component;

class Dashboard extends Component
{
    public bool $showUploadDrawer = false;

    public function render()
    {
        return view('livewire.dashboard')
            ->title('Gallery');
    }
}
