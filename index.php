<?php
(PHP_MAJOR_VERSION >= 7 && PHP_MINOR_VERSION >= 1) ?: die('Sorry, PHP 7.1 or above is required to run XBackBone.');
require __DIR__ . '/vendor/autoload.php';

define('BASE_DIR', __DIR__ . DIRECTORY_SEPARATOR);
define('PLATFORM_VERSION', json_decode(file_get_contents('composer.json'))->version);

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->run();
