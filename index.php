<?php

((PHP_MAJOR_VERSION >= 7 && PHP_MINOR_VERSION >= 2) || PHP_MAJOR_VERSION > 7) ?: die('Sorry, PHP 7.2 or above is required to run XBackBone.');
require __DIR__.'/vendor/autoload.php';

define('BASE_DIR', realpath(__DIR__).DIRECTORY_SEPARATOR);
define('PLATFORM_VERSION', json_decode(file_get_contents('composer.json'))->version);
define('CONFIG_FILE', BASE_DIR.'config.php');

$app = require __DIR__.'/bootstrap/app.php';
$app->run();
