<?php

namespace App\Controllers;


use App\Database\DB;
use App\Traits\SingletonController;
use App\Web\Session;
use Flight;
use League\Flysystem\FileNotFoundException;

class DashboardController extends Controller
{
	use SingletonController;

	const PER_PAGE = 21;
	const PER_PAGE_ADMIN = 50;

	public function redirects(): void
	{
		$this->checkLogin();
		Flight::redirect('/home');
	}

	public function home($page = 1): void
	{
		$this->checkLogin();

		$page = max(0, --$page);

		if (Session::get('admin', false)) {
			$medias = DB::query('SELECT `uploads`.*, `users`.`user_code`, `users`.`username` FROM `uploads` LEFT JOIN `users` ON `uploads`.`user_id` = `users`.`id` ORDER BY `timestamp` DESC LIMIT ? OFFSET ?', [self::PER_PAGE_ADMIN, $page * self::PER_PAGE_ADMIN])->fetchAll();
			$pages = DB::query('SELECT COUNT(*) AS `count` FROM `uploads`')->fetch()->count / self::PER_PAGE_ADMIN;
		} else {
			$medias = DB::query('SELECT `uploads`.*,`users`.`user_code`, `users`.`username` FROM `uploads` INNER JOIN `users` ON `uploads`.`user_id` = `users`.`id` WHERE `user_id` = ? ORDER BY `timestamp` DESC LIMIT ? OFFSET ?', [Session::get('user_id'), self::PER_PAGE, $page * self::PER_PAGE])->fetchAll();
			$pages = DB::query('SELECT COUNT(*) AS `count` FROM `uploads` WHERE `user_id` = ?', Session::get('user_id'))->fetch()->count / self::PER_PAGE;
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

		Flight::render(
			Session::get('admin', false) ? 'dashboard/admin.twig' : 'dashboard/home.twig',
			[
				'medias' => $medias,
				'next' => $page < floor($pages),
				'previous' => $page >= 1,
				'current_page' => ++$page,
			]
		);
	}

	public function system()
	{
		$this->checkAdmin();

		$usersCount = DB::query('SELECT COUNT(*) AS `count` FROM `users`')->fetch()->count;
		$mediasCount = DB::query('SELECT COUNT(*) AS `count` FROM `uploads`')->fetch()->count;
		$orphanFilesCount = DB::query('SELECT COUNT(*) AS `count` FROM `uploads` WHERE `user_id` IS NULL')->fetch()->count;

		$medias = DB::query('SELECT `users`.`user_code`, `uploads`.`code`, `uploads`.`storage_path` FROM `uploads` LEFT JOIN `users` ON `uploads`.`user_id` = `users`.`id`')->fetchAll();

		$totalSize = 0;

		$filesystem = $this->getStorage();
		foreach ($medias as $media) {
			$totalSize += $filesystem->getSize($media->storage_path);
		}

		Flight::render('dashboard/system.twig', [
			'usersCount' => $usersCount,
			'mediasCount' => $mediasCount,
			'orphanFilesCount' => $orphanFilesCount,
			'totalSize' => $this->humanFilesize($totalSize),
			'max_filesize' => ini_get('post_max_size') . '/' . ini_get('upload_max_filesize'),
		]);
	}
}