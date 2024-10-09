# AssetGrabber

AssetGrabber is designed to enumerate and download the plugins and themes stored in the WordPress SVN repository and
Content Delivery Network.

## Prerequisites

This project depends on a Postgres database, but does not supply container for it. You can use a Postgres instance
installed locally, or use a Docker container. If you use a Docker container, you'll want to make sure that it
joins a network, and then define the network in the .env file.

1. Run `make` to build the Docker container.
2. Run `make run` to see available commands.
3. Run `make run` which will run the container.
4. Type `./assetgrabber <command>` to execute your desired command.

You'll need to copy the .env.dist file and populate it with credentials for the database. You can also define the
network that the database is on, if you're using a Docker container.

If you plan to push the containers to Amazon, you'll need to provide an endpoint for AWS and the name of your Elastic
Container Registry. You can also populate other container registries here if you would like.

## Installation (Development)

To install for development, follow these commands (once the prerequisites are met);

1. Run `make init`. This will run the build, install the Composer dependencies, and execute the migrations.
2. Run `make run-dev` to run the container.

## Configuration

| Env Variable | Description                                    |
|--------------|------------------------------------------------|
| DB_USER      | The username for the database                  |
| DB_PASS      | The password for the database                  |
| DB_NAME      | Name of the database to insert the information |
| DB_HOST      | The hostname of the database                   |
| DB_SCHEMA    | The schema in the database to use              |

## Usage

This package ships with the following commands;

| Command                 | Arguments                          | Options                                        | Description                                                                                           |
|-------------------------|------------------------------------|------------------------------------------------|-------------------------------------------------------------------------------------------------------|
| `meta:download:plugins` | None                               | [--update-all or -u] [--plugins]               | Downloads metadata for plugins.                                                                       |
| `meta:download:themes`  | None                               | [--update-all or -u] [--themes]                | Downloads metadata for themes.                                                                        |
| `meta:import:plugins`   | None                               | [--update-list]                                | Imports the downloaded metadata for plugins. Assumes you've run `meta:download:plugins`               |
| `meta:import:themes`    | None                               | [--update-list]                                | Imports the downloaded metadata for themes. Assumes you've run `meta:download:themes`                 |
| `download:plugins`      | [num-to-pull=latest]               | [--plugins [--force-download or -f]            | Downloads any plugins outstanding from the last time the command was run. Defaults to latest version, |
| `download:themes`       | [num-to-pull=latest]               | [--themes] [--force-download or  -f]           | Downloads any themes outstanding from the last time the command was run. Defaults to latest version.  |
| `util:upload`           | action<plugins, themes>            | [--slugs] [--limit] [--offset] [--clean or -c] | Uploads any downloaded plugins/themes to your file system (right now supports S3).                    |
| `util:clean`            | None                               | None                                           | Cleans the data directory of any files that remain.                                                   |  
| `run:all`               | [asset-type<all, plugins, themes>] | None                                           | Runs the four commands for themes/plugins or both. A shortcut to a full run of the downloader.        |

## License

The AssetGrabber tool is licensed under the MIT license. You may use and distribute it freely.

The code that this tool is designed to download is likely licensed under the GPL. Please respect that license. However,
because this tool does not implement GPL components, it is not required to be licensed under the GPL.
