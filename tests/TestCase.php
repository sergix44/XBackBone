<?php

namespace Tests;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** @var Client */
    protected $client;

    protected function setUp()
    {

        $_SERVER['HTTP_HOST'] = 'http://localhost';
        $_SERVER['HTTPS'] = false;

        $this->client = new Client();
    }

}