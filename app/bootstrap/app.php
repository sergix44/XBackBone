<?php

use Illuminate\Foundation\Application;

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
    putenv('APP_ROOT='.APP_ROOT);
}
putenv('COMPOSER_VENDOR_DIR='.APP_ROOT.'/vendor');

/** @var Application $app */
$app = require APP_ROOT.'/vendor/xbb/core/bootstrap/app.php';

return $app->usePublicPath(APP_ROOT.'/public')
    ->useEnvironmentPath(APP_ROOT)
    ->useStoragePath(APP_ROOT.'/storage')
    ->useBootstrapPath(__DIR__);
