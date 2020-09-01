<?php

namespace Tests;

use GuzzleHttp\Psr7\Response;
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

    /**
     * @param  string  $uri
     * @param  array  $parameters
     * @return Response|object
     */
    public function get(string $uri, array $parameters = [])
    {
        $this->client->request('GET', $uri, $parameters);
        return $this->client->getResponse();
    }

    /**
     * @param  string  $uri
     * @param  array  $parameters
     * @return Response|object
     */
    public function post(string $uri, array $parameters = [])
    {
        $this->client->request('POST', $uri, $parameters);
        return $this->client->getResponse();
    }
}
