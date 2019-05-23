<?php
return [
	'base_url' => 'http://localhost',
	'db' => [
		'connection' => 'sqlite',
		'dsn' => 'resources/database/xbackbone.db',
		'username' => null,
		'password' => null,
	],
	'storage' => [
		'driver' => 'local',
		'path' => './storage',
	],
];
