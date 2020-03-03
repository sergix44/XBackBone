<?php

namespace App\Controllers;

use App\Database\Queries\UserQuery;
use App\Exceptions\ValidationException;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;

class UploadController extends Controller
{
    private $json = [
        'message' => null,
        'version' => PLATFORM_VERSION,
    ];

    /**
     * @param  Response  $response
     *
     * @return Response
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function uploadWebPage(Response $response): Response
    {
        return view()->render($response, 'upload/web.twig');
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @return Response
     * @throws Exception
     */
    public function uploadWeb(Request $request, Response $response): Response
    {
        if ($this->config['maintenance']) {
            $this->json['message'] = 'Endpoint under maintenance.';

            return json($response, $this->json, 503);
        }

        try {
            $file = $this->validateFile($request, $response);

            $user = make(UserQuery::class)->get($request, $this->session->get('user_id'));

            $this->validateUser($request, $response, $file, $user);
        } catch (ValidationException $e) {
            return $e->response();
        }

        if (!$this->updateUserQuota($request, $user->id, $file->getSize())) {
            $this->json['message'] = 'User disk quota exceeded.';

            return json($response, $this->json, 507);
        }

        try {
            $response = $this->saveMedia($response, $file, $user);
            $this->setSessionQuotaInfo($user->current_disk_quota + $file->getSize(), $user->max_disk_quota);
        } catch (Exception $e) {
            $this->updateUserQuota($request, $user->id, $file->getSize(), true);
            throw $e;
        }

        return $response;
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     *
     * @return Response
     * @throws Exception
     */
    public function uploadEndpoint(Request $request, Response $response): Response
    {
        if ($this->config['maintenance']) {
            $this->json['message'] = 'Endpoint under maintenance.';

            return json($response, $this->json, 503);
        }

        try {
            $file = $this->validateFile($request, $response);
        } catch (ValidationException $e) {
            return $e->response();
        }

        if (param($request, 'token') === null) {
            $this->json['message'] = 'Token not specified.';

            return json($response, $this->json, 400);
        }

        $user = $this->database->query('SELECT * FROM `users` WHERE `token` = ? LIMIT 1', param($request, 'token'))->fetch();

        if (!$user) {
            $this->json['message'] = 'Token specified not found.';

            return json($response, $this->json, 404);
        }

        try {
            $this->validateUser($request, $response, $file, $user);
        } catch (ValidationException $e) {
            return $e->response();
        }

        if (!$this->updateUserQuota($request, $user->id, $file->getSize())) {
            $this->json['message'] = 'User disk quota exceeded.';

            return json($response, $this->json, 507);
        }

        try {
            $response = $this->saveMedia($response, $file, $user);
        } catch (Exception $e) {
            $this->updateUserQuota($request, $user->id, $file->getSize(), true);
            throw $e;
        }
        return $response;
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @return UploadedFileInterface
     * @throws ValidationException
     */
    protected function validateFile(Request $request, Response $response)
    {
        if ($request->getServerParams()['CONTENT_LENGTH'] > stringToBytes(ini_get('post_max_size'))) {
            $this->json['message'] = 'File too large (post_max_size too low?).';

            throw new ValidationException(json($response, $this->json, 400));
        }

        $file = array_values($request->getUploadedFiles());
        /** @var UploadedFileInterface|null $file */
        $file = $file[0] ?? null;

        if ($file === null) {
            $this->json['message'] = 'Request without file attached.';

            throw new ValidationException(json($response, $this->json, 400));
        }

        if ($file->getError() === UPLOAD_ERR_INI_SIZE) {
            $this->json['message'] = 'File too large (upload_max_filesize too low?).';

            throw new ValidationException(json($response, $this->json, 400));
        }

        return $file;
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  UploadedFileInterface  $file
     * @param $user
     * @return void
     * @throws ValidationException
     */
    protected function validateUser(Request $request, Response $response, UploadedFileInterface $file, $user)
    {
        if (!$user->active) {
            $this->json['message'] = 'Account disabled.';

            throw new ValidationException(json($response, $this->json, 401));
        }
    }


    /**
     * @param  Response  $response
     * @param  UploadedFileInterface  $file
     * @param $user
     * @return Response
     * @throws \League\Flysystem\FileExistsException
     */
    protected function saveMedia(Response $response, UploadedFileInterface $file, $user)
    {
        do {
            $code = humanRandomString();
        } while ($this->database->query('SELECT COUNT(*) AS `count` FROM `uploads` WHERE `code` = ?', $code)->fetch()->count > 0);

        $published = 1;
        if ($this->getSetting('hide_by_default') === 'on') {
            $published = 0;
        }

        $fileInfo = pathinfo($file->getClientFilename());
        $storagePath = "$user->user_code/$code.$fileInfo[extension]";

        $this->storage->writeStream($storagePath, $file->getStream()->detach());

        $this->database->query('INSERT INTO `uploads`(`user_id`, `code`, `filename`, `storage_path`, `published`) VALUES (?, ?, ?, ?, ?)', [
            $user->id,
            $code,
            $file->getClientFilename(),
            $storagePath,
            $published,
        ]);

        $this->json['message'] = 'OK';
        $this->json['url'] = urlFor("/{$user->user_code}/{$code}.{$fileInfo['extension']}");

        $this->logger->info("User $user->username uploaded new media.", [$this->database->getPdo()->lastInsertId()]);

        return json($response, $this->json, 201);
    }
}
