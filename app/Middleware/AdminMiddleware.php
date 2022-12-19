<?php

namespace App\Middleware;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Exception\HttpUnauthorizedException;

class AdminMiddleware extends Middleware
{
    /**
     * @param Request        $request
     * @param RequestHandler $handler
     *
     * @throws HttpUnauthorizedException
     *
     * @return Response
     */
    public function __invoke(Request $request, RequestHandler $handler): ResponseInterface
    {
        if (!$this->database->query('SELECT `id`, `is_admin` FROM `users` WHERE `id` = ? LIMIT 1', [$this->session->get('user_id')])->fetch()->is_admin) {
            $this->session->set('admin', false);

            throw new HttpUnauthorizedException($request);
        }

        return $handler->handle($request);
    }
}
