<?php

namespace App\Actions\Resource;

use App\Models\Resource;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Sqids\Sqids;

class StoreResource
{
    public function __construct(protected Sqids $genId)
    {
    }

    /**
     * @param User $user
     * @param UploadedFile|null $file
     * @param string|null $data
     * @return Resource
     */
    public function __invoke(User $user, ?UploadedFile $file = null, ?string $data = null): Resource
    {
    }
}
