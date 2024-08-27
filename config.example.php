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
    // Sample config for Azure AD oauth
    // 'oauth' => [
    //     'title' => "Login with Azure AD",
    //     'enabled' => true,  // Set to true to enable OAuth login
    //     'clientId' => '[client_id]',    // Azure AD Application (client) ID
    //     'clientSecret' => '[client_secret]',  // Azure AD Client Secret
    //     'redirectUri' => 'http://localhost:8080/login/oauth/callback',  // Redirect URI configured in Azure
    //     'urlAuthorize' => 'https://login.microsoftonline.com/[tenant_id]/oauth2/v2.0/authorize',
    //     'urlAccessToken' => 'https://login.microsoftonline.com/[tenant_id]/oauth2/v2.0/token',
    //     'urlResourceOwnerDetails' => '',
    //     'scopes' => ['openid', 'profile', 'email', 'User.Read'],  // Adjust scopes as needed
    //     'defaultEndPointVersion' => '2.0',  // Use v2.0 endpoint
    //     'tenant_id' => '[tenant_id]', // Azure AD tenant_id
    // ],
];
