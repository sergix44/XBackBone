<div class="grid place-items-center p-4">
    <div class="w-full max-w-5xl">
        <div class="card bg-base-100">
            <div class="card-body">
                <h1 class="card-title">
                    {{ $resource->filename ?? $resource->code }}
                </h1>

                @php
                    $ext = strtolower((string) $resource->extension);
                    $isImageByExt = in_array($ext, ['png','jpg','jpeg','gif','webp','bmp','svg','avif']);
                    $isImageByMime = is_string($resource->mime ?? null) && str_starts_with($resource->mime, 'image/');
                    $isImage = $isImageByExt || $isImageByMime;
                @endphp

                @if($isImage)
                    <div class="w-full overflow-auto">
                        @php
                            $rawRoute = $resource->extension
                                ? route('raw.ext', ['resource' => $resource->code, 'ext' => $resource->extension])
                                : route('raw', ['resource' => $resource->code]);
                        @endphp
                        <img
                            src="{{ $rawRoute }}"
                            alt="{{ $resource->filename ?? 'image' }}"
                            class="max-w-full h-auto rounded-lg shadow"
                            loading="lazy"
                        />
                    </div>
                @else
                    <div class="alert">
                        Nessuna anteprima immagine disponibile per questo file.
                    </div>
                @endif

                <div class="text-sm opacity-70 mt-2 flex flex-wrap gap-2 items-center">
                    @if($resource->extension)
                        <span class="badge badge-ghost">{{ strtoupper($resource->extension) }}</span>
                    @endif
                    @if($resource->size_human_readable)
                        <span class="badge badge-ghost">{{ $resource->size_human_readable }}</span>
                    @endif

                    <a class="link" href="{{ $resource->extension
                        ? route('download.ext', ['resource' => $resource->code, 'ext' => $resource->extension])
                        : route('download', ['resource' => $resource->code]) }}">
                        Scarica
                    </a>

                    <a class="link" target="_blank" rel="noopener" href="{{ $resource->extension
                        ? route('raw.ext', ['resource' => $resource->code, 'ext' => $resource->extension])
                        : route('raw', ['resource' => $resource->code]) }}">
                        Apri originale
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
