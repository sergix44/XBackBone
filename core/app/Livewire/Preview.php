<?php

namespace App\Livewire;

use App\Models\Resource;
use Livewire\Component;

class Preview extends Component
{
    public Resource $resource;

    public function mount(Resource $resource, ?string $ext = null): void
    {
        view()->share('previewMode', true);
        $this->resource = $resource;

        if ($ext && $resource->extension !== $ext) {
            abort(404);
        }
    }

    public function render()
    {
        return view('livewire.preview')->title($this->resource->filename ?? 'Preview');
    }
}
