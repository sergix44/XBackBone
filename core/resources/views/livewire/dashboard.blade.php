<div>
    <div class="flex justify-between">
        <div class="flex gap-2">
            <x-button label="New" class="btn-primary" icon="o-plus"/>

            <x-input placeholder="Search..." inline>
                <x-slot:append>
                    <x-button icon="o-magnifying-glass" class="join-item btn-primary" />
                </x-slot:append>
            </x-input>
        </div>
        <div>
            pagination here
        </div>
        <div class="flex items-center gap-2">
            <div class="join">
                <x-dropdown label="Sort by" class="btn-neutral rounded-r-none">
                    <x-menu-item title="It should align correctly on right side" />
                    <x-menu-item title="Yes!" />
                </x-dropdown>
                <x-button icon="o-bars-3-bottom-right" class="btn-neutral join-item"/>
            </div>
            <x-button icon="o-trash" class="btn-error join-item"/>
        </div>
    </div>
    <div class="mt-5 grid grid-cols-4 gap-4">
        <x-resource />
        <x-resource />
        <x-resource />
        <x-resource />
        <x-resource />
        <x-resource />
    </div>

</div>
