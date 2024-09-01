<div>
    <div>
        <x-errors title="Oops!" icon="o-face-frown" class="mb-4" />
        <x-form method="post" action="{{ route('password.update') }}" no-separator>
            @csrf
            <x-input type="hidden" name="token" value="{{ request()->route('token') }}"/>
            <x-input label="E-mail" type="email" name="email" value="{{ request()->email }}" inline readonly/>
            <x-input label="Password" type="password" name="password" inline/>
            <x-input label="Confirm Password" type="password" name="password_confirmation" inline/>

            <div class="flex flex-col gap-2 mt-6">
                <x-button label="{{ __('passwords.reset-password') }}" class="btn-primary btn-block" type="submit"/>
            </div>
        </x-form>
    </div>
</div>
