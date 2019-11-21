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

namespace App\Web;

use Psr\Http\Message\ResponseInterface as Response;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class View
{
    /**
     * @var Environment
     */
    private $twig;

    /**
     * View constructor.
     *
     * @param Environment $twig
     */
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * @param Response   $response
     * @param string     $view
     * @param array|null $parameters
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     *
     * @return Response
     */
    public function render(Response $response, string $view, ?array $parameters = [])
    {
        $body = $this->twig->render($view, $parameters);
        $response->getBody()->write($body);

        return $response;
    }

    /**
     * @param string     $view
     * @param array|null $parameters
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     *
     * @return string
     */
    public function string(string $view, ?array $parameters = [])
    {
        return $this->twig->render($view, $parameters);
    }

    /**
     * @return Environment
     */
    public function getTwig(): Environment
    {
        return $this->twig;
    }
}
