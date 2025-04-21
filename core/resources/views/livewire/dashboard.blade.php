<div>
    <div class="flex items-center w-full mx-auto">
        <div class="inline-flex items-center justify-start gap-2 w-1/2">
            <x-button label="New" class="btn-primary" icon="o-plus"/>
            <x-input placeholder="Search..." inline>
                <x-slot:append>
                    <x-button icon="o-magnifying-glass" class="join-item btn-accent"/>
                </x-slot:append>
            </x-input>
        </div>
        <div class="inline-flex shrink-0">
            <div class="join">
                <button class="join-item btn">«</button>
                <button class="join-item btn">Page 1</button>
                <button class="join-item btn">»</button>
            </div>
        </div>
        <div class="inline-flex items-center justify-end gap-2 w-1/2">
            <div class="join">
                <x-dropdown label="Sort by" class="btn-accent rounded-r-none join-item">
                    <x-menu-item title="It should align correctly on right side" />
                    <x-menu-item title="Yes!" />
                </x-dropdown>
                <x-button icon="o-bars-3-bottom-right" class="btn-accent join-item"/>
            </div>
        </div>
    </div>
    <div class="mt-5 grid grid-cols-4 gap-4">
        <x-resource />
        <x-resource />
        <x-resource />
        <x-resource />
        <x-resource />
        <x-resource />
        <x-resource/>
    </div>

</div>
