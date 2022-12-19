<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class LangMiddleware extends Middleware
{
    /**
     * @param Request        $request
     * @param RequestHandler $handler
     *
     * @return Response
     */
    public function __invoke(Request $request, RequestHandler $handler)
    {
        $forcedLang = $this->getSetting('lang');
        if ($forcedLang !== null) {
            $this->lang::setLang($forcedLang);
            $request = $request->withAttribute('forced_lang', $forcedLang);
        }

        return $handler->handle($request);
    }
}
