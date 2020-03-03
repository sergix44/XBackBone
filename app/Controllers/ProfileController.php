<?php


namespace App\Controllers;

use App\Database\Queries\UserQuery;
use App\Web\ValidationChecker;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProfileController extends Controller
{
    /**
     * @param  Request  $request
     * @param  Response  $response
     *
     * @return Response
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function profile(Request $request, Response $response): Response
    {
        $user = make(UserQuery::class)->get($request, $this->session->get('user_id'), true);

        return view()->render($response, 'user/edit.twig', [
            'profile' => true,
            'user'    => $user,
        ]);
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  int  $id
     *
     * @return Response
     */
    public function profileEdit(Request $request, Response $response, int $id): Response
    {
        $user = make(UserQuery::class)->get($request, $id, true);

        $validator = ValidationChecker::make()
            ->rules([
                'email.required' => filter_var(param($request, 'email'), FILTER_VALIDATE_EMAIL),
                'email.unique' => $this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `email` = ? AND `email` <> ?', [param($request, 'email'), $user->email])->fetch()->count == 0,
            ])
            ->onFail(function ($rule) {
                $alerts = [
                    'email.required' => lang('email_required'),
                    'email.unique' => lang('email_taken'),
                ];

                $this->session->alert($alerts[$rule], 'danger');
            });

        if ($validator->fails()) {
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
