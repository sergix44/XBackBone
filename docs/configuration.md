---
layout: default
title: Configuration
nav_order: 3
---
# Platform Configuration

## Web Server Configuration
*Apache need the `mod_rewrite` extension to make XBackBone work properly*.

If you do not use Apache, or the Apache `.htaccess` is not enabled, set your web server so that the `static/` folder is the only one accessible from the outside, otherwise even private uploads and logs will be accessible!

You can find an example configuration [`nginx.conf`](https://github.com/SergiX44/XBackBone/blob/master/nginx.conf) in the project repository.

## Maintenance Mode
Maintenance mode is automatically enabled during an upgrade using the upgrade manager. You can activate it manually by adding in the configuration file this:

```php
return array(
    ...
    'maintenance' => true,
);
```


## Enable LDAP Authentication

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

By activating this function, it will not be possible for users logging in via ldap to reset the password from the application (for obvious reasons), and it will also be possible to bring existing users under LDAP authentication.

## Change app install name
Add to the `config.php` file an array element like this:
```php
return array(
    'app_name' => 'This line will overwrite "XBackBone"',
    ...
);
```