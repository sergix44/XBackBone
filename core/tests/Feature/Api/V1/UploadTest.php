<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    Storage::fake();
});

test('uploading a file', function () {
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
            'data' => 'ij j ewojfeiojwio eoje jwefjiwe jf '
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
