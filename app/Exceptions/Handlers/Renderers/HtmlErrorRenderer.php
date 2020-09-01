<?php

namespace App\Exceptions\Handlers\Renderers;

use App\Exceptions\UnderMaintenanceException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Interfaces\ErrorRendererInterface;
use Throwable;

class HtmlErrorRenderer implements ErrorRendererInterface
{
    /**
     * @param Throwable $exception
     * @param bool      $displayErrorDetails
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     *
     * @return string
     */
    public function __invoke(Throwable $exception, bool $displayErrorDetails): string
    {
        if ($exception instanceof UnderMaintenanceException) {
            return view()->string('errors/maintenance.twig');
        }

        if ($exception instanceof HttpUnauthorizedException || $exception instanceof HttpForbiddenException) {
            return view()->string('errors/403.twig');
        }

        if ($exception instanceof HttpMethodNotAllowedException) {
            return view()->string('errors/405.twig');
        }

        if ($exception instanceof HttpNotFoundException) {
            return view()->string('errors/404.twig');
        }

        if ($exception instanceof HttpBadRequestException) {
            return view()->string('errors/400.twig');
        }

        return view()->string('errors/500.twig', ['exception' => $displayErrorDetails ? $exception : null]);
    }
}
