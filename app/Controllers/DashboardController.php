<?php

namespace App\Controllers;

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
			$this->session->alert(lang('installed'), 'success');
			removeDirectory('install');
		}

		return redirect($response, 'home');
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

		if ($this->session->get('admin', false)) {
			$medias = $this->database->query('SELECT `uploads`.*, `users`.`user_code`, `users`.`username` FROM `uploads` LEFT JOIN `users` ON `uploads`.`user_id` = `users`.`id` ORDER BY `timestamp` DESC LIMIT ? OFFSET ?', [self::PER_PAGE_ADMIN, $page * self::PER_PAGE_ADMIN])->fetchAll();
			$pages = $this->database->query('SELECT COUNT(*) AS `count` FROM `uploads`')->fetch()->count / self::PER_PAGE_ADMIN;
		} else {
			$medias = $this->database->query('SELECT `uploads`.*,`users`.`user_code`, `users`.`username` FROM `uploads` INNER JOIN `users` ON `uploads`.`user_id` = `users`.`id` WHERE `user_id` = ? ORDER BY `timestamp` DESC LIMIT ? OFFSET ?', [$this->session->get('user_id'), self::PER_PAGE, $page * self::PER_PAGE])->fetchAll();
			$pages = $this->database->query('SELECT COUNT(*) AS `count` FROM `uploads` WHERE `user_id` = ?', $this->session->get('user_id'))->fetch()->count / self::PER_PAGE;
		}

		$filesystem = storage();

		foreach ($medias as $media) {
			try {
				$media->size = humanFileSize($filesystem->getSize($media->storage_path));
				$media->mimetype = $filesystem->getMimetype($media->storage_path);
			} catch (FileNotFoundException $e) {
				$media->size = null;
				$media->mimetype = null;
			}
			$media->extension = pathinfo($media->filename, PATHINFO_EXTENSION);
		}

		return $this->view->render(
			$response,
			$this->session->get('admin', false) ? 'dashboard/admin.twig' : 'dashboard/home.twig',
			[
				'medias' => $medias,
				'next' => $page < floor($pages),
				'previous' => $page >= 1,
				'current_page' => ++$page,
			]
		);
	}
}