@section('menu-items')
    <x-menu-item title="Copy link" icon="o-link" link="javascript:copyToClipboard('{{ $resource->extension
                        ? route('raw.ext', ['resource' => $resource->code, 'ext' => $resource->extension])
                        : route('raw', ['resource' => $resource->code]) }}')"
                 no-wire-navigate/>
    <x-menu-item title="Download" icon="o-cloud-arrow-down" link="{{ $resource->extension
                        ? route('download.ext', ['resource' => $resource->code, 'ext' => $resource->extension])
                        : route('download', ['resource' => $resource->code]) }}" :external="true"/>
    <x-menu-item title="Original" icon="o-eye" link="{{ $resource->extension
                       ? route('raw.ext', ['resource' => $resource->code, 'ext' => $resource->extension])
                        : route('raw', ['resource' => $resource->code]) }}" :external="true"/>
@endsection

<div class="grid place-items-center p-4">
    <div class="w-full max-w-5xl">
        <div class="card bg-base-100">
            <div class="card-body">
                @if($resource->is_displayable)
                    <div class="mb-4 flex justify-center">
                        <img src="{{ $resource->extension
                            ? route('raw.ext', ['resource' => $resource->code, 'ext' => $resource->extension])
                            : route('raw', ['resource' => $resource->code]) }}"
                             alt="{{ $resource->filename ?? $resource->code }}"
                             class="max-h-[600px] object-contain"/>
                    </div>
                @endif
                <h1 class="card-title">
                    {{ $resource->filename ?? $resource->code }}
                </h1>
                <div class="text-sm opacity-70 mt-2 flex flex-wrap gap-2 items-center">
                    @if($resource->extension)
                        <span class="badge badge-ghost">{{ strtoupper($resource->extension) }}</span>
                    @endif
                    @if($resource->size_human_readable)
                        <span class="badge badge-ghost">{{ $resource->size_human_readable }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
