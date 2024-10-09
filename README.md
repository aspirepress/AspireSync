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

## Usage

This package ships with the following commands;

| Command                 | Arguments            | Options                                           | Description |
|-------------------------|----------------------|---------------------------------------------------|-------------|
| `meta:download:plugins` | None                 | [--update-all / -u] [--plugins={plugin list}]     |             |
| `meta:download:themes`  | None                 | [--update-all / -u] [--themes={theme list}]       |             |
| `meta:import:plugins`   | None                 | [--update-list={list to update}]                  |             |
| `meta:import:themes`    | None                 | [--update-list={list to update}]                  |             |
| `download:plugins`      | [num-to-pull=latest] | [--plugins={plugin list}] [--force-download / -f] |             |
| `download:themes`       | [num-to-pull=latest] | [--themes={theme list}] [--force-download / -f]   |             |
| `util:upload`           | plugins OR themes    | [--slug                                           |             |
| `util:clean`            |                      |                                                   |             |

## License

The AssetGrabber tool is licensed under the MIT license. You may use and distribute it freely.

The code that this tool is designed to download is likely licensed under the GPL. Please respect that license. However,
because this tool does not implement GPL components, it is not required to be licensed under the GPL.
