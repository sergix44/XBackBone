<?php

namespace App\Livewire\User;

use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Laravel\Pennant\Feature;
use Livewire\Component;
use Mary\Traits\Toast;

class Profile extends Component
{
    use Toast;

    protected $listeners = [
        'reload' => '$refresh',
    ];

    public string $name = '';
    public string $email = '';
    public ?string $currentPassword = null;
    public ?string $newPassword = null;
    public ?string $theme = null;

    public array $themes = [];

    public function mount()
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->theme = $user->theme ?? Feature::value('default-theme');

        $this->themes = collect(config('themes'))
            ->map(fn($theme, $key) => (object) ['id' => $theme, 'name' => $theme])
            ->sortBy('name')
            ->prepend(['id' => null, 'name' => '(default)'])
            ->toArray();
    }

    public function updateTheme(): void
    {
        $user = auth()->user();
        $user->theme = $this->theme;
        $user->save();

        $this->success(__('Theme updated successfully!'), redirectTo: '#');
    }

    public function updateProfile(): void
    {
        app(UpdateUserProfileInformation::class)->update(
            auth()->user(),
            ['name' => $this->name, 'email' => $this->email]
        );

        if ($this->currentPassword) {
            app(UpdateUserPassword::class)->update(
                auth()->user(),
                ['current_password' => $this->currentPassword, 'password' => $this->newPassword]
            );
        }

        $this->success(__('Profile updated successfully!'));
    }

    public function render()
    {
        return view('livewire.user.profile')->title('Profile');
    }
}
