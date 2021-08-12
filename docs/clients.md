---
layout: default
title: Client Configuration
nav_order: 5
---

# Clients Configuration

## ShareX (Windows)
Once you are logged in, just go in your profile settings and download the ShareX config file for your account.

## Screencloud (Windows, Mac and Linux)
Once you are logged in, go in your profile account and click on the Screencloud button.
Now open Screencloud, open "Preferences" > "Online Services" tab > click "More Services" > and "Install from URL"
and paste the URL copied from XBackBone, and all should work out-of-the-box.

If for whatever reason you need to change the instance url or the token, just edit the settings of the XBackBone plugin.

## MagicCap (Mac and Linux)
MagicCap supports the same file format used by ShareX.

Just download the ShareX config file from your profile, and then on MagicCap open the Preferences > Uploader settings and choose ShareX.
Set the path to the file you have downloaded, and you are good to go!

## uPic (Mac)
This tool does not support plugins or custom configuration, but you can configure it manually:
In preferences, you should add "Custom" host and configure it as follows:
- **API URL:** Your instance upload url, like `http://example.com/upload`
- **Request method:** POST
- **File field:** file
- **URL Path:** ["url"]
- In "Other fields", in the body section, you should add the field `token`, with your upload token.
- In "Other fields", in the headers section, you should add the field `Content-Type`, with the value `application/x-www-form-urlencoded`.

## Bash Script (Linux, Mac, WSL)
XBackBone can generate a script that allows you to share an item from any tool, even headless servers:
+ Login into your account
+ Navigate to your profile and download the Linux script for your account.
+ Place the script where you want (ex. in your user home: `/home/<username>`).
+ Add execution permissions (`chmod +x xbackbone_uploader_XXX.sh`)
+ Run the script for the first time to create the desktop entry: `./xbackbone_uploader_XXX.sh -desktop-entry`.

Now, to upload a media, just use the right click on the file > "Open with ..." > search XBackBone Uploader (XXX) in the app list.
You can use this feature in combination with tools like [Flameshot](https://github.com/lupoDharkael/flameshot), just use the "Open with ..." button once you have done the screenshot.

The script requires `xclip`, `curl`, and `notify-send` on a desktop distribution.

*Note: XXX is the username of your XBackBone account.*
