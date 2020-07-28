<?php

namespace App\Controllers;

use App\Database\Queries\UserQuery;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ClientController extends Controller
{
    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  int  $id
     *
     * @return Response
     */
    public function getShareXConfig(Request $request, Response $response, int $id): Response
    {
        $user = make(UserQuery::class)->get($request, $id, true);

        if ($user->token === null || $user->token === '') {
            $this->session->alert(lang('no_upload_token'), 'danger');

            return redirect($response, $request->getHeaderLine('Referer'));
        }

        $json = [
            'DestinationType' => 'ImageUploader, TextUploader, FileUploader',
            'RequestURL'      => route('upload'),
            'FileFormName'    => 'upload',
            'Arguments'       => [
                'file'  => '$filename$',
                'text'  => '$input$',
                'token' => $user->token,
            ],
            'URL'          => '$json:url$',
            'ThumbnailURL' => '$json:url$/raw',
            'DeletionURL'  => '$json:url$/delete/'.$user->token,
        ];

        return json($response, $json, 200, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
            ->withHeader('Content-Disposition', 'attachment;filename="'.$user->username.'-ShareX.sxcu"');
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  int  $id
     *
     * @return Response
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function getBashScript(Request $request, Response $response, int $id): Response
    {
        $user = make(UserQuery::class)->get($request, $id, true);

        if ($user->token === null || $user->token === '') {
            $this->session->alert(lang('no_upload_token'), 'danger');

            return redirect($response, $request->getHeaderLine('Referer'));
        }

        return view()->render(
            $response->withHeader('Content-Disposition', 'attachment;filename="xbackbone_uploader_'.$user->username.'.sh"'),
            'scripts/xbackbone_uploader.sh.twig',
            [
                'username'   => $user->username,
                'upload_url' => route('upload'),
                'token'      => $user->token,
            ]
        );
    }
}
