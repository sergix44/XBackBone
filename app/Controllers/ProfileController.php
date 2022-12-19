<?php


namespace App\Controllers;

use App\Database\Repositories\UserRepository;
use App\Web\ValidationHelper;
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
        $user = make(UserRepository::class)->get($request, $this->session->get('user_id'), true);

        return view()->render($response, 'user/edit.twig', [
            'profile' => true,
            'user' => $user,
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
        $user = make(UserRepository::class)->get($request, $id, true);

        /** @var ValidationHelper $validator */
        $validator = make(ValidationHelper::class)
            ->alertIf(!filter_var(param($request, 'email'), FILTER_VALIDATE_EMAIL), 'email_required')
            ->alertIf($this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `email` = ? AND `email` <> ?', [param($request, 'email'), $user->email])->fetch()->count != 0, 'email_taken');

        if ($validator->fails()) {
            return redirect($response, route('profile'));
        }

        if (param($request, 'password') !== null && !empty(param($request, 'password'))) {
            $this->database->query('UPDATE `users` SET `email`=?, `password`=?, `hide_uploads`=?, `copy_raw`=? WHERE `id` = ?', [
                param($request, 'email'),
                password_hash(param($request, 'password'), PASSWORD_DEFAULT),
                param($request, 'hide_uploads') !== null ? 1 : 0,
                param($request, 'copy_raw') !== null ? 1 : 0,
                $user->id,
            ]);
        } else {
            $this->database->query('UPDATE `users` SET `email`=?, `hide_uploads`=?, `copy_raw`=? WHERE `id` = ?', [
                param($request, 'email'),
                param($request, 'hide_uploads') !== null ? 1 : 0,
                param($request, 'copy_raw') !== null ? 1 : 0,
                $user->id,
            ]);
        }

        $this->session->set('copy_raw', param($request, 'copy_raw') !== null ? 1 : 0)->alert(lang('profile_updated'), 'success');
        $this->logger->info('User '.$this->session->get('username')." updated profile of $user->id.");

        return redirect($response, route('profile'));
    }
}
