<?php

namespace App\Controllers;

use App\Database\Queries\UserQuery;
use App\Web\ValidationChecker;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController extends Controller
{

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
        $validator = $this->getUserCreateValidator($request);

        if ($validator->fails()) {
            return redirect($response, route('user.create'));
        }

        $maxUserQuota = -1;
        if ($this->getSetting('quota_enabled') === 'on') {
            $maxUserQuotaStr = param($request, 'max_user_quota', humanFileSize($this->getSetting('default_user_quota', -1), 0, true));
            if (!preg_match('/([0-9]+[K|M|G|T])|(\-1)/i', $maxUserQuotaStr)) {
                $this->session->alert(lang('invalid_quota', 'danger'));
                return redirect($response, route('user.create'));
            }

            if ($maxUserQuotaStr !== '-1') {
                $maxUserQuota = stringToBytes($maxUserQuotaStr);
            }
        }

        make(UserQuery::class)->create(
            param($request, 'email'),
            param($request, 'username'),
            param($request, 'password'),
            param($request, 'is_admin') !== null ? 1 : 0,
            param($request, 'is_active') !== null ? 1 : 0,
            $maxUserQuota
        );

        $this->session->alert(lang('user_created', [param($request, 'username')]), 'success');
        $this->logger->info('User '.$this->session->get('username').' created a new user.', [array_diff_key($request->getParsedBody(), array_flip(['password']))]);

        return redirect($response, route('user.index'));
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
    public function edit(Request $request, Response $response, int $id): Response
    {
        $user = make(UserQuery::class)->get($request, $id);

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
     */
    public function update(Request $request, Response $response, int $id): Response
    {
        $user = make(UserQuery::class)->get($request, $id);

        $validator = ValidationChecker::make()
            ->rules([
                'email.required' => filter_var(param($request, 'email'), FILTER_VALIDATE_EMAIL),
                'username.required' => !empty(param($request, 'username')),
                'email.unique' => $this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `email` = ? AND `email` <> ?', [param($request, 'email'), $user->email])->fetch()->count == 0,
                'username.unique' => $this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `username` = ? AND `username` <> ?', [param($request, 'username'), $user->username])->fetch()->count == 0,
                'demote' => !($user->id === $this->session->get('user_id') && param($request, 'is_admin') === null),
            ])
            ->onFail(function ($rule) {
                $alerts = [
                    'email.required' => lang('email_required'),
                    'username.required' => lang('username_required'),
                    'email.unique' => lang('email_taken'),
                    'username.unique' => lang('username_taken'),
                    'demote' => lang('cannot_demote'),
                ];

                $this->session->alert($alerts[$rule], 'danger');
            });

        if ($validator->fails()) {
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

        make(UserQuery::class)->update(
            $user->id,
            param($request, 'email'),
            param($request, 'username'),
            param($request, 'password'),
            param($request, 'is_admin') !== null ? 1 : 0,
            param($request, 'is_active') !== null ? 1 : 0,
            $user->max_disk_quota
        );

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
     */
    public function delete(Request $request, Response $response, int $id): Response
    {
        $user = make(UserQuery::class)->get($request, $id);

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
     */
    public function refreshToken(Request $request, Response $response, int $id): Response
    {
        $query = make(UserQuery::class);
        $user = $query->get($request, $id, true);

        $this->logger->info('User '.$this->session->get('username')." refreshed token of user $user->id.");

        $response->getBody()->write($query->refreshToken($user->id));

        return $response;
    }
}
