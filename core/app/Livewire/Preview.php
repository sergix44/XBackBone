<?php

namespace App\Livewire;

use Livewire\Component;

class Preview extends Component
{
    public function render()
    {
        abort(404);

        return view('livewire.preview');
    }
}
