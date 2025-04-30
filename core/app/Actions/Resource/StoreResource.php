<?php

namespace App\Actions\Resource;

use App\Models\Properties\ResourceType;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
use Sqids\Sqids;

class StoreResource
{
    public function __construct(protected Sqids $genId) {}

    public function __invoke(User $user, ?UploadedFile $file = null, ?string $name = null, ?string $data = null): Resource
    {
        if (! $file && ! $data) {
            throw new InvalidArgumentException('Cannot store a resource without a file or data.');
        }

        if (! $name && $file) {
            $name = $file?->getClientOriginalName() ?? $file?->hashName();
        }

        /** @var resource $resource */
        $resource = DB::transaction(function () use ($user, $file, $name, $data) {
            $resource = Resource::query()->create([
                'type' => $this->findType($file, $data),
                'user_id' => $user->id,
                'filename' => $file?->getClientOriginalName(),
                'size' => $file?->getSize() ?? strlen($data),
                'mime' => $file?->getMimeType() ?? 'text/plain',
                'extension' => $file?->extension() ?? 'txt',
                'name' => $name,
                'data' => $data,
            ]);

            if (! $resource) {
                throw new InvalidArgumentException('Failed to store the resource.');
            }

            $code = $this->genId->encode([$user->id, $resource->id]);
            $stream = $file ? fopen($file->getRealPath(), 'rb') : $data;

            if (! Storage::put($code, $stream)) {
                throw new RuntimeException('Failed to store the file.');
            }

            $resource->fill([
                'code' => $code,
                'published_at' => now(),
            ]);

            return $resource;
        });

        activity()
            ->performedOn($resource)
            ->causedBy($user)
            ->log('resource.uploaded');

        return $resource;
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
