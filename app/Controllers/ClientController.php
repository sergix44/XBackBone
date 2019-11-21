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

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class ClientController extends Controller
{
    /**
     * @param Request  $request
     * @param Response $response
     * @param int      $id
     *
     * @throws HttpNotFoundException
     * @throws HttpUnauthorizedException
     *
     * @return Response
     */
    public function getShareXConfig(Request $request, Response $response, int $id): Response
    {
        $user = $this->getUser($request, $id, true);

        if ($user->token === null || $user->token === '') {
            $this->session->alert(lang('no_upload_token'), 'danger');

            return redirect($response, $request->getHeaderLine('Referer'));
        }

        $json = array(
            'DestinationType' => 'ImageUploader, TextUploader, FileUploader',
            'RequestURL'      => route('upload'),
            'FileFormName'    => 'upload',
            'Arguments'       => array(
                'file'  => '$filename$',
                'text'  => '$input$',
                'token' => $user->token,
            ),
            'URL'          => '$json:url$',
            'ThumbnailURL' => '$json:url$/raw',
            'DeletionURL'  => '$json:url$/delete/'.$user->token,
        );

        return json($response, $json, 200, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
            ->withHeader('Content-Disposition', 'attachment;filename="'.$user->username.'-ShareX.sxcu"');
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param int      $id
     *
     * @throws HttpNotFoundException
     * @throws HttpUnauthorizedException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     *
     * @return Response
     */
    public function getBashScript(Request $request, Response $response, int $id): Response
    {
        $user = $this->getUser($request, $id, true);

        if ($user->token === null || $user->token === '') {
            $this->session->alert(lang('no_upload_token'), 'danger');

            return redirect($response, $request->getHeaderLine('Referer'));
        }

        return view()->render($response->withHeader('Content-Disposition', 'attachment;filename="xbackbone_uploader_'.$user->username.'.sh"'),
            'scripts/xbackbone_uploader.sh.twig',
            array(
                'username'   => $user->username,
                'upload_url' => route('upload'),
                'token'      => $user->token,
            )
        );
    }
}
