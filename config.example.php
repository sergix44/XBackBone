<?php
return [
    'base_url' => 'https://localhost', // no trailing slash
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
