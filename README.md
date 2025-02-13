# AspireSync

AspireSync is designed to scrape the WordPress API for plugin and theme metadata, storing the API responses which are eventually pushed to [AspireCloud](https://github.com/aspiresync/AspireCloud).

## Quick Start

```shell
make
bin/console list sync
```

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