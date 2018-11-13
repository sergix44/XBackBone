<?php

namespace App\Middleware;

use App\Exceptions\UnauthorizedException;
use App\Web\Session;
use Slim\Http\Request;
use Slim\Http\Response;

class AdminMiddleware
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
	 * @throws UnauthorizedException
	 */
	public function __invoke(Request $request, Response $response, callable $next)
	{
		if (!$this->container->database->query('SELECT `id`, `is_admin` FROM `users` WHERE `id` = ? LIMIT 1', [Session::get('user_id')])->fetch()->is_admin) {
			Session::alert('Your account is not admin anymore.', 'danger');
			Session::set('admin', false);
			Session::set('redirectTo', (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
			throw new UnauthorizedException();
		}

		return $next($request, $response);
	}

}