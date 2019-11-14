<?php

namespace App\Middleware;


use App\Database\DB;
use App\Web\Lang;
use App\Web\Session;
use App\Web\View;
use DI\Container;
use League\Flysystem\Filesystem;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * @property Session|null session
 * @property View view
 * @property DB|null database
 * @property Logger|null logger
 * @property Filesystem|null storage
 * @property Lang lang
 * @property array config
 */
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
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function __get($name)
    {
        if ($this->container->has($name)) {
            return $this->container->get($name);
        }
        return null;
    }

    /**
     * @param  Request  $request
     * @param  RequestHandler  $handler
     * @return Response
     */
    public abstract function __invoke(Request $request, RequestHandler $handler);
}