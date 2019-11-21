<?php

/*
 * @copyright Copyright (c) 2019 Sergio Brighenti <sergio@brighenti.me>
 *
 * @author Sergio Brighenti <sergio@brighenti.me>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 */

namespace App\Exception\Handlers\Renderers;

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

        return view()->string('errors/500.twig', array('exception' => $displayErrorDetails ? $exception : null));
    }
}
