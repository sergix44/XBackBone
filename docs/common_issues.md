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

If uploads fail or stop part-way (especially for files around 1 GB or larger), it is usually due to PHP, web server, or reverse-proxy limits.

1. **Update PHP limits (`php.ini`)**

   Make sure these values are high enough for the files you want to upload:

   ```ini
   upload_max_filesize = 2G
   post_max_size = 2G
   max_execution_time = 600
   max_input_time = 600

