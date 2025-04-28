<?php

namespace App\Livewire\Auth;

use Livewire\Component;

class ConfirmPassword extends Component
{
    public function render()
    {
        return view('livewire.auth.confirm-password')
            ->layout('components.layouts.auth')
            ->title('Confirm Password');
    }
}
