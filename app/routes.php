<?php
// Auth routes
use App\Controllers\AdminController;
use App\Controllers\DashboardController;
use App\Controllers\LoginController;
use App\Controllers\ThemeController;
use App\Controllers\UpgradeController;
use App\Controllers\UploadController;
use App\Controllers\UserController;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\CheckForMaintenanceMiddleware;

$app->group('', function () {
	$this->get('/home[/page/{page}]', DashboardController::class . ':home')->setName('home');

	$this->group('', function () {
		$this->get('/home/switchView', DashboardController::class . ':switchView')->setName('switchView');

		$this->get('/system/deleteOrphanFiles', AdminController::class . ':deleteOrphanFiles')->setName('system.deleteOrphanFiles');

		$this->get('/system/themes', ThemeController::class . ':getThemes')->setName('theme');
		$this->post('/system/theme/apply', ThemeController::class . ':applyTheme')->setName('theme.apply');

		$this->post('/system/lang/apply', AdminController::class . ':applyLang')->setName('lang.apply');

		$this->post('/system/upgrade', UpgradeController::class . ':upgrade')->setName('system.upgrade');
		$this->get('/system/checkForUpdates', UpgradeController::class . ':checkForUpdates')->setName('system.checkForUpdates');

		$this->get('/system', AdminController::class . ':system')->setName('system');

		$this->get('/users[/page/{page}]', UserController::class . ':index')->setName('user.index');
	})->add(AdminMiddleware::class);

	$this->group('/user', function () {

		$this->get('/create', UserController::class . ':create')->setName('user.create');
		$this->post('/create', UserController::class . ':store')->setName('user.store');
		$this->get('/{id}/edit', UserController::class . ':edit')->setName('user.edit');
		$this->post('/{id}', UserController::class . ':update')->setName('user.update');
		$this->get('/{id}/delete', UserController::class . ':delete')->setName('user.delete');
	})->add(AdminMiddleware::class);

	$this->get('/profile', UserController::class . ':profile')->setName('profile');
	$this->post('/profile/{id}', UserController::class . ':profileEdit')->setName('profile.update');
	$this->post('/user/{id}/refreshToken', UserController::class . ':refreshToken')->setName('refreshToken');
	$this->get('/user/{id}/config/sharex', UserController::class . ':getShareXconfigFile')->setName('config.sharex');
	$this->get('/user/{id}/config/script', UserController::class . ':getUploaderScriptFile')->setName('config.script');

	$this->post('/upload/{id}/publish', UploadController::class . ':togglePublish')->setName('upload.publish');
	$this->post('/upload/{id}/unpublish', UploadController::class . ':togglePublish')->setName('upload.unpublish');
	$this->get('/upload/{id}/raw', UploadController::class . ':getRawById')->add(AdminMiddleware::class)->setName('upload.raw');
	$this->post('/upload/{id}/delete', UploadController::class . ':delete')->setName('upload.delete');

})->add(App\Middleware\CheckForMaintenanceMiddleware::class)->add(AuthMiddleware::class);

$app->get('/', DashboardController::class . ':redirects')->setName('root');
$app->get('/login', LoginController::class . ':show')->setName('login.show');
$app->post('/login', LoginController::class . ':login')->setName('login');
$app->map(['GET', 'POST'], '/logout', LoginController::class . ':logout')->setName('logout');

$app->post('/upload', UploadController::class . ':upload')->setName('upload');

$app->get('/{userCode}/{mediaCode}', UploadController::class . ':show')->setName('public');
$app->get('/{userCode}/{mediaCode}/delete/{token}', UploadController::class . ':show')->setName('public.delete.show')->add(CheckForMaintenanceMiddleware::class);
$app->post('/{userCode}/{mediaCode}/delete/{token}', UploadController::class . ':deleteByToken')->setName('public.delete')->add(CheckForMaintenanceMiddleware::class);
$app->get('/{userCode}/{mediaCode}/raw', UploadController::class . ':showRaw')->setName('public.raw')->setOutputBuffering(false);
$app->get('/{userCode}/{mediaCode}/download', UploadController::class . ':download')->setName('public.download')->setOutputBuffering(false);