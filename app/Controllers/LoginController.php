<?php

namespace App\Controllers;


use App\Database\DB;
use App\Traits\SingletonController;
use App\Web\Log;
use App\Web\Session;
use Flight;

class LoginController extends Controller
{
	use SingletonController;

	public function show(): void
	{
		if (Session::get('logged', false)) {
			Flight::redirect('/home');
			return;
		}
		Flight::render('auth/login.twig');
	}

	public function login(): void
	{
		$form = Flight::request()->data;

		$result = DB::query('SELECT `id`,`username`, `password`,`is_admin`, `active` FROM `users` WHERE `username` = ? LIMIT 1', $form->username)->fetch();

		if (!$result || !password_verify($form->password, $result->password)) {
			Flight::redirect('login');
			Session::alert('Wrong credentials', 'danger');
			return;
		}

		if (!$result->active) {
			Flight::redirect('login');
			Session::alert('Your account is disabled.', 'danger');
			return;
		}

		Session::set('logged', true);
		Session::set('user_id', $result->id);
		Session::set('username', $result->username);
		Session::set('admin', $result->is_admin);

		Session::alert("Welcome, $result->username!", 'info');
		Log::info("User $result->username logged in.");

		if (Session::has('redirectTo')) {
			Flight::redirect(Session::get('redirectTo'));
			return;
		}

		Flight::redirect('/home');
	}

	public function logout(): void
	{
		$this->checkLogin();
		Session::clear();
		Session::set('logged', false);
		Session::alert('Goodbye!', 'warning');
		Flight::redirect('/login');
	}

}