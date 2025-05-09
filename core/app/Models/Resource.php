<?php

namespace App\Models;

use App\Models\Properties\ResourceType;
use App\Support\Helpers;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'type',
        'user_id',
        'code',
        'is_private',
        'data',
        'extension',
        'filename',
        'size',
        'mime',
        'preview_type',
        'preview_extension',
        'views',
        'downloads',
        'fingerprint',
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
            'is_private' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Resource::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Resource::class, 'parent_id');
    }

    public function previewUrl(): Attribute
    {
        return Attribute::make(get: fn() => route('preview', $this->code));
    }

    public function previewExtUrl(): Attribute
    {
        return Attribute::make(get: fn() => route('preview.ext', ['resource' => $this->code, 'ext' => $this->extension]));
    }

    public function isDir(): Attribute
    {
        return Attribute::make(get: fn() => $this->type === ResourceType::DIRECTORY);
    }

    public function sizeHumanReadable(): Attribute
    {
        return Attribute::make(get: fn() => $this->size ? Helpers::humanizeBytes($this->size) : null);
    }

    public function hasPreview(): Attribute
    {
        return Attribute::make(get: fn() => $this->preview_type !== null);
    }
}
