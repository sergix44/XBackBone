@extends('components.layouts.base')

@section('body')
    <div class="grid h-screen place-items-center">
        <div class="relative flex flex-col items-center justify-center h-screen overflow-hidden w-96">
            <x-card shadow class="w-96 pr-8 pl-8">
                <h1 class="mb-6 justify-center flex">
                    <x-app-brand on-top/>
                </h1>
                @if(session()->has('status'))
                    <x-alert icon="o-check" class="alert-success mb-4">
                        {{ session('status') }}
                    </x-alert>
                @endif
                {{ $slot }}
            </x-card>
        </div>
    </div>
    <x-toast />
@endsection
