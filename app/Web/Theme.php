<?php

namespace App\Web;

class Theme
{
    public const DEFAULT_THEME_URL = 'https://bootswatch.com/4/_vendor/bootstrap/dist/css/bootstrap.min.css';

    /**
     * @return array
     */
    public function availableThemes(): array
    {
        $apiJson = json_decode(file_get_contents('https://bootswatch.com/api/4.json'));

        $default = [];
        $default['Default - Bootstrap 4 default theme'] = self::DEFAULT_THEME_URL;

        $bootswatch = [];
        foreach ($apiJson->themes as $theme) {
            $bootswatch["{$theme->name} - {$theme->description}"] = $theme->cssMin;
        }

        $apiJson = json_decode(file_get_contents('https://theme-park.dev/themes.json'));
        $base = $apiJson->applications->xbackbone->base_css;

        $themepark = [];
        foreach ($apiJson->themes as $name => $urls) {
            $themepark[$name] = "{$base},{$urls->url}";
        }

        return [
            'default' => $default,
            'bootswatch.com' => $bootswatch,
            'theme-park.dev' => $themepark
        ];
    }

    /**
     * @param  string  $input
     * @return bool
     */
    public function applyTheme(string $input): bool
    {
        [$vendor, $css] = explode('|', $input, 2);

        if ($vendor === 'theme-park.dev') {
            [$base, $theme] = explode(',', $css);
            $data = file_get_contents(self::DEFAULT_THEME_URL).file_get_contents($base).file_get_contents($theme);
        } else {
            $data = file_get_contents($css ?? self::DEFAULT_THEME_URL);
        }

        return (bool) file_put_contents(
            BASE_DIR.'static/bootstrap/css/bootstrap.min.css',
            $data
        );
    }

    /**
     * @return string
     */
    public static function default(): string
    {
        return 'default|'.self::DEFAULT_THEME_URL;
    }
}
