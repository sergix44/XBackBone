<?php

namespace App\Controllers;

use App\Exceptions\UnauthorizedException;
use Intervention\Image\ImageManagerStatic as Image;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use Slim\Exception\NotFoundException;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Stream;

class UploadController extends Controller
{

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws FileExistsException
	 */
	public function upload(Request $request, Response $response): Response
	{

		$json = ['message' => null];

		if ($this->settings['maintenance'] && !$this->database->query('SELECT `id`, `is_admin` FROM `users` WHERE `id` = ? LIMIT 1', [$this->session->get('user_id')])->fetch()->is_admin) {
			$json['message'] = 'Endpoint under maintenance.';
			return $response->withJson($json, 503);
		}

		if ($request->getServerParam('CONTENT_LENGTH') > stringToBytes(ini_get('post_max_size'))) {
			$json['message'] = 'File too large (post_max_size too low).';
			return $response->withJson($json, 400);
		}

		if ($request->getUploadedFiles()['upload']->getError() === UPLOAD_ERR_INI_SIZE) {
			$json['message'] = 'File too large (upload_max_filesize too low).';
			return $response->withJson($json, 400);
		}

		if ($request->getParam('token') === null) {
			$json['message'] = 'Token not specified.';
			return $response->withJson($json, 400);
		}

		$user = $this->database->query('SELECT * FROM `users` WHERE `token` = ? LIMIT 1', $request->getParam('token'))->fetch();

		if (!$user) {
			$json['message'] = 'Token specified not found.';
			return $response->withJson($json, 404);
		}

		if (!$user->active) {
			$json['message'] = 'Account disabled.';
			return $response->withJson($json, 401);
		}

		do {
			$code = uniqid();
		} while ($this->database->query('SELECT COUNT(*) AS `count` FROM `uploads` WHERE `code` = ?', $code)->fetch()->count > 0);

		/** @var \Psr\Http\Message\UploadedFileInterface $file */
		$file = $request->getUploadedFiles()['upload'];

		$fileInfo = pathinfo($file->getClientFilename());
		$storagePath = "$user->user_code/$code.$fileInfo[extension]";

		storage()->writeStream($storagePath, $file->getStream()->detach());

		$this->database->query('INSERT INTO `uploads`(`user_id`, `code`, `filename`, `storage_path`) VALUES (?, ?, ?, ?)', [
			$user->id,
			$code,
			$file->getClientFilename(),
			$storagePath,
		]);

		$json['message'] = 'OK.';
		$json['url'] = urlFor("/$user->user_code/$code.$fileInfo[extension]");

		$this->logger->info("User $user->username uploaded new media.", [$this->database->raw()->lastInsertId()]);

		return $response->withJson($json, 201);
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return Response
	 * @throws FileNotFoundException
	 * @throws NotFoundException
	 */
	public function show(Request $request, Response $response, $args): Response
	{
		$media = $this->getMedia($args['userCode'], $args['mediaCode']);

		if (!$media || (!$media->published && $this->session->get('user_id') !== $media->user_id && !$this->session->get('admin', false))) {
			throw new NotFoundException($request, $response);
		}

		if (isBot($request->getHeaderLine('User-Agent'))) {
			return $this->streamMedia($request, $response, storage(), $media);
		} else {
			$filesystem = storage();
			try {
				$media->mimetype = $filesystem->getMimetype($media->storage_path);
				$media->size = humanFileSize($filesystem->getSize($media->storage_path));

				$type = explode('/', $media->mimetype)[0];
				if ($type === 'text') {
					$media->text = $filesystem->read($media->storage_path);
				}

			} catch (FileNotFoundException $e) {
				throw new NotFoundException($request, $response);
			}

			return $this->view->render($response, 'upload/public.twig', [
				'delete_token' => isset($args['token']) ? $args['token'] : null,
				'media' => $media,
				'extension' => pathinfo($media->filename, PATHINFO_EXTENSION),
			]);
		}
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return Response
	 * @throws NotFoundException
	 * @throws UnauthorizedException
	 */
	public function deleteByToken(Request $request, Response $response, $args): Response
	{
		$media = $this->getMedia($args['userCode'], $args['mediaCode']);

		if (!$media) {
			throw new NotFoundException($request, $response);
		}

		$user = $this->database->query('SELECT `id`, `active` FROM `users` WHERE `token` = ? LIMIT 1', $args['token'])->fetch();

		if (!$user) {
			$this->session->alert(lang('token_not_found'), 'danger');
			return $response->withRedirect($request->getHeaderLine('HTTP_REFERER'));
		}

		if (!$user->active) {
			$this->session->alert(lang('account_disabled'), 'danger');
			return $response->withRedirect($request->getHeaderLine('HTTP_REFERER'));
		}

		if ($this->session->get('admin', false) || $user->id === $media->user_id) {

			try {
				storage()->delete($media->storage_path);
			} catch (FileNotFoundException $e) {
				throw new NotFoundException($request, $response);
			} finally {
				$this->database->query('DELETE FROM `uploads` WHERE `id` = ?', $media->mediaId);
				$this->logger->info('User ' . $user->username . ' deleted a media via token.', [$media->mediaId]);
			}
		} else {
			throw new UnauthorizedException();
		}

		return redirect($response, 'home');
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return Response
	 * @throws NotFoundException
	 * @throws FileNotFoundException
	 */
	public function getRawById(Request $request, Response $response, $args): Response
	{

		$media = $this->database->query('SELECT * FROM `uploads` WHERE `id` = ? LIMIT 1', $args['id'])->fetch();

		if (!$media) {
			throw new NotFoundException($request, $response);
		}

		return $this->streamMedia($request, $response, storage(), $media);
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return Response
	 * @throws NotFoundException
	 * @throws FileNotFoundException
	 */
	public function showRaw(Request $request, Response $response, $args): Response
	{
		$media = $this->getMedia($args['userCode'], $args['mediaCode']);

		if (!$media || !$media->published && $this->session->get('user_id') !== $media->user_id && !$this->session->get('admin', false)) {
			throw new NotFoundException($request, $response);
		}
		return $this->streamMedia($request, $response, storage(), $media);
	}


	/**
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return Response
	 * @throws NotFoundException
	 * @throws FileNotFoundException
	 */
	public function download(Request $request, Response $response, $args): Response
	{
		$media = $this->getMedia($args['userCode'], $args['mediaCode']);

		if (!$media || !$media->published && $this->session->get('user_id') !== $media->user_id && !$this->session->get('admin', false)) {
			throw new NotFoundException($request, $response);
		}
		return $this->streamMedia($request, $response, storage(), $media, 'attachment');
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return Response
	 * @throws NotFoundException
	 */
	public function togglePublish(Request $request, Response $response, $args): Response
	{
		if ($this->session->get('admin')) {
			$media = $this->database->query('SELECT * FROM `uploads` WHERE `id` = ? LIMIT 1', $args['id'])->fetch();
		} else {
			$media = $this->database->query('SELECT * FROM `uploads` WHERE `id` = ? AND `user_id` = ? LIMIT 1', [$args['id'], $this->session->get('user_id')])->fetch();
		}

		if (!$media) {
			throw new NotFoundException($request, $response);
		}

		$this->database->query('UPDATE `uploads` SET `published`=? WHERE `id`=?', [$media->published ? 0 : 1, $media->id]);

		return $response->withStatus(200);
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return Response
	 * @throws NotFoundException
	 * @throws UnauthorizedException
	 */
	public function delete(Request $request, Response $response, $args): Response
	{
		$media = $this->database->query('SELECT * FROM `uploads` WHERE `id` = ? LIMIT 1', $args['id'])->fetch();

		if (!$media) {
			throw new NotFoundException($request, $response);
		}

		if ($this->session->get('admin', false) || $media->user_id === $this->session->get('user_id')) {

			try {
				storage()->delete($media->storage_path);
			} catch (FileNotFoundException $e) {
				throw new NotFoundException($request, $response);
			} finally {
				$this->database->query('DELETE FROM `uploads` WHERE `id` = ?', $args['id']);
				$this->logger->info('User ' . $this->session->get('username') . ' deleted a media.', [$args['id']]);
				$this->session->set('used_space', humanFileSize($this->getUsedSpaceByUser($this->session->get('user_id'))));
			}
		} else {
			throw new UnauthorizedException();
		}

		return $response->withStatus(200);
	}

	/**
	 * @param $userCode
	 * @param $mediaCode
	 * @return mixed
	 */
	protected function getMedia($userCode, $mediaCode)
	{
		$mediaCode = pathinfo($mediaCode)['filename'];

		$media = $this->database->query('SELECT `uploads`.*, `users`.*, `users`.`id` AS `userId`, `uploads`.`id` AS `mediaId` FROM `uploads` INNER JOIN `users` ON `uploads`.`user_id` = `users`.`id` WHERE `user_code` = ? AND `uploads`.`code` = ? LIMIT 1', [
			$userCode,
			$mediaCode,
		])->fetch();

		return $media;
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param Filesystem $storage
	 * @param $media
	 * @param string $disposition
	 * @return Response
	 * @throws FileNotFoundException
	 */
	protected function streamMedia(Request $request, Response $response, Filesystem $storage, $media, string $disposition = 'inline'): Response
	{
		set_time_limit(0);
		$mime = $storage->getMimetype($media->storage_path);

		if ($request->getParam('width') !== null && explode('/', $mime)[0] === 'image') {

			$image = Image::make($storage->readStream($media->storage_path))
				->resizeCanvas(
					$request->getParam('width'),
					$request->getParam('height'),
					'center')
				->encode('png');

			return $response
				->withHeader('Content-Type', 'image/png')
				->withHeader('Content-Disposition', $disposition . ';filename="scaled-' . pathinfo($media->filename)['filename'] . '.png"')
				->write($image);
		} else {
			$stream = new Stream($storage->readStream($media->storage_path));

			if (!in_array(explode('/', $mime)[0], ['image', 'video', 'audio']) || $disposition === 'attachment') {
				return $response->withHeader('Content-Type', $mime)
					->withHeader('Content-Disposition', $disposition . '; filename="' . $media->filename . '"')
					->withHeader('Content-Length', $stream->getSize())
					->withBody($stream);
			}

			$end = $stream->getSize() - 1;

			if ($request->getServerParam('HTTP_RANGE') !== null) {
				list(, $range) = explode('=', $request->getServerParam('HTTP_RANGE'), 2);

				if (strpos($range, ',') !== false) {
					return $response->withHeader('Content-Type', $mime)
						->withHeader('Content-Disposition', $disposition . '; filename="' . $media->filename . '"')
						->withHeader('Content-Length', $stream->getSize())
						->withHeader('Accept-Ranges', 'bytes')
						->withHeader('Content-Range', "0,{$stream->getSize()}")
						->withStatus(416)
						->withBody($stream);
				}

				if ($range === '-') {
					$start = $stream->getSize() - (int)substr($range, 1);
				} else {
					$range = explode('-', $range);
					$start = (int)$range[0];
					$end = (isset($range[1]) && is_numeric($range[1])) ? (int)$range[1] : $stream->getSize();
				}

				$end = ($end > $stream->getSize() - 1) ? $stream->getSize() - 1 : $end;
				$stream->seek($start);

				$buffer = 16384;
				$readed = $start;
				while ($readed < $end) {
					if ($readed + $buffer > $end) {
						$buffer = $end - $readed + 1;
					}
					echo $stream->read($buffer);
					$readed += $buffer;
				}

				return $response->withHeader('Content-Type', $mime)
					->withHeader('Content-Length', $end - $start + 1)
					->withHeader('Accept-Ranges', 'bytes')
					->withHeader('Content-Range', "bytes $start-$end/{$stream->getSize()}")
					->withStatus(206);
			}

			return $response->withHeader('Content-Type', $mime)
				->withHeader('Content-Length', $stream->getSize())
				->withHeader('Accept-Ranges', 'bytes')
				->withStatus(200)
				->withBody($stream);
		}
	}
}