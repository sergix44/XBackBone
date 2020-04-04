<?php

namespace App\Exception\Handlers;

use Slim\Handlers\ErrorHandler;

class AppErrorHandler extends ErrorHandler
{
    protected function logError(string $error): void
    {
        resolve('logger')->error($error);
    }
}
