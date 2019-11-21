<?php

/*
 * @copyright Copyright (c) 2019 Sergio Brighenti <sergio@brighenti.me>
 *
 * @author Sergio Brighenti <sergio@brighenti.me>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 */

use App\Controllers\AdminController;
use App\Controllers\ClientController;
use App\Controllers\DashboardController;
use App\Controllers\LoginController;
use App\Controllers\MediaController;
use App\Controllers\ThemeController;
use App\Controllers\UpgradeController;
use App\Controllers\UploadController;
use App\Controllers\UserController;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\CheckForMaintenanceMiddleware;
use Slim\Routing\RouteCollectorProxy;

global $app;
$app->group('', function (RouteCollectorProxy $group) {
    $group->get('/home[/page/{page}]', array(DashboardController::class, 'home'))->setName('home');
    $group->get('/upload', array(UploadController::class, 'webUpload'))->setName('upload.web');

    $group->group('', function (RouteCollectorProxy $group) {
        $group->get('/home/switchView', array(DashboardController::class, 'switchView'))->setName('switchView');

        $group->get('/system/deleteOrphanFiles', array(AdminController::class, 'deleteOrphanFiles'))->setName('system.deleteOrphanFiles');

        $group->get('/system/themes', array(ThemeController::class, 'getThemes'))->setName('theme');
        $group->post('/system/theme/apply', array(ThemeController::class, 'applyTheme'))->setName('theme.apply');

        $group->post('/system/lang/apply', array(AdminController::class, 'applyLang'))->setName('lang.apply');

        $group->post('/system/customHead', array(AdminController::class, 'applyCustomHead'))->setName('customHead.apply');

        $group->post('/system/upgrade', array(UpgradeController::class, 'upgrade'))->setName('system.upgrade');
        $group->get('/system/checkForUpdates', array(UpgradeController::class, 'checkForUpdates'))->setName('system.checkForUpdates');

        $group->get('/system', array(AdminController::class, 'system'))->setName('system');

        $group->get('/users[/page/{page}]', array(UserController::class, 'index'))->setName('user.index');
    })->add(AdminMiddleware::class);

    $group->group('/user', function (RouteCollectorProxy $group) {
        $group->get('/create', array(UserController::class, 'create'))->setName('user.create');
        $group->post('/create', array(UserController::class, 'store'))->setName('user.store');
        $group->get('/{id}/edit', array(UserController::class, 'edit'))->setName('user.edit');
        $group->post('/{id}', array(UserController::class, 'update'))->setName('user.update');
        $group->get('/{id}/delete', array(UserController::class, 'delete'))->setName('user.delete');
    })->add(AdminMiddleware::class);

    $group->get('/profile', array(UserController::class, 'profile'))->setName('profile');
    $group->post('/profile/{id}', array(UserController::class, 'profileEdit'))->setName('profile.update');
    $group->post('/user/{id}/refreshToken', array(UserController::class, 'refreshToken'))->setName('refreshToken');
    $group->get('/user/{id}/config/sharex', array(ClientController::class, 'getShareXConfig'))->setName('config.sharex');
    $group->get('/user/{id}/config/script', array(ClientController::class, 'getBashScript'))->setName('config.script');

    $group->post('/upload/{id}/publish', array(MediaController::class, 'togglePublish'))->setName('upload.publish');
    $group->post('/upload/{id}/unpublish', array(MediaController::class, 'togglePublish'))->setName('upload.unpublish');
    $group->get('/upload/{id}/raw', array(MediaController::class, 'getRawById'))->add(AdminMiddleware::class)->setName('upload.raw');
    $group->map(array('GET', 'POST'), '/upload/{id}/delete', array(MediaController::class, 'delete'))->setName('upload.delete');
})->add(App\Middleware\CheckForMaintenanceMiddleware::class)->add(AuthMiddleware::class);

$app->get('/', array(DashboardController::class, 'redirects'))->setName('root');
$app->get('/login', array(LoginController::class, 'show'))->setName('login.show');
$app->post('/login', array(LoginController::class, 'login'))->setName('login');
$app->map(array('GET', 'POST'), '/logout', array(LoginController::class, 'logout'))->setName('logout');

$app->post('/upload', array(UploadController::class, 'upload'))->setName('upload');

$app->get('/{userCode}/{mediaCode}', array(MediaController::class, 'show'))->setName('public');
$app->get('/{userCode}/{mediaCode}/delete/{token}', array(MediaController::class, 'show'))->setName('public.delete.show')->add(CheckForMaintenanceMiddleware::class);
$app->post('/{userCode}/{mediaCode}/delete/{token}', array(MediaController::class, 'deleteByToken'))->setName('public.delete')->add(CheckForMaintenanceMiddleware::class);
$app->get('/{userCode}/{mediaCode}/raw[.{ext}]', array(MediaController::class, 'getRaw'))->setName('public.raw');
$app->get('/{userCode}/{mediaCode}/download', array(MediaController::class, 'download'))->setName('public.download');
