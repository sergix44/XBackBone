<div class="card bg-base-100 w-full shadow-sm card-xs flex-col">
    <div class="card-body justify-start">
        <div class="flex justify-between items-center">
            <h3 class="card-title">Scarpe di lukazzo.png</h3>
            <div>
                <x-button icon="m-link" class="btn-success btn-xs btn-square btn-soft"/>
                <x-button icon="m-cloud-arrow-down" class="btn-info btn-xs btn-square btn-soft"/>
                <x-button icon="m-eye-slash" class="btn-warning btn-xs btn-square btn-soft"/>
                <x-button icon="m-x-mark" class="btn-error btn-xs btn-square btn-soft"/>
            </div>
        </div>
    </div>
    <figure class="justify-center">
        @if($isDir ?? false)
            <x-icon name="o-folder" class="w-full h-32"></x-icon>
        @else
            <img
                src="https://img.daisyui.com/images/stock/photo-1606107557195-0e29a4b5b4aa.webp"
                alt="Shoes"/>
        @endif
    </figure>
    <div class="card-body justify-end">
        <div class="flex justify-between items-center">
            <div class="font-mono">30.2 TB</div>
            <div class="font-semibold">31/12/2037 14:23:01</div>
        </div>
    </div>
</div>
