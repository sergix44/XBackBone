<div>
    <x-drawer
        wire:model="showUploadDrawer"
        class="w-11/12 lg:w-1/3"
        with-close-button
        close-on-escape
        title="Uploads"
    >
        <div class="w-full p-6 border-2 border-dashed border-neutral-content/50 rounded-lg text-center relative">
            <input type="file" class="absolute inset-0 w-full h-full opacity-0 z-50 cursor-pointer" multiple
                   wire:model="files">
            <div class="flex flex-col items-center justify-center gap-4">
                <x-icon name="o-cloud-arrow-up" class="text-base-content/70 w-20 h-20"/>
                <div class="flex flex-col items-center">
                    <span class="text-base-content/70">Drop files here or click to upload</span>
                </div>
            </div>
        </div>
        <div class="flex flex-col mt-4">
            <div class="card w-full bg-neutral/20 card-sm shadow-sm">
                <div class="card-body">
                    <div class="flex items-center justify-between mb-2">
                        <h2 class="card-title">Filename</h2>
                        <div class="card-actions justify-end">
                            <x-button icon="o-x-mark" class="btn-circle btn-xs btn-error"/>
                        </div>
                    </div>
                    <progress class="progress progress-primary w-full" value="66" max="100"></progress>
                </div>
            </div>
        </div>
    </x-drawer>
    <div class="flex flex-col lg:flex-row items-center w-full mx-auto gap-4 lg:gap-0">
        <div class="flex items-center justify-center lg:justify-start gap-2 w-full lg:w-1/2">
            <x-button label="New" class="btn-primary" icon="o-plus" wire:click="$toggle('showUploadDrawer')"/>
            <x-input placeholder="Search..." inline class="flex-1">
                <x-slot:append>
                    <x-button icon="o-magnifying-glass" class="join-item btn-accent"/>
                </x-slot:append>
            </x-input>
        </div>
        <div class="flex justify-center lg:shrink-0 lg:my-0">
            <div class="join">
                <button class="join-item btn">«</button>
                <button class="join-item btn">Page 1</button>
                <button class="join-item btn">»</button>
            </div>
        </div>
        <div class="flex items-center justify-center lg:justify-end gap-2 lg:w-1/2">
            <div class="join">
                <x-dropdown label="Sort by" class="btn-accent rounded-r-none join-item">
                    <x-menu-item title="It should align correctly on right side"/>
                    <x-menu-item title="Yes!"/>
                </x-dropdown>
                <x-button icon="o-bars-3-bottom-right" class="btn-accent join-item"/>
            </div>
        </div>
    </div>
    <div class="mt-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-resource/>
        <x-resource/>
        <x-resource/>
        <x-resource/>
        <x-resource/>
        <x-resource/>
        <x-resource/>
    </div>

</div>
