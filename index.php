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

(PHP_MAJOR_VERSION >= 7 && PHP_MINOR_VERSION >= 1) ?: die('Sorry, PHP 7.1 or above is required to run XBackBone.');
require __DIR__.'/vendor/autoload.php';

define('BASE_DIR', realpath(__DIR__).DIRECTORY_SEPARATOR);
define('PLATFORM_VERSION', json_decode(file_get_contents('composer.json'))->version);

$app = require_once __DIR__.'/bootstrap/app.php';
$app->run();
