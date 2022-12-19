<?php

return [
    'base_url' => 'https://localhost', // no trailing slash
    'db'       => [
        'connection' => 'sqlite',
        'dsn'        => realpath(__DIR__).'/resources/database/xbackbone.db',
        'username'   => null,
        'password'   => null,
    ],
    'storage' => [
        'driver' => 'local',
        'path'   => realpath(__DIR__).'/storage',
    ],
];
