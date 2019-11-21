<?php

namespace App\Middleware;

use App\Controllers\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

abstract class Middleware extends Controller
{
    /**
     * @param Request        $request
     * @param RequestHandler $handler
     *
     * @return Response
     */
    abstract public function __invoke(Request $request, RequestHandler $handler);
}
