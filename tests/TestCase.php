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
