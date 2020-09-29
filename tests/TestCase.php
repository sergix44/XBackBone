<?php

namespace Tests;

use App\Database\DB;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\BrowserKit\Response;

abstract class TestCase extends BaseTestCase
{
    use BootApplication;

    /** @var Client */
    protected $client;

    protected function setUp()
    {
        $this->client = new Client($this->createApplication());
        $this->client->followRedirects(false);
    }

    /**
     * @param  string  $uri
     * @param  array  $parameters
     * @return object|Response
     */
    public function get(string $uri, array $parameters = [])
    {
        $this->client->request('GET', $uri, $parameters);
        return $this->client->getResponse();
    }

    /**
     * @param  string  $uri
     * @param  array  $parameters
     * @return object|Response
     */
    public function post(string $uri, array $parameters = [])
    {
        $this->client->request('POST', $uri, $parameters);
        return $this->client->getResponse();
    }

    /**
     * @return DB
     */
    public function database()
    {
        return $this->app->getContainer()->get('database');
    }
}
