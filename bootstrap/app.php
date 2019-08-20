<?php

use App\Database\DB;
use App\Exceptions\MaintenanceException;
use App\Exceptions\UnauthorizedException;
use App\Web\Lang;
use App\Web\Session;
use Aws\S3\S3Client;
use Google\Cloud\Storage\StorageClient;
use League\Flysystem\Adapter\Ftp as FtpAdapter;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Slim\App;
use Slim\Container;
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Uri;
use Slim\Views\Twig;
use Spatie\Dropbox\Client as DropboxClient;
use Spatie\FlysystemDropbox\DropboxAdapter;
use Superbalist\Flysystem\GoogleStorage\GoogleStorageAdapter;
use Twig\TwigFunction;

if (!file_exists('config.php') && is_dir('install/')) {
	header('Location: ./install/');
	exit();
} else if (!file_exists('config.php') && !is_dir('install/')) {
	exit('Cannot find the config file.');
}

// Load the config
$config = array_replace_recursive([
	'app_name' => 'XBackBone',
	'base_url' => isset($_SERVER['HTTPS']) ? 'https://' . $_SERVER['HTTP_HOST'] : 'http://' . $_SERVER['HTTP_HOST'],
	'displayErrorDetails' => false,
	'maintenance' => false,
	'db' => [
		'connection' => 'sqlite',
		'dsn' => BASE_DIR . 'resources/database/xbackbone.db',
		'username' => null,
		'password' => null,
	],
	'storage' => [
		'driver' => 'local',
	],
], require BASE_DIR . 'config.php');

if (!$config['displayErrorDetails']) {
	$config['routerCacheFile'] = BASE_DIR . 'resources/cache/routes.cache.php';
}

$container = new Container(['settings' => $config]);

$container['config'] = function ($container) use ($config) {
	return $config;
};

$container['logger'] = function ($container) {
	$logger = new Logger('app');

	$streamHandler = new RotatingFileHandler(BASE_DIR . 'logs/log.txt', 10, Logger::DEBUG);

	$lineFormatter = new LineFormatter("[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n", "Y-m-d H:i:s");
	$lineFormatter->includeStacktraces(true);

	$streamHandler->setFormatter($lineFormatter);

	$logger->pushHandler($streamHandler);

	return $logger;
};

$container['session'] = function ($container) {
	return new Session('xbackbone_session', BASE_DIR . 'resources/sessions');
};

$container['database'] = function ($container) use (&$config) {
	$dsn = $config['db']['connection'] === 'sqlite' ? BASE_DIR . $config['db']['dsn'] : $config['db']['dsn'];
	return new DB($config['db']['connection'] . ':' . $dsn, $config['db']['username'], $config['db']['password']);
};

$container['storage'] = function ($container) use (&$config) {

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
};

$container['lang'] = function ($container) use (&$config) {
	if (isset($config['lang'])) {
		return Lang::build($config['lang'], BASE_DIR . 'resources/lang/');
	}
	return Lang::build(Lang::recognize(), BASE_DIR . 'resources/lang/');
};

$container['view'] = function ($container) use (&$config) {
	$view = new Twig(BASE_DIR . 'resources/templates', [
		'cache' => BASE_DIR . 'resources/cache',
		'autoescape' => 'html',
		'debug' => $config['displayErrorDetails'],
		'auto_reload' => $config['displayErrorDetails'],
	]);

	// Instantiate and add Slim specific extension
	$router = $container->get('router');
	$uri = Uri::createFromEnvironment(new Environment($_SERVER));
	$view->addExtension(new Slim\Views\TwigExtension($router, $uri));

	$view->getEnvironment()->addGlobal('config', $config);
	$view->getEnvironment()->addGlobal('request', $container->get('request'));
	$view->getEnvironment()->addGlobal('alerts', $container->get('session')->getAlert());
	$view->getEnvironment()->addGlobal('session', $container->get('session')->all());
	$view->getEnvironment()->addGlobal('current_lang', $container->get('lang')->getLang());
	$view->getEnvironment()->addGlobal('PLATFORM_VERSION', PLATFORM_VERSION);

	$view->getEnvironment()->addFunction(new TwigFunction('route', 'route'));
	$view->getEnvironment()->addFunction(new TwigFunction('lang', 'lang'));
	$view->getEnvironment()->addFunction(new TwigFunction('urlFor', 'urlFor'));
	$view->getEnvironment()->addFunction(new TwigFunction('mime2font', 'mime2font'));
	$view->getEnvironment()->addFunction(new TwigFunction('queryParams', 'queryParams'));
	return $view;
};

$container['phpErrorHandler'] = function ($container) {
	return function (Request $request, Response $response, Throwable $error) use (&$container) {
		$container->logger->critical('Fatal runtime error during app execution', ['exception' => $error]);
		return $container->view->render($response->withStatus(500), 'errors/500.twig', ['exception' => $error]);
	};
};

$container['errorHandler'] = function ($container) {
	return function (Request $request, Response $response, Exception $exception) use (&$container) {

		if ($exception instanceof MaintenanceException) {
			return $container->view->render($response->withStatus(503), 'errors/maintenance.twig');
		}

		if ($exception instanceof UnauthorizedException) {
			return $container->view->render($response->withStatus(403), 'errors/403.twig');
		}

		$container->logger->critical('Fatal exception during app execution', ['exception' => $exception]);
		return $container->view->render($response->withStatus(500), 'errors/500.twig', ['exception' => $exception]);
	};
};

$container['notAllowedHandler'] = function ($container) {
	return function (Request $request, Response $response, $methods) use (&$container) {
		return $container->view->render($response->withStatus(405)->withHeader('Allow', implode(', ', $methods)), 'errors/405.twig');
	};
};

$container['notFoundHandler'] = function ($container) {
	return function (Request $request, Response $response) use (&$container) {
		$response->withStatus(404)->withHeader('Content-Type', 'text/html');
		return $container->view->render($response, 'errors/404.twig');
	};
};

$app = new App($container);

// Permanently redirect paths with a trailing slash to their non-trailing counterpart
$app->add(function (Request $request, Response $response, callable $next) {
	$uri = $request->getUri();
	$path = $uri->getPath();

	if ($path !== '/' && substr($path, -1) === '/') {
		$uri = $uri->withPath(substr($path, 0, -1));

		if ($request->getMethod() === 'GET') {
			return $response->withRedirect((string)$uri, 301);
		} else {
			return $next($request->withUri($uri), $response);
		}
	}

	return $next($request, $response);
});

// Load the application routes
require BASE_DIR . 'app/routes.php';

return $app;