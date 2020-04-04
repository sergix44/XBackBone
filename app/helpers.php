<?php

if (!defined('HUMAN_RANDOM_CHARS')) {
    define('HUMAN_RANDOM_CHARS', 'bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZaeiouAEIOU');
}

if (!function_exists('humanFileSize')) {
    /**
     * Generate a human readable file size
     * @param $size
     * @param int $precision
     * @return string
     */
    function humanFileSize($size, $precision = 2): string
    {
        for ($i = 0; ($size / 1024) > 0.9; $i++, $size /= 1024) {
        }
        return round($size, $precision) . ' ' . ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'][$i];
    }
}

if (!function_exists('humanRandomString')) {
    /**
     * @param int $length
     * @return string
     */
    function humanRandomString(int $length = 13): string
    {
        $result = '';
        $numberOffset = round($length * 0.2);
        for ($x = 0; $x < $length - $numberOffset; $x++) {
            $result .= ($x % 2) ? HUMAN_RANDOM_CHARS[rand(42, 51)] : HUMAN_RANDOM_CHARS[rand(0, 41)];
        }
        for ($x = 0; $x < $numberOffset; $x++) {
            $result .= rand(0, 9);
        }
        return $result;
    }
}

if (!function_exists('isDisplayableImage')) {
    /**
     * @param string $mime
     * @return bool
     */
    function isDisplayableImage(string $mime): bool
    {
        return in_array($mime, [
            'image/apng',
            'image/bmp',
            'image/gif',
            'image/x-icon',
            'image/jpeg',
            'image/png',
            'image/svg',
            'image/svg+xml',
            'image/tiff',
            'image/webp',
        ]);
    }
}

if (!function_exists('stringToBytes')) {
    /**
     * @param $str
     * @return float
     */
    function stringToBytes(string $str): float
    {
        $val = trim($str);
        if (is_numeric($val)) {
            return (float)$val;
        }

        $last = strtolower($val[strlen($val) - 1]);
        $val = substr($val, 0, -1);

        $val = (float)$val;
        switch ($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        return $val;
    }
}

if (!function_exists('removeDirectory')) {
    /**
     * Remove a directory and it's content
     * @param $path
     */
    function removeDirectory($path)
    {
        $files = glob($path . '/*');
        foreach ($files as $file) {
            is_dir($file) ? removeDirectory($file) : unlink($file);
        }
        rmdir($path);
        return;
    }
}

if (!function_exists('cleanDirectory')) {
    /**
     * Removes all directory contents
     * @param $path
     */
    function cleanDirectory($path)
    {
        $directoryIterator = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
        $iteratorIterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iteratorIterator as $file) {
            if ($file->getFilename() !== '.gitkeep') {
                $file->isDir() ? rmdir($file) : unlink($file);
            }
        }
    }
}

if (!function_exists('redirect')) {
    /**
     * Set the redirect response
     * @param \Slim\Http\Response $response
     * @param string $path
     * @param array $args
     * @param null $status
     * @return \Slim\Http\Response
     */
    function redirect(\Slim\Http\Response $response, string $path, $args = [], $status = null)
    {
        if (substr($path, 0, 1) === '/' || substr($path, 0, 3) === '../' || substr($path, 0, 2) === './') {
            $url = urlFor($path);
        } else {
            $url = route($path, $args);
        }

        return $response->withRedirect($url, $status);
    }
}

if (!function_exists('asset')) {
    /**
     * Get the asset link with timestamp
     * @param string $path
     * @return string
     */
    function asset(string $path): string
    {
        return urlFor($path, '?' . filemtime(realpath(BASE_DIR . $path)));
    }
}

if (!function_exists('urlFor')) {
    /**
     * Generate the app url given a path
     * @param string $path
     * @param string $append
     * @return string
     */
    function urlFor(string $path, string $append = ''): string
    {
        global $app;
        $baseUrl = $app->getContainer()->get('settings')['base_url'];
        return $baseUrl . $path . $append;
    }
}

if (!function_exists('route')) {
    /**
     * Generate the app url given a path
     * @param string $path
     * @param array $args
     * @param string $append
     * @return string
     */
    function route(string $path, array $args = [], string $append = ''): string
    {
        global $app;
        $uri = $app->getContainer()->get('router')->relativePathFor($path, $args);
        return urlFor($uri, $append);
    }
}

if (!function_exists('lang')) {
    /**
     * @param string $key
     * @param array $args
     * @return string
     */
    function lang(string $key, $args = []): string
    {
        global $app;
        return $app->getContainer()->get('lang')->get($key, $args);
    }
}

if (!function_exists('isBot')) {
    /**
     * @param string $userAgent
     * @return boolean
     */
    function isBot(string $userAgent)
    {
        $bots = [
            'TelegramBot',
            'facebookexternalhit/',
            'Discordbot/',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.10; rv:38.0) Gecko/20100101 Firefox/38.0', // The discord service bot?
            'Facebot',
            'curl/',
            'wget/',
        ];

        foreach ($bots as $bot) {
            if (stripos($userAgent, $bot) !== false) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('mime2font')) {
    /**
     * Convert get the icon from the file mimetype
     * @param $mime
     * @return mixed|string
     */
    function mime2font($mime)
    {
        $classes = [
            'image' => 'fa-file-image',
            'audio' => 'fa-file-audio',
            'video' => 'fa-file-video',
            'application/pdf' => 'fa-file-pdf',
            'application/msword' => 'fa-file-word',
            'application/vnd.ms-word' => 'fa-file-word',
            'application/vnd.oasis.opendocument.text' => 'fa-file-word',
            'application/vnd.openxmlformats-officedocument.wordprocessingml' => 'fa-file-word',
            'application/vnd.ms-excel' => 'fa-file-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml' => 'fa-file-excel',
            'application/vnd.oasis.opendocument.spreadsheet' => 'fa-file-excel',
            'application/vnd.ms-powerpoint' => 'fa-file-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml' => 'fa-file-powerpoint',
            'application/vnd.oasis.opendocument.presentation' => 'fa-file-powerpoint',
            'text/plain' => 'fa-file-alt',
            'text/html' => 'fa-file-code',
            'text/x-php' => 'fa-file-code',
            'application/json' => 'fa-file-code',
            'application/gzip' => 'fa-file-archive',
            'application/zip' => 'fa-file-archive',
            'application/octet-stream' => 'fa-file-alt',
        ];

        foreach ($classes as $fullMime => $class) {
            if (strpos($mime, $fullMime) === 0) {
                return $class;
            }
        }
        return 'fa-file';
    }
}

if (!function_exists('dd')) {
    /**
     * Dumps all the given vars and halt the execution.
     */
    function dd()
    {
        array_map(function ($x) {
            echo '<pre>';
            print_r($x);
            echo '</pre>';
        }, func_get_args());
        die();
    }
}

if (!function_exists('queryParams')) {
    /**
     * Get the query parameters of the current request.
     * @param array $replace
     * @return string
     * @throws \Interop\Container\Exception\ContainerException
     */
    function queryParams(array $replace = [])
    {
        global $container;
        /** @var \Slim\Http\Request $request */
        $request = $container->get('request');

        $params = array_replace_recursive($request->getQueryParams(), $replace);

        return !empty($params) ? '?' . http_build_query($params) : '';
    }
}

if (!function_exists('glob_recursive')) {
    /**
     * Does not support flag GLOB_BRACE
     * @param $pattern
     * @param int $flags
     * @return array|false
     */
    function glob_recursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, glob_recursive($dir . '/' . basename($pattern), $flags));
        }
        return $files;
    }
}
