<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;

class Dashboard extends Component
{
    use WithFileUploads;

    public bool $showUploadDrawer = false;

    public $files;

    public function render()
    {
        return view('livewire.dashboard')
            ->title('Gallery');
    }
}
