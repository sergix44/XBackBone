<?php
// Auth routes
$app->group('', function () {
	$this->get('/home[/page/{page}]', \App\Controllers\DashboardController::class . ':home')->setName('home');
	$this->get('/system/deleteOrphanFiles', \App\Controllers\AdminController::class . ':deleteOrphanFiles')->add(\App\Middleware\AdminMiddleware::class)->setName('system.deleteOrphanFiles');
	$this->get('/system/themes', \App\Controllers\ThemeController::class . ':getThemes')->add(\App\Middleware\AdminMiddleware::class)->setName('theme');
	$this->post('/system/theme/apply', \App\Controllers\ThemeController::class . ':applyTheme')->add(\App\Middleware\AdminMiddleware::class)->setName('theme.apply');
	$this->get('/system', \App\Controllers\AdminController::class . ':system')->add(\App\Middleware\AdminMiddleware::class)->setName('system');

	$this->group('', function () {
		$this->get('/users[/page/{page}]', \App\Controllers\UserController::class . ':index')->setName('user.index');
		$this->get('/user/create', \App\Controllers\UserController::class . ':create')->setName('user.create');
		$this->post('/user/create', \App\Controllers\UserController::class . ':store')->setName('user.store');
		$this->get('/user/{id}/edit', \App\Controllers\UserController::class . ':edit')->setName('user.edit');
		$this->post('/user/{id}', \App\Controllers\UserController::class . ':update')->setName('user.update');
		$this->get('/user/{id}/delete', \App\Controllers\UserController::class . ':delete')->setName('user.delete');
	})->add(\App\Middleware\AdminMiddleware::class);

	$this->get('/profile', \App\Controllers\UserController::class . ':profile')->setName('profile');
	$this->post('/profile/{id}', \App\Controllers\UserController::class . ':profileEdit')->setName('profile.update');
	$this->post('/user/{id}/refreshToken', \App\Controllers\UserController::class . ':refreshToken')->setName('refreshToken');
	$this->get('/user/{id}/config/sharex', \App\Controllers\UserController::class . ':getShareXconfigFile')->setName('config.sharex');
	$this->get('/user/{id}/config/script', \App\Controllers\UserController::class . ':getUploaderScriptFile')->setName('config.script');

	$this->post('/upload/{id}/publish', \App\Controllers\UploadController::class . ':togglePublish')->setName('upload.publish');
	$this->post('/upload/{id}/unpublish', \App\Controllers\UploadController::class . ':togglePublish')->setName('upload.unpublish');
	$this->get('/upload/{id}/raw', \App\Controllers\UploadController::class . ':getRawById')->add(\App\Middleware\AdminMiddleware::class)->setName('upload.raw');
	$this->post('/upload/{id}/delete', \App\Controllers\UploadController::class . ':delete')->setName('upload.delete');

})->add(\App\Middleware\AuthMiddleware::class);

$app->get('/', \App\Controllers\DashboardController::class . ':redirects')->setName('root');
$app->get('/login', \App\Controllers\LoginController::class . ':show')->setName('login.show');
$app->post('/login', \App\Controllers\LoginController::class . ':login')->setName('login');
$app->map(['GET', 'POST'], '/logout', \App\Controllers\LoginController::class . ':logout')->setName('logout');

$app->post('/upload', \App\Controllers\UploadController::class . ':upload')->setName('upload');

$app->get('/{userCode}/{mediaCode}', \App\Controllers\UploadController::class . ':show')->setName('public');
$app->get('/{userCode}/{mediaCode}/delete/{token}', \App\Controllers\UploadController::class . ':show')->setName('public.delete.show');
$app->post('/{userCode}/{mediaCode}/delete/{token}', \App\Controllers\UploadController::class . ':deleteByToken')->setName('public.delete');
$app->get('/{userCode}/{mediaCode}/raw', \App\Controllers\UploadController::class . ':showRaw')->setName('public.raw')->setOutputBuffering(false);
$app->get('/{userCode}/{mediaCode}/download', \App\Controllers\UploadController::class . ':download')->setName('public.download')->setOutputBuffering(false);