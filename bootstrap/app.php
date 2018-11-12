<?php

use App\Database\DB;
use App\Web\Session;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Slim\App;
use Slim\Container;

if (!file_exists('config.php') && is_dir('install/')) {
	header('Location: /install/');
	exit();
} else if (!file_exists('config.php') && !is_dir('install/')) {
	die('Cannot find the config file.');
}

// Load the config
$config = array_replace_recursive([
	'app_name' => 'XBackBone',
	'base_url' => isset($_SERVER['HTTPS']) ? 'https://' . $_SERVER['HTTP_HOST'] : 'http://' . $_SERVER['HTTP_HOST'],
	'storage_dir' => 'storage',
	'displayErrorDetails' => false,
	'db' => [
		'connection' => 'sqlite',
		'dsn' => 'resources/database/xbackbone.db',
		'username' => null,
		'password' => null,
	],
], require 'config.php');

$container = new Container(['settings' => $config]);

$container['logger'] = function ($container) {
	$logger = new Logger('app');

	$streamHandler = new RotatingFileHandler(__DIR__ . '/../logs/log.txt', 10, Logger::DEBUG);
	$streamHandler->setFormatter(new LineFormatter("[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n", "Y-m-d H:i:s", true));

	$logger->pushHandler($streamHandler);

	return $logger;
};

// Session init
Session::init('xbackbone_session', 'resources/sessions');

// Set the database dsn
DB::setDsn($config['db']['connection'] . ':' . $config['db']['dsn'], $config['db']['username'], $config['db']['password']);

$container['database'] = function ($container) use (&$config) {
	return DB::getInstance();
};


$container['view'] = function ($container) use (&$config) {
	$view = new \Slim\Views\Twig('resources/templates', [
		'cache' => 'resources/cache',
		'autoescape' => 'html',
		'debug' => $config['displayErrorDetails'],
		'auto_reload' => $config['displayErrorDetails'],
	]);

	// Instantiate and add Slim specific extension
	$router = $container->get('router');
	$uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
	$view->addExtension(new Slim\Views\TwigExtension($router, $uri));

	$view->getEnvironment()->addGlobal('config', $config);
	$view->getEnvironment()->addGlobal('request', $container->get('request'));
	$view->getEnvironment()->addGlobal('alerts', Session::getAlert());
	$view->getEnvironment()->addGlobal('session', Session::all());
	$view->getEnvironment()->addGlobal('PLATFORM_VERSION', PLATFORM_VERSION);
	return $view;
};

$container['errorHandler'] = function ($container) {
	return function (\Slim\Http\Request $request, \Slim\Http\Response $response, $exception) use (&$container) {

		if ($exception instanceof \App\Exceptions\UnauthorizedException) {
			return $container->view->render($response->withStatus(403), 'errors/403.twig');
		}

		$container->logger->critical('Fatal error during app execution', [$exception, $exception->getTraceAsString()]);
		return $container->view->render($response->withStatus(500), 'errors/500.twig', ['exception' => $exception]);
	};
};

$container['notFoundHandler'] = function ($container) {
	return function (\Slim\Http\Request $request, \Slim\Http\Response $response) use (&$container) {
		$response->withStatus(404)->withHeader('Content-Type', 'text/html');
		return $container->view->render($response, 'errors/404.twig');
	};
};

$app = new App($container);

// Load the application routes
require 'app/routes.php';