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

### Change app install name
Add to the `config.php` file an array element like this:
```php
return array(
    'app_name' => 'This line will overwrite "XBackBone"',
    ...
);
```

# Clients Configuration

## ShareX Configuration
Once you are logged in, just go in your profile settings and download the ShareX config file for your account.

## Linux/Mac Support
Since ShareX does not support Linux, XBackBone can generate a script that allows you to share an item from any tool:
+ Login into your account
+ Navigate to your profile and download the Linux script for your account.
+ Place the script where you want (ex. in your user home: `/home/<username>`).
+ Add execution permissions (`chmod +x xbackbone_uploader_XXX.sh`)
+ Run the script for the first time to create the desktop entry: `./xbackbone_uploader_XXX.sh -desktop-entry`.

Now, to upload a media, just use the right click on the file > "Open with ..." > search XBackBone Uploader (XXX) in the app list.
You can use this feature in combination with tools like [Flameshot](https://github.com/lupoDharkael/flameshot), just use the "Open with ..." button once you have done the screenshot.

The script requires `xclip`, `curl`, and `notify-send` on a desktop distribution.

*Note: XXX is the username of your XBackBone account.*