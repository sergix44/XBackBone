<?php

use App\Exceptions\Handlers\AppErrorHandler;
use App\Exceptions\Handlers\Renderers\HtmlErrorRenderer;
use App\Factories\ViewFactory;
use App\Middleware\InjectMiddleware;
use App\Middleware\LangMiddleware;
use App\Middleware\RememberMiddleware;
use App\Web\Session;
use App\Web\View;
use DI\Bridge\Slim\Bridge;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use function DI\factory;
use function DI\get;

if (!file_exists(CONFIG_FILE) && is_dir(BASE_DIR.'install/')) {
    header('Location: ./install/');
    exit();
}

if (!file_exists(CONFIG_FILE) && !is_dir(BASE_DIR.'install/')) {
    exit('Cannot find the config file.');
}

// Load the config
$config = array_replace_recursive([
    'app_name' => 'XBackBone',
    'base_url' => isSecure() ? 'https://'.$_SERVER['HTTP_HOST'] : 'http://'.$_SERVER['HTTP_HOST'],
    'debug' => false,
    'maintenance' => false,
    'db' => [
        'connection' => 'sqlite',
        'dsn' => BASE_DIR.implode(DIRECTORY_SEPARATOR, ['resources', 'database', 'xbackbone.db']),
        'username' => null,
        'password' => null,
    ],
    'storage' => [
        'driver' => 'local',
        'path' => realpath(__DIR__.'/').DIRECTORY_SEPARATOR.'storage',
    ],
    'ldap' => [
        'enabled' => false,
        'host' => null,
        'port' => null,
        'base_domain' => null,
        'user_domain' => null,
    ],
], require CONFIG_FILE);

$builder = new ContainerBuilder();

if (!$config['debug']) {
    $builder->enableCompilation(BASE_DIR.'/resources/cache/di');
    $builder->writeProxiesToFile(true, BASE_DIR.'/resources/cache/di');
}

$builder->addDefinitions([
    Session::class => factory(function () {
        return new Session('xbackbone_session', BASE_DIR.'resources/sessions');
    }),
    'session' => get(Session::class),
    View::class => factory(function (Container $container) {
        return ViewFactory::createAppInstance($container);
    }),
    'view' => get(View::class),
]);

$builder->addDefinitions(__DIR__.'/container.php');

global $app;
$app = Bridge::create($builder->build());
$app->getContainer()->set('config', $config);
$app->setBasePath(parse_url($config['base_url'], PHP_URL_PATH) ?: '');

if (!$config['debug']) {
    $app->getRouteCollector()->setCacheFile(BASE_DIR.'resources/cache/routes.cache.php');
}

$app->add(InjectMiddleware::class);
$app->add(LangMiddleware::class);
$app->add(RememberMiddleware::class);

// Permanently redirect paths with a trailing slash to their non-trailing counterpart
$app->add(function (Request $request, RequestHandler $handler) use (&$app, &$config) {
    $uri = $request->getUri();
    $path = $uri->getPath();

    if ($path !== $app->getBasePath().'/' && substr($path, -1) === '/') {
        // permanently redirect paths with a trailing slash
        // to their non-trailing counterpart
        $uri = $uri->withPath(substr($path, 0, -1));

        if ($request->getMethod() === 'GET') {
            return $app->getResponseFactory()
                ->createResponse(301)
                ->withHeader('Location', (string) $uri);
        } else {
            $request = $request->withUri($uri);
        }
    }

    return $handler->handle($request);
});

$app->addRoutingMiddleware();

// Configure the error handler
$errorHandler = new AppErrorHandler($app->getCallableResolver(), $app->getResponseFactory());
$errorHandler->registerErrorRenderer('text/html', HtmlErrorRenderer::class);

// Add Error Middleware
$errorMiddleware = $app->addErrorMiddleware($config['debug'], false, true);
$errorMiddleware->setDefaultErrorHandler($errorHandler);

// Load the application routes
require BASE_DIR.'app/routes.php';

return $app;
