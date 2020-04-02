---
layout: default
title: Common Issues
nav_order: 7
---

# Common Issues

#### Error 404 after installation
If you have apache, check if it's reading the file `.htaccess` and the `mod_rewrite` is enabled.

#### [Discord, Telegram, ...] is not showing the image/video preview of the link.
If you have Cloudflare enabled, check if it's blocking bots. If this function is enabled the Discord bot, Telebot, etc that fetch the preview will be blocked.

#### How to increase the max file size?
Increase the post_max_size and upload_max_filesize in your php.ini