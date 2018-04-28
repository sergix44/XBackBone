# XBackBone 📤
XBackBone is a simple, self-hosted, lightweight PHP backend for the instant sharing tool ShareX. It supports uploading and displaying images, GIF, video, code, formatted text, and file downloading and uploading. Also have a web UI with multi user managemant and past uploads history.

## Features

+ Supports every upload type from ShareX.
+ User management, multi user features.
+ Public and private uploads.
+ Web UI for each user.
+ Logging system.

## How to Install
XBackBone require PHP >= `7.1`, the composer package manager and writable storage path:

+ Clone this repository in your web root folder:

```sh
git clone http://github.com/SergiX44/XBackBone .
```
+ Run a composer from your shell:

```sh
composer install --no-dev
```
+ Setup the config file:

```sh
cp config.example.php config.php
```
By default, XBackBone will use Sqlite as DB engine, and a `storage` dir in the current directory. You can leave these settings unchanged for a simple personal installation.
You must set the `base_url`, or remove it for get dynamically the url from request (not raccomanded).

```php
return [
	'base_url' => 'https://myaswesomedomain.com', // no trailing slash
	'storage_dir' => 'storage',
	'db' => [
		'connection' => 'sqlite',
		'dsn' => 'resources/database/xbackbone.db',
		'username' => null, // username and password not needed for sqlite
		'password' => null,
	]
];
```
+ Finally, run the migrate script to setup the database

```sh
php bin/migrate --install
```
+ Now just login with `admin/admin`, **be sure to change these credentials after your first login**.

## Notes
If you do not use Apache, or the Apache `.htaccess` is not enabled, set your web server so that the `static/` folder is the only one accessible from the outside, otherwise even private uploads and logs will be accessible!