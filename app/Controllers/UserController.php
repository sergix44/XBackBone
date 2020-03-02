<?php

namespace App\Controllers;

use App\Exceptions\ValidationException;
use App\Validators\ValidateUser;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class UserController extends Controller
{
    use ValidateUser;

    const PER_PAGE = 15;

    /**
     * @param  Response  $response
     * @param  int|null  $page
     *
     * @return Response
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     *
     * @throws \Twig\Error\LoaderError
     */
    public function index(Response $response, int $page = 0): Response
    {
        $page = max(0, --$page);

        $users = $this->database->query('SELECT * FROM `users` LIMIT ? OFFSET ?', [self::PER_PAGE, $page * self::PER_PAGE])->fetchAll();

        $pages = $this->database->query('SELECT COUNT(*) AS `count` FROM `users`')->fetch()->count / self::PER_PAGE;

        return view()->render($response,
            'user/index.twig',
            [
                'users' => $users,
                'next' => $page < floor($pages),
                'previous' => $page >= 1,
                'current_page' => ++$page,
                'quota_enabled' => $this->getSetting('quota_enabled'),
            ]
        );
    }

    /**
     * @param  Response  $response
     *
     * @return Response
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     *
     * @throws \Twig\Error\LoaderError
     */
    public function create(Response $response): Response
    {
        return view()->render($response, 'user/create.twig', [
            'default_user_quota' => humanFileSize($this->getSetting('default_user_quota'), 0, true),
            'quota_enabled' => $this->getSetting('quota_enabled', 'off'),
        ]);
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     *
     * @return Response
     */
    public function store(Request $request, Response $response): Response
    {
        try {
            $this->validateUser($request, $response, route('user.create'));
        } catch (ValidationException $e) {
            return $e->response();
        }

        if ($this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `email` = ?', param($request, 'email'))->fetch()->count > 0) {
            $this->session->alert(lang('email_taken'), 'danger');

            return redirect($response, route('user.create'));
        }

        if ($this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `username` = ?', param($request, 'username'))->fetch()->count > 0) {
            $this->session->alert(lang('username_taken'), 'danger');

            return redirect($response, route('user.create'));
        }

        $maxUserQuota = -1;
        if ($this->getSetting('quota_enabled') === 'on') {
            $maxUserQuotaStr = param($request, 'max_user_quota', humanFileSize($this->getSetting('default_user_quota'), 0, true));
            if (!preg_match('/([0-9]+[K|M|G|T])|(\-1)/i', $maxUserQuotaStr)) {
                $this->session->alert(lang('invalid_quota', 'danger'));
                return redirect($response, route('user.create'));
            }

            if ($maxUserQuotaStr !== '-1') {
                $maxUserQuota = stringToBytes($maxUserQuotaStr);
            }
        }

        do {
            $userCode = humanRandomString(5);
        } while ($this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `user_code` = ?', $userCode)->fetch()->count > 0);

        $token = $this->generateUserUploadToken();

        $this->database->query('INSERT INTO `users`(`email`, `username`, `password`, `is_admin`, `active`, `user_code`, `token`, `max_disk_quota`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [
            param($request, 'email'),
            param($request, 'username'),
            password_hash(param($request, 'password'), PASSWORD_DEFAULT),
            param($request, 'is_admin') !== null ? 1 : 0,
            param($request, 'is_active') !== null ? 1 : 0,
            $userCode,
            $token,
            $maxUserQuota,
        ]);

        $this->session->alert(lang('user_created', [param($request, 'username')]), 'success');
        $this->logger->info('User '.$this->session->get('username').' created a new user.', [array_diff_key($request->getParsedBody(), array_flip(['password']))]);

        return redirect($response, route('user.index'));
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param $id
     *
     * @return Response
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws HttpUnauthorizedException
     *
     * @throws HttpNotFoundException
     */
    public function edit(Request $request, Response $response, int $id): Response
    {
        $user = $this->getUser($request, $id, false);

        return view()->render($response, 'user/edit.twig', [
            'profile' => false,
            'user' => $user,
            'quota_enabled' => $this->getSetting('quota_enabled', 'off'),
            'max_disk_quota' => $user->max_disk_quota > 0 ? humanFileSize($user->max_disk_quota, 0, true) : -1,
        ]);
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  int  $id
     *
     * @return Response
     * @throws HttpUnauthorizedException
     *
     * @throws HttpNotFoundException
     */
    public function update(Request $request, Response $response, int $id): Response
    {
        try {
            $this->validateUser($request, $response, route('user.edit', ['id' => $id]));
        } catch (ValidationException $e) {
            return $e->response();
        }

        $user = $this->getUser($request, $id, false);

        if ($this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `email` = ? AND `email` <> ?', [param($request, 'email'), $user->email])->fetch()->count > 0) {
            $this->session->alert(lang('email_taken'), 'danger');

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

        $user->max_disk_quota = -1;
        if ($this->getSetting('quota_enabled') === 'on') {
            $maxUserQuota = param($request, 'max_user_quota', humanFileSize($this->getSetting('default_user_quota'), 0, true));
            if (!preg_match('/([0-9]+[K|M|G|T])|(\-1)/i', $maxUserQuota)) {
                $this->session->alert(lang('invalid_quota', 'danger'));
                return redirect($response, route('user.create'));
            }

            if ($maxUserQuota !== '-1') {
                $user->max_disk_quota = stringToBytes($maxUserQuota);
            }
        }

        if (param($request, 'password') !== null && !empty(param($request, 'password'))) {
            $this->database->query('UPDATE `users` SET `email`=?, `username`=?, `password`=?, `is_admin`=?, `active`=?, `max_disk_quota`=? WHERE `id` = ?', [
                param($request, 'email'),
                param($request, 'username'),
                password_hash(param($request, 'password'), PASSWORD_DEFAULT),
                param($request, 'is_admin') !== null ? 1 : 0,
                param($request, 'is_active') !== null ? 1 : 0,
                $user->max_disk_quota,
                $user->id,
            ]);
        } else {
            $this->database->query('UPDATE `users` SET `email`=?, `username`=?, `is_admin`=?, `active`=?, `max_disk_quota`=? WHERE `id` = ?', [
                param($request, 'email'),
                param($request, 'username'),
                param($request, 'is_admin') !== null ? 1 : 0,
                param($request, 'is_active') !== null ? 1 : 0,
                $user->max_disk_quota,
                $user->id,
            ]);
        }

        if ($user->id === $this->session->get('user_id')) {
            $this->setSessionQuotaInfo($user->current_disk_quota, $user->max_disk_quota);
        }

        $this->session->alert(lang('user_updated', [param($request, 'username')]), 'success');
        $this->logger->info('User '.$this->session->get('username')." updated $user->id.", [
            array_diff_key((array) $user, array_flip(['password'])),
            array_diff_key($request->getParsedBody(), array_flip(['password'])),
        ]);

        return redirect($response, route('user.index'));
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  int  $id
     *
     * @return Response
     * @throws HttpUnauthorizedException
     *
     * @throws HttpNotFoundException
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
     * @param  Request  $request
     * @param  Response  $response
     * @param  int  $id
     *
     * @return Response
     * @throws HttpUnauthorizedException
     *
     * @throws HttpNotFoundException
     */
    public function refreshToken(Request $request, Response $response, int $id): Response
    {
        $user = $this->getUser($request, $id, true);

        $token = $this->generateUserUploadToken();

        $this->database->query('UPDATE `users` SET `token`=? WHERE `id` = ?', [
            $token,
            $user->id,
        ]);

        $this->logger->info('User '.$this->session->get('username')." refreshed token of user $user->id.");

        $response->getBody()->write($token);

        return $response;
    }
}
