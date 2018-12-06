<?php

namespace App\Web;


class Session
{

	/**
	 * Start a session if is not already started in the current context
	 * @param string $name
	 * @param string $path
	 * @throws \Exception
	 */
	public static function init(string $name, $path = ''): void
	{
		if (session_status() === PHP_SESSION_NONE) {
			if (!is_writable($path) && $path !== '') {
				throw new \Exception("The given path '{$path}' is not writable.");
			}
			session_start([
				'name' => $name,
				'save_path' => $path,
				'cookie_httponly' => true,
			]);
		}
	}

	/**
	 * Destroy the current session
	 * @return bool
	 */
	public static function destroy(): bool
	{
		return session_destroy();
	}

	/**
	 * Clear all session stored values
	 */
	public static function clear(): void
	{
		$_SESSION = [];
	}

	/**
	 * Check if session has a stored key
	 * @param $key
	 * @return bool
	 */
	public static function has($key): bool
	{
		return isset($_SESSION[$key]);
	}

	/**
	 * Get the content of the current session
	 * @return array
	 */
	public static function all(): array
	{
		return $_SESSION;
	}

	/**
	 * Returned a value given a key
	 * @param $key
	 * @param null $default
	 * @return mixed
	 */
	public static function get($key, $default = null)
	{
		return self::has($key) ? $_SESSION[$key] : $default;
	}

	/**
	 * Add a key-value pair to the session
	 * @param $key
	 * @param $value
	 */
	public static function set($key, $value): void
	{
		$_SESSION[$key] = $value;
	}

	/**
	 * Set a flash alert
	 * @param $message
	 * @param string $type
	 */
	public static function alert($message, string $type = 'info'): void
	{
		$_SESSION['_flash'] = [$type => $message];
	}


	/**
	 * Retrieve flash alerts
	 * @return array
	 */
	public static function getAlert()
	{
		$flash = self::get('_flash');
		self::set('_flash', []);
		return $flash;
	}

}