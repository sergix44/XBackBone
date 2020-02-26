<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class InjectMiddleware extends Middleware
{
    /**
     * @param Request        $request
     * @param RequestHandler $handler
     *
     * @return Response
     */
    public function __invoke(Request $request, RequestHandler $handler)
    {
        $head = $this->database->query('SELECT `value` FROM `settings` WHERE `key` = \'custom_head\'')->fetch();
        $this->view->getTwig()->addGlobal('customHead', $head->value ?? null);

        return $handler->handle($request);
    }
}
