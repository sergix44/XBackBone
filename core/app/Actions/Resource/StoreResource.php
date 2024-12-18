<?php

namespace App\Actions\Resource;

use App\Models\Properties\ResourceType;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Sqids\Sqids;
use Throwable;

class StoreResource
{
    public function __construct(protected Sqids $genId)
    {
    }

    /**
     * @param  User  $user
     * @param  UploadedFile|null  $file
     * @param  string|null  $name
     * @param  string|null  $data
     * @return Resource
     */
    public function __invoke(User $user, ?UploadedFile $file = null, ?string $name = null, ?string $data = null): Resource
    {
        if (!$file && !$data) {
            throw new InvalidArgumentException('Cannot store a resource without a file or data.');
        }

        if (!$name && $file) {
            $name = $file?->getClientOriginalName() ?? $file?->hashName();
        }

        return DB::transaction(function () use ($user, $file, $name, $data) {
            $resource = Resource::query()->create([
                'type' => $this->findType($file, $data),
                'user_id' => $user->id,
                'filename' => $file?->getClientOriginalName(),
                'size' => $file?->getSize() ?? strlen($data),
                'mime' => $file?->getMimeType(),
                'extension' => $file?->extension(),
                'name' => $name,
                'data' => $data,
            ]);

            if (!$resource) {
                throw new InvalidArgumentException('Failed to store the resource.');
            }

            $code = $this->genId->encode([$user->id, $resource->id]);

            if (!Storage::put($code, $file)) {
                throw new InvalidArgumentException('Failed to store the file.');
            }

            $resource->update([
                'code' => $code,
                'published_at' => now(),
            ]);


            return $resource;
        });
    }

    private function findType(?UploadedFile $file, ?string $data): ResourceType
    {
        if ($file) {
            return ResourceType::fromMime($file->getMimeType());
        }

        if ($data) {
            return ResourceType::fromValue($data);
        }

        return ResourceType::FILE;
    }
}
