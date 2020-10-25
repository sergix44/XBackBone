<?php


namespace App\Controllers\Auth;

use App\Web\Mail;
use App\Web\ValidationHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;

class PasswordRecoveryController extends AuthController
{

    /**
     * @param  Response  $response
     * @return Response
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function recover(Response $response): Response
    {
        return view()->render($response, 'auth/recover_mail.twig', [
            'recaptcha_site_key' => $this->getSetting('recaptcha_enabled') === 'on' ? $this->getSetting('recaptcha_site_key') : null,
        ]);
    }


    /**
     * @param  Request  $request
     * @param  Response  $response
     * @return Response
     * @throws \Exception
     */
    public function recoverMail(Request $request, Response $response): Response
    {
        if ($this->session->get('logged', false)) {
            return redirect($response, route('home'));
        }

        if ($this->checkRecaptcha(make(ValidationHelper::class), $request)->fails()) {
            return redirect($response, route('recover'));
        }

        $user = $this->database->query('SELECT `id`, `username` FROM `users` WHERE `email` = ? AND NOT `ldap` LIMIT 1', param($request, 'email'))->fetch();

        if (!isset($user->id)) {
            $this->session->alert(lang('recover_email_sent'), 'success');
            return redirect($response, route('recover'));
        }

        $resetToken = bin2hex(random_bytes(16));

        $this->database->query('UPDATE `users` SET `reset_token`=? WHERE `id` = ?', [
            $resetToken,
            $user->id,
        ]);

        Mail::make()
            ->from(platform_mail(), $this->config['app_name'])
            ->to(param($request, 'email'))
            ->subject(lang('mail.recover_password', [$this->config['app_name']]))
            ->message(lang('mail.recover_text', [
                $user->username,
                route('recover.password', ['resetToken' => $resetToken]),
                route('recover.password', ['resetToken' => $resetToken]),
            ]))
            ->send();

        $this->session->alert(lang('recover_email_sent'), 'success');
        return redirect($response, route('recover'));
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  string  $resetToken
     * @return Response
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws HttpNotFoundException
     */
    public function recoverPasswordForm(Request $request, Response $response, string $resetToken): Response
    {
        $user = $this->database->query('SELECT `id` FROM `users` WHERE `reset_token` = ? LIMIT 1', $resetToken)->fetch();

        if (!$user) {
            throw new HttpNotFoundException($request);
        }

        return view()->render($response, 'auth/recover_password.twig', [
            'reset_token' => $resetToken
        ]);
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  string  $resetToken
     * @return Response
     * @throws HttpNotFoundException
     */
    public function recoverPassword(Request $request, Response $response, string $resetToken): Response
    {
        $user = $this->database->query('SELECT `id` FROM `users` WHERE `reset_token` = ? LIMIT 1', $resetToken)->fetch();

        if (!$user) {
            throw new HttpNotFoundException($request);
        }

        /** @var ValidationHelper $validator */
        $validator = make(ValidationHelper::class)
            ->alertIf(empty(param($request, 'password')), 'password_required')
            ->alertIf(param($request, 'password') !== param($request, 'password_repeat'), 'password_match');

        if ($validator->fails()) {
            return redirect($response, route('recover.password', ['resetToken' => $resetToken]));
        }

        $this->database->query('UPDATE `users` SET `password`=?, `reset_token`=? WHERE `id` = ?', [
            password_hash(param($request, 'password'), PASSWORD_DEFAULT),
            null,
            $user->id,
        ]);

        $this->session->alert(lang('password_restored'), 'success');
        return redirect($response, route('login.show'));
    }
}
