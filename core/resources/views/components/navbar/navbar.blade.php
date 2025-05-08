<div class="navbar bg-base-100 w-full mx-auto lg:px-10 shadow p-0">
    <div class="navbar-start">
        @if($previewMode ?? false)
        @else
            <x-dropdown>
                <x-slot:trigger>
                    <x-button icon="o-bars-3" class="btn btn-ghost btn-sm lg:hidden mr-1"/>
                </x-slot:trigger>
                @include('components.navbar.menuitems')
            </x-dropdown>
        @endif
        <x-app-brand/>
    </div>
    <div class="navbar-center hidden lg:flex">
        @if($previewMode ?? false)
        @else
            <x-menu activate-by-route class="menu-horizontal z-50 flex items-center gap-1">
                @include('components.navbar.menuitems')
            </x-menu>
        @endif
    </div>
    <div class="navbar-end">
        @if($user = auth()->user())
            <x-dropdown right>
                <x-slot:trigger>
                    <div class="btn btn-ghost">
                        <x-avatar :image="$user->avatar" :title="$user->name"/>
                    </div>
                </x-slot:trigger>
                <x-menu-item icon="o-user" title="Profile" link="{{ route('user.profile') }}"/>
                <x-menu-item icon="o-power" title="Quit" link="javascript:document.getElementById('logout-form').submit();" no-wire-navigate/>
            </x-dropdown>
        @endif
    </div>
</div>
