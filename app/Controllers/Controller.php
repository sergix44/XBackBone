<?php

namespace App\Controllers;


use App\Exceptions\AuthenticationException;
use App\Exceptions\UnauthorizedException;
use App\Web\Session;
use Flight;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

abstract class Controller
{

	/**
	 * @throws AuthenticationException
	 */
	protected function checkLogin(): void
	{
		if (!Session::get('logged', false)) {
			Session::set('redirectTo', (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
			throw new AuthenticationException();
		}
	}

	/**
	 * @throws AuthenticationException
	 * @throws UnauthorizedException
	 */
	protected function checkAdmin(): void
	{
		$this->checkLogin();
		if (!Session::get('admin', false)) {
			throw new UnauthorizedException();
		}
	}


	protected function humanFilesize($size, $precision = 2): string
	{
		for ($i = 0; ($size / 1024) > 0.9; $i++, $size /= 1024) {
		}
		return round($size, $precision) . ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'][$i];
	}

	protected function getStorage(): Filesystem
	{
		return new Filesystem(new Local(Flight::get('config')['storage_dir']));
	}

	protected function http2push(string $url, string $as = 'image'): void
	{
		if (Flight::request()->scheme === 'HTTP/2.0') {
			$headers = isset(Flight::response()->headers()['Link']) ? Flight::response()->headers()['Link'] : [];
			$headers[] = "<${url}>; rel=preload; as=${as}";
			Flight::response()->header('Link', $headers);
		}
	}
}