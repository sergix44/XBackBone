<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Resource\StoreResource;
use App\Http\Controllers\Controller;
use app\Http\Requests\Api\V1\UploadResourceRequest;

class UploadController extends Controller
{
    public function __invoke(UploadResourceRequest $request, StoreResource $uploadResource)
    {

    }
}
