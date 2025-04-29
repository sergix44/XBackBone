<div class="grid grid-cols-12 gap-6">
    <div class="col-span-3">
        <x-menu class="rounded-lg bg-base-100">
            <x-menu-item title="Profile" icon="o-user-circle" />
            <x-menu-item title="Tokens" icon="o-command-line" />
            <x-menu-item title="Export Data" icon="o-arrow-right-start-on-rectangle" />
            <x-menu-item title="Delete Account" icon="o-user-minus" class="text-red-500" />
        </x-menu>
    </div>
    <div class="col-span-9">
        <div class="card bg-base-100 w-full">
            <div class="card-body">
                <x-avatar :image="$user->avatar" class="!w-22">
                    <x-slot:title class="text-3xl !font-bold pl-2">
                        {{ $user->name }}
                    </x-slot:title>

                    <x-slot:subtitle class="grid gap-1 mt-2 pl-2 text-xs">
                        <x-icon name="o-paper-airplane" label="12 posts" />
                        <x-icon name="o-chat-bubble-left" label="45 comments" />
                    </x-slot:subtitle>
                </x-avatar>
                <div class="divider"></div>
                <x-select label="Theme" wire:model="theme" icon="o-paint-brush" :options="$themes"  inline />
            </div>
        </div>
    </div>
</div>
