---
layout: default
title: Upgrading
nav_order: 4
---

# Upgrading

The system updates can be applied via the web interface by an administrator, or manually via CLI.

## Self-update (since v2.5)
+ Navigate to the system page as administrator.
+ Click the check for update button, and finally the upgrade button.
+ Wait until the browser redirect to the install page.
+ Click the update button.
+ Done.


## Manual update
+ Download and extract the release zip to your document root, overwriting any file.
+ Navigate to the `/install` path (es: `http://example.com/` -> `http://example.com/install/`)
+ Click the update button.
+ Done.

## CLI update
If, for whatever reason, the web UI is not accessible, you can upgrade from CLI:
+ Download and extract the release zip to your document root, overwriting any file.
+ Run the command `php\migrate`.
+ Run the command `php\clean`.
+ Done.

### Pre-release channel

From the system page, you can also choose to check from beta/RC releases, these are NOT considered stable enough for every day use, but only for testing purposes, **take a backup before upgrading to these versions**.