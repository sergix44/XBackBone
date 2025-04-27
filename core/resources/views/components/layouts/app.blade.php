@extends('components.layouts.base')

@section('body')
    <x-navbar.navbar/>

    <x-custom-main with-nav full-width>
        <x-slot:content>
            {{ $slot }}
        </x-slot:content>
    </x-custom-main>

    <form method="POST" action="{{ route('logout') }}" id="logout-form">@csrf</form>
    <x-toast/>
@endsection
