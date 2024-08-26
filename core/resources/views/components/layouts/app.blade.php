@extends('components.layouts.base')

@section('body')
    <x-layouts.navbar.navbar/>

    <x-main with-nav full-width>
        <x-slot:content>
            {{ $slot }}
        </x-slot:content>
    </x-main>

    <form method="POST" action="{{ route('logout') }}" id="logout-form">@csrf</form>
    <x-toast/>
@endsection
