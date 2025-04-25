<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Mary\Traits\Toast;

class ForgotPassword extends Component
{
    use Toast;

    public function render()
    {
        return view('livewire.auth.forgot-password')
            ->layout('components.layouts.auth')
            ->title('Forgot Password');
    }
}
