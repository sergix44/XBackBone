<div>
    <div>
        <x-form method="post" action="{{ route('password.update') }}" no-separator>
            @csrf
            <x-input type="hidden" name="token" value="{{ request()->route('token') }}"/>
            <x-input placeholder="E-mail" label="E-mail" type="email" name="email" value="{{ request()->email }}" error-field="email" inline readonly/>
            <x-input placeholder="Password" label="Password" type="password" name="password" error-field="password" inline/>
            <x-input placeholder="Confirm Password" label="Confirm Password" type="password" name="password_confirmation" error-field="password_confirmation" inline/>

            <div class="flex flex-col gap-2 mt-6">
                <x-button label="{{ __('passwords.reset-password') }}" class="btn-primary btn-block" type="submit"/>
            </div>
        </x-form>
    </div>
</div>
