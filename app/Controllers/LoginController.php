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

class LoginController extends Controller
{
    /**
     * @param Response $response
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     *
     * @return Response
     */
    public function show(Response $response): Response
    {
        if ($this->session->get('logged', false)) {
            return redirect($response, route('home'));
        }

        return view()->render($response, 'auth/login.twig');
    }

    /**
     * @param Request  $request
     * @param Response $response
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function login(Request $request, Response $response): Response
    {
        $username = param($request, 'username');
        $result = $this->database->query('SELECT `id`, `email`, `username`, `password`,`is_admin`, `active` FROM `users` WHERE `username` = ? OR `email` = ? LIMIT 1', [$username, $username])->fetch();

        if (!$result || !password_verify(param($request, 'password'), $result->password)) {
            $this->session->alert(lang('bad_login'), 'danger');

            return redirect($response, route('login'));
        }

        if (isset($this->config['maintenance']) && $this->config['maintenance'] && !$result->is_admin) {
            $this->session->alert(lang('maintenance_in_progress'), 'info');

            return redirect($response, route('login'));
        }

        if (!$result->active) {
            $this->session->alert(lang('account_disabled'), 'danger');

            return redirect($response, route('login'));
        }

        $this->session->set('logged', true);
        $this->session->set('user_id', $result->id);
        $this->session->set('username', $result->username);
        $this->session->set('admin', $result->is_admin);
        $this->session->set('used_space', humanFileSize($this->getUsedSpaceByUser($result->id)));

        $this->session->alert(lang('welcome', [$result->username]), 'info');
        $this->logger->info("User $result->username logged in.");

        if (param($request, 'remember') === 'on') {
            $selector = bin2hex(random_bytes(8));
            $token = bin2hex(random_bytes(32));
            $expire = time() + 604800; // a week

            $this->database->query('UPDATE `users` SET `remember_selector`=?, `remember_token`=?, `remember_expire`=? WHERE `id`=?', [
                $selector,
                password_hash($token, PASSWORD_DEFAULT),
                date('Y-m-d\TH:i:s', $expire),
                $result->id,
            ]);

            // Workaround for php <= 7.3
            if (PHP_VERSION_ID < 70300) {
                setcookie('remember', "{$selector}:{$token}", $expire, '; SameSite=Lax', '', false, true);
            } else {
                setcookie('remember', "{$selector}:{$token}", [
                    'expires'  => $expire,
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }
        }

        if ($this->session->has('redirectTo')) {
            return redirect($response, $this->session->get('redirectTo'));
        }

        return redirect($response, route('home'));
    }

    /**
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     */
    public function logout(Request $request, Response $response): Response
    {
        $this->session->clear();
        $this->session->set('logged', false);
        $this->session->alert(lang('goodbye'), 'warning');

        if (!empty($request->getCookieParams()['remember'])) {
            setcookie('remember', null);
        }

        return redirect($response, route('login.show'));
    }
}
