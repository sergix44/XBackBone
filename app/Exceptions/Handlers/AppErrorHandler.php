<?php

namespace App\Exceptions\Handlers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Handlers\ErrorHandler;
use Throwable;

class AppErrorHandler extends ErrorHandler
{
    protected function logError(string $error): void
    {
        resolve('logger')->error($error);
    }

    public function __invoke(ServerRequestInterface $request, Throwable $exception, bool $displayErrorDetails, bool $logErrors, bool $logErrorDetails): ResponseInterface
    {
        $response = parent::__invoke($request, $exception, $displayErrorDetails, $logErrors, $logErrorDetails);

        if ($response->getStatusCode() !== 404) {
            $this->writeToErrorLog();
        }

        return $response;
    }
}
