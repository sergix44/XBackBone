<?php

namespace App\Controllers;

use App\Database\Repositories\UserRepository;
use App\Web\Mail;
use App\Web\ValidationHelper;
use League\Flysystem\FileNotFoundException;
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

        return view()->render(
            $response,
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
     * @throws \Exception
     */
    public function store(Request $request, Response $response): Response
    {
        $maxUserQuota = -1;
        $validator = $this->getUserCreateValidator($request)
            ->callIf($this->getSetting('quota_enabled') === 'on', function ($session) use (&$maxUserQuota, &$request) {
                $maxUserQuota = param($request, 'max_user_quota', humanFileSize($this->getSetting('default_user_quota'), 0, true));
                if (!preg_match('/(^[0-9]+[B|K|M|G|T]$)|(^\-1$)/i', $maxUserQuota)) {
                    $session->alert(lang('invalid_quota', 'danger'));
                    return false;
                }

                if ($maxUserQuota !== '-1') {
                    $maxUserQuota = stringToBytes($maxUserQuota);
                }

                return true;
            });

        if ($validator->fails()) {
            return redirect($response, route('user.create'));
        }

        make(UserRepository::class)->create(
            param($request, 'email'),
            param($request, 'username'),
            param($request, 'password'),
            param($request, 'is_admin') !== null ? 1 : 0,
            param($request, 'is_active') !== null ? 1 : 0,
            $maxUserQuota,
            false,
            param($request, 'hide_uploads') !== null ? 1 : 0,
            param($request, 'copy_raw') !== null ? 1 : 0
        );

        if (param($request, 'send_notification') !== null) {
            $resetToken = null;
            if (empty(param($request, 'password'))) {
                $resetToken = bin2hex(random_bytes(16));

                $this->database->query('UPDATE `users` SET `reset_token`=? WHERE `id` = ?', [
                    $resetToken,
                    $this->database->getPdo()->lastInsertId(),
                ]);
            }
            $this->sendCreateNotification($request, $resetToken);
        }

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
        $user = make(UserRepository::class)->get($request, $id);

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
        $user = make(UserRepository::class)->get($request, $id);
        $user->max_disk_quota = -1;

        /** @var ValidationHelper $validator */
        $validator = make(ValidationHelper::class)
            ->alertIf(!filter_var(param($request, 'email'), FILTER_VALIDATE_EMAIL), 'email_required')
            ->alertIf(empty(param($request, 'username')), 'username_required')
            ->alertIf($this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `email` = ? AND `email` <> ?', [param($request, 'email'), $user->email])->fetch()->count != 0, 'email_taken')
            ->alertIf($this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `username` = ? AND `username` <> ?', [param($request, 'username'), $user->username])->fetch()->count != 0, 'username_taken')
            ->alertIf($user->id === $this->session->get('user_id') && param($request, 'is_admin') === null, 'cannot_demote')
            ->callIf($this->getSetting('quota_enabled') === 'on', function ($session) use (&$user, &$request) {
                $maxUserQuota = param($request, 'max_user_quota', humanFileSize($this->getSetting('default_user_quota'), 0, true));
                if (!preg_match('/(^[0-9]+[B|K|M|G|T]$)|(^\-1$)/i', $maxUserQuota)) {
                    $session->alert(lang('invalid_quota', 'danger'));
                    return false;
                }

                if ($maxUserQuota !== '-1') {
                    $user->max_disk_quota = stringToBytes($maxUserQuota);
                }

                return true;
            });

        if ($validator->fails()) {
            return redirect($response, route('user.edit', ['id' => $id]));
        }

        make(UserRepository::class)->update(
            $user->id,
            param($request, 'email'),
            param($request, 'username'),
            param($request, 'password'),
            param($request, 'is_admin') !== null ? 1 : 0,
            param($request, 'is_active') !== null ? 1 : 0,
            $user->max_disk_quota,
            param($request, 'ldap') !== null ? 1 : 0,
            param($request, 'hide_uploads') !== null ? 1 : 0,
            param($request, 'copy_raw') !== null ? 1 : 0
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
        $user = make(UserRepository::class)->get($request, $id);

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
     * @return Response
     */
    public function clearUserMedia(Request $request, Response $response, int $id): Response
    {
        $user = make(UserRepository::class)->get($request, $id, true);

        $medias = $this->database->query('SELECT * FROM `uploads` WHERE `user_id` = ?', $user->id);

        foreach ($medias as $media) {
            try {
                $this->storage->delete($media->storage_path);
            } catch (FileNotFoundException $e) {
            }
        }

        $this->database->query('DELETE FROM `uploads` WHERE `user_id` = ?', $user->id);
        $this->database->query('UPDATE `users` SET `current_disk_quota`=? WHERE `id` = ?', [
            0,
            $user->id,
        ]);

        $this->session->alert(lang('account_media_deleted'), 'success');
        return redirect($response, route('user.edit', ['id' => $id]));
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
        $query = make(UserRepository::class);
        $user = $query->get($request, $id, true);

        $this->logger->info('User '.$this->session->get('username')." refreshed token of user $user->id.");

        $response->getBody()->write($query->refreshToken($user->id));

        return $response;
    }

    /**
     * @param $request
     * @param  null  $resetToken
     */
    private function sendCreateNotification($request, $resetToken = null)
    {
        if ($resetToken === null && !empty(param($request, 'password'))) {
            $message = lang('mail.new_account_text_with_pw', [
                param($request, 'username'),
                $this->config['app_name'],
                $this->config['base_url'],
                $this->config['base_url'],
                param($request, 'username'),
                param($request, 'password'),
                route('login.show'),
                route('login.show'),
            ]);
        } else {
            $message = lang('mail.new_account_text_with_reset', [
                param($request, 'username'),
                $this->config['app_name'],
                $this->config['base_url'],
                $this->config['base_url'],
                route('recover.password', ['resetToken' => $resetToken]),
                route('recover.password', ['resetToken' => $resetToken]),
            ]);
        }

        Mail::make()
            ->from(platform_mail(), $this->config['app_name'])
            ->to(param($request, 'email'))
            ->subject(lang('mail.new_account', [$this->config['app_name']]))
            ->message($message)
            ->send();
    }
}
