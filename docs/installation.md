---
layout: default
title: Installation
nav_order: 2
---

# Installation

### Prerequisites
XBackBone require PHP >= `7.3`, with installed the required extensions:
+ `php-sqlite3` for SQLite.
+ `php-mysql` for MariaDB/MySQL.
+ `php-gd` image manipualtion library.
+ `php-json` json file support.
+ `php-intl` internationalization functions.
+ `php-fileinfo` file related functions.
+ `php-zip` compressed files related functions.
+ (optional) `php-ftp` to use the FTP remote storage driver.
+ (optional) `php-ldap` to use LDAP authentication.

## Web installation
+ Download latest release from GitHub: [Latest Release](https://github.com/SergiX44/XBackBone/releases/latest)
+ Extract the release zip to your document root.
+ Navigate to the webspace root (ex. `http://example.com/xbackbone`, this should auto redirect your browser to the install page `http://example.com/xbackbone/install/`)
+ Follow the instructions.

For futher and advanced configurations, see the [configuration page](configuration.md).

## Manual installation
+ Download latest release from GitHub: [Latest Release](https://github.com/SergiX44/XBackBone/releases/latest)
+ Extract the release zip to your document root.
+ Copy and edit the config file:
```sh
cp config.example.php config.php && nano config.php
```
By default, XBackBone will use Sqlite3 as DB engine, and a `storage` dir in the main directory. You can leave these settings unchanged for a simple personal installation.
You must set the `base_url`, or remove it for get dynamically the url from request (not recommended).

```php
return [
	'base_url' => 'https://example.com', // no trailing slash
	'storage' => [
		'driver' => 'local',
		'path' => 'absolute/path/to/storage',
	],
	'db' => [
		'connection' => 'sqlite', // current support for sqlite and mysql
		'dsn' => 'absolute/path/to/resources/database/xbackbone.db', // if sqlite should be an absolute path
		'username' => null, // username and password not needed for sqlite
		'password' => null,
	]
];
```
+ Finally, run the migrate script to setup the database

```sh
php bin/migrate --install
```
+ Delete the `/install` directory.
+ Now just login with `admin/admin`, **be sure to change these credentials after your first login**.


For futher and advanced configurations, see the [configuration page](configuration.md).

## Docker deployment
Alternatively, a docker container is available.

[Docker container](https://fleet.linuxserver.io/image?name=linuxserver/xbackbone){: .btn .btn-purple }
