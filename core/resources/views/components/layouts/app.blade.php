@extends('components.layouts.base')

@section('body')
    <div class="navbar bg-base-100">
        <div class="navbar-start">
            <div class="dropdown">
                <div tabindex="0" role="button" class="btn btn-ghost lg:hidden">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 12h8m-8 6h16"/>
                    </svg>
                </div>
                <ul
                    tabindex="0"
                    class="menu menu-sm dropdown-content bg-base-100 rounded-box z-[1] mt-3 w-52 p-2 shadow">
                    <li><a>Item 1</a></li>
                    <li>
                        <a>Parent</a>
                        <ul class="p-2">
                            <li><a>Submenu 1</a></li>
                            <li><a>Submenu 2</a></li>
                        </ul>
                    </li>
                    <li><a>Item 3</a></li>
                </ul>
            </div>
            <x-app-brand/>
        </div>
        <div class="navbar-center hidden lg:flex">
            <x-menu activate-by-route class="menu-horizontal z-50 flex items-center">
                <x-menu-item title="Home" icon="o-home" link="###"/>
                <x-menu-item title="Messages" icon="o-envelope" link="###"/>
                <x-menu-sub title="Settings" icon="o-cog-6-tooth">
                    <x-menu-item title="Wifi" icon="o-wifi" link="####"/>
                    <x-menu-item title="Wifi" icon="o-wifi" link="####"/>
                    <x-menu-item title="Wifi" icon="o-wifi" link="####"/>
                    <x-menu-item title="Wifi" icon="o-wifi" link="####"/>
                    <x-menu-item title="Archives" icon="o-archive-box" link="####"/>
                </x-menu-sub>
            </x-menu>
        </div>
        <div class="navbar-end mr-2">
            @if($user = auth()->user())
                <x-dropdown>
                    <x-slot:trigger>
                        <x-avatar :image="$user->avatar" :title="$user->username" :subtitle="$user->name"
                                  class="!w-10"/>
                    </x-slot:trigger>
                    <x-menu-item icon="o-power" title="Logout" link="javascript:document.getElementById('logout-form').submit();" no-wire-navigate/>
                </x-dropdown>
            @endif
        </div>
    </div>

    <x-main with-nav full-width>
        <x-slot:content>
            {{ $slot }}
        </x-slot:content>
    </x-main>

    <form method="POST" action="{{ route('logout') }}" id="logout-form">@csrf</form>
    <x-toast/>
@endsection
