<?php

namespace App\Middleware;

use App\Web\Session;
use Slim\Http\Request;
use Slim\Http\Response;

class AuthMiddleware
{

	/** @var \Slim\Container */
	private $container;

	public function __construct($container)
	{
		$this->container = $container;
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param callable $next
	 * @return Response
	 */
	public function __invoke(Request $request, Response $response, callable $next)
	{
		if (!Session::get('logged', false)) {
			Session::set('redirectTo', (isset($_SERVER['HTTPS']) ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
			return $response->withRedirect($this->container->settings['base_url'] . '/login');
		}

		if (!$this->container->database->query('SELECT `id`, `active` FROM `users` WHERE `id` = ? LIMIT 1', [Session::get('user_id')])->fetch()->active) {
			Session::alert('Your account is not active anymore.', 'danger');
			Session::set('logged', false);
			Session::set('redirectTo', (isset($_SERVER['HTTPS']) ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
			return $response->withRedirect($this->container->settings['base_url'] . '/login');
		}

		return $next($request, $response);
	}

}