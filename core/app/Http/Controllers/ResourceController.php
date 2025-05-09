<?php

namespace App\Http\Controllers;

use App\Actions\Resource\GetResourcePreview;
use App\Models\Resource;
use Illuminate\Support\Facades\Storage;

class ResourceController extends Controller
{
    public function raw(Resource $resource)
    {
        return Storage::response($resource->code, $resource->filename);
    }

    public function preview(Resource $resource, GetResourcePreview $getResourcePreview)
    {
        return $getResourcePreview($resource);
    }

    public function download(Resource $resource)
    {
        return Storage::response($resource->code, $resource->filename, disposition: 'attachment');
    }
}
