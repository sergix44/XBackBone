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
                    <div class="flex items-center justify-between mb-2 gap-2">
                        <h2 class="card-title truncate" x-text="file.name"></h2>
                        <div class="card-actions justify-end">
                            <x-button x-show="!file.completed && !file.canceled" icon="o-x-mark" class="btn-circle btn-xs btn-error" x-on:click="cancelFile(file.id)"/>
                            <x-button x-show="file.completed" icon="o-check" class="btn-circle btn-xs btn-success" x-on:click="removeFile(file.id)"/>
                            <x-button x-show="file.canceled" icon="o-x-mark" class="btn-circle btn-xs btn-neutral" x-on:click="removeFile(file.id)"/>
                        </div>
                    </div>
                    <progress x-show="!file.completed && !file.canceled" class="progress progress-primary w-full" max="100" x-bind:value="file.progress"></progress>
                    <progress x-show="file.completed" class="progress progress-success w-full" max="100" value="100"></progress>
                </div>
            </div>
        </template>
    </div>
</x-drawer>

@script
<script>
    Alpine.data('uploads', () => ({
        counter: 0,
        list: {
            // // Example file list
            // 0: {id: 0, name: 'file1.txt', completed: false, canceled: false, progress: 20},
            // 1: {id: 1, name: 'file2.txt', completed: false, canceled: false, progress: 30},
            // // completed
            // 2: {id: 2, name: 'file3.txt', completed: true, canceled: false, progress: 100},
            // // canceled
            // 3: {id: 3, name: 'file4.txt', completed: false, canceled: true, progress: 0},
            // // very long file name
            // 4: {id: 4, name: 'file5 skk lkdm mdlkfms dfmksdmlkf lksdmfk msdfm lksdmfk .txt', completed: false, canceled: false, progress: 50},
            // // very long file name without spaces
            // 5: {id: 5, name: 'file6skkdkmdlkfmsdfmksdmlkfmsdfmksdmlkfmsdfmksdmlkfmsdfmksdmlkf.txt', completed: false, canceled: false, progress: 50},
        },
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
                let id = this.counter;
                this.counter++;
                this.list[id] = {
                    id: id,
                    name: file.name,
                    completed: false,
                    canceled: false,
                    progress: 0,
                };

                console.log(`Uploading file: ${file.name}`);
                $wire.upload('files.' + id, file, (uploadedFilename) => {
                    console.log(`File uploaded: ${uploadedFilename}`);
                    this.list[id].completed = true;
                    this.list[id].progress = 100;
                    $wire.saveUpload(id)
                }, () => {
                    console.log(`File upload failed: ${file.name}`);
                    delete this.list[id];
                }, (event) => {
                    console.log(`File upload progress: ${event.detail.progress}`);
                    this.list[id].progress = event.detail.progress;
                }, () => {
                    console.log(`File upload canceled: ${file.name}`);
                    delete this.list[id];
                })
            }
        },
        cancelFile(id) {
            if (this.list[id] && !this.list[id].canceled) {
                $wire.cancelUpload('files.' + id);
                this.list[id].canceled = true;
                this.list[id].progress = 0;
            }
        },
        removeFile(id) {
            if (this.list[id]) {
                delete this.list[id];
            }
        },
    }));
</script>
@endscript
