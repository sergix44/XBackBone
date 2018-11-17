<?php
// Auth routes
$app->group('', function () {
	$this->get('/home[/page/{page}]', \App\Controllers\DashboardController::class . ':home');
	$this->get('/system', \App\Controllers\DashboardController::class . ':system')->add(\App\Middleware\AdminMiddleware::class);
	$this->get('/system/themes', \App\Controllers\DashboardController::class . ':getThemes')->add(\App\Middleware\AdminMiddleware::class);
	$this->post('/system/theme/apply', \App\Controllers\DashboardController::class . ':applyTheme')->add(\App\Middleware\AdminMiddleware::class);

	$this->group('', function () {
		$this->get('/users[/page/{page}]', \App\Controllers\UserController::class . ':index');
		$this->get('/user/create', \App\Controllers\UserController::class . ':create');
		$this->post('/user/create', \App\Controllers\UserController::class . ':store');
		$this->get('/user/{id}/edit', \App\Controllers\UserController::class . ':edit');
		$this->post('/user/{id}', \App\Controllers\UserController::class . ':update');
		$this->get('/user/{id}/delete', \App\Controllers\UserController::class . ':delete');
	})->add(\App\Middleware\AdminMiddleware::class);

	$this->get('/profile', \App\Controllers\UserController::class . ':profile');
	$this->post('/profile/{id}', \App\Controllers\UserController::class . ':profileEdit');
	$this->post('/user/{id}/refreshToken', \App\Controllers\UserController::class . ':refreshToken');
	$this->get('/user/{id}/config/sharex', \App\Controllers\UserController::class . ':getShareXconfigFile');

	$this->post('/upload/{id}/publish', \App\Controllers\UploadController::class . ':togglePublish');
	$this->post('/upload/{id}/unpublish', \App\Controllers\UploadController::class . ':togglePublish');
	$this->get('/upload/{id}/raw', \App\Controllers\UploadController::class . ':getRawById')->add(\App\Middleware\AdminMiddleware::class);
	$this->post('/upload/{id}/delete', \App\Controllers\UploadController::class . ':delete');

})->add(\App\Middleware\AuthMiddleware::class);

$app->get('/', \App\Controllers\DashboardController::class . ':redirects');
$app->get('/login', \App\Controllers\LoginController::class . ':show');
$app->post('/login', \App\Controllers\LoginController::class . ':login');
$app->map(['GET', 'POST'], '/logout', \App\Controllers\LoginController::class . ':logout');

$app->post('/upload', \App\Controllers\UploadController::class . ':upload');

$app->get('/{userCode}/{mediaCode}', \App\Controllers\UploadController::class . ':show');
$app->get('/{userCode}/{mediaCode}/delete/{token}', \App\Controllers\UploadController::class . ':show');
$app->post('/{userCode}/{mediaCode}/delete/{token}', \App\Controllers\UploadController::class . ':deleteByToken');
$app->get('/{userCode}/{mediaCode}/raw', \App\Controllers\UploadController::class . ':showRaw');
$app->get('/{userCode}/{mediaCode}/download', \App\Controllers\UploadController::class . ':download');