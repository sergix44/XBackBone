<?php

namespace App\Models;

use App\Models\Properties\ResourceType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'user_id',
        'code',
        'is_private',
        'data',
        'extension',
        'filename',
        'size',
        'mime',
        'views',
        'downloads',
        'password',
        'published_at',
        'expires_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'type' => ResourceType::class,
            'hidden' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function previewUrl(): Attribute
    {
        return Attribute::make(get: fn() => route('preview', $this->code));
    }

    public function previewExtUrl(): Attribute
    {
        return Attribute::make(get: fn() => route('preview.ext', ['resource' => $this->code, 'ext' => $this->extension]));
    }
}
