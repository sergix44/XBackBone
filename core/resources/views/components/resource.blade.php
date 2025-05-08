<div class="card bg-base-100 w-full shadow-sm card-xs flex-col">
    <div class="card-body justify-start">
        <div class="flex justify-between items-center gap-1">
            <a class="card-title truncate block cursor-pointer" href="{{ $resource?->preview_ext_url }}" wire:navigate>
                {{ $resource?->filename ?? 'File Name' }}
            </a>
            <div class="inline-flex gap-0.5">
                <x-button icon="m-link" class="btn-success btn-xs btn-square btn-soft" @click="$clipboard('{{$resource?->preview_ext_url}}')"/>
                <x-button icon="m-cloud-arrow-down" class="btn-info btn-xs btn-square btn-soft"/>
                <x-button icon="m-eye-slash" class="btn-warning btn-xs btn-square btn-soft"/>
                <x-button icon="m-x-mark" class="btn-error btn-xs btn-square btn-soft"/>
            </div>
        </div>
    </div>
    <figure class="justify-center">
        @if($resource->is_dir ?? false)
            <x-icon name="o-folder" class="w-full h-32"></x-icon>
        @else
            <img
                src="https://img.daisyui.com/images/stock/photo-1606107557195-0e29a4b5b4aa.webp"
                alt="Shoes"/>
        @endif
    </figure>
    <div class="card-body justify-end">
        <div class="flex justify-between items-center">
            <div class="font-mono">{{ $resource?->size_human_readable ?? '0' }}</div>
            <div class="font-semibold tooltip tooltip-bottom" data-tip="{{ $resource?->created_at ?? '0' }}">
                {{ $resource ?? null ? $resource->created_at->diffForHumans() : '0' }}
            </div>
        </div>
    </div>
</div>
