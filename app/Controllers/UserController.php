<?php

namespace App\Controllers;


use App\Database\DB;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Traits\SingletonController;
use App\Web\Log;
use App\Web\Session;
use Flight;

class UserController extends Controller
{
	use SingletonController;

	const PER_PAGE = 15;

	public function index($page = 1): void
	{
		$this->checkAdmin();

		$page = max(0, --$page);

		$users = DB::query('SELECT * FROM `users` LIMIT ? OFFSET ?', [self::PER_PAGE, $page * self::PER_PAGE])->fetchAll();

		$pages = DB::query('SELECT COUNT(*) AS `count` FROM `users`')->fetch()->count / self::PER_PAGE;

		Flight::render('user/index.twig', [
			'users' => $users,
			'next' => $page < floor($pages),
			'previous' => $page >= 1,
			'current_page' => ++$page,
		]);
	}

	public function create(): void
	{
		$this->checkAdmin();
		Flight::render('user/create.twig');
	}

	public function store(): void
	{
		$this->checkAdmin();

		$form = Flight::request()->data;

		if (!isset($form->email) || empty($form->email)) {
			Session::alert('The email is required.', 'danger');
			Flight::redirectBack();
			return;
		}

		if (!isset($form->username) || empty($form->username)) {
			Session::alert('The username is required.', 'danger');
			Flight::redirectBack();
			return;
		}

		if (DB::query('SELECT COUNT(*) AS `count` FROM `users` WHERE `username` = ?', $form->username)->fetch()->count > 0) {
			Session::alert('The username already taken.', 'danger');
			Flight::redirectBack();
			return;
		}

		if (!isset($form->password) || empty($form->password)) {
			Session::alert('The password is required.', 'danger');
			Flight::redirectBack();
			return;
		}

		do {
			$userCode = substr(hash('sha256', microtime()), random_int(0, 26), 5);
		} while (DB::query('SELECT COUNT(*) AS `count` FROM `users` WHERE `user_code` = ?', $userCode)->fetch()->count > 0);

		$token = $this->generateNewToken();

		DB::query('INSERT INTO `users`(`email`, `username`, `password`, `is_admin`, `active`, `user_code`, `token`) VALUES (?, ?, ?, ?, ?, ?, ?)', [
			$form->email,
			$form->username,
			password_hash($form->password, PASSWORD_DEFAULT),
			isset($form->is_admin),
			isset($form->is_active),
			$userCode,
			$token
		]);

		Session::alert("User '$form->username' created!", 'success');
		Log::info('User ' . Session::get('username') . ' created a new user.', [array_diff($form->getData(), ['password'])]);

		Flight::redirect('/users');
	}

	public function edit($id): void
	{
		$this->checkAdmin();

		$user = DB::query('SELECT * FROM `users` WHERE `id` = ? LIMIT 1', $id)->fetch();

		if (!$user) {
			Flight::error(new NotFoundException());
			return;
		}

		Flight::render('user/edit.twig', [
			'user' => $user
		]);
	}

	public function update($id): void
	{
		$this->checkAdmin();

		$form = Flight::request()->data;

		$user = DB::query('SELECT * FROM `users` WHERE `id` = ? LIMIT 1', $id)->fetch();

		if (!$user) {
			Flight::error(new NotFoundException());
			return;
		}

		if (!isset($form->email) || empty($form->email)) {
			Session::alert('The email is required.', 'danger');
			Flight::redirectBack();
			return;
		}

		if (!isset($form->username) || empty($form->username)) {
			Session::alert('The username is required.', 'danger');
			Flight::redirectBack();
			return;
		}

		if (DB::query('SELECT COUNT(*) AS `count` FROM `users` WHERE `username` = ? AND `username` <> ?', [$form->username, $user->username])->fetch()->count > 0) {
			Session::alert('The username already taken.', 'danger');
			Flight::redirectBack();
			return;
		}

		if ($user->id === Session::get('user_id') && !isset($form->is_admin)) {
			Session::alert('You cannot demote yourself.', 'danger');
			Flight::redirectBack();
			return;
		}

		if (isset($form->password) && !empty($form->password)) {
			DB::query('UPDATE `users` SET `email`=?, `username`=?, `password`=?, `is_admin`=?, `active`=? WHERE `id` = ?', [
				$form->email,
				$form->username,
				password_hash($form->password, PASSWORD_DEFAULT),
				isset($form->is_admin),
				isset($form->is_active),
				$user->id
			]);
		} else {
			DB::query('UPDATE `users` SET `email`=?, `username`=?, `is_admin`=?, `active`=? WHERE `id` = ?', [
				$form->email,
				$form->username,
				isset($form->is_admin),
				isset($form->is_active),
				$user->id
			]);
		}

		Session::alert("User '$form->username' updated!", 'success');
		Log::info('User ' . Session::get('username') . " updated $user->id.", [$user, array_diff($form->getData(), ['password'])]);

		Flight::redirect('/users');

	}

