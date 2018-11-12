<?php

namespace App\Controllers;


use App\Web\Session;
use League\Flysystem\FileNotFoundException;
use Slim\Http\Request;
use Slim\Http\Response;

class DashboardController extends Controller
{

	const PER_PAGE = 21;
	const PER_PAGE_ADMIN = 25;

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 */
	public function redirects(Request $request, Response $response): Response
	{

		if ($request->getParam('afterInstall') !== null && is_dir('install')) {
			Session::alert('Installation completed successfully!', 'success');
			$this->removeDirectory('install');
		}

		return $response->withRedirect('/home');
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return Response
	 */
	public function home(Request $request, Response $response, $args): Response
	{
		$page = isset($args['page']) ? (int)$args['page'] : 0;
		$page = max(0, --$page);

		if (Session::get('admin', false)) {
			$medias = $this->database->query('SELECT `uploads`.*, `users`.`user_code`, `users`.`username` FROM `uploads` LEFT JOIN `users` ON `uploads`.`user_id` = `users`.`id` ORDER BY `timestamp` DESC LIMIT ? OFFSET ?', [self::PER_PAGE_ADMIN, $page * self::PER_PAGE_ADMIN])->fetchAll();
			$pages = $this->database->query('SELECT COUNT(*) AS `count` FROM `uploads`')->fetch()->count / self::PER_PAGE_ADMIN;
		} else {
			$medias = $this->database->query('SELECT `uploads`.*,`users`.`user_code`, `users`.`username` FROM `uploads` INNER JOIN `users` ON `uploads`.`user_id` = `users`.`id` WHERE `user_id` = ? ORDER BY `timestamp` DESC LIMIT ? OFFSET ?', [Session::get('user_id'), self::PER_PAGE, $page * self::PER_PAGE])->fetchAll();
			$pages = $this->database->query('SELECT COUNT(*) AS `count` FROM `uploads` WHERE `user_id` = ?', Session::get('user_id'))->fetch()->count / self::PER_PAGE;
		}

		$filesystem = $this->getStorage();

		foreach ($medias as $media) {
			$extension = pathinfo($media->filename, PATHINFO_EXTENSION);
			try {
				$mime = $filesystem->getMimetype($media->storage_path);
				$size = $filesystem->getSize($media->storage_path);
			} catch (FileNotFoundException $e) {
				$mime = null;
			}
			$media->mimetype = $mime;
			$media->extension = $extension;
			$media->size = $this->humanFilesize($size);
		}

		return $this->view->render(
			$response,
			Session::get('admin', false) ? 'dashboard/admin.twig' : 'dashboard/home.twig',
			[
				'medias' => $medias,
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
	 * @throws FileNotFoundException
	 */
	public function system(Request $request, Response $response): Response
	{
		$usersCount = $this->database->query('SELECT COUNT(*) AS `count` FROM `users`')->fetch()->count;
		$mediasCount = $this->database->query('SELECT COUNT(*) AS `count` FROM `uploads`')->fetch()->count;
		$orphanFilesCount = $this->database->query('SELECT COUNT(*) AS `count` FROM `uploads` WHERE `user_id` IS NULL')->fetch()->count;

		$medias = $this->database->query('SELECT `users`.`user_code`, `uploads`.`code`, `uploads`.`storage_path` FROM `uploads` LEFT JOIN `users` ON `uploads`.`user_id` = `users`.`id`')->fetchAll();

		$totalSize = 0;

		$filesystem = $this->getStorage();
		foreach ($medias as $media) {
			$totalSize += $filesystem->getSize($media->storage_path);
		}

		return $this->view->render($response, 'dashboard/system.twig', [
			'usersCount' => $usersCount,
			'mediasCount' => $mediasCount,
			'orphanFilesCount' => $orphanFilesCount,
			'totalSize' => $this->humanFilesize($totalSize),
			'post_max_size' => ini_get('post_max_size'),
			'upload_max_filesize' => ini_get('upload_max_filesize'),
		]);
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 */
	public function getThemes(Request $request, Response $response): Response
	{
		$apiJson = json_decode(file_get_contents('https://bootswatch.com/api/4.json'));

		$out = [];

		foreach ($apiJson->themes as $theme) {
			$out["{$theme->name} - {$theme->description}"] = $theme->cssMin;
		}

		return $response->withJson($out);
	}


	public function applyTheme(Request $request, Response $response): Response
	{
		file_put_contents('static/bootstrap/css/bootstrap.min.css', file_get_contents($request->getParam('css')));
		return $response->withRedirect('/system')->withAddedHeader('Cache-Control', 'no-cache, must-revalidate');
	}
}