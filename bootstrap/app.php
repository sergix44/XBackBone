<?php

use App\Database\DB;

$config = array_replace_recursive([
	'app_name' => 'XBackBone',
	'base_url' => isset($_SERVER['HTTPS']) ? 'https://' . $_SERVER['HTTP_HOST'] : 'http://' . $_SERVER['HTTP_HOST'],
	'storage_dir' => 'storage',
	'debug' => false,
	'db' => [
		'connection' => 'sqlite',
		'dsn' => 'resources/database/xbackbone.db',
		'username' => null,
		'password' => null,
	],
], require 'config.php');

Flight::set('flight.base_url', $config['base_url']);
Flight::set('flight.log_errors', false);
Flight::set('config', $config);

DB::setDsn($config['db']['connection'] . ':' . $config['db']['dsn'], $config['db']['username'], $config['db']['password']);

Flight::register('view', 'Twig_Environment',
	[new Twig_Loader_Filesystem('resources/templates'),
		[
			'cache' => 'resources/cache',
			'autoescape' => 'html',
			'debug' => $config['debug'],
			'auto_reload' => $config['debug'],
		]
	]
);

Flight::register('redirectBack', function () {
	Flight::redirect(Flight::request()->referrer);
});

Flight::map('render', function (string $template, array $data = []) use (&$config) {
	Flight::view()->addGlobal('config', $config);
	Flight::view()->addGlobal('request', Flight::request());
	Flight::view()->addGlobal('alerts', App\Web\Session::getAlert());
	Flight::view()->addGlobal('session', App\Web\Session::all());
	Flight::view()->addGlobal('PLATFORM_VERSION', PLATFORM_VERSION);
	Flight::view()->display($template, $data);
});

Flight::map('error', function (Exception $exception) {
	if ($exception instanceof \App\Exceptions\AuthenticationException) {
		Flight::redirect('/login');
		return;
	}

	if ($exception instanceof \App\Exceptions\UnauthorizedException) {
		Flight::response()->status(403);
		Flight::render('errors/403.twig');
		return;
	}

	if ($exception instanceof \App\Exceptions\NotFoundException) {
		Flight::response()->status(404);
		Flight::render('errors/404.twig');
		return;
	}

	Flight::response()->status(500);
	\App\Web\Log::critical('Fatal error during app execution', [$exception->getTraceAsString()]);
	Flight::render('errors/500.twig', ['exception' => $exception]);
});

Flight::map('notFound', function () {
	Flight::render('errors/404.twig');
});

require 'app/routes.php';