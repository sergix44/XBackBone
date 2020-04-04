---
layout: default
title: Common Issues
nav_order: 7
---

# Common Issues

### Error 404 after installation
If you have Apache web server, check if it's reading the file `.htaccess` and the module `mod_rewrite` is enabled.

<hr>

### [Discord, Telegram, ...] is not showing the image/video preview of the link.
If you use Cloudflare, check if the setting that blocks access to bots is active. If enabled, the bots of the respective platforms will not be able to access to download the preview.

<hr>

### How to increase the upload max file size?
Increase the `post_max_size` and `upload_max_filesize` in your `php.ini`.