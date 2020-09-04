<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\BrowserKit\Response;

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

    /**
     * @param  string  $uri
     * @param  array  $parameters
     * @param  array  $files
     * @param  array  $server
     * @param  string|null  $content
     * @param  bool  $changeHistory
     * @return Response|object
     */
    protected function get(string $uri, array $parameters = [], array $files = [], array $server = [], string $content = null, bool $changeHistory = true)
    {
        $this->client->request('GET', $uri, $parameters, $files, $server, $content, $changeHistory);
        return $this->client->getResponse();
    }

    /**
     * @param  string  $uri
     * @param  array  $parameters
     * @param  array  $files
     * @param  array  $server
     * @param  string|null  $content
     * @param  bool  $changeHistory
     * @return Response|object
     */
    protected function post(string $uri, array $parameters = [], array $files = [], array $server = [], string $content = null, bool $changeHistory = true)
    {
        $this->client->request('POST', $uri, $parameters, $files, $server, $content, $changeHistory);
        return $this->client->getResponse();
    }
}
