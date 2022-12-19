<?php

return [
    'base_url' => 'http://localhost',
    'debug' => true,
    'db' =>
        [
            'connection' => 'sqlite',
            'dsn' => ':memory:',
        ],
    'storage' =>
        [
            'driver' => 'local',
            'path' => 'storage/test',
        ],
];
