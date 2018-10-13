<?php

namespace App\Controllers;


use App\Exceptions\AuthenticationException;
use App\Exceptions\UnauthorizedException;
use App\Web\Session;
use Flight;
use App\Database\DB;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

abstract class Controller
{

	/**
	 * Check if the current user is logged in
	 * @throws AuthenticationException
	 */
	protected function checkLogin(): void
	{
		if (!Session::get('logged', false)) {
			Session::set('redirectTo', (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
			throw new AuthenticationException();
		}

		if (!DB::query('SELECT `id`, `active` FROM `users` WHERE `id` = ? LIMIT 1', [Session::get('user_id')])->fetch()->active) {
			Session::alert('Your account is not active anymore.', 'danger');
			Session::set('logged', false);
			Session::set('redirectTo', (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
			throw new AuthenticationException();
		}

	}

	/**
	 * Check if the current user is an admin
	 * @throws AuthenticationException
	 * @throws UnauthorizedException
	 */
	protected function checkAdmin(): void
	{
		$this->checkLogin();

		if (!DB::query('SELECT `id`, `is_admin` FROM `users` WHERE `id` = ? LIMIT 1', [Session::get('user_id')])->fetch()->is_admin) {
			Session::alert('Your account is not admin anymore.', 'danger');
			Session::set('admin', false);
			Session::set('redirectTo', (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
			throw new UnauthorizedException();
		}
	}


	/**
	 * Generate a human readable file size
	 * @param $size
	 * @param int $precision
	 * @return string
	 */
	protected function humanFilesize($size, $precision = 2): string
	{
		for ($i = 0; ($size / 1024) > 0.9; $i++, $size /= 1024) {
		}
		return round($size, $precision) . ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'][$i];
	}

	/**
	 * Get a filesystem instance
	 * @return Filesystem
	 */
	protected function getStorage(): Filesystem
	{
		return new Filesystem(new Local(Flight::get('config')['storage_dir']));
	}

	/**
	 * Set http2 header for a resource if is supported
	 * @param string $url
	 * @param string $as
	 */
	protected function http2push(string $url, string $as = 'image'): void
	{
		if (Flight::request()->scheme === 'HTTP/2.0') {
			$headers = isset(Flight::response()->headers()['Link']) ? Flight::response()->headers()['Link'] : [];
			$headers[] = "<${url}>; rel=preload; as=${as}";
			Flight::response()->header('Link', $headers);
		}
	}
}