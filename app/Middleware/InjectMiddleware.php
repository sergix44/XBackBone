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

        return $handler->handle($request->withAttribute('custom_head_key_present', isset($head->value)));
    }
}
