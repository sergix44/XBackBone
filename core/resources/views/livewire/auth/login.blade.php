<div>
    <x-form wire:submit="authenticate" no-separator>
        <x-input label="Username" type="email" wire:model="form.email" inline/>
        <x-input label="Password" type="password" wire:model="form.password" inline/>
        <x-checkbox label="Remember me" wire:model="form.remember"/>

        <div class="flex flex-col gap-2 mt-6">
            <x-button label="Login" class="btn-primary btn-block" type="submit" spinner="authenticate"/>
            <x-button label="Register" class="btn-block"/>
            <x-button label="Forgot Password?" class="btn-link btn-sm"/>
        </div>
    </x-form>
</div>
