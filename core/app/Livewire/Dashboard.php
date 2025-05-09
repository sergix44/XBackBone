<?php

namespace App\Livewire;

use App\Actions\Resource\ListResources;
use App\Actions\Resource\StoreResource;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class Dashboard extends Component
{
    use WithFileUploads, Toast, WithPagination;

    public bool $showUploadDrawer = false;
    public array $files = [];

    public function render()
    {
        return view('livewire.dashboard')->title('Gallery');
    }

    #[Computed]
    public function resources()
    {
        return app(ListResources::class)(auth()->user());
    }

    public function saveUpload(int $id): void
    {
        /** @var TemporaryUploadedFile $file */
        $file = $this->files[$id] ?? null;

        if (!$file) {
            $this->error('File not found');
            return;
        }

        $resource = app(StoreResource::class)(auth()->user(), $file);

        activity()
            ->performedOn($resource)
            ->causedBy(auth()->user())
            ->log('resource.uploaded');

        $this->success('Upload successful!', $resource->preview_ext_url);

        $file->delete();
    }
}
