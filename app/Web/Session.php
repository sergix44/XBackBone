<?php

namespace App\Web;

use Exception;

class Session
{
    /**
     * Session constructor.
     *
     * @param  string  $name
     * @param  string  $path
     *
     * @throws Exception
     */
    public function __construct(string $name, $path = '')
    {
        if (session_status() === PHP_SESSION_NONE) {
            if (!is_writable($path) && $path !== '') {
                throw new Exception("The given path '{$path}' is not writable.");
            }

            // Workaround for php <= 7.3
            if (PHP_VERSION_ID < 70300) {
                $params = session_get_cookie_params();
                session_set_cookie_params(
                    $params['lifetime'],
                    $params['path'].'; SameSite=Strict',
                    $params['domain'],
                    isSecure(),
                    $params['httponly']
                );
            }

            $started = @session_start([
                'name' => $name,
                'save_path' => $path,
                'cookie_httponly' => true,
                'gc_probability' => 25,
                'cookie_samesite' => 'Strict', // works only for php  >= 7.3
                'cookie_secure' => isSecure(),
            ]);

            if (!$started) {
                throw new Exception("Cannot start the HTTP session. The session path '{$path}' is not writable.");
            }
        }
    }

    /**
     * @return string
     */
    public function getId()
    {
        return session_id();
    }

    /**
     * Destroy the current session.
     *
     * @return bool
     */
    public function destroy(): bool
    {
        return session_destroy();
    }

    /**
     * Clear all session stored values.
     */
    public function clear(): Session
    {
        $_SESSION = [];
        return $this;
    }

    /**
     * Check if session has a stored key.
     *
     * @param $key
     *
     * @return bool
     */
    public function has($key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Get the content of the current session.
     *
     * @return array
     */
    public function all(): array
    {
        return $_SESSION;
    }

    /**
     * Returned a value given a key.
     *
     * @param $key
     * @param  null  $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return self::has($key) ? $_SESSION[$key] : $default;
    }

    /**
     * Add a key-value pair to the session.
     *
     * @param $key
     * @param $value
     * @return Session
     */
    public function set($key, $value): Session
    {
        $_SESSION[$key] = $value;
        return $this;
    }

    /**
     * Set a flash alert.
     *
     * @param $message
     * @param  string  $type
     * @return Session
     */
    public function alert($message, string $type = 'info'): Session
    {
        $_SESSION['_flash'][] = [$type => $message];
        return $this;
    }

    /**
     * Closes the current session
     *
     * @return bool|void
     */
    public function close()
    {
        return session_write_close();
    }

    /**
     * Retrieve flash alerts.
     *
     * @return array
     */
    public function getAlert(): ?array
    {
        $flash = self::get('_flash');
        self::set('_flash', []);

        return $flash;
    }
}
