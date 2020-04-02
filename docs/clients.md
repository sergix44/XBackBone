---
layout: default
title: Clients
nav_order: 5
---

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