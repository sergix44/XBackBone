<?php

return [
    'base_path' => 'http://localhost',
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