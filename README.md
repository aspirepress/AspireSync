# AssetGrabber

AssetGrabber is designed to enumerate and download the plugins and themes stored in the WordPress SVN repository and
Content Delivery Network.

## Installation

To install, you need to have Docker or Docker Desktop with command line access. You also need `make` installed.
Then, run the following commands:

1. Run `make build` to build the Docker container.
2. Run `make run` to see available commands.
3. Run `make run OPTS=<your command>` to execute your desired command.

You can also run `make run-base` to start a shell script into the container and load the code base as a volume, so it
can be modified (as in a development enviornment).

## Usage

This package ships with the following commands;

| Command            | Description                                                                                                                                                            |
|--------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `plugins:grab`     | This command will grab all the plugins, or all the plugins since your last download.                                                                                   |
| `plugins:download` | This command will grab all the versions of a particular plugin (provided as an argument). It is also used by the `plugins:grab` command to spin up download instances. |

## License

The AssetGrabber tool is licensed under the MIT license. You may use and distribute it freely.

The code that this tool is designed to download is likely licensed under the GPL. Please respect that license. However,
because this tool does not implement GPL components, it is not required to be licensed under the GPL.
