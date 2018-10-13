<?php

namespace App\Controllers;


use App\Database\DB;
use App\Exceptions\NotFoundException;
use App\Traits\SingletonController;
use App\Web\Log;
use App\Web\Session;
use Flight;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;

class UploadController extends Controller
{
	use SingletonController;

	public function upload(): void
	{
		$requestData = Flight::request()->data;

		$response = [
			'message' => null,
		];

		if (!isset($requestData->token)) {
			$response['message'] = 'Token not specified.';
			Flight::json($response, 400);
			return;
		}

		$user = DB::query('SELECT * FROM `users` WHERE `token` = ? LIMIT 1', $requestData->token)->fetch();

		if (!$user) {
			$response['message'] = 'Token specified not found.';
			Flight::json($response, 404);
			return;
		}

		if (!$user->active) {
			$response['message'] = 'Account disabled.';
			Flight::json($response, 401);
			return;
		}

		do {
			$code = uniqid();
		} while (DB::query('SELECT COUNT(*) AS `count` FROM `uploads` WHERE `code` = ?', $code)->fetch()->count > 0);

		$file = Flight::request()->files->current();
		$fileInfo = pathinfo($file['name']);
		$storagePath = "$user->user_code/$code.$fileInfo[extension]";

		$stream = fopen($file['tmp_name'], 'r+');

		$filesystem = $this->getStorage();
		try {
			$filesystem->writeStream($storagePath, $stream);
		} catch (FileExistsException $e) {
			Flight::halt(500);
			return;
		} finally {
			fclose($stream);
		}

		DB::query('INSERT INTO `uploads`(`user_id`, `code`, `filename`, `storage_path`) VALUES (?, ?, ?, ?)', [
			$user->id,
			$code,
			$file['name'],
			$storagePath
		]);

		$base_url = Flight::get('config')['base_url'];

		$response['message'] = 'OK.';
		$response['url'] = "$base_url/$user->user_code/$code.$fileInfo[extension]";
		Flight::json($response, 201);

		Log::info("User $user->username uploaded new media.", [DB::raw()->lastInsertId()]);
	}

	public function show($userCode, $mediaCode): void
	{
		$media = $this->getMedia($userCode, $mediaCode);

		if (!$media || !$media->published && Session::get('user_id') !== $media->user_id && !Session::get('admin', false)) {
			Flight::error(new NotFoundException());
			return;
		}

		$filesystem = $this->getStorage();

		if (stristr(Flight::request()->user_agent, 'TelegramBot') ||
			stristr(Flight::request()->user_agent, 'facebookexternalhit/') ||
			stristr(Flight::request()->user_agent, 'Facebot')) {
			$this->streamMedia($filesystem, $media);
		} else {

			try {
				$mime = $filesystem->getMimetype($media->storage_path);

				$type = explode('/', $mime)[0];
				if ($type === 'text') {
					$media->text = $filesystem->read($media->storage_path);
				} elseif (in_array($type, ['image', 'video'])) {
					$this->http2push(Flight::get('config')['base_url'] . "/$userCode/$mediaCode/raw");
				}

			} catch (FileNotFoundException $e) {
				Flight::error($e);
				return;
			}

			Flight::render('upload/public.twig', [
				'media' => $media,
				'type' => $mime,
				'extension' => pathinfo($media->filename, PATHINFO_EXTENSION)
			]);
		}
	}

	public function getRawById($id): void
	{
		$this->checkAdmin();

		$media = DB::query('SELECT * FROM `uploads` WHERE `id` = ? LIMIT 1', $id)->fetch();

		if (!$media) {
			Flight::error(new NotFoundException());
			return;
		}

		$this->streamMedia($this->getStorage(), $media);
	}

	public function showRaw($userCode, $mediaCode): void
	{
		$media = $this->getMedia($userCode, $mediaCode);

		if (!$media || !$media->published && Session::get('user_id') !== $media->user_id && !Session::get('admin', false)) {
			Flight::error(new NotFoundException());
			return;
		}

		$this->streamMedia($this->getStorage(), $media);
	}


	public function download($userCode, $mediaCode): void
	{
		$media = $this->getMedia($userCode, $mediaCode);

		if (!$media || !$media->published && Session::get('user_id') !== $media->user_id && !Session::get('admin', false)) {
			Flight::error(new NotFoundException());
			return;
		}

		$this->streamMedia($this->getStorage(), $media, 'attachment');
	}

	public function togglePublish($id): void
	{
		$this->checkLogin();

		if (Session::get('admin')) {
			$media = DB::query('SELECT * FROM `uploads` WHERE `id` = ? LIMIT 1', $id)->fetch();
		} else {
			$media = DB::query('SELECT * FROM `uploads` WHERE `id` = ? AND `user_id` = ? LIMIT 1', [$id, Session::get('user_id')])->fetch();
		}

		if (!$media) {
			Flight::halt(404);
			return;
		}

		DB::query('UPDATE `uploads` SET `published`=? WHERE `id`=?', [!$media->published, $media->id]);
	}

	public function delete($id): void
	{
		$this->checkLogin();

		$media = DB::query('SELECT * FROM `uploads` WHERE `id` = ? LIMIT 1', $id)->fetch();

		if (Session::get('admin', false) || $media->user_id === Session::get('user_id')) {

			$filesystem = $this->getStorage();
			try {
				$filesystem->delete($media->storage_path);
			} catch (FileNotFoundException $e) {
				Flight::halt(404);
				return;
			} finally {
				DB::query('DELETE FROM `uploads` WHERE `id` = ?', $id);
				Log::info('User ' . Session::get('username') . " deleted media $id");
			}
		} else {
			Flight::halt(403);
		}
	}

	protected function getMedia($userCode, $mediaCode)
	{
		$mediaCode = pathinfo($mediaCode)['filename'];

		$media = DB::query('SELECT * FROM `uploads` INNER JOIN `users` ON `uploads`.`user_id` = `users`.`id` WHERE `user_code` = ? AND `uploads`.`code` = ? LIMIT 1', [
			$userCode,
			$mediaCode
		])->fetch();

		return $media;
	}

	protected function streamMedia(Filesystem $storage, $media, string $disposition = 'inline'): void
	{
		try {
			$mime = $storage->getMimetype($media->storage_path);
			$query = Flight::request()->query;

			if ($query['width'] !== null && explode('/', $mime)[0] === 'image') {
				Flight::response()->header('Content-Type', 'image/png');
				Flight::response()->header('Content-Disposition', $disposition . ';filename="scaled-' . pathinfo($media->filename)['filename'] . '.png"');
				Flight::response()->sendHeaders();
				ob_clean();

				$image = imagecreatefromstring($storage->read($media->storage_path));
				$scaled = imagescale($image, $query['width'], $query['height'] !== null ? $query['height'] : -1);

				imagedestroy($image);

				imagepng($scaled, null, 9);
				imagedestroy($scaled);
			} else {
				Flight::response()->header('Content-Type', $mime);
				Flight::response()->header('Content-Disposition', $disposition . ';filename="' . $media->filename . '"');
				Flight::response()->header('Content-Length', $storage->getSize($media->storage_path));
				Flight::response()->sendHeaders();
				ob_end_clean();

				fpassthru($storage->readStream($media->storage_path));
			}
		} catch (FileNotFoundException $e) {
			Flight::error($e);
		}
	}
}