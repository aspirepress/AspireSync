# AspireSync

AspireSync is designed to enumerate and download the plugins and themes stored in the WordPress SVN repository and Content Delivery Network.

## Features

* Download themes and plugins from the WordPress .org repository and upload them to local or S3 storage.
* Store metadata and other information about every version of every plugin.
* Download the latest version of a plugin/theme, or all versions, or a subset.
* Handles closed and not found plugins/themes.
* Updates only those items that are updated in SVN, so you don't have to pull the entire dataset.
* Supports Postgres out of the box for metadata retention (SQLite coming eventually)
* Runs downloads in parallel tasks (24 max) to allow for speedy download of assets.

## Quick Start

AspireSync requires a running instance of [AspireCloud](https://github.com/AspirePress/AspireCloud). Simply clone it and start it up with
`make init`.

Once you have AspireCloud started, start the AspireSync service by running
`make init`

Get a shell by typing
`make run`. Once in the shell, type
`aspiresync` without arguments for help.

## Configuration

### Uploads

AspireSync places download files in a location of your choosing, either in S3(-compatible) storage or local storage somewhere on disk.

You can configure the following environment variables to determine where uploads are placed.

| Env Variable         | Description                                                                                                              |
|----------------------|--------------------------------------------------------------------------------------------------------------------------|
| DOWNLOADS_FILESYSTEM | One of `local` (default) or `s3`.                                                                                        |
| DOWNLOADS_DIR        | The destination for downloaded files if using `upload_local_filesystem`<br>Relative paths are from the `data/` directory |
| S3_BUCKET            | The S3 bucket to upload to                                                                                               |                                                                                                                                   
| S3_REGION            | AWS region to use (default `us-east-2`)                                                                                  |
| S3_ENDPOINT          | The S3 API endpoint, only required if using S3 storage from a provider other than AWS                                    |
| S3_KEY               | The AWS access_key_id for S3.  Optional if host/container roles are in effect.                                           |
| S3_SECRET            | The companion secret to the `S3_KEY`;   Optional if host/container roles are in effect.                                  |
