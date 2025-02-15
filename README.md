# AspireSync

AspireSync is designed to scrape the WordPress API for plugin and theme metadata, storing the API responses which are eventually pushed to [AspireCloud](https://github.com/aspiresync/AspireCloud).

The focus of AspireSync 2.0 is going to be more robust integration with AspireCloud at an API level, including possibly making use of AS as a web service for AC to call back to AS. However, while the two are partnering, there's still no plans to move in with each other yet, and they'll maintain separate databases, communicating solely through their respective APIs.

A side project of AS 2.0 will be to improve and polish svn mirroring support, in anticipation of a future AspireBuild (final name TBD) project that will integrate intimately with AspireCloud.

## Configuration

To upload metadata to AspireCloud, use the
`meta/bin/push-to-aspirecloud` script, which requires two environment variables:

**NOTE**: these variables must be set in your actual environment: putting them only in a .env file will not work!

| Env Variable              | Description                                                                                            |
|---------------------------|--------------------------------------------------------------------------------------------------------|
| ASPIRECLOUD_ADMIN_API_URL | Base URL of AC admin API, e.g. `http://aspiredev.org/admin/api/v1`.  Do not include a trailing slash.  |
| ASPIRECLOUD_ADMIN_API_KEY | API key generated in the AC instance.  Must belong to a user with RepoAdmin or SuperAdmin permissions. |

## Version Stability

AspireSync does **not** follow semantic versioning, and compatibility between versions is not guaranteed.  