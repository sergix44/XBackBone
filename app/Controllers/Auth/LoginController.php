<?php

namespace App\Controllers\Auth;

use App\Controllers\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LoginController extends Controller
{
    /**
     * @param  Response  $response
     *
     * @return Response
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     *
     * @throws \Twig\Error\LoaderError
     */
    public function show(Response $response): Response
    {
        if ($this->session->get('logged', false)) {
            return redirect($response, route('home'));
        }

        return view()->render($response, 'auth/login.twig', [
            'register_enabled' => $this->getSetting('register_enabled', 'off'),
        ]);
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     *
     * @return Response
     * @throws \Exception
     *
     */
    public function login(Request $request, Response $response): Response
    {
        $username = param($request, 'username');
        $user = $this->database->query('SELECT `id`, `email`, `username`, `password`,`is_admin`, `active`, `current_disk_quota`, `max_disk_quota` FROM `users` WHERE `username` = ? OR `email` = ? LIMIT 1', [$username, $username])->fetch();

        if (!$user || !password_verify(param($request, 'password'), $user->password)) {
            $this->session->alert(lang('bad_login'), 'danger');
            return redirect($response, route('login'));
        }

        if (isset($this->config['maintenance']) && $this->config['maintenance'] && !$user->is_admin) {
            $this->session->alert(lang('maintenance_in_progress'), 'info');
            return redirect($response, route('login'));
        }

        if (!$user->active) {
            $this->session->alert(lang('account_disabled'), 'danger');
            return redirect($response, route('login'));
        }

        $this->session->set('logged', true);
        $this->session->set('user_id', $user->id);
        $this->session->set('username', $user->username);
        $this->session->set('admin', $user->is_admin);
        $this->setSessionQuotaInfo($user->current_disk_quota, $user->max_disk_quota);

        $this->session->alert(lang('welcome', [$user->username]), 'info');
        $this->logger->info("User $user->username logged in.");

        if (param($request, 'remember') === 'on') {
            $this->refreshRememberCookie($user->id);
        }

        if ($this->session->has('redirectTo')) {
            return redirect($response, $this->session->get('redirectTo'));
        }

        return redirect($response, route('home'));
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
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
