<x-drawer
        wire:model="showUploadDrawer"
        class="w-11/12 lg:w-1/3"
        with-close-button
        close-on-escape
        title="Uploads"
>
    <div id="drop-area"
         class="w-full p-6 border-2 border-dashed border-neutral-content/50 rounded-lg text-center relative">
        <input id="files" type="file" class="absolute inset-0 w-full h-full opacity-0 z-50 cursor-pointer" multiple>
        <div class="flex flex-col items-center justify-center gap-4">
            <x-icon name="o-cloud-arrow-up" class="text-base-content/70 w-20 h-20"/>
            <div class="flex flex-col items-center">
                <span class="text-base-content/70">Drop files here or click to upload</span>
            </div>
        </div>
    </div>
    <div class="flex flex-col mt-4" x-data="uploads">
        <template x-for="(file, index) in Object.values(list)" :key="index">
            <div class="card w-full bg-neutral/20 card-sm shadow-sm mb-2">
                <div class="card-body">
                    <div class="flex items-center justify-between mb-2">
                        <h2 class="card-title" x-text="file.name"></h2>
                        <div class="card-actions justify-end">
                            <x-button icon="o-x-mark" class="btn-circle btn-xs btn-error"/>
                        </div>
                    </div>
                    <progress class="progress progress-primary w-full" max="100" x-bind:value="file.progress"></progress>
                </div>
            </div>
        </template>
    </div>
</x-drawer>

@script
<script>
    Alpine.data('uploads', () => ({
        list: {},
        init() {
            const input = $wire.el.querySelector('#files');
            const dropArea = $wire.el.querySelector('#drop-area');

            input.addEventListener('change', e => {
                dropArea.classList.remove('bg-neutral/30');
                this.uploadFiles(e.target.files);
            });

            input.addEventListener('dragover', e => {
                e.preventDefault();
                dropArea.classList.add('bg-neutral/30');
            });

            input.addEventListener('dragleave', () => {
                dropArea.classList.remove('bg-neutral/30');
            });

            input.addEventListener('drop', e => {
                e.preventDefault();
                dropArea.classList.remove('bg-neutral/30');
                this.uploadFiles(e.dataTransfer.files);
            });
        },
        uploadFiles(files) {
            for (let file of files) {
                this.list[file.name] = {
                    name: file.name,
                    obj: file,
                    completed: false,
                    progress: 0,
                };
                console.log(`Uploading file: ${file.name}`);
                $wire.upload('files', file, (uploadedFilename) => {
                    console.log(`File uploaded: ${uploadedFilename}`);
                    this.list[file.name].completed = true;
                    this.list[file.name].progress = 100;
                }, () => {
                    console.log(`File upload failed: ${file.name}`);
                    delete this.list[file.name];
                }, (event) => {
                    console.log(`File upload progress: ${event.detail.progress}`);
                    this.list[file.name].progress = event.detail.progress;
                }, () => {
                    console.log(`File upload canceled: ${file.name}`);
                    delete this.list[file.name];
                })
            }
        }
    }));
</script>
@endscript
