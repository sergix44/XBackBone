<?php

use App\Database\DB;
use App\Exception\Handlers\AppErrorHandler;
use App\Exception\Handlers\Renderers\HtmlErrorRenderer;
use App\Factories\ViewFactory;
use App\Middleware\InjectMiddleware;
use App\Middleware\RememberMiddleware;
use App\Web\Lang;
use App\Web\Session;
use Aws\S3\S3Client;
use DI\Bridge\Slim\Bridge;
use DI\ContainerBuilder;
use Google\Cloud\Storage\StorageClient;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use League\Flysystem\Adapter\Ftp as FtpAdapter;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface as Container;
use Spatie\Dropbox\Client as DropboxClient;
use Spatie\FlysystemDropbox\DropboxAdapter;
use Superbalist\Flysystem\GoogleStorage\GoogleStorageAdapter;
use function DI\factory;
use function DI\value;

if (!file_exists('config.php') && is_dir('install/')) {
    header('Location: ./install/');
    exit();
} else {
    if (!file_exists('config.php') && !is_dir('install/')) {
        exit('Cannot find the config file.');
    }
}

// Load the config
$config = array_replace_recursive([
    'app_name' => 'XBackBone',
    'base_path' => $_SERVER['REQUEST_URI'],
    'debug' => false,
    'maintenance' => false,
    'db' => [
        'connection' => 'sqlite',
        'dsn' => BASE_DIR.'resources/database/xbackbone.db',
        'username' => null,
        'password' => null,
    ],
    'storage' => [
        'driver' => 'local',
        'path' => realpath(__DIR__.'/').DIRECTORY_SEPARATOR.'storage',
    ],
], require BASE_DIR.'config.php');

$builder = new ContainerBuilder();

if (!$config['debug']) {
    $builder->enableCompilation(BASE_DIR.'/resources/cache/di/');
    $builder->writeProxiesToFile(true, BASE_DIR.'/resources/cache/proxies');
}
$builder->addDefinitions([
    'config' => value($config),

    'logger' => factory(function (Container $container) {
        $logger = new Logger('app');

        $streamHandler = new RotatingFileHandler(BASE_DIR.'logs/log.txt', 10, Logger::DEBUG);

        $lineFormatter = new LineFormatter("[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n", "Y-m-d H:i:s");
        $lineFormatter->includeStacktraces(true);

        $streamHandler->setFormatter($lineFormatter);

        $logger->pushHandler($streamHandler);

        return $logger;
    }),

    'session' => factory(function (Container $container) {
        return new Session('xbackbone_session', BASE_DIR.'resources/sessions');
    }),

    'database' => factory(function (Container $container) {
        $config = $container->get('config');
        $dsn = $config['db']['connection'] === 'sqlite' ? BASE_DIR.$config['db']['dsn'] : $config['db']['dsn'];
        return new DB($config['db']['connection'].':'.$dsn, $config['db']['username'], $config['db']['password']);
    }),

    'storage' => factory(function (Container $container) {
        $config = $container->get('config');
        switch ($config['storage']['driver']) {
            case 'local':
                return new Filesystem(new Local($config['storage']['path']));
            case 's3':
                $client = new S3Client([
                    'credentials' => [
                        'key' => $config['storage']['key'],
                        'secret' => $config['storage']['secret'],
                    ],
                    'region' => $config['storage']['region'],
                    'version' => 'latest',
                ]);

                return new Filesystem(new AwsS3Adapter($client, $config['storage']['bucket'], $config['storage']['path']));
            case 'dropbox':
                $client = new DropboxClient($config['storage']['token']);
                return new Filesystem(new DropboxAdapter($client), ['case_sensitive' => false]);
            case 'ftp':
                return new Filesystem(new FtpAdapter([
                    'host' => $config['storage']['host'],
                    'username' => $config['storage']['username'],
                    'password' => $config['storage']['password'],
                    'port' => $config['storage']['port'],
                    'root' => $config['storage']['path'],
                    'passive' => $config['storage']['passive'],
                    'ssl' => $config['storage']['ssl'],
                    'timeout' => 30,
                ]));
            case 'google-cloud':
                $client = new StorageClient([
                    'projectId' => $config['storage']['project_id'],
                    'keyFilePath' => $config['storage']['key_path'],
                ]);
                return new Filesystem(new GoogleStorageAdapter($client, $client->bucket($config['storage']['bucket'])));
            default:
                throw new InvalidArgumentException('The driver specified is not supported.');
        }
    }),

    'lang' => factory(function (Container $container) {
        $config = $container->get('config');
        if (isset($config['lang'])) {
            return Lang::build($config['lang'], BASE_DIR.'resources/lang/');
        }
        return Lang::build(Lang::recognize(), BASE_DIR.'resources/lang/');
    }),

    'view' => factory(function (Container $container) {
        return ViewFactory::createAppInstance($container);
    }),
]);

$app = Bridge::create($builder->build());
$app->setBasePath(substr($config['base_path'], 0, -1));

if (!$config['debug']) {
    $app->getRouteCollector()->setCacheFile(BASE_DIR.'resources/cache/routes.cache.php');
}

$app->add(InjectMiddleware::class);
$app->add(RememberMiddleware::class);

// Permanently redirect paths with a trailing slash to their non-trailing counterpart
$app->add(function (Request $request, RequestHandler $handler) use (&$config) {
    $uri = $request->getUri();
    $path = $uri->getPath();

    if ($path !== $config['base_path'] && substr($path, -1) === '/') {
        // permanently redirect paths with a trailing slash
        // to their non-trailing counterpart
        $uri = $uri->withPath(substr($path, 0, -1));

        if ($request->getMethod() == 'GET') {
            $response = new Response();
            return $response->withStatus(301)
                ->withHeader('Location', (string)$uri);
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
$errorMiddleware = $app->addErrorMiddleware($config['debug'], true, true);
$errorMiddleware->setDefaultErrorHandler($errorHandler);

// Load the application routes
require BASE_DIR.'app/routes.php';

return $app;