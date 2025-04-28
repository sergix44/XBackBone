<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\ConfirmPassword;
use App\Models\User;
use function Pest\Laravel\actingAs;

test('confirm password screen can be rendered', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get('/user/confirm-password')
        ->assertSeeLivewire(ConfirmPassword::class)
        ->assertStatus(200);
});

test('password can be confirmed', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post('/user/confirm-password', ['password' => 'password'])
        ->assertRedirect('/dashboard');
});

test('password is not confirmed with invalid password', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post('/user/confirm-password', ['password' => 'notpassword'])
        ->assertSessionHasErrors();
});
