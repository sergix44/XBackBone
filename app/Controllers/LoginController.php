<?php

namespace App\Controllers;


use App\Database\DB;
use App\Web\Session;
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
		if (Session::get('logged', false)) {
			return redirect($response, '/home');
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

		$result = DB::query('SELECT `id`, `email`, `username`, `password`,`is_admin`, `active` FROM `users` WHERE `username` = ? OR `email` = ? LIMIT 1', [$request->getParam('username'), $request->getParam('username')])->fetch();

		if (!$result || !password_verify($request->getParam('password'), $result->password)) {
			Session::alert('Wrong credentials', 'danger');
			return redirect($response, '/login');
		}

		if (!$result->active) {
			Session::alert('Your account is disabled.', 'danger');
			return redirect($response, '/login');
		}

		Session::set('logged', true);
		Session::set('user_id', $result->id);
		Session::set('username', $result->username);
		Session::set('admin', $result->is_admin);
		Session::set('used_space', humanFileSize($this->getUsedSpaceByUser($result->id)));

		Session::alert("Welcome, $result->username!", 'info');
		$this->logger->info("User $result->username logged in.");

		if (Session::has('redirectTo')) {
			return $response->withRedirect(Session::get('redirectTo'));
		}

		return redirect($response,'/home');
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 */
	public function logout(Request $request, Response $response): Response
	{
		Session::clear();
		Session::set('logged', false);
		Session::alert('Goodbye!', 'warning');
		return redirect($response,'/login');
	}

}