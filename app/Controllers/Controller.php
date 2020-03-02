<?php

namespace App\Controllers;

use App\Database\DB;
use App\Web\Lang;
use App\Web\Session;
use App\Web\View;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
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

        return null;
    }

    /**
     * @param $key
     * @param  null  $default
     * @return object
     */
    protected function getSetting($key, $default = null)
    {
        return $this->database->query('SELECT `value` FROM `settings` WHERE `key` = '.$this->database->getPdo()->quote($key))->fetch()->value ?? $default;
    }

    /**
     * @param $current
     * @param $max
     */
    protected function setSessionQuotaInfo($current, $max)
    {
        $this->session->set('current_disk_quota', humanFileSize($current));
        if ($this->getSetting('quota_enabled', 'off') === 'on') {
            if ($max < 0) {
                $this->session->set('max_disk_quota', '∞');
                $this->session->set('percent_disk_quota', null);
            } else {
                $this->session->set('max_disk_quota', humanFileSize($max));
                $this->session->set('percent_disk_quota', round(($current * 100) / $max));
            }
        } else {
            $this->session->set('max_disk_quota', null);
            $this->session->set('percent_disk_quota', null);
        }
    }

    /**
     * @param  Request  $request
     * @param $userId
     * @param $fileSize
     * @param  bool  $dec
     * @return bool
     * @throws HttpNotFoundException
     * @throws HttpUnauthorizedException
     */
    protected function updateUserQuota(Request $request, $userId, $fileSize, $dec = false)
    {
        $user = $this->getUser($request, $userId);

        if ($dec) {
            $tot = max($user->current_disk_quota - $fileSize, 0);
        } else {
            $tot = $user->current_disk_quota + $fileSize;

            if ($this->getSetting('quota_enabled') === 'on' && $user->max_disk_quota > 0 && $user->max_disk_quota < $tot) {
                return false;
            }
        }

        $this->database->query('UPDATE `users` SET `current_disk_quota`=? WHERE `id` = ?', [
            $tot,
            $user->id,
        ]);

        return true;
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
