<?php

/*
 * @copyright Copyright (c) 2019 Sergio Brighenti <sergio@brighenti.me>
 *
 * @author Sergio Brighenti <sergio@brighenti.me>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 */

namespace App\Web;

use Exception;

class Session
{
    /**
     * Session constructor.
     *
     * @param string $name
     * @param string $path
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
                    $params['path'].'; SameSite=Lax',
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }

            $started = @session_start([
                'name'            => $name,
                'save_path'       => $path,
                'cookie_httponly' => true,
                'gc_probability'  => 25,
                'cookie_samesite' => 'Lax', // works only for php  >= 7.3
            ]);

            if (!$started) {
                throw new Exception("Cannot start the HTTP session. That the session path '{$path}' is writable and your PHP settings.");
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
    public function clear(): void
    {
        $_SESSION = [];
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
     * @param null $default
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
     */
    public function set($key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Set a flash alert.
     *
     * @param $message
     * @param string $type
     */
    public function alert($message, string $type = 'info'): void
    {
        $_SESSION['_flash'][] = [$type => $message];
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
