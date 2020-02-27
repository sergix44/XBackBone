<?php


namespace App\Controllers\Auth;

use App\Controllers\Controller;
use App\Web\Mail;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

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
            ->from('no-reply@'.str_ireplace('www.', '', parse_url($this->config['base_url'], PHP_URL_HOST)), $this->config['app_name'])
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
     */
    public function recoverPasswordForm(Request $request, Response $response, string $resetToken): Response
    {
        return view()->render($response, 'auth/recover_password.twig', [
            'reset_token' => $resetToken
        ]);
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  string  $resetToken
     * @return Response
     */
    public function recoverPassword(Request $request, Response $response, string $resetToken): Response
    {

    }
}
