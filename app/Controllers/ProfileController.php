<?php


namespace App\Controllers;


use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class ProfileController extends Controller
{
    /**
     * @param Request  $request
     * @param Response $response
     *
     * @throws HttpNotFoundException
     * @throws HttpUnauthorizedException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     *
     * @return Response
     */
    public function profile(Request $request, Response $response): Response
    {
        $user = $this->getUser($request, $this->session->get('user_id'), true);

        return view()->render($response, 'user/edit.twig', [
            'profile' => true,
            'user'    => $user,
        ]);
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param int      $id
     *
     * @throws HttpNotFoundException
     * @throws HttpUnauthorizedException
     *
     * @return Response
     */
    public function profileEdit(Request $request, Response $response, int $id): Response
    {
        if (param($request, 'email') === null) {
            $this->session->alert(lang('email_required'), 'danger');

            return redirect($response, route('profile'));
        }

        $user = $this->getUser($request, $id, true);

        if ($this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `email` = ? AND `email` <> ?', [param($request, 'email'), $user->email])->fetch()->count > 0) {
            $this->session->alert(lang('email_taken'), 'danger');

            return redirect($response, route('profile'));
        }

        if (param($request, 'password') !== null && !empty(param($request, 'password'))) {
            $this->database->query('UPDATE `users` SET `email`=?, `password`=? WHERE `id` = ?', [
                param($request, 'email'),
                password_hash(param($request, 'password'), PASSWORD_DEFAULT),
                $user->id,
            ]);
        } else {
            $this->database->query('UPDATE `users` SET `email`=? WHERE `id` = ?', [
                param($request, 'email'),
                $user->id,
            ]);
        }

        $this->session->alert(lang('profile_updated'), 'success');
        $this->logger->info('User '.$this->session->get('username')." updated profile of $user->id.");

        return redirect($response, route('profile'));
    }
}