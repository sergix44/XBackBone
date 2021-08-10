<?php

namespace App\Controllers;

use App\Database\Queries\UserQuery;
use App\Web\UA;
use GuzzleHttp\Psr7\Stream;
use Intervention\Image\Constraint;
use Intervention\Image\ImageManagerStatic as Image;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class MediaController extends Controller
{
    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  string  $userCode
     * @param  string  $mediaCode
     * @param  string|null  $token
     *
     * @return Response
     * @throws HttpNotFoundException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws FileNotFoundException
     *
     */
    public function show(Request $request, Response $response, string $userCode, string $mediaCode, string $token = null): Response
    {
        $media = $this->getMedia($userCode, $mediaCode, true);

        if (!$media || (!$media->published && $this->session->get('user_id') !== $media->user_id && !$this->session->get('admin', false))) {
            throw new HttpNotFoundException($request);
        }

        $filesystem = $this->storage;

        $userAgent = $request->getHeaderLine('User-Agent');
        $mime = $filesystem->getMimetype($media->storage_path);

        if (UA::isBot($userAgent) && !(UA::embedsLinks($userAgent) && isDisplayableImage($mime) && $this->getSetting('image_embeds') === 'on')) {
            return $this->streamMedia($request, $response, $filesystem, $media);
        }

        try {
            $media->mimetype = $mime;
            $media->extension = pathinfo($media->filename, PATHINFO_EXTENSION);
            $size = $filesystem->getSize($media->storage_path);

            $type = explode('/', $media->mimetype)[0];
            if ($type === 'image' && !isDisplayableImage($media->mimetype)) {
                $type = 'application';
                $media->mimetype = 'application/octet-stream';
            }
            if ($type === 'text') {
                if ($size <= (500 * 1024)) { // less than 500 KB
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
            'url' => urlFor("/{$userCode}/{$mediaCode}"),
            'copy_raw' => $this->getSetting('copy_url_behavior', 'off') === 'raw',
        ]);
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  int  $id
     *
     * @return Response
     * @throws HttpNotFoundException
     *
     * @throws FileNotFoundException
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
     *
     * @return Response
     * @throws HttpBadRequestException
     * @throws HttpNotFoundException
     *
     * @throws FileNotFoundException
     */
    public function getRaw(
        Request $request,
        Response $response,
        string $userCode,
        string $mediaCode,
        ?string $ext = null
    ): Response {
        $media = $this->getMedia($userCode, $mediaCode, false);

        if (!$media || (!$media->published && $this->session->get('user_id') !== $media->user_id && !$this->session->get(
            'admin',
            false
        ))) {
            throw new HttpNotFoundException($request);
        }

        if ($ext !== null && pathinfo($media->filename, PATHINFO_EXTENSION) !== $ext) {
            throw new HttpBadRequestException($request);
        }

        if (must_be_escaped($this->storage->getMimetype($media->storage_path))) {
            $response = $this->streamMedia($request, $response, $this->storage, $media);
            return $response->withHeader('Content-Type', 'text/plain');
        }

        return $this->streamMedia($request, $response, $this->storage, $media);
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  string  $userCode
     * @param  string  $mediaCode
     *
     * @return Response
     * @throws HttpNotFoundException
     *
     * @throws FileNotFoundException
     */
    public function download(Request $request, Response $response, string $userCode, string $mediaCode): Response
    {
        $media = $this->getMedia($userCode, $mediaCode, false);

        if (!$media || (!$media->published && $this->session->get('user_id') !== $media->user_id && !$this->session->get(
            'admin',
            false
        ))) {
            throw new HttpNotFoundException($request);
        }

        return $this->streamMedia($request, $response, $this->storage, $media, 'attachment');
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  int  $id
     *
     * @return Response
     * @throws HttpNotFoundException
     *
     */
    public function togglePublish(Request $request, Response $response, int $id): Response
    {
        if ($this->session->get('admin')) {
            $media = $this->database->query('SELECT * FROM `uploads` WHERE `id` = ? LIMIT 1', $id)->fetch();
        } else {
            $media = $this->database->query(
                'SELECT * FROM `uploads` WHERE `id` = ? AND `user_id` = ? LIMIT 1',
                [$id, $this->session->get('user_id')]
            )->fetch();
        }

        if (!$media) {
            throw new HttpNotFoundException($request);
        }

        $this->database->query(
            'UPDATE `uploads` SET `published`=? WHERE `id`=?',
            [$media->published ? 0 : 1, $media->id]
        );

        return $response;
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  int  $id
     *
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
            $this->deleteMedia($request, $media->storage_path, $id, $media->user_id);

            $this->logger->info('User '.$this->session->get('username').' deleted a media.', [$id]);

            if ($media->user_id === $this->session->get('user_id')) {
                $user = make(UserQuery::class)->get($request, $media->user_id, true);
                $this->setSessionQuotaInfo($user->current_disk_quota, $user->max_disk_quota);
            }
        } else {
            throw new HttpUnauthorizedException($request);
        }

        if ($request->getMethod() === 'GET') {
            return redirect($response, route('home'));
        }

        return $response;
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  string  $userCode
     * @param  string  $mediaCode
     * @param  string  $token
     *
     * @return Response
     * @throws HttpUnauthorizedException
     *
     * @throws HttpNotFoundException
     */
    public function deleteByToken(
        Request $request,
        Response $response,
        string $userCode,
        string $mediaCode,
        string $token
    ): Response {
        $media = $this->getMedia($userCode, $mediaCode, false);

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
            $this->deleteMedia($request, $media->storage_path, $media->mediaId, $user->id);
            $this->logger->info('User '.$user->username.' deleted a media via token.', [$media->mediaId]);
        } else {
            throw new HttpUnauthorizedException($request);
        }

        return redirect($response, route('home'));
    }

    /**
     * @param  Request  $request
     * @param  string  $storagePath
     * @param  int  $id
     *
     * @param  int  $userId
     * @return void
     * @throws HttpNotFoundException
     */
    protected function deleteMedia(Request $request, string $storagePath, int $id, int $userId)
    {
        try {
            $size = $this->storage->getSize($storagePath);
            $this->storage->delete($storagePath);
            $this->updateUserQuota($request, $userId, $size, true);
        } catch (FileNotFoundException $e) {
            throw new HttpNotFoundException($request);
        } finally {
            $this->database->query('DELETE FROM `uploads` WHERE `id` = ?', $id);
            $this->database->query('DELETE FROM `tags` WHERE `tags`.`id` NOT IN (SELECT `uploads_tags`.`tag_id` FROM `uploads_tags`)');
        }
    }

    /**
     * @param $userCode
     * @param $mediaCode
     *
     * @param  bool  $withTags
     * @return mixed
     */
    protected function getMedia($userCode, $mediaCode, $withTags = false)
    {
        $mediaCode = pathinfo($mediaCode)['filename'];

        $media = $this->database->query(
            'SELECT `uploads`.*, `users`.*, `users`.`id` AS `userId`, `uploads`.`id` AS `mediaId` FROM `uploads` INNER JOIN `users` ON `uploads`.`user_id` = `users`.`id` WHERE `user_code` = ? AND `uploads`.`code` = ? LIMIT 1',
            [
                $userCode,
                $mediaCode,
            ]
        )->fetch();

        if (!$withTags || !$media) {
            return $media;
        }

        $media->tags = [];
        foreach ($this->database->query(
            'SELECT `tags`.`id`, `tags`.`name` FROM `uploads_tags` INNER JOIN `tags` ON `uploads_tags`.`tag_id` = `tags`.`id` WHERE `uploads_tags`.`upload_id` = ?',
            $media->mediaId
        ) as $tag) {
            $media->tags[$tag->id] = $tag->name;
        }

        return $media;
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  Filesystem  $storage
     * @param $media
     * @param  string  $disposition
     *
     * @return Response
     * @throws FileNotFoundException
     *
     */
    protected function streamMedia(
        Request $request,
        Response $response,
        Filesystem $storage,
        $media,
        string $disposition = 'inline'
    ): Response {
        set_time_limit(0);
        $this->session->close();
        $mime = $storage->getMimetype($media->storage_path);

        if ((param($request, 'width') !== null || param($request, 'height') !== null) && explode(
            '/',
            $mime
        )[0] === 'image') {
            return $this->makeThumbnail(
                $storage,
                $media,
                param($request, 'width'),
                param($request, 'height'),
                $disposition
            );
        } else {
            $stream = new Stream($storage->readStream($media->storage_path));

            if (!in_array(explode('/', $mime)[0], ['image', 'video', 'audio']) || $disposition === 'attachment') {
                return $response->withHeader('Content-Type', $mime)
                    ->withHeader('Content-Disposition', $disposition.'; filename="'.$media->filename.'"')
                    ->withHeader('Content-Length', $stream->getSize())
                    ->withBody($stream);
            }

            if (isset($request->getServerParams()['HTTP_RANGE'])) {
                return $this->handlePartialRequest(
                    $response,
                    $stream,
                    $request->getServerParams()['HTTP_RANGE'],
                    $disposition,
                    $media,
                    $mime
                );
            }

            return $response->withHeader('Content-Type', $mime)
                ->withHeader('Content-Length', $stream->getSize())
                ->withHeader('Accept-Ranges', 'bytes')
                ->withBody($stream);
        }
    }

    /**
     * @param  Filesystem  $storage
     * @param $media
     * @param  null  $width
     * @param  null  $height
     * @param  string  $disposition
     *
     * @return Response
     * @throws FileNotFoundException
     *
     */
    protected function makeThumbnail(
        Filesystem $storage,
        $media,
        $width = null,
        $height = null,
        string $disposition = 'inline'
    ) {
        return Image::make($storage->readStream($media->storage_path))
            ->resize($width, $height, function (Constraint $constraint) {
                $constraint->aspectRatio();
            })
            ->resizeCanvas($width, $height, 'center')
            ->psrResponse('png')
            ->withHeader(
                'Content-Disposition',
                $disposition.';filename="scaled-'.pathinfo($media->filename, PATHINFO_FILENAME).'.png"'
            );
    }

    /**
     * @param  Response  $response
     * @param  Stream  $stream
     * @param  string  $range
     * @param  string  $disposition
     * @param $media
     * @param $mime
     *
     * @return Response
     */
    protected function handlePartialRequest(
        Response $response,
        Stream $stream,
        string $range,
        string $disposition,
        $media,
        $mime
    ) {
        $end = $stream->getSize() - 1;
        [, $range] = explode('=', $range, 2);

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
            $start = $stream->getSize() - (int) substr($range, 1);
        } else {
            $range = explode('-', $range);
            $start = (int) $range[0];
            $end = (isset($range[1]) && is_numeric($range[1])) ? (int) $range[1] : $stream->getSize();
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
}
