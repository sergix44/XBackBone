<div class="navbar bg-base-100">
    <div class="navbar-start">
        <x-dropdown>
            <x-slot:trigger>
                <x-button icon="o-bars-3" class="btn btn-ghost btn-sm lg:hidden" />
            </x-slot:trigger>
            @include('components.layouts.navbar.menuitems')
        </x-dropdown>
        <x-app-brand/>
    </div>
    <div class="navbar-center hidden lg:flex">
        <x-menu activate-by-route class="menu-horizontal z-50 flex items-center">
            @include('components.layouts.navbar.menuitems')
        </x-menu>
    </div>
    <div class="navbar-end">
        @if($user = auth()->user())
            <x-dropdown no-x-anchor right>
                <x-slot:trigger>
                    <div class="btn btn-ghost">
                        <x-avatar :image="$user->avatar" :title="$user->username" :subtitle="$user->name" class="!w-10"/>
                    </div>

                </x-slot:trigger>
                <x-menu-item icon="o-user" title="Profile" link="#"/>
                <x-menu-item icon="o-power" title="Quit" link="javascript:document.getElementById('logout-form').submit();" no-wire-navigate/>
            </x-dropdown>
        @endif
    </div>
</div>
