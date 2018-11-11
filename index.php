<?php
require __DIR__ . '/vendor/autoload.php';

define('PLATFORM_VERSION', json_decode(file_get_contents('composer.json'))->version);

require 'bootstrap/app.php';

$app->run();
