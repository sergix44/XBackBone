<?php

namespace App\Livewire\Auth;

use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Exceptions\ToastException;
use Mary\Traits\Toast;

class ForgotPassword extends Component
{
    use Toast;

    #[Validate('email')]
    public string $email = '';

    public function sendPasswordResetLink()
    {
        $this->validate();

        $this->notify('Password reset link sent. Please check your email.');
    }

    public function render()
    {
        return view('livewire.auth.forgot-password')
            ->layout('components.layouts.auth', ['title' => 'Forgot Password']);
    }
}
