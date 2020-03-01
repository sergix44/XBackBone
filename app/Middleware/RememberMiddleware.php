<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class RememberMiddleware extends Middleware
{
    /**
     * @param  Request  $request
     * @param  RequestHandler  $handler
     *
     * @return Response
     * @throws \Exception
     */
    public function __invoke(Request $request, RequestHandler $handler)
    {
        if (!$this->session->get('logged', false) && !empty($request->getCookieParams()['remember'])) {
            [$selector, $token] = explode(':', $request->getCookieParams()['remember']);

            $user = $this->database->query('SELECT `id`, `email`, `username`,`is_admin`, `active`, `remember_token`, `current_disk_quota`, `max_disk_quota` FROM `users` WHERE `remember_selector` = ? AND `remember_expire` > ? LIMIT 1',
                [$selector, date('Y-m-d\TH:i:s', time())]
            )->fetch();

            if ($user && password_verify($token, $user->remember_token) && $user->active) {
                $this->session->set('logged', true);
                $this->session->set('user_id', $user->id);
                $this->session->set('username', $user->username);
                $this->session->set('admin', $user->is_admin);
                $this->setSessionQuotaInfo($user->current_disk_quota, $user->max_disk_quota);
            }

            $this->refreshRememberCookie($user->id);
        }

        return $handler->handle($request);
    }
}
