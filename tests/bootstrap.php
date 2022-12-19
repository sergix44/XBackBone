<?php

define('BASE_DIR', realpath(__DIR__.'/../').DIRECTORY_SEPARATOR);
define('PLATFORM_VERSION', json_decode(file_get_contents(BASE_DIR.'composer.json'))->version);
define('CONFIG_FILE', BASE_DIR.'tests/config.test.php');

ob_start();
