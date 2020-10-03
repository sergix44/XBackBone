<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use WithApplication;

    protected function setUp()
    {
        parent::setUp();
        $_SESSION = []; // ugly workaround to the the session superglobal between tests
        $this->createApplication();
    }

    public function updateSetting($key, $value = null)
    {
        if (!$this->database()->query('SELECT `value` FROM `settings` WHERE `key` = '.$this->database()->getPdo()->quote($key))->fetch()) {
            $this->database()->query('INSERT INTO `settings`(`key`, `value`) VALUES ('.$this->database()->getPdo()->quote($key).', ?)', $value);
        } else {
            $this->database()->query('UPDATE `settings` SET `value`=? WHERE `key` = '.$this->database()->getPdo()->quote($key), $value);
        }
    }

    public function createAdminUser($email = 'admin@example.com', $username = 'admin', $password = 'admin')
    {
        $this->database()->query("INSERT INTO `users` (`email`, `username`, `password`, `is_admin`, `user_code`) VALUES (?, ?, ?, 1, ?)", [$email, $username, password_hash($password, PASSWORD_DEFAULT), humanRandomString(5)]);
        return $this->database()->getPdo()->lastInsertId();
    }
}
