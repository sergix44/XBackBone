<?php


namespace App\Controllers\Auth;

use App\Controllers\Controller;
use App\Web\Mail;
use App\Web\ValidationChecker;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;

class PasswordRecoveryController extends Controller
{

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @return Response
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function recover(Request $request, Response $response): Response
    {
        return view()->render($response, 'auth/recover_mail.twig');
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

        $user = $this->database->query('SELECT `id`, `username` FROM `users` WHERE `email` = ? LIMIT 1', param($request, 'email'))->fetch();

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

        $validator = ValidationChecker::make()
            ->rules([
                'password.required' => !empty(param($request, 'password')),
                'password.match' => param($request, 'password') === param($request, 'password_repeat'),
            ])
            ->onFail(function ($rule) {
                $alerts = [
                    'password.required' => lang('password_required'),
                    'password.match' => lang('password_match'),
                ];

                $this->session->alert($alerts[$rule], 'danger');
            });

        if ($validator->fails()){
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
