@section('menu-items')
    <x-menu-item title="Copy link" icon="o-link" link="javascript:copyToClipboard('{{ $resource->raw_url }}')"
                 no-wire-navigate/>
    <x-menu-item title="Download" icon="o-cloud-arrow-down" link="{{ $resource->download_url }}" :external="true"/>
    <x-menu-item title="Original" icon="o-eye" link="{{$resource->raw_url }}" :external="true"/>
@endsection

<div class="grid place-items-center p-2">
    <div class="w-full max-w-5xl">
        @if($resource->is_displayable)
            @switch($resource->type)
                @case(\App\Models\Properties\ResourceType::IMAGE)
                    <img src="{{ $resource->raw_url }}" alt="{{ $resource->filename ?? $resource->code }}" class="max-h-[80vh] mx-auto rounded shadow-lg"/>
                    @break
            @endswitch
        @endif

        <div class="card bg-base-100 w-96 shadow-sm">
            <div class="card-body">
                <h2 class="card-title">{{ $resource->filename  }}</h2>
                <div class="card-actions justify-end">
                    <button class="btn btn-primary">Buy Now</button>
                </div>
            </div>
        </div>
    </div>
</div>
