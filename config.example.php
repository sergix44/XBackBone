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
    // SMTP configuration (optional - if not configured, PHP's mail() function will be used)
    // 'mail' => [
    //     'driver' => 'smtp',        // 'smtp' or 'mail' (default: 'mail')
    //     'host' => 'smtp.example.com',
    //     'port' => 587,
    //     'encryption' => 'tls',     // 'tls', 'ssl', or '' for none
    //     'username' => 'your-username',
    //     'password' => 'your-password',
    //     'from' => 'noreply@example.com',
    //     'from_name' => 'XBackBone',
    // ],
];
