<?php

namespace App\Models\Properties;

use Illuminate\Support\Str;

enum ResourceType: string
{
    case IMAGE = 'IMAGE';
    case VIDEO = 'VIDEO';
    case AUDIO = 'AUDIO';
    case PDF = 'PDF';
    case TEXT = 'TEXT';
    case FILE = 'FILE';
    case LINK = 'LINK';
    case DIRECTORY = 'DIRECTORY';

    public static function fromMime(string $mime): self
    {
        $data = explode('/', $mime);
        $type = $data[0];
        $subtype = $data[1] ?? '';

        return match (true) {
            $type === 'image' => self::IMAGE,
            $type === 'video' => self::VIDEO,
            $type === 'audio' => self::AUDIO,
            Str::contains($subtype, ['pdf', 'x-pdf']) => self::PDF,
            $type === 'text' => self::TEXT,
            default => self::FILE,
        };
    }

    public static function fromValue(string $value): self
    {
        return match (true) {
            Str::startsWith($value, 'http') => self::LINK,
            default => self::FILE,
        };
    }

    public function isPreviewable(): bool
    {
        return match ($this) {
            self::IMAGE => true,
            self::VIDEO => true,
            self::AUDIO => true,
            self::PDF => true,
            self::TEXT => true,
            default => false,
        };
    }
}
