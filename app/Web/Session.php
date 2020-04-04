<?php

namespace App\Web;

use Exception;

class Session
{

    /**
     * Session constructor.
     * @param string $name
     * @param string $path
     * @throws Exception
     */
    public function __construct(string $name, $path = '')
    {
        if (session_status() === PHP_SESSION_NONE) {
            if (!is_writable($path) && $path !== '') {
                throw new Exception("The given path '{$path}' is not writable.");
            }

            $started = @session_start([
                'name' => $name,
                'save_path' => $path,
                'cookie_httponly' => true,
                'gc_probability' => 25,
            ]);

            if (!$started) {
                throw new Exception("Cannot start the HTTP session. That the session path '{$path}' is writable and your PHP settings.");
            }
        }
    }

    /**
     * Destroy the current session
     * @return bool
     */
    public function destroy(): bool
    {
        return session_destroy();
    }

    /**
     * Clear all session stored values
     */
    public function clear(): void
    {
        $_SESSION = [];
    }

    /**
     * Check if session has a stored key
     * @param $key
     * @return bool
     */
    public function has($key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Get the content of the current session
     * @return array
     */
    public function all(): array
    {
        return $_SESSION;
    }

    /**
     * Returned a value given a key
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return self::has($key) ? $_SESSION[$key] : $default;
    }

    /**
     * Add a key-value pair to the session
     * @param $key
     * @param $value
     */
    public function set($key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Set a flash alert
     * @param $message
     * @param string $type
     */
    public function alert($message, string $type = 'info'): void
    {
        $_SESSION['_flash'][] = [$type => $message];
    }


    /**
     * Retrieve flash alerts
     * @return array
     */
    public function getAlert(): ?array
    {
        $flash = self::get('_flash');
        self::set('_flash', []);
        return $flash;
    }
}
