<div>
    <div class="flex justify-between">
        <div class="flex gap-2">
            <x-button label="New" class="btn-primary" icon="o-plus"/>
            <x-input placeholder="Search...">
                <x-slot:append>
                    <x-button icon="o-magnifying-glass" class="btn-primary rounded-s-none"/>
                </x-slot:append>
            </x-input>
        </div>
        <div>
            pagination here
        </div>
        <div class="flex items-center gap-2">
            <div class="join">
                <x-dropdown label="Sort by" class="btn-neutral rounded-r-none">
                    <x-menu-item title="It should align correctly on right side" />
                    <x-menu-item title="Yes!" />
                </x-dropdown>
                <x-button icon="o-bars-3-bottom-right" class="btn-neutral join-item"/>
            </div>
            <x-button icon="o-trash" class="btn-error join-item"/>
        </div>
    </div>
    <div class="mt-5">
        <div class="card image-full w-96 shadow-xl group">
            <figure>
                <img
                        src="https://img.daisyui.com/images/stock/photo-1606107557195-0e29a4b5b4aa.webp"
                        alt="Shoes" />
            </figure>
            <div class="card-body hidden group-hover:block">
                <h2 class="card-title">Shoes!</h2>
                <p>If a dog chews shoes whose shoes does he choose?</p>
                <div class="card-actions justify-end">
                    <button class="btn btn-primary">Buy Now</button>
                </div>
            </div>
        </div>
    </div>

</div>
