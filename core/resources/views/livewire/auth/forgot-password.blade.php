<div>
    <x-form method="post" action="{{ route('password.email') }}" no-separator>
        @csrf
        <x-input placeholder="E-mail" label="E-mail" type="email" name="email" error-field="email" inline/>

        <div class="flex flex-col gap-2 mt-6">
            <x-button label="{{ __('passwords.reset-password') }}" class="btn-primary btn-block" type="submit"/>
        </div>
    </x-form>
</div>
