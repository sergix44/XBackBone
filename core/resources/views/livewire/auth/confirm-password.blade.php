<div>
    <div>
        <x-form method="post" action="{{ route('password.confirm.store') }}" no-separator>
            @csrf
            <x-input placeholder="Password" label="Password" type="password" name="password" error-field="password" inline/>

            <div class="flex flex-col gap-2 mt-6">
                <x-button label="Confirm" class="btn-primary btn-block" type="submit"/>
            </div>
        </x-form>
    </div>
</div>
