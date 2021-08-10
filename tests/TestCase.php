<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use WithApplication;

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = []; // ugly workaround to the the session superglobal between tests
        $this->createApplication();
    }

    /**
     * @param $key
     * @param  null  $value
     */
    public function updateSetting($key, $value = null)
    {
        if (!$this->database()->query('SELECT `value` FROM `settings` WHERE `key` = '.$this->database()->getPdo()->quote($key))->fetch()) {
            $this->database()->query('INSERT INTO `settings`(`key`, `value`) VALUES ('.$this->database()->getPdo()->quote($key).', ?)', $value);
        } else {
            $this->database()->query('UPDATE `settings` SET `value`=? WHERE `key` = '.$this->database()->getPdo()->quote($key), $value);
        }
    }

    /**
     * @param  string  $email
     * @param  string  $username
     * @param  string  $password
     * @return string
     */
    public function createAdminUser($email = 'admin@example.com', $username = 'admin', $password = 'admin')
    {
        return $this->createUser([
            'email' => $email,
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);
    }

    /**
     * @param  array  $attributes
     * @return string
     */
    public function createUser($attributes = [])
    {
        $attributes = array_replace_recursive([
            'email' => 'user@example.com',
            'username' => 'user',
            'password' => password_hash('user', PASSWORD_DEFAULT),
            'is_admin' => 1,
            'active' => 1,
            'user_code' => humanRandomString(5),
            'token' => 'token_'.md5(uniqid('', true)),
            'max_disk_quota' => -1,
            'activate_token' => null,
            'ldap' => 0,
            'hide_uploads' => 0,
            'copy_raw' => 0,
            'reset_token' => null,
        ], $attributes);

        $this->database()->query('INSERT INTO `users`(`email`, `username`, `password`, `is_admin`, `active`, `user_code`, `token`, `max_disk_quota`, `activate_token`, `ldap`, `hide_uploads`, `copy_raw`, `reset_token`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($attributes));
        return $this->database()->getPdo()->lastInsertId();
    }
}
