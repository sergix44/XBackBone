<?php

namespace App\Livewire;

use App\Actions\Resource\ListResources;
use App\Actions\Resource\StoreResource;
use Illuminate\Pagination\AbstractPaginator;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

class Dashboard extends Component
{
    use WithFileUploads, Toast;

    private readonly StoreResource $storeResource;

    public bool $showUploadDrawer = false;

    public array $files = [];
    private ListResources $listResources;

    public function boot(StoreResource $storeResource, ListResources $listResources): void
    {
        $this->storeResource = $storeResource;
        $this->listResources = $listResources;
    }

    public function render()
    {
        return view('livewire.dashboard', [
            'resources' => ($this->listResources)(auth()->user()),
        ])->title('Gallery');
    }

    public function saveUpload(int $id): void
    {
        /** @var TemporaryUploadedFile $file */
        $file = $this->files[$id] ?? null;

        if (!$file) {
            $this->error('File not found');
            return;
        }

        $resource = ($this->storeResource)(auth()->user(), $file);
        $this->success('Upload successful!', $resource->preview_ext_url);

        $file->delete();
    }
}
