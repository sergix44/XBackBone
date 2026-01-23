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

    public function isDisplayable(string $mime): bool
    {
        $mime = strtolower(trim(explode(';', $mime, 2)[0])); // strips "; charset=..."

        // only types that can be displayed directly by the browser (commonly)
        return match ($this) {
            self::IMAGE => in_array($mime, [
                'image/apng',
                'image/avif',
                'image/bmp',
                'image/gif',
                'image/jpeg',
                'image/png',
                'image/svg+xml',
                'image/webp',
                'image/x-icon',
                'image/vnd.microsoft.icon',
            ], true),

            // Note: browser support depends on codecs; these are the most common HTML5-friendly ones
            self::VIDEO => in_array($mime, [
                'video/mp4',
                'video/webm',
                'video/ogg',
            ], true),

            self::AUDIO => in_array($mime, [
                'audio/mpeg', // mp3
                'audio/mp4',  // aac/m4a often comes as audio/mp4
                'audio/aac',
                'audio/wav',
                'audio/ogg',
                'audio/opus',
            ], true),

            self::PDF => in_array($mime, [
                'application/pdf',
                'application/x-pdf',
            ]),

            // Many text/* are displayable, but this is "renderable", not necessarily "safe to inline"
            self::TEXT => str_starts_with($mime, 'text/'),

            default => false,
        };
    }

}