	public function delete($id): void
	{
		$this->checkAdmin();

		$user = DB::query('SELECT * FROM `users` WHERE `id` = ? LIMIT 1', $id)->fetch();

		if (!$user) {
			Flight::error(new NotFoundException());
			return;
		}

		if ($user->id === Session::get('user_id')) {
			Session::alert('You cannot delete yourself.', 'danger');
			Flight::redirectBack();
			return;
		}

		DB::query('DELETE FROM `users` WHERE `id` = ?', $user->id);

		Session::alert('User deleted.', 'success');
		Log::info('User ' . Session::get('username') . " deleted $user->id.");

		Flight::redirect('/users');
	}

	public function profile(): void
	{
		$this->checkLogin();

		$user = DB::query('SELECT * FROM `users` WHERE `id` = ? LIMIT 1', Session::get('user_id'))->fetch();

		if (!$user) {
			Flight::error(new NotFoundException());
			return;
		}

		if ($user->id !== Session::get('user_id') && !Session::get('admin', false)) {
			Flight::error(new UnauthorizedException());
			return;
		}

		Flight::render('user/profile.twig', [
			'user' => $user
		]);
	}

	public function profileEdit($id): void
	{
		$this->checkLogin();

		$form = Flight::request()->data;

		$user = DB::query('SELECT * FROM `users` WHERE `id` = ? LIMIT 1', $id)->fetch();

		if (!$user) {
			Flight::error(new NotFoundException());
			return;
		}

		if ($user->id !== Session::get('user_id') && !Session::get('admin', false)) {
			Flight::error(new UnauthorizedException());
			return;
		}

		if (!isset($form->email) || empty($form->email)) {
			Session::alert('The email is required.', 'danger');
			Flight::redirectBack();
			return;
		}

		if (isset($form->password) && !empty($form->password)) {
			DB::query('UPDATE `users` SET `email`=?, `password`=? WHERE `id` = ?', [
				$form->email,
				password_hash($form->password, PASSWORD_DEFAULT),
				$user->id
			]);
		} else {
			DB::query('UPDATE `users` SET `email`=? WHERE `id` = ?', [
				$form->email,
				$user->id
			]);
		}

		Session::alert('Profile updated successfully!', 'success');
		Log::info('User ' . Session::get('username') . " updated profile of $user->id.");

		Flight::redirectBack();
	}

	public function refreshToken($id): void
	{
		$this->checkLogin();

		$user = DB::query('SELECT * FROM `users` WHERE `id` = ? LIMIT 1', $id)->fetch();

		if (!$user) {
			Flight::halt(404);
			return;
		}

		if ($user->id !== Session::get('user_id') && !Session::get('admin', false)) {
			Flight::halt(403);
			return;
		}

		$token = $this->generateNewToken();

		DB::query('UPDATE `users` SET `token`=? WHERE `id` = ?', [
			$token,
			$user->id
		]);

		Log::info('User ' . Session::get('username') . " refreshed token of user $user->id.");

		echo $token;
	}

	public function getShareXconfigFile($id): void
	{
		$this->checkLogin();

		$user = DB::query('SELECT * FROM `users` WHERE `id` = ? LIMIT 1', $id)->fetch();

		if (!$user) {
			Flight::halt(404);
			return;
		}

		if ($user->id !== Session::get('user_id') && !Session::get('admin', false)) {
			Flight::halt(403);
			return;
		}

		$base_url = Flight::get('config')['base_url'];
		$json = [
			'DestinationType' => 'ImageUploader, TextUploader, FileUploader',
			'RequestURL' => "$base_url/upload",
			'FileFormName' => 'upload',
			'Arguments' => [
				'file' => '$filename$',
				'text' => '$input$',
				'token' => $user->token,
			],
			'URL' => '$json:url$',
			'ThumbnailURL' => '$json:url$/raw',
		];

		Flight::response()->header('Content-Type', 'application/json');
		Flight::response()->header('Content-Disposition', 'attachment;filename="' . $user->username . '-ShareX.sxcu"');
		Flight::response()->sendHeaders();

		echo json_encode($json, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
	}

	protected function generateNewToken(): string
	{
		do {
			$token = 'token_' . hash('sha256', microtime().random_bytes(256).uniqid('', true));
		} while (DB::query('SELECT COUNT(*) AS `count` FROM `users` WHERE `token` = ?', $token)->fetch()->count > 0);

		return $token;
	}
}