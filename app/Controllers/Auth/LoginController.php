<?php

namespace App\Controllers\Auth;

use App\Database\Repositories\UserRepository;
use App\Web\ValidationHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LoginController extends AuthController
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
        /** @var ValidationHelper $validator */
        $validator = make(ValidationHelper::class);

        if ($this->checkRecaptcha($validator, $request)->fails()) {
            return redirect($response, route('login'));
        }

        $username = param($request, 'username');
        $password = param($request, 'password');
        $user = $this->database->query('SELECT `id`, `email`, `username`, `password`,`is_admin`, `active`, `current_disk_quota`, `max_disk_quota`, `ldap`, `copy_raw` FROM `users` WHERE `username` = ? OR `email` = ? LIMIT 1', [$username, $username])->fetch();

        if ($this->config['ldap']['enabled'] && ($user->ldap ?? true)) {
            $user = $this->ldapLogin($request, $username, param($request, 'password'), $user);
        }

        $validator
            ->alertIf(!$user || !password_verify($password, $user->password), 'bad_login')
            ->alertIf(isset($this->config['maintenance']) && $this->config['maintenance'] && !($user->is_admin ?? true), 'maintenance_in_progress', 'info')
            ->alertIf(!($user->active ?? false), 'account_disabled');

        if ($validator->fails()) {
            if (!empty($request->getHeaderLine('X-Forwarded-For'))) {
                $ip = $request->getHeaderLine('X-Forwarded-For');
            } else {
                $ip = $request->getServerParams()['REMOTE_ADDR'] ?? null;
            }
            $this->logger->info("Login failed with username='{$username}', ip={$ip}.");
            return redirect($response, route('login'));
        }

        $this->session->set('logged', true)
            ->set('user_id', $user->id)
            ->set('username', $user->username)
            ->set('admin', $user->is_admin)
            ->set('copy_raw', $user->copy_raw);

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
            setcookie('remember', null, 0, '', '', false, true);
        }

        return redirect($response, route('login.show'));
    }

    /**
     * @param  Request  $request
     * @param  string  $username
     * @param  string  $password
     * @param $dbUser
     * @return bool|null
     * @throws \Slim\Exception\HttpNotFoundException
     * @throws \Slim\Exception\HttpUnauthorizedException
     */
    protected function ldapLogin(Request $request, string $username, string $password, $dbUser)
    {
        // Build LDAP connection
        $server = $this->ldapConnect();
        if (!$server) {
            $this->session->alert(lang('ldap_cant_connect'), 'warning');
            return $dbUser;
        }

        //Get LDAP user's (R)DN
        $userDN=$this->getLdapRdn($username, $server);
        if (!is_string($userDN)) {
            return null;
        }

        //Bind as user to validate password
        if (@ldap_bind($server, $userDN, $password)) {
            $this->logger->debug("$userDN authenticated against LDAP sucessfully");
        } else {
            $this->logger->debug("$userDN authenticated against LDAP unsucessfully");
            if ($dbUser && !$dbUser->ldap) {
                return $dbUser;
            }
            return null;
        }

        if (!$dbUser) {
            $email = $username;
            if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
                if (@is_string($this->config['ldap']['search_filter'])) {
                    $search = ldap_read(
                        $server,
                        $userDN,
                        'objectClass=*',
                        array('mail',$this->config['ldap']['rdn_attribute'])
                    );
                } else {
                    $search = ldap_search($server, $this->config['ldap']['base_domain'], ($this->config['ldap']['rdn_attribute'] ?? 'uid=').addslashes($username), ['mail']);
                }
                $entry = ldap_first_entry($server, $search);
                $email = @ldap_get_values($server, $entry, 'mail')[0] ?? platform_mail($username.rand(0, 100)); // if the mail is not set, generate a placeholder
            }
            /** @var UserRepository $userQuery */
            $userQuery = make(UserRepository::class);
            $userQuery->create($email, $username, $password, 0, 1, (int) $this->getSetting('default_user_quota', -1), null, 1);
            return $userQuery->get($request, $this->database->getPdo()->lastInsertId());
        }

        if ($server) {
            ldap_close($server);
        }

        if (!password_verify($password, $dbUser->password)) {
            $userQuery = make(UserRepository::class);
            $userQuery->update($dbUser->id, $dbUser->email, $username, $password, $dbUser->is_admin, $dbUser->active, $dbUser->max_disk_quota, $dbUser->ldap);
            return $userQuery->get($request, $dbUser->id);
        }

        return $dbUser;
    }
}
