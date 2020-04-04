<?php

namespace App\Middleware;

use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

abstract class Middleware
{
    /** @var Container */
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param $name
     * @return mixed|null
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function __get($name)
    {
        if ($this->container->has($name)) {
            return $this->container->get($name);
        }
        return null;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     */
    abstract public function __invoke(Request $request, Response $response, callable $next);
}
