<?php

namespace App\Livewire\Auth;

use Livewire\Component;

class ResetPassword extends Component
{
    public function render()
    {
        return view('livewire.auth.reset-password')
            ->layout('components.layouts.auth')
            ->title('Reset Password');
    }
}
