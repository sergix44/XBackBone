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
}
