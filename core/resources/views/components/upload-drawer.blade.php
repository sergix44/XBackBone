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
