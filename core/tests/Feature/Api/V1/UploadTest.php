<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;

test('uploading a file', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.upload'), [
            'file' => UploadedFile::fake()->image('screen.jpg'),
        ])
        ->dump()
        ->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'code',
                'name',
                'filename',
                'size',
                'mime',
                'views',
                'downloads',
                'published_at',
                'expires_at',
            ],
        ]);
});
