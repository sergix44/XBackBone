<?php

namespace App\Middleware;

use App\Exceptions\UnderMaintenanceException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class CheckForMaintenanceMiddleware extends Middleware
{
    /**
     * @param Request        $request
     * @param RequestHandler $handler
     *
     * @throws UnderMaintenanceException
     *
     * @return Response
     */
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        if ($this->config['maintenance'] && !$this->database->query('SELECT `id`, `is_admin` FROM `users` WHERE `id` = ? LIMIT 1', [$this->session->get('user_id')])->fetch()->is_admin) {
            throw new UnderMaintenanceException($request);
        }

        return $handler->handle($request);
    }
}
