# AspireSync

AspireSync is designed to enumerate and download the plugins and themes stored in the WordPress SVN repository and
Content Delivery Network.

## Features

* Download themes and plugins from the WordPress .org repository and upload them to local or S3 storage.
* Store metadata and other information about every version of every plugin.
* Download the latest version of a plugin/theme, or all versions, or a subset.
* Handles closed and not found plugins/themes.
* Updates only those items that are updated in SVN, so you don't have to pull the entire dataset.
* Supports Postgres out of the box for metadata retention (SQLite coming eventually)
* Runs downloads in parallel tasks (24 max) to allow for speedy download of assets.

## Quick Start

AspireSync requires a running instance of [AspireCloud](https://github.com/AspirePress/AspireCloud).  Simply clone it and start it up with `make init`.

Once you have AspireCloud started, start the AspireSync service by running `make init`

Get a shell by typing `make run`.  Once in the shell, type `aspiresync` without arguments for help.

## Configuration

### Database

AspireSync makes use of a Postgres database for storing information about each asset it pulls. These configuration
values are **required**.

| Env Variable | Description                                    |
|--------------|------------------------------------------------|
| DB_USER      | The username for the database                  |
| DB_PASS      | The password for the database                  |
| DB_NAME      | Name of the database to insert the information |
| DB_HOST      | The hostname of the database                   |
| DB_SCHEMA    | The schema in the database to use              |

### Uploads

AspireSync also uploads files to a location of your choosing, either in S3(-compatible) storage or local storage
somewhere on disk.

You can configure the following environment variables to determine where uploads are placed.

| Env Variable          | Description                                                                                                                                                  |
|-----------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------|
| UPLOAD_ASSETS_ADAPTER | Which adapter to use for file system uploads (current options are `upload_local_filesystem` or `upload_aws_s3`)                                              |
| UPLOAD_DIR            | The fully qualified directory path to upload files into if UPLOAD_ASSETS_ADAPTER is set to `upload_local_filesystem`                                         |
| AWS_BUCKET            | The bucket to use for Amazon S3 uploads                                                                                                                      |
| AWS_REGION            | Defaults to `us-east-2`, this is the Amazon region to use                                                                                                    |
| AWS_S3_ENDPOINT       | This is a hard-coded bucket endpoint, useful for S3-compatible storage. This is not a required parameter and will default to `null`                          |
| AWS_S3_KEY            | For users of access keys with AWS IAM, this is the access key. If this is not provided, the client will attempt authentication through an attached IAM role. |
| AWS_S3_SECRET         | The companion secret to the `AWS_S3_KEY`; this is optional and if omitted will default to IAM role authentication.                                           |

## Usage

This package ships with the following commands;

| Command               | Arguments                          | Options                                        | Description                                                                                           |
|-----------------------|------------------------------------|------------------------------------------------|-------------------------------------------------------------------------------------------------------|
| `plugins:meta`        | None                               | [--update-all or -u] [--plugins]               | Downloads metadata for plugins.                                                                       |
| `themes:meta`         | None                               | [--update-all or -u] [--themes]                | Downloads metadata for themes.                                                                        |
| `plugins:download`    | [num-to-pull=latest]               | [--plugins [--force-download or -f]            | Downloads any plugins outstanding from the last time the command was run. Defaults to latest version, |
| `themes:download`     | [num-to-pull=latest]               | [--themes] [--force-download or  -f]           | Downloads any themes outstanding from the last time the command was run. Defaults to latest version.  |
| `util:upload`         | action<plugins, themes>            | [--slugs] [--limit] [--offset] [--clean or -c] | Uploads any downloaded plugins/themes to your file system (right now supports S3).                    |
| `util:clean`          | None                               | None                                           | Cleans the data directory of any files that remain.                                                   |  
| `run:all`             | [asset-type<all, plugins, themes>] | None                                           | Runs the four commands for themes/plugins or both. A shortcut to a full run of the downloader.        |

## License

The AspireSync tool is licensed under the MIT license. You may use and distribute it freely.

The code that this tool is designed to download is likely licensed under the GPL. Please respect that license. However,
because this tool does not implement GPL components, it is not required to be licensed under the GPL.
