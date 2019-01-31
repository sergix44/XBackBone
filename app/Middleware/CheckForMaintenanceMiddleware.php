<?php

namespace App\Middleware;

use App\Exceptions\MaintenanceException;
use Slim\Http\Request;
use Slim\Http\Response;

class CheckForMaintenanceMiddleware extends Middleware
{
	/**
	 * @param Request $request
	 * @param Response $response
	 * @param callable $next
	 * @return Response
	 * @throws MaintenanceException
	 */
	public function __invoke(Request $request, Response $response, callable $next)
	{
		if (isset($this->settings['maintenance']) && $this->settings['maintenance'] && !$this->database->query('SELECT `id`, `is_admin` FROM `users` WHERE `id` = ? LIMIT 1', [$this->session->get('user_id')])->fetch()->is_admin) {
			throw new MaintenanceException();
		}

		return $next($request, $response);
	}
}