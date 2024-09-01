<?php

namespace App\Livewire\Auth;

use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Exceptions\ToastException;
use Mary\Traits\Toast;

class ForgotPassword extends Component
{
    use Toast;

    public function render()
    {
        return view('livewire.auth.forgot-password')
            ->layout('components.layouts.auth', ['title' => 'Forgot Password']);
    }
}
