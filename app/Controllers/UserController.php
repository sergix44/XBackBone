<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class UserController extends Controller
{
    const PER_PAGE = 15;

    /**
     * @param Response $response
     * @param int|null $page
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     *
     * @return Response
     */
    public function index(Response $response, int $page = 0): Response
    {
        $page = max(0, --$page);

        $users = $this->database->query('SELECT * FROM `users` LIMIT ? OFFSET ?', [self::PER_PAGE, $page * self::PER_PAGE])->fetchAll();

        $pages = $this->database->query('SELECT COUNT(*) AS `count` FROM `users`')->fetch()->count / self::PER_PAGE;

        return view()->render($response,
            'user/index.twig',
            [
                'users'        => $users,
                'next'         => $page < floor($pages),
                'previous'     => $page >= 1,
                'current_page' => ++$page,
            ]
        );
    }

    /**
     * @param Response $response
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     *
     * @return Response
     */
    public function create(Response $response): Response
    {
        return view()->render($response, 'user/create.twig');
    }

    /**
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     */
    public function store(Request $request, Response $response): Response
    {
        if (param($request, 'email') === null) {
            $this->session->alert(lang('email_required'), 'danger');

            return redirect($response, route('user.create'));
        }

        if ($this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `email` = ?', param($request, 'email'))->fetch()->count > 0) {
            $this->session->alert(lang('email_taken'), 'danger');

            return redirect($response, route('user.create'));
        }

        if (param($request, 'username') === null) {
            $this->session->alert(lang('username_required'), 'danger');

            return redirect($response, route('user.create'));
        }

        if (param($request, 'password') === null) {
            $this->session->alert(lang('password_required'), 'danger');

            return redirect($response, route('user.create'));
        }

        if ($this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `username` = ?', param($request, 'username'))->fetch()->count > 0) {
            $this->session->alert(lang('username_taken'), 'danger');

            return redirect($response, route('user.create'));
        }

        do {
            $userCode = humanRandomString(5);
        } while ($this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `user_code` = ?', $userCode)->fetch()->count > 0);

        $token = $this->generateNewToken();

        $this->database->query('INSERT INTO `users`(`email`, `username`, `password`, `is_admin`, `active`, `user_code`, `token`) VALUES (?, ?, ?, ?, ?, ?, ?)', [
            param($request, 'email'),
            param($request, 'username'),
            password_hash(param($request, 'password'), PASSWORD_DEFAULT),
            param($request, 'is_admin') !== null ? 1 : 0,
            param($request, 'is_active') !== null ? 1 : 0,
            $userCode,
            $token,
        ]);

        $this->session->alert(lang('user_created', [param($request, 'username')]), 'success');
        $this->logger->info('User '.$this->session->get('username').' created a new user.', [array_diff_key($request->getParsedBody(), array_flip(['password']))]);

        return redirect($response, route('user.index'));
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param $id
     *
     * @throws HttpNotFoundException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws HttpUnauthorizedException
     *
     * @return Response
     */
    public function edit(Request $request, Response $response, int $id): Response
    {
        $user = $this->getUser($request, $id, false);

        return view()->render($response, 'user/edit.twig', [
            'profile' => false,
            'user'    => $user,
        ]);
    }

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
    public function update(Request $request, Response $response, int $id): Response
    {
        $user = $this->getUser($request, $id, false);

        if (param($request, 'email') === null) {
            $this->session->alert(lang('email_required'), 'danger');

            return redirect($response, route('user.edit', ['id' => $id]));
        }

        if ($this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `email` = ? AND `email` <> ?', [param($request, 'email'), $user->email])->fetch()->count > 0) {
            $this->session->alert(lang('email_taken'), 'danger');

            return redirect($response, route('user.edit', ['id' => $id]));
        }

        if (param($request, 'username') === null) {
            $this->session->alert(lang('username_required'), 'danger');

            return redirect($response, route('user.edit', ['id' => $id]));
        }

        if ($this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `username` = ? AND `username` <> ?', [param($request, 'username'), $user->username])->fetch()->count > 0) {
            $this->session->alert(lang('username_taken'), 'danger');

            return redirect($response, route('user.edit', ['id' => $id]));
        }

        if ($user->id === $this->session->get('user_id') && param($request, 'is_admin') === null) {
            $this->session->alert(lang('cannot_demote'), 'danger');

            return redirect($response, route('user.edit', ['id' => $id]));
        }

        if (param($request, 'password') !== null && !empty(param($request, 'password'))) {
            $this->database->query('UPDATE `users` SET `email`=?, `username`=?, `password`=?, `is_admin`=?, `active`=? WHERE `id` = ?', [
                param($request, 'email'),
                param($request, 'username'),
                password_hash(param($request, 'password'), PASSWORD_DEFAULT),
                param($request, 'is_admin') !== null ? 1 : 0,
                param($request, 'is_active') !== null ? 1 : 0,
                $user->id,
            ]);
        } else {
            $this->database->query('UPDATE `users` SET `email`=?, `username`=?, `is_admin`=?, `active`=? WHERE `id` = ?', [
                param($request, 'email'),
                param($request, 'username'),
                param($request, 'is_admin') !== null ? 1 : 0,
                param($request, 'is_active') !== null ? 1 : 0,
                $user->id,
            ]);
        }

        $this->session->alert(lang('user_updated', [param($request, 'username')]), 'success');
        $this->logger->info('User '.$this->session->get('username')." updated $user->id.", [
            array_diff_key((array) $user, array_flip(['password'])),
            array_diff_key($request->getParsedBody(), array_flip(['password'])),
        ]);

        return redirect($response, route('user.index'));
    }

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
    public function delete(Request $request, Response $response, int $id): Response
    {
        $user = $this->getUser($request, $id, false);

        if ($user->id === $this->session->get('user_id')) {
            $this->session->alert(lang('cannot_delete'), 'danger');

            return redirect($response, route('user.index'));
        }

        $this->database->query('DELETE FROM `users` WHERE `id` = ?', $user->id);

        $this->session->alert(lang('user_deleted'), 'success');
        $this->logger->info('User '.$this->session->get('username')." deleted $user->id.");

        return redirect($response, route('user.index'));
    }

    /**
     * @param Request  $request
     * @param Response $response
     *
     * @throws HttpNotFoundException
     * @throws HttpUnauthorizedException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     *
     * @return Response
     */
    public function profile(Request $request, Response $response): Response
    {
        $user = $this->getUser($request, $this->session->get('user_id'), true);

        return view()->render($response, 'user/edit.twig', [
            'profile' => true,
            'user'    => $user,
        ]);
    }

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
    public function profileEdit(Request $request, Response $response, int $id): Response
    {
        $user = $this->getUser($request, $id, true);

        if (param($request, 'email') === null) {
            $this->session->alert(lang('email_required'), 'danger');

            return redirect($response, route('profile'));
        }

        if ($this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `email` = ? AND `email` <> ?', [param($request, 'email'), $user->email])->fetch()->count > 0) {
            $this->session->alert(lang('email_taken'), 'danger');

            return redirect($response, route('profile'));
        }

        if (param($request, 'password') !== null && !empty(param($request, 'password'))) {
            $this->database->query('UPDATE `users` SET `email`=?, `password`=? WHERE `id` = ?', [
                param($request, 'email'),
                password_hash(param($request, 'password'), PASSWORD_DEFAULT),
                $user->id,
            ]);
        } else {
            $this->database->query('UPDATE `users` SET `email`=? WHERE `id` = ?', [
                param($request, 'email'),
                $user->id,
            ]);
        }

        $this->session->alert(lang('profile_updated'), 'success');
        $this->logger->info('User '.$this->session->get('username')." updated profile of $user->id.");

        return redirect($response, route('profile'));
    }

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
    public function refreshToken(Request $request, Response $response, int $id): Response
    {
        $user = $this->getUser($request, $id, true);

        $token = $this->generateNewToken();

        $this->database->query('UPDATE `users` SET `token`=? WHERE `id` = ?', [
            $token,
            $user->id,
        ]);

        $this->logger->info('User '.$this->session->get('username')." refreshed token of user $user->id.");

        $response->getBody()->write($token);

        return $response;
    }

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
    public function getShareXconfigFile(Request $request, Response $response, int $id): Response
    {
        $user = $this->getUser($request, $id, true);

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
    public function getUploaderScriptFile(Request $request, Response $response, int $id): Response
    {
        $user = $this->getUser($request, $id, true);

        if ($user->token === null || $user->token === '') {
            $this->session->alert(lang('no_upload_token'), 'danger');

            return redirect($response, $request->getHeaderLine('Referer'));
        }

        return view()->render($response->withHeader('Content-Disposition', 'attachment;filename="xbackbone_uploader_'.$user->username.'.sh"'),
            'scripts/xbackbone_uploader.sh.twig',
            [
                'username'   => $user->username,
                'upload_url' => route('upload'),
                'token'      => $user->token,
            ]
        );
    }

    /**
     * @return string
     */
    protected function generateNewToken(): string
    {
        do {
            $token = 'token_'.md5(uniqid('', true));
        } while ($this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `token` = ?', $token)->fetch()->count > 0);

        return $token;
    }
}
