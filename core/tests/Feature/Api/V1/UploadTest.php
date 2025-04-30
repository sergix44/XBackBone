<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake();
});

test('upload a file', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.upload'), [
            'file' => UploadedFile::fake()->image('screen.jpg'),
        ])
        ->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'type',
                'filename',
                'mime',
                'size',
                'is_private',
                'extension',
                'view_count',
                'download_count',
                'preview_url',
                'preview_ext_url',
                'published_at',
                'expires_at',
            ],
        ]);
});

test('upload a file string', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.upload'), [
            'data' => 'ij j ewojfeiojwio eoje jwefjiwe jf ',
        ])
        ->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'type',
                'filename',
                'mime',
                'size',
                'is_private',
                'extension',
                'view_count',
                'download_count',
                'preview_url',
                'preview_ext_url',
                'published_at',
                'expires_at',
            ],
        ]);
});

test('fails when not authenticated', function () {
    $this->postJson(route('api.v1.upload'), [
        'file' => UploadedFile::fake()->image('screen.jpg'),
    ])
        ->assertUnauthorized();
});

test('fails file is not specified', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.upload'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['file']);
});
