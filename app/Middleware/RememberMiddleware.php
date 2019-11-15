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
     * @return Response
     */
    public function __invoke(Request $request, RequestHandler $handler)
    {
        if (!$this->session->get('logged', false) && !empty($request->getCookieParams()['remember'])) {
            list($selector, $token) = explode(':', $request->getCookieParams()['remember']);

            $result = $this->database->query('SELECT `id`, `email`, `username`,`is_admin`, `active`, `remember_token` FROM `users` WHERE `remember_selector` = ? AND `remember_expire` > ? LIMIT 1',
                [$selector, date('Y-m-d\TH:i:s', time())]
            )->fetch();

            if ($result && password_verify($token, $result->remember_token) && $result->active) {
                $this->session->set('logged', true);
                $this->session->set('user_id', $result->id);
                $this->session->set('username', $result->username);
                $this->session->set('admin', $result->is_admin);
                $this->session->set('used_space', humanFileSize($this->getUsedSpaceByUser($result->id)));
            }
        }


        return $handler->handle($request);
    }
}