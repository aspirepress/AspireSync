# AspireSync

AspireSync is designed to enumerate and download the plugins and themes stored in the WordPress SVN repository and Content Delivery Network.

## Features

* Download themes and plugins from the WordPress .org repository to the local filesystem or S3 storage.
* Stores metadata and other information about every version of every plugin in sqlite.
* Can export metadata as newline-delimited json for consumption by [AspireCloud](https://github.com/aspiresync/AspireCloud). 
* Can download all versions of plugins and themes, or just the latest version.
* Handles closed and not found plugins/themes, preventing further download attempts for them.
* Incremental updates, syncing only those items that have updated in subversion since the last sync.
* Runs downloads in parallel tasks (20 max) to allow for speedy download of assets.

## Quick Start

```shell
make
bin/console list sync
```

## Configuration

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
