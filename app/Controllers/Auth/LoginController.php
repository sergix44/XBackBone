<?php

namespace App\Controllers\Auth;

use App\Controllers\Controller;
use App\Database\Queries\UserQuery;
use App\Web\ValidationChecker;
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
            'recaptcha_site_key' => $this->getSetting('recaptcha_enabled') === 'on' ? $this->getSetting('recaptcha_site_key') : null,
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
        if ($this->getSetting('recaptcha_enabled') === 'on') {
            $recaptcha = json_decode(file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$this->getSetting('recaptcha_secret_key').'&response='.param($request, 'recaptcha_token')));

            if ($recaptcha->success && $recaptcha->score < 0.5) {
                $this->session->alert(lang('recaptcha_failed'), 'danger');
                return redirect($response, route('login'));
            }
        }

        $username = param($request, 'username');
        $user = $this->database->query('SELECT `id`, `email`, `username`, `password`,`is_admin`, `active`, `current_disk_quota`, `max_disk_quota` FROM `users` WHERE `username` = ? OR `email` = ? LIMIT 1', [$username, $username])->fetch();

        if ($this->config['ldap']['enabled'] && !$user) {
            $this->ldapLogin($username, param($request, 'password'), $user);
        }

        $validator = ValidationChecker::make()
            ->rules([
                'login' => $user && password_verify(param($request, 'password'), $user->password),
                'maintenance' => !isset($this->config['maintenance']) || !$this->config['maintenance'] || $user->is_admin,
                'user_active' => $user->active,
            ])
            ->onFail(function ($rule) {
                $alerts = [
                    'login' => lang('bad_login'),
                    'maintenance' => lang('maintenance_in_progress'),
                    'user_active' => lang('account_disabled'),
                ];

                $this->session->alert($alerts[$rule], $rule === 'maintenance' ? 'info' : 'danger');
            });
        if ($validator->fails()) {
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

    /**
     * @param  string  $username
     * @param  string  $password
     * @param $dbUser
     * @return bool
     */
    protected function ldapLogin(string $username, string $password, $dbUser)
    {
        if (!extension_loaded('ldap')) {
            return false;
        }

        $server = ldap_connect($this->config['ldap']['host'], $this->config['ldap']['port']);

        if (!$server) {
            $this->session->alert(lang('ldap_cant_connect'), 'warning');
            return false;
        }

        ldap_set_option($server, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($server, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($server, LDAP_OPT_NETWORK_TIMEOUT, 10);

        $bindString = 'uid='.addslashes($username);
        if ($this->config['ldap']['user_domain'] !== null) {
            $bindString .= ','.$this->config['ldap']['user_domain'];
        }

        if ($this->config['ldap']['base_domain'] !== null) {
            $bindString .= ','.$this->config['ldap']['base_domain'];
        }

        if (!@ldap_bind($server, $bindString, $password)) {
            return false;
        }

        if (!$dbUser) {
            make(UserQuery::class)->create(
                filter_var($username, FILTER_VALIDATE_EMAIL) ? $username : $username.$this->config['ldap']['user_domain'],
                $username,
                $password,
                0,
                1,
                (int) $this->getSetting('default_user_quota', -1)
            );
            return true;
        }

        if (!password_verify($password, $dbUser->password)) {
            make(UserQuery::class)->update(
                $dbUser->id,
                $dbUser->email,
                $username,
                $password,
                $dbUser->is_admin,
                $dbUser->active,
                $dbUser->max_disk_quota
            );
        }

        return true;
    }
}
