<?php

namespace App\Controllers;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UploadController extends Controller
{
    /**
     * @param  Request  $request
     * @param  Response  $response
     *
     * @return Response
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     *
     * @throws \Twig\Error\LoaderError
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
     *
     * @return Response
     * @throws \Slim\Exception\HttpNotFoundException
     * @throws \Slim\Exception\HttpUnauthorizedException
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
        $file = $file[0] ?? null;

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

        if (!$this->updateUserQuota($request, $user->id, $file->getSize())) {
            $json['message'] = 'User disk quota exceeded.';

            return json($response, $json, 507);
        }

        try {
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

            $json['message'] = 'OK';
            $json['url'] = urlFor("/{$user->user_code}/{$code}.{$fileInfo['extension']}");

            $this->logger->info("User $user->username uploaded new media.", [$this->database->getPdo()->lastInsertId()]);

            return json($response, $json, 201);
        } catch (Exception $e) {
            $this->updateUserQuota($request, $user->id, $file->getSize(), true);
            throw $e;
        }
    }
}
