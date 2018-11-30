<?php

namespace App\Controllers;


use App\Exceptions\UnauthorizedException;
use App\Web\Session;
use Slim\Exception\NotFoundException;
use Slim\Http\Request;
use Slim\Http\Response;

class UserController extends Controller
{
	const PER_PAGE = 15;

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return Response
	 */
	public function index(Request $request, Response $response, $args): Response
	{
		$page = isset($args['page']) ? (int)$args['page'] : 0;
		$page = max(0, --$page);

		$users = $this->database->query('SELECT * FROM `users` LIMIT ? OFFSET ?', [self::PER_PAGE, $page * self::PER_PAGE])->fetchAll();

		$pages = $this->database->query('SELECT COUNT(*) AS `count` FROM `users`')->fetch()->count / self::PER_PAGE;

		return $this->view->render($response,
			'user/index.twig',
			[
				'users' => $users,
				'next' => $page < floor($pages),
				'previous' => $page >= 1,
				'current_page' => ++$page,
			]
		);
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 */
	public function create(Request $request, Response $response): Response
	{
		return $this->view->render($response, 'user/create.twig');
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 */
	public function store(Request $request, Response $response): Response
	{
		if ($request->getParam('email') === null) {
			Session::alert(lang('email_required'), 'danger');
			return redirect($response, 'user.create');
		}

		if ($this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `email` = ?', $request->getParam('email'))->fetch()->count > 0) {
			Session::alert(lang('email_taken'), 'danger');
			return redirect($response, 'user.create');
		}

		if ($request->getParam('username') === null) {
			Session::alert(lang('username_required'), 'danger');
			return redirect($response, 'user.create');
		}

		if ($request->getParam('password') === null) {
			Session::alert(lang('password_required'), 'danger');
			return redirect($response, 'user.create');
		}

		if ($this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `username` = ?', $request->getParam('username'))->fetch()->count > 0) {
			Session::alert(lang('username_taken'), 'danger');
			return redirect($response, 'user.create');
		}

		do {
			$userCode = substr(md5(microtime()), rand(0, 26), 5);
		} while ($this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `user_code` = ?', $userCode)->fetch()->count > 0);

		$token = $this->generateNewToken();

		$this->database->query('INSERT INTO `users`(`email`, `username`, `password`, `is_admin`, `active`, `user_code`, `token`) VALUES (?, ?, ?, ?, ?, ?, ?)', [
			$request->getParam('email'),
			$request->getParam('username'),
			password_hash($request->getParam('password'), PASSWORD_DEFAULT),
			$request->getParam('is_admin') !== null ? 1 : 0,
			$request->getParam('is_active') !== null ? 1 : 0,
			$userCode,
			$token,
		]);

		Session::alert(lang('user_created', [$request->getParam('username')]), 'success');
		$this->logger->info('User ' . Session::get('username') . ' created a new user.', [array_diff($request->getParams(), ['password'])]);

		return redirect($response, 'user.index');
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return Response
	 * @throws NotFoundException
	 */
	public function edit(Request $request, Response $response, $args): Response
	{
		$user = $this->database->query('SELECT * FROM `users` WHERE `id` = ? LIMIT 1', $args['id'])->fetch();

		if (!$user) {
			throw new NotFoundException($request, $response);
		}

		return $this->view->render($response, 'user/edit.twig', [
			'profile' => false,
			'user' => $user,
		]);
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return Response
	 * @throws NotFoundException
	 */
	public function update(Request $request, Response $response, $args): Response
	{
		$user = $this->database->query('SELECT * FROM `users` WHERE `id` = ? LIMIT 1', $args['id'])->fetch();

		if (!$user) {
			throw new NotFoundException($request, $response);
		}

		if ($request->getParam('email') === null) {
			Session::alert(lang('email_required'), 'danger');
			return redirect($response, 'user.edit', ['id' => $args['id']]);
		}

		if ($this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `email` = ? AND `email` <> ?', [$request->getParam('email'), $user->email])->fetch()->count > 0) {
			Session::alert(lang('email_taken'), 'danger');
			return redirect($response, 'user.edit', ['id' => $args['id']]);
		}

		if ($request->getParam('username') === null) {
			Session::alert(lang('username_required'), 'danger');
			return redirect($response, 'user.edit', ['id' => $args['id']]);
		}

		if ($this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `username` = ? AND `username` <> ?', [$request->getParam('username'), $user->username])->fetch()->count > 0) {
			Session::alert(lang('username_taken'), 'danger');
			return redirect($response, 'user.edit', ['id' => $args['id']]);
		}

		if ($user->id === Session::get('user_id') && $request->getParam('is_admin') === null) {
			Session::alert(lang('cannot_demote'), 'danger');
			return redirect($response, 'user.edit', ['id' => $args['id']]);
		}

		if ($request->getParam('password') !== null && !empty($request->getParam('password'))) {
			$this->database->query('UPDATE `users` SET `email`=?, `username`=?, `password`=?, `is_admin`=?, `active`=? WHERE `id` = ?', [
				$request->getParam('email'),
				$request->getParam('username'),
				password_hash($request->getParam('password'), PASSWORD_DEFAULT),
				$request->getParam('is_admin') !== null ? 1 : 0,
				$request->getParam('is_active') !== null ? 1 : 0,
				$user->id,
			]);
		} else {
			$this->database->query('UPDATE `users` SET `email`=?, `username`=?, `is_admin`=?, `active`=? WHERE `id` = ?', [
				$request->getParam('email'),
				$request->getParam('username'),
				$request->getParam('is_admin') !== null ? 1 : 0,
				$request->getParam('is_active') !== null ? 1 : 0,
				$user->id,
			]);
		}

		Session::alert(lang('user_updated', [$request->getParam('username')]), 'success');
		$this->logger->info('User ' . Session::get('username') . " updated $user->id.", [$user, array_diff($request->getParams(), ['password'])]);

		return redirect($response, 'user.index');

	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return Response
	 * @throws NotFoundException
	 */
	public function delete(Request $request, Response $response, $args): Response
	{
		$user = $this->database->query('SELECT * FROM `users` WHERE `id` = ? LIMIT 1', $args['id'])->fetch();

		if (!$user) {
			throw new NotFoundException($request, $response);
		}

		if ($user->id === Session::get('user_id')) {
			Session::alert(lang('cannot_delete'), 'danger');
			return redirect($response, 'user.index');
		}

		$this->database->query('DELETE FROM `users` WHERE `id` = ?', $user->id);

		Session::alert(lang('user_deleted'), 'success');
		$this->logger->info('User ' . Session::get('username') . " deleted $user->id.");

		return redirect($response, 'user.index');
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws NotFoundException
	 * @throws UnauthorizedException
	 */
	public function profile(Request $request, Response $response): Response
	{
		$user = $this->database->query('SELECT * FROM `users` WHERE `id` = ? LIMIT 1', Session::get('user_id'))->fetch();

		if (!$user) {
			throw new NotFoundException($request, $response);
		}

		if ($user->id !== Session::get('user_id') && !Session::get('admin', false)) {
			throw new UnauthorizedException();
		}

		return $this->view->render($response, 'user/edit.twig', [
			'profile' => true,
			'user' => $user,
		]);
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return Response
	 * @throws NotFoundException
	 * @throws UnauthorizedException
	 */
	public function profileEdit(Request $request, Response $response, $args): Response
	{
		$user = $this->database->query('SELECT * FROM `users` WHERE `id` = ? LIMIT 1', $args['id'])->fetch();

		if (!$user) {
			throw new NotFoundException($request, $response);
		}

		if ($user->id !== Session::get('user_id') && !Session::get('admin', false)) {
			throw new UnauthorizedException();
		}

		if ($request->getParam('email') === null) {
			Session::alert(lang('email_required'), 'danger');
			return redirect($response, 'profile');
		}

		if ($this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `email` = ? AND `email` <> ?', [$request->getParam('email'), $user->email])->fetch()->count > 0) {
			Session::alert(lang('email_taken'), 'danger');
			return redirect($response, 'profile');
		}

		if ($request->getParam('password') !== null && !empty($request->getParam('password'))) {
			$this->database->query('UPDATE `users` SET `email`=?, `password`=? WHERE `id` = ?', [
				$request->getParam('email'),
				password_hash($request->getParam('password'), PASSWORD_DEFAULT),
				$user->id,
			]);
		} else {
			$this->database->query('UPDATE `users` SET `email`=? WHERE `id` = ?', [
				$request->getParam('email'),
				$user->id,
			]);
		}

		Session::alert(lang('profile_updated'), 'success');
		$this->logger->info('User ' . Session::get('username') . " updated profile of $user->id.");

		return redirect($response, 'profile');
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return Response
	 * @throws NotFoundException
	 * @throws UnauthorizedException
	 */
	public function refreshToken(Request $request, Response $response, $args): Response
	{
		$user = $this->database->query('SELECT * FROM `users` WHERE `id` = ? LIMIT 1', $args['id'])->fetch();

		if (!$user) {
			throw new NotFoundException($request, $response);
		}

		if ($user->id !== Session::get('user_id') && !Session::get('admin', false)) {
			throw new UnauthorizedException();
		}

		$token = $this->generateNewToken();

		$this->database->query('UPDATE `users` SET `token`=? WHERE `id` = ?', [
			$token,
			$user->id,
		]);

		$this->logger->info('User ' . Session::get('username') . " refreshed token of user $user->id.");

		$response->getBody()->write($token);

		return $response;
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return Response
	 * @throws NotFoundException
	 * @throws UnauthorizedException
	 */
	public function getShareXconfigFile(Request $request, Response $response, $args): Response
	{
		$user = $this->database->query('SELECT * FROM `users` WHERE `id` = ? LIMIT 1', $args['id'])->fetch();

		if (!$user) {
			throw new NotFoundException($request, $response);
		}

		if ($user->id !== Session::get('user_id') && !Session::get('admin', false)) {
			throw new UnauthorizedException();
		}

		if ($user->token === null || $user->token === '') {
			Session::alert('You don\'t have a personal upload token. (Click the update token button and try again)', 'danger');
			return $response->withRedirect($request->getHeaderLine('HTTP_REFERER'));
		}

		$json = [
			'DestinationType' => 'ImageUploader, TextUploader, FileUploader',
			'RequestURL' => route('upload'),
			'FileFormName' => 'upload',
			'Arguments' => [
				'file' => '$filename$',
				'text' => '$input$',
				'token' => $user->token,
			],
			'URL' => '$json:url$',
			'ThumbnailURL' => '$json:url$/raw',
			'DeletionURL' => '$json:url$/delete/' . $user->token,
		];

		return $response
			->withHeader('Content-Disposition', 'attachment;filename="' . $user->username . '-ShareX.sxcu"')
			->withJson($json, 200, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return Response
	 * @throws NotFoundException
	 * @throws UnauthorizedException
	 */
	public function getUploaderScriptFile(Request $request, Response $response, $args): Response
	{
		$user = $this->database->query('SELECT * FROM `users` WHERE `id` = ? LIMIT 1', $args['id'])->fetch();

		if (!$user) {
			throw new NotFoundException($request, $response);
		}

		if ($user->id !== Session::get('user_id') && !Session::get('admin', false)) {
			throw new UnauthorizedException();
		}

		if ($user->token === null || $user->token === '') {
			Session::alert('You don\'t have a personal upload token. (Click the update token button and try again)', 'danger');
			return $response->withRedirect($request->getHeaderLine('HTTP_REFERER'));
		}

		return $this->view->render($response->withHeader('Content-Disposition', 'attachment;filename="xbackbone_uploader_' . $user->username . '.sh"'),
			'scripts/xbackbone_uploader.sh.twig',
			[
				'username' => $user->username,
				'upload_url' => route('upload'),
				'token' => $user->token,
			]
		);
	}

	/**
	 * @return string
	 */
	protected function generateNewToken(): string
	{
		do {
			$token = 'token_' . md5(uniqid('', true));
		} while ($this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `token` = ?', $token)->fetch()->count > 0);

		return $token;
	}
}