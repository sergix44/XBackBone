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
			return $response->withRedirect('/home');
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

		$result = DB::query('SELECT `id`,`username`, `password`,`is_admin`, `active` FROM `users` WHERE `username` = ? LIMIT 1', $request->getParam('username'))->fetch();

		if (!$result || !password_verify($request->getParam('password'), $result->password)) {
			Session::alert('Wrong credentials', 'danger');
			return $response->withRedirect('/login');
		}

		if (!$result->active) {
			Session::alert('Your account is disabled.', 'danger');
			return $response->withRedirect('/login');
		}

		Session::set('logged', true);
		Session::set('user_id', $result->id);
		Session::set('username', $result->username);
		Session::set('admin', $result->is_admin);

		Session::alert("Welcome, $result->username!", 'info');
		$this->logger->info("User $result->username logged in.");

		if (Session::has('redirectTo')) {
			return $response->withRedirect(Session::get('redirectTo'));
		}

		return $response->withRedirect('/home');
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
		return $response->withRedirect('/login');
	}

}