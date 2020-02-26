<?php


namespace App\Controllers\Auth;


use App\Controllers\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;

class RegisterController extends Controller
{

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @return Response
     * @throws HttpNotFoundException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function registerForm(Request $request, Response $response): Response
    {
        if ($this->session->get('logged', false)) {
            return redirect($response, route('home'));
        }

        $registerEnabled = $this->database->query('SELECT `value` FROM `settings` WHERE `key` = \'register_enabled\'')->fetch()->value ?? 'off';
        if ($registerEnabled === 'off') {
            throw new HttpNotFoundException($request);
        }

        return view()->render($response, 'auth/register.twig');
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @return Response
     * @throws HttpNotFoundException
     * @throws \Exception
     */
    public function register(Request $request, Response $response): Response
    {
        if ($this->session->get('logged', false)) {
            return redirect($response, route('home'));
        }

        $registerEnabled = $this->database->query('SELECT `value` FROM `settings` WHERE `key` = \'register_enabled\'')->fetch()->value ?? 'off';
        if ($registerEnabled === 'off') {
            throw new HttpNotFoundException($request);
        }

        if (param($request, 'email') === null && !filter_var(param($request, 'email'), FILTER_VALIDATE_EMAIL)) {
            $this->session->alert(lang('email_required'), 'danger');

            return redirect($response, route('register.show'));
        }

        if ($this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `email` = ?', param($request, 'email'))->fetch()->count > 0) {
            $this->session->alert(lang('email_taken'), 'danger');

            return redirect($response, route('register.show'));
        }

        if (param($request, 'username') === null) {
            $this->session->alert(lang('username_required'), 'danger');

            return redirect($response, route('register.show'));
        }

        if (param($request, 'password') === null) {
            $this->session->alert(lang('password_required'), 'danger');

            return redirect($response, route('register.show'));
        }

        if ($this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `username` = ?', param($request, 'username'))->fetch()->count > 0) {
            $this->session->alert(lang('username_taken'), 'danger');

            return redirect($response, route('register.show'));
        }

        do {
            $userCode = humanRandomString(5);
        } while ($this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `user_code` = ?', $userCode)->fetch()->count > 0);

        $token = $this->generateUserUploadToken();
        $activateToken = bin2hex(random_bytes(16));

        $this->database->query('INSERT INTO `users`(`email`, `username`, `password`, `is_admin`, `active`, `user_code`, `token`, `activate_token`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [
            param($request, 'email'),
            param($request, 'username'),
            password_hash(param($request, 'password'), PASSWORD_DEFAULT),
            0,
            0,
            $userCode,
            $token,
            $activateToken,
        ]);

        $this->session->alert(lang('register_success', [param($request, 'username')]), 'success');
        $this->logger->info('New user registered.', [array_diff_key($request->getParsedBody(), array_flip(['password']))]);

        return redirect($response, route('login.show'));
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  string  $activateToken
     * @return Response
     */
    public function activateUser(Request $request, Response $response, string $activateToken): Response
    {

    }
}