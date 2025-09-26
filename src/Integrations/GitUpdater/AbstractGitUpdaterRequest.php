<?php

declare(strict_types=1);

namespace App\Integrations\GitUpdater;

use Saloon\Enums\Method;
use Saloon\Http\Request;

abstract class AbstractGitUpdaterRequest extends Request
{
    protected Method $method = Method::GET;
}

// sample urls

// https://api.fair.pm/wp-json/git-updater/v1/update-api/?slug=fair-plugin
// https://fair.git-updater.com/wp-json/git-updater/v1/update-api/?slug=git-updater
// https://fair.git-updater.com/wp-json/git-updater/v1/update-api/?slug=handbook-callout-blocks

// note: no version in the /namespace url
// https://api.fair.pm/wp-json/git-updater/namespace

// these are all relative to /wp-json/git-updater/v1/
// * test
// * repos
// * update-api
// * plugins-api (alias for update-api)
// * themes-api (alias for update-api)
// * update-api-additions
// * get-additions-data
// * flush-repo-cache
// * update
// * reset-branch
