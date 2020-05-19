---
layout: default
title: Configuration
nav_order: 3
---
# Configuration

## Web Server
*Apache need the `mod_rewrite` extension to make XBackBone work properly*.

If you do not use Apache, or the Apache `.htaccess` is not enabled, set your web server so that the `static/` folder is the only one accessible from the outside, otherwise even private uploads and logs will be accessible!

If you are using NGINX, you can find an example configuration [`nginx.conf`](https://github.com/SergiX44/XBackBone/blob/master/nginx.conf) in the project repository.

## Maintenance Mode
Maintenance mode is automatically enabled during an upgrade using the upgrade manager. You can activate it manually by editing the `config.php`, and adding this line:

```php
return array(
    ...
    'maintenance' => true,
);
```

## Database support

Currently, is supported `MySQL/MariaDB` and `SQLite3`.

For big installations, `MySQL/MariaDB` is recommended.

Example config:
```php
return array(
    ...,
    'db' => array (
        'connection' => 'mysql', // sqlite or mysql
        'dsn' => 'host=localhost;port=3306;dbname=xbackbone', // the path to db, if sqlite
        'username' => 'xbackbone', // null, if sqlite
        'password' => 's3cr3t', // null, if sqlite
    ),
)
```

## LDAP Authentication

Since the release 3.1, the LDAP integration can be configured.

Edit the `config.php`, and add the following lines:
```php
return array(
    ...
    'ldap' => array(
        'enabled' => true, // enable it
        'host' => 'ad.example.com', // set the ldap host
        'port' => 389, // ldap port
        'base_domain' => 'dc=example,dc=com', // the base_dn string
        'user_domain' => 'ou=Users', // the user dn string
    )
);
```

By activating this function, it will not be possible for users logging in via LDAP to reset the password from the application (for obvious reasons), and it will also be possible to bring existing users under LDAP authentication.


## Storage drivers

XBackBone supports these storage drivers (with some configuration examples):

+ Local Storage (default)
```php
return array(
    ...
    'storage' => array (
        'driver' => 'local',
        'path' => '/path/to/storage/folder',
    )
);
```

+ Amazon S3
```php
return array(
    ...
    'storage' => array (
        'driver' => 's3',
        'key' => 'the-key',
        'secret' => 'the-secret',
        'region' => 'the-region',
        'bucket' => 'bucket-name',
        'path' => 'optional/path/prefix',
    )
);
```

+ Dropbox
```php
return array(
    ...
    'storage' => array (
        'driver' => 'dropbox',
        'token' => 'the-token',
    )
);
```

+ FTP(s)
```php
return array(
    ...
    'storage' => array (
        'driver' => 'ftp',
        'host' => 'ftp.example.com',
        'port' => 21,
        'username' => 'the-username',
        'password' => 'the-password',
        'path' => 'the/prefix/path/',
        'passive' => true/false,
        'ssl' => true/false,
    )
);
```

+ Google Cloud Storage
```php
return array(
    ...
    'storage' => array (
        'driver' => 'google-cloud',
        'project_id' => 'the-project-id',
        'key_path' => 'the-key-path',
        'bucket' => 'bucket-name',
    )
);
```

+ Azure Blob Storage
```php
return array(
    ...
    'storage' => array (
        'driver' => 'azure',
        'account_name' => 'the-storage-account-name',
        'account_key' => 'the-account-key',
        'container_name' => 'container-name',
    )
);
```

## Changing themes
XBackBone supports all [bootswatch.com](https://bootswatch.com/) themes.

From the web UI:
+ Navigate to the web interface as admin -> System Menu -> Choose a theme from the dropdown.

From the CLI:
+ Run the command `php bin/theme` to see the available themes.
+ Use the same command with the argument name (`php bin/theme <THEME-NAME>`) to choose a theme.
+ If you want to revert back to the original bootstrap theme, run the command `php bin/theme default`.

*Clear the browser cache once you have applied.*

## Change app install name
Add to the `config.php` file an array element like this:
```php
return array(
    'app_name' => 'This line will overwrite "XBackBone"',
    ...
);
```