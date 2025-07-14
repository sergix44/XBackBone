<?php

namespace App\Controllers\Auth;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TwoFactorController extends AuthController
{
    public function show(Response $response): Response
    {
        if (!$this->session->has('twofa_user')) {
            return redirect($response, route('login.show'));
        }

        return view()->render($response, 'auth/2fa.twig');
    }

    public function verify(Request $request, Response $response): Response
    {
        $user = $this->session->get('twofa_user');
        if (!$user) {
            return redirect($response, route('login.show'));
        }

        $code = param($request, 'code');
        if (!verify_totp_code($user->twofa_secret, $code)) {
            $this->session->alert(lang('2fa_invalid'), 'danger');
            return redirect($response, route('2fa.show'));
        }

        $this->session->set('logged', true)
            ->set('user_id', $user->id)
            ->set('username', $user->username)
            ->set('admin', $user->is_admin)
            ->set('copy_raw', $user->copy_raw);

        $this->setSessionQuotaInfo($user->current_disk_quota, $user->max_disk_quota);

        $this->session->alert(lang('welcome', [$user->username]), 'info');
        $this->logger->info("User $user->username logged in.");

        if ($this->session->get('twofa_remember')) {
            $this->refreshRememberCookie($user->id);
        }

        $this->session->delete('twofa_user')->delete('twofa_remember');

        if ($this->session->has('redirectTo')) {
            return redirect($response, $this->session->get('redirectTo'));
        }

        return redirect($response, route('home'));
    }
}
