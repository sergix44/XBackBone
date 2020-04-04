<?php

namespace App\Middleware;

use Slim\Http\Request;
use Slim\Http\Response;

class AuthMiddleware extends Middleware
{

    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     */
    public function __invoke(Request $request, Response $response, callable $next)
    {
        if (!$this->session->get('logged', false)) {
            $this->session->set('redirectTo', (isset($_SERVER['HTTPS']) ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
            return redirect($response, 'login.show');
        }

        if (!$this->database->query('SELECT `id`, `active` FROM `users` WHERE `id` = ? LIMIT 1', [$this->session->get('user_id')])->fetch()->active) {
            $this->session->alert('Your account is not active anymore.', 'danger');
            $this->session->set('logged', false);
            $this->session->set('redirectTo', (isset($_SERVER['HTTPS']) ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
            return redirect($response, 'login.show');
        }

        return $next($request, $response);
    }
}
