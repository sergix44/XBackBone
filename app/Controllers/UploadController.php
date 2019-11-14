<?php

namespace App\Controllers;

use GuzzleHttp\Psr7\Stream;
use Intervention\Image\Constraint;
use Intervention\Image\ImageManagerStatic as Image;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class UploadController extends Controller
{

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @return Response
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function webUpload(Request $request, Response $response): Response
    {
        $user = $this->database->query('SELECT * FROM `users` WHERE `id` = ? LIMIT 1', $this->session->get('user_id'))->fetch();

        if ($user->token === null || $user->token === '') {
            $this->session->alert(lang('no_upload_token'), 'danger');
            return redirect($response, $request->getHeaderLine('Referer'));
        }

        return view()->render($response, 'upload/web.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @return Response
     * @throws FileExistsException
     */
    public function upload(Request $request, Response $response): Response
    {
        $json = [
            'message' => null,
            'version' => PLATFORM_VERSION,
        ];

        if ($this->config['maintenance']) {
            $json['message'] = 'Endpoint under maintenance.';
            return json($response, $json, 503);
        }

        if ($request->getServerParams()['CONTENT_LENGTH'] > stringToBytes(ini_get('post_max_size'))) {
            $json['message'] = 'File too large (post_max_size too low?).';
            return json($response, $json, 400);
        }

        $file = array_values($request->getUploadedFiles());
        /** @var \Psr\Http\Message\UploadedFileInterface|null $file */
        $file = isset($file[0]) ? $file[0] : null;

        if ($file === null) {
            $json['message'] = 'Request without file attached.';
            return json($response, $json, 400);
        }

        if ($file->getError() === UPLOAD_ERR_INI_SIZE) {
            $json['message'] = 'File too large (upload_max_filesize too low?).';
            return json($response, $json, 400);
        }

        if (param($request, 'token') === null) {
            $json['message'] = 'Token not specified.';
            return json($response, $json, 400);
        }

        $user = $this->database->query('SELECT * FROM `users` WHERE `token` = ? LIMIT 1', param($request, 'token'))->fetch();

        if (!$user) {
            $json['message'] = 'Token specified not found.';
            return json($response, $json, 404);
        }

        if (!$user->active) {
            $json['message'] = 'Account disabled.';
            return json($response, $json, 401);
        }

        do {
            $code = humanRandomString();
        } while ($this->database->query('SELECT COUNT(*) AS `count` FROM `uploads` WHERE `code` = ?', $code)->fetch()->count > 0);

        $fileInfo = pathinfo($file->getClientFilename());
        $storagePath = "$user->user_code/$code.$fileInfo[extension]";

        $this->storage->writeStream($storagePath, $file->getStream()->detach());

        $this->database->query('INSERT INTO `uploads`(`user_id`, `code`, `filename`, `storage_path`) VALUES (?, ?, ?, ?)', [
            $user->id,
            $code,
            $file->getClientFilename(),
            $storagePath,
        ]);

        $json['message'] = 'OK.';
        $json['url'] = urlFor("/$user->user_code/$code.$fileInfo[extension]");

        $this->logger->info("User $user->username uploaded new media.", [$this->database->getPdo()->lastInsertId()]);

        return json($response, $json, 201);
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  string  $userCode
     * @param  string  $mediaCode
     * @param  string|null  $token
     * @return Response
     * @throws HttpNotFoundException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws FileNotFoundException
     */
    public function show(Request $request, Response $response, string $userCode, string $mediaCode, string $token = null): Response
    {
        $media = $this->getMedia($userCode, $mediaCode);

        if (!$media || (!$media->published && $this->session->get('user_id') !== $media->user_id && !$this->session->get('admin', false))) {
            throw new HttpNotFoundException($request);
        }

        $filesystem = $this->storage;

        if (isBot($request->getHeaderLine('User-Agent'))) {
            return $this->streamMedia($request, $response, $filesystem, $media);
        } else {
            try {
                $media->mimetype = $filesystem->getMimetype($media->storage_path);
                $size = $filesystem->getSize($media->storage_path);

                $type = explode('/', $media->mimetype)[0];
                if ($type === 'image' && !isDisplayableImage($media->mimetype)) {
                    $type = 'application';
                    $media->mimetype = 'application/octet-stream';
                }
                if ($type === 'text') {
                    if ($size <= (200 * 1024)) { // less than 200 KB
                        $media->text = $filesystem->read($media->storage_path);
                    } else {
                        $type = 'application';
                        $media->mimetype = 'application/octet-stream';
                    }
                }
                $media->size = humanFileSize($size);

            } catch (FileNotFoundException $e) {
                throw new HttpNotFoundException($request);
            }

            return view()->render($response, 'upload/public.twig', [
                'delete_token' => $token,
                'media' => $media,
                'type' => $type,
                'extension' => pathinfo($media->filename, PATHINFO_EXTENSION),
            ]);
        }
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  string  $userCode
     * @param  string  $mediaCode
     * @param  string  $token
     * @return Response
     * @throws HttpNotFoundException
     * @throws HttpUnauthorizedException
     */
    public function deleteByToken(Request $request, Response $response, string $userCode, string $mediaCode, string $token): Response
    {
        $media = $this->getMedia($userCode, $mediaCode);

        if (!$media) {
            throw new HttpNotFoundException($request);
        }

        $user = $this->database->query('SELECT `id`, `active` FROM `users` WHERE `token` = ? LIMIT 1', $token)->fetch();

        if (!$user) {
            $this->session->alert(lang('token_not_found'), 'danger');
            return redirect($response, $request->getHeaderLine('Referer'));
        }

        if (!$user->active) {
            $this->session->alert(lang('account_disabled'), 'danger');
            return redirect($response, $request->getHeaderLine('Referer'));
        }

        if ($this->session->get('admin', false) || $user->id === $media->user_id) {

            try {
                $this->storage->delete($media->storage_path);
            } catch (FileNotFoundException $e) {
                throw new HttpNotFoundException($request);
            } finally {
                $this->database->query('DELETE FROM `uploads` WHERE `id` = ?', $media->mediaId);
                $this->logger->info('User '.$user->username.' deleted a media via token.', [$media->mediaId]);
            }
        } else {
            throw new HttpUnauthorizedException($request);
        }

        return redirect($response, route('home'));
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  int  $id
     * @return Response
     * @throws FileNotFoundException
     * @throws HttpNotFoundException
     */
    public function getRawById(Request $request, Response $response, int $id): Response
    {

        $media = $this->database->query('SELECT * FROM `uploads` WHERE `id` = ? LIMIT 1', $id)->fetch();

        if (!$media) {
            throw new HttpNotFoundException($request);
        }

        return $this->streamMedia($request, $response, $this->storage, $media);
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  string  $userCode
     * @param  string  $mediaCode
     * @param  string|null  $ext
     * @return Response
     * @throws FileNotFoundException
     * @throws HttpNotFoundException
     */
    public function showRaw(Request $request, Response $response, string $userCode, string $mediaCode, ?string $ext = null): Response
    {
        $media = $this->getMedia($userCode, $mediaCode);

        if (!$media || !$media->published && $this->session->get('user_id') !== $media->user_id && !$this->session->get('admin', false)) {
            throw new HttpNotFoundException($request);
        }

        if ($ext !== null && pathinfo($media->filename, PATHINFO_EXTENSION) !== $ext) {
            throw new HttpBadRequestException($request);
        }

        return $this->streamMedia($request, $response, $this->storage, $media);
    }


    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  string  $userCode
     * @param  string  $mediaCode
     * @return Response
     * @throws FileNotFoundException
     * @throws HttpNotFoundException
     */
    public function download(Request $request, Response $response, string $userCode, string $mediaCode): Response
    {
        $media = $this->getMedia($userCode, $mediaCode);

        if (!$media || !$media->published && $this->session->get('user_id') !== $media->user_id && !$this->session->get('admin', false)) {
            throw new HttpNotFoundException($request);
        }
        return $this->streamMedia($request, $response, $this->storage, $media, 'attachment');
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  int  $id
     * @return Response
     * @throws HttpNotFoundException
     */
    public function togglePublish(Request $request, Response $response, int $id): Response
    {
        if ($this->session->get('admin')) {
            $media = $this->database->query('SELECT * FROM `uploads` WHERE `id` = ? LIMIT 1', $id)->fetch();
        } else {
            $media = $this->database->query('SELECT * FROM `uploads` WHERE `id` = ? AND `user_id` = ? LIMIT 1', [$id, $this->session->get('user_id')])->fetch();
        }

        if (!$media) {
            throw new HttpNotFoundException($request);
        }

        $this->database->query('UPDATE `uploads` SET `published`=? WHERE `id`=?', [$media->published ? 0 : 1, $media->id]);

        return $response->withStatus(200);
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  int  $id
     * @return Response
     * @throws HttpNotFoundException
     * @throws HttpUnauthorizedException
     */
    public function delete(Request $request, Response $response, int $id): Response
    {
        $media = $this->database->query('SELECT * FROM `uploads` WHERE `id` = ? LIMIT 1', $id)->fetch();

        if (!$media) {
            throw new HttpNotFoundException($request);
        }

        if ($this->session->get('admin', false) || $media->user_id === $this->session->get('user_id')) {

            try {
                $this->storage->delete($media->storage_path);
            } catch (FileNotFoundException $e) {
                throw new HttpNotFoundException($request);
            } finally {
                $this->database->query('DELETE FROM `uploads` WHERE `id` = ?', $id);
                $this->logger->info('User '.$this->session->get('username').' deleted a media.', [$id]);
                $this->session->set('used_space', humanFileSize($this->getUsedSpaceByUser($this->session->get('user_id'))));
            }
        } else {
            throw new HttpUnauthorizedException($request);
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
     * @param  Request  $request
     * @param  Response  $response
     * @param  Filesystem  $storage
     * @param $media
     * @param  string  $disposition
     * @return Response
     * @throws FileNotFoundException
     */
    protected function streamMedia(Request $request, Response $response, Filesystem $storage, $media, string $disposition = 'inline'): Response
    {
        set_time_limit(0);
        $mime = $storage->getMimetype($media->storage_path);

        if (param($request, 'width') !== null && explode('/', $mime)[0] === 'image') {

            $image = Image::make($storage->readStream($media->storage_path))
                ->resize(
                    param($request, 'width'),
                    param($request, 'height'),
                    function (Constraint $constraint) {
                        $constraint->aspectRatio();
                    })
                ->resizeCanvas(param($request, 'width'),
                    param($request, 'height'), 'center')
                ->stream('png');

            return $response
                ->withHeader('Content-Type', 'image/png')
                ->withHeader('Content-Disposition', $disposition.';filename="scaled-'.pathinfo($media->filename, PATHINFO_FILENAME).'.png"')
                ->withBody($image);
        } else {
            $stream = new Stream($storage->readStream($media->storage_path));

            if (!in_array(explode('/', $mime)[0], ['image', 'video', 'audio']) || $disposition === 'attachment') {
                return $response->withHeader('Content-Type', $mime)
                    ->withHeader('Content-Disposition', $disposition.'; filename="'.$media->filename.'"')
                    ->withHeader('Content-Length', $stream->getSize())
                    ->withBody($stream);
            }

            $end = $stream->getSize() - 1;
            if (isset($request->getServerParams()['HTTP_RANGE'])) {
                list(, $range) = explode('=', $request->getServerParams()['HTTP_RANGE'], 2);

                if (strpos($range, ',') !== false) {
                    return $response->withHeader('Content-Type', $mime)
                        ->withHeader('Content-Disposition', $disposition.'; filename="'.$media->filename.'"')
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

                header("Content-Type: $mime");
                header('Content-Length: '.($end - $start + 1));
                header('Accept-Ranges: bytes');
                header("Content-Range: bytes $start-$end/{$stream->getSize()}");

                http_response_code(206);
                ob_end_clean();

                $buffer = 16348;
                $readed = $start;
                while ($readed < $end) {
                    if ($readed + $buffer > $end) {
                        $buffer = $end - $readed + 1;
                    }
                    echo $stream->read($buffer);
                    $readed += $buffer;
                }

                exit(0);
            }

            return $response->withHeader('Content-Type', $mime)
                ->withHeader('Content-Length', $stream->getSize())
                ->withHeader('Accept-Ranges', 'bytes')
                ->withStatus(200)
                ->withBody($stream);
        }
    }
}