<?php

namespace App\Web;


class Session
{

	public static function init(): void
	{
		if (session_status() === PHP_SESSION_NONE) {
			session_start([
				'name' => 'xbackbone_session',
				'save_path' => 'resources/sessions'
			]);
		}
	}

	public static function destroy(): bool
	{
		return session_destroy();
	}

	public static function clear(): void
	{
		self::init();
		$_SESSION = [];
	}

	public static function has($key): bool
	{
		self::init();
		return isset($_SESSION[$key]);
	}

	public static function all(): array
	{
		self::init();
		return $_SESSION;
	}

	public static function get($key, $default = null)
	{
		self::init();
		return self::has($key) ? $_SESSION[$key] : $default;
	}

	public static function set($key, $value): void
	{
		self::init();
		$_SESSION[$key] = $value;
	}

	public static function alert($message, string $type = 'info'): void
	{
		self::init();
		$_SESSION['_flash'] = [$type => $message];
	}

	public static function getAlert()
	{
		$flash = self::get('_flash');
		self::set('_flash', []);
		return $flash;
	}

}