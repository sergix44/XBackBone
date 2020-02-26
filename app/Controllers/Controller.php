<?php

namespace App\Controllers;

use App\Database\DB;
use App\Web\Lang;
use App\Web\Session;
use App\Web\View;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use Monolog\Logger;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

/**
 * @property Session|null session
 * @property View view
 * @property DB|null database
 * @property Logger|null logger
 * @property Filesystem|null storage
 * @property Lang lang
 * @property array config
 */
abstract class Controller
{
    /** @var Container */
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param $name
     *
     * @return mixed|null
     * @throws NotFoundException
     *
     * @throws DependencyException
     */
    public function __get($name)
    {
        if ($this->container->has($name)) {
            return $this->container->get($name);
        }
    }

    /**
     * @param $id
     *
     * @return int
     */
    protected function getUsedSpaceByUser($id): int
    {
        $medias = $this->database->query('SELECT `uploads`.`storage_path` FROM `uploads` WHERE `user_id` = ?', $id);

        $totalSize = 0;

        $filesystem = $this->storage;
        foreach ($medias as $media) {
            try {
                $totalSize += $filesystem->getSize($media->storage_path);
            } catch (FileNotFoundException $e) {
                $this->logger->error('Error calculating file size', ['exception' => $e]);
            }
        }

        return $totalSize;
    }

    /**
     * @param  Request  $request
     * @param $id
     * @param  bool  $authorize
     *
     * @return mixed
     * @throws HttpUnauthorizedException
     *
     * @throws HttpNotFoundException
     */
    protected function getUser(Request $request, $id, $authorize = false)
    {
        $user = $this->database->query('SELECT * FROM `users` WHERE `id` = ? LIMIT 1', $id)->fetch();

        if (!$user) {
            throw new HttpNotFoundException($request);
        }

        if ($authorize && $user->id !== $this->session->get('user_id') && !$this->session->get('admin', false)) {
            throw new HttpUnauthorizedException($request);
        }

        return $user;
    }

    /**
     * @param $userId
     * @throws \Exception
     */
    protected function refreshRememberCookie($userId)
    {
        $selector = bin2hex(random_bytes(8));
        $token = bin2hex(random_bytes(32));
        $expire = time() + 604800; // a week

        $this->database->query('UPDATE `users` SET `remember_selector`=?, `remember_token`=?, `remember_expire`=? WHERE `id`=?', [
            $selector,
            password_hash($token, PASSWORD_DEFAULT),
            date('Y-m-d\TH:i:s', $expire),
            $userId,
        ]);

        // Workaround for php <= 7.3
        if (PHP_VERSION_ID < 70300) {
            setcookie('remember', "{$selector}:{$token}", $expire, '; SameSite=Lax', '', false, true);
        } else {
            setcookie('remember', "{$selector}:{$token}", [
                'expires' => $expire,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
    }


    /**
     * @return string
     */
    protected function generateUserUploadToken(): string
    {
        do {
            $token = 'token_'.md5(uniqid('', true));
        } while ($this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `token` = ?', $token)->fetch()->count > 0);

        return $token;
    }
}
