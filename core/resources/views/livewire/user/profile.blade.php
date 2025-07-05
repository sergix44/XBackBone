<div class="grid grid-cols-12 gap-6">
    <div class="md:col-span-3 col-span-12">
        <x-menu class="rounded-lg bg-base-100">
            <x-menu-item title="Profile" icon="o-user-circle"/>
            <x-menu-item title="Tokens" icon="o-command-line"/>
            <x-menu-item title="Export Data" icon="o-arrow-right-start-on-rectangle"/>
            <x-menu-item title="Delete Account" icon="o-user-minus" class="text-red-500"/>
        </x-menu>
    </div>
    <div class="md:col-span-9 col-span-12 flex flex-col gap-2">
        <div class="card bg-base-100">
            <div class="card-body">
                <x-avatar :image="auth()->user()->avatar" class="!w-22">
                    <x-slot:title class="text-3xl !font-bold pl-2">
                        {{ $name }}
                    </x-slot:title>

                    <x-slot:subtitle class="grid gap-1 mt-2 pl-2 text-xs">
                        <x-icon name="o-paper-airplane" label="12 posts"/>
                        <x-icon name="o-chat-bubble-left" label="45 comments"/>
                    </x-slot:subtitle>
                </x-avatar>
                <div class="divider mt-8">Profile</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input placeholder="Username" label="Username" type="text" wire:model="name" inline/>
                    <x-input placeholder="E-mail" label="E-mail" type="email" wire:model="email" inline/>

                    <x-input placeholder="Current password" label="Current password" type="password" inline/>
                    <x-input placeholder="New password" label="New password" type="password" inline/>

                    <div>
                        <x-button label="Update" class="btn-primary" wire:click="updateProfile()"/>
                    </div>
                </div>
                <div class="divider mt-8">Theme</div>
                <div class="grid grid-cols-1 gap-4">
                    <x-select :value="$theme" icon="o-paint-brush" :options="$themes" wire:model="theme" wire:change="updateTheme()" inline/>
                </div>
            </div>
        </div>
    </div>
</div>
