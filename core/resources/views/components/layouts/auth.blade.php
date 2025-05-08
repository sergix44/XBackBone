@extends('components.layouts.base')

@section('body')
    <div class="grid flex-grow place-items-center">
        <div class="relative flex flex-col items-center justify-center overflow-hidden w-96">
            <x-card shadow class="w-96 pr-8 pl-8">
                <h1 class="mb-6 justify-center flex">
                    <x-app-brand on-top/>
                </h1>
                {{ $slot }}
            </x-card>
        </div>
    </div>
    <x-toast />
    <x-footer/>
@endsection
