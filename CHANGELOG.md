# Changelog

## Unreleased

* [CHANGED] Major internal refactor and reorganization of commands.
* [CHANGED] Downloads go directly to s3 
* [CHANGED] Use sqlite for all locally cached data.

## 1.0 Alpha 5

* [ADDED] Docker Compose support.
* [CHANGED] Added hashing for files to upload and storage
* [ADDED] Created the --download-all/-d options to plugins and themes to ignore created date and download all unprocessed plugins
* [FIXED] Resolved an issue where the item was missing from the theme command to mark an item not found.

## 1.0 Alpha 4

* [CHANGED] Renamed the project from AssetGrabber to AspireSync to keep with our branding.
* [FIXED] Removed thrown exception in PluginDownloadFromWpService when the Client got a non-200 response.
* [CHANGED] Renamed executable from `assetgrabber` to `aspiresync`.

## 1.0 Alpha 3

* [FIXED] Resolved issue that left the internal download command unable to be run.
