<?php


namespace App\Database\Queries;

use App\Database\DB;
use App\Web\Session;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class UserQuery
{
    /**
     * @var DB
     */
    private $database;
    /**
     * @var Session
     */
    private $session;

    /**
     * UserQuery constructor.
     * @param  DB  $db
     * @param  Session|null  $session
     */
    public function __construct(DB $db, ?Session $session)
    {
        $this->database = $db;
        $this->session = $session;
    }

    /**
     * @param  DB  $db
     * @param  Session|null  $session
     * @return UserQuery
     */
    public static function make(DB $db, Session $session = null)
    {
        return new self($db, $session);
    }

    /**
     * @param  Request  $request
     * @param $id
     * @param  bool  $authorize
     * @return mixed
     * @throws HttpNotFoundException
     * @throws HttpUnauthorizedException
     */
    public function get(Request $request, $id, $authorize = false)
    {
        $user = $this->database->query('SELECT * FROM `users` WHERE `id` = ? LIMIT 1', $id)->fetch();

        if (!$user) {
            throw new HttpNotFoundException($request);
        }

        if ($authorize) {
            if ($this->session === null) {
                throw new \InvalidArgumentException('The session is null.');
            }

            if ($user->id !== $this->session->get('user_id') && !$this->session->get('admin', false)) {
                throw new HttpUnauthorizedException($request);
            }
        }

        return $user;
    }


    /**
     * @param  string  $email
     * @param  string  $username
     * @param  string|null  $password
     * @param  int  $isAdmin
     * @param  int  $isActive
     * @param  int  $maxUserQuota
     * @param  string|null  $activateToken
     * @return bool|\PDOStatement|string
     */
    public function create(string $email, string $username, string $password = null, int $isAdmin = 0, int $isActive = 0, int $maxUserQuota = -1, string $activateToken = null)
    {
        do {
            $userCode = humanRandomString(5);
        } while ($this->database->query('SELECT COUNT(*) AS `count` FROM `users` WHERE `user_code` = ?', $userCode)->fetch()->count > 0);

        $token = $this->generateUserUploadToken();

        return $this->database->query('INSERT INTO `users`(`email`, `username`, `password`, `is_admin`, `active`, `user_code`, `token`, `max_disk_quota`, `activate_token`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $email,
            $username,
            $password !== null ? password_hash($password, PASSWORD_DEFAULT) : null,
            $isAdmin,
            $isActive,
            $userCode,
            $token,
            $maxUserQuota,
            $activateToken,
        ]);
    }

    /**
     * @param $id
     * @param  string  $email
     * @param  string  $username
     * @param  string|null  $password
     * @param  int  $isAdmin
     * @param  int  $isActive
     * @param  int  $maxUserQuota
     * @return bool|\PDOStatement|string
     */
    public function update($id, string $email, string $username, string $password = null, int $isAdmin = 0, int $isActive = 0, int $maxUserQuota = -1)
    {
        if (!empty($password)) {
            return $this->database->query('UPDATE `users` SET `email`=?, `username`=?, `password`=?, `is_admin`=?, `active`=?, `max_disk_quota`=? WHERE `id` = ?', [
                $email,
                $username,
                password_hash($password, PASSWORD_DEFAULT),
                $isAdmin,
                $isActive,
                $maxUserQuota,
                $id,
            ]);
        } else {
            return $this->database->query('UPDATE `users` SET `email`=?, `username`=?, `is_admin`=?, `active`=?, `max_disk_quota`=? WHERE `id` = ?', [
                $email,
                $username,
                $isAdmin,
                $isActive,
                $maxUserQuota,
                $id,
            ]);
        }
    }

    /**
     * @param $id
     * @return string
     */
    public function refreshToken($id)
    {
        $token = $this->generateUserUploadToken();

        $this->database->query('UPDATE `users` SET `token`=? WHERE `id` = ?', [
            $token,
            $id,
        ]);

        return $token;
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
