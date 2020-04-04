<?php

namespace App\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;

class LoginController extends Controller
{

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function show(Request $request, Response $response): Response
    {
        if ($this->session->get('logged', false)) {
            return redirect($response, 'home');
        }
        return $this->view->render($response, 'auth/login.twig');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function login(Request $request, Response $response): Response
    {
        $result = $this->database->query('SELECT `id`, `email`, `username`, `password`,`is_admin`, `active` FROM `users` WHERE `username` = ? OR `email` = ? LIMIT 1', [$request->getParam('username'), $request->getParam('username')])->fetch();

        if (!$result || !password_verify($request->getParam('password'), $result->password)) {
            $this->session->alert(lang('bad_login'), 'danger');
            return redirect($response, 'login');
        }

        if (isset($this->settings['maintenance']) && $this->settings['maintenance'] && !$result->is_admin) {
            $this->session->alert(lang('maintenance_in_progress'), 'info');
            return redirect($response, 'login');
        }

        if (!$result->active) {
            $this->session->alert(lang('account_disabled'), 'danger');
            return redirect($response, 'login');
        }

        $this->session->set('logged', true);
        $this->session->set('user_id', $result->id);
        $this->session->set('username', $result->username);
        $this->session->set('admin', $result->is_admin);
        $this->session->set('used_space', humanFileSize($this->getUsedSpaceByUser($result->id)));

        $this->session->alert(lang('welcome', [$result->username]), 'info');
        $this->logger->info("User $result->username logged in.");

        if ($this->session->has('redirectTo')) {
            return $response->withRedirect($this->session->get('redirectTo'));
        }

        return redirect($response, 'home');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function logout(Request $request, Response $response): Response
    {
        $this->session->clear();
        $this->session->set('logged', false);
        $this->session->alert(lang('goodbye'), 'warning');
        return redirect($response, 'login.show');
    }
}
