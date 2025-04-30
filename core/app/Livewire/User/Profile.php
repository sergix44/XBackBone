<?php

namespace App\Livewire\User;

use App\Models\User;
use Livewire\Component;

class Profile extends Component
{
    public User $user;

    public array $themes = [];

    public function mount()
    {
        $this->user = auth()->user();
        $this->themes = collect(config('themes'))->map(function ($theme, $key) {
            return (object) ['id' => $key, 'name' => $theme];
        })->toArray();
    }

    public function render()
    {
        return view('livewire.user.profile')->title('Profile');
    }
}
