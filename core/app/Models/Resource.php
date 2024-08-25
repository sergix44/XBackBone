<?php

namespace App\Models;

use App\Models\Properties\ResourceType;
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
        'hidden',
        'target',
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
}
