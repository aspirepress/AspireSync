<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Meta;

use AspirePress\AspireSync\Commands\AbstractBaseCommand;
use AspirePress\AspireSync\Integrations\Wordpress\WordpressApiConnector;
use AspirePress\AspireSync\Resource;
use AspirePress\AspireSync\Services\Interfaces\ListServiceInterface;
use AspirePress\AspireSync\Services\Interfaces\MetadataServiceInterface;
use Exception;
use Saloon\Http\Request;

use Symfony\Component\Console\Input\InputOption;
use function Safe\json_decode;

abstract class AbstractMetaSyncCommand extends AbstractBaseCommand
{
    public function __construct(
        protected readonly ListServiceInterface $listService,
        protected readonly MetadataServiceInterface $meta,
        protected readonly WordpressApiConnector $api,
    ) {
        parent::__construct();
    }

    protected Resource $resource;

    abstract protected function makeRequest($slug): Request;

    protected function configure(): void
    {
        $type = $this->resource->value;
        $category = $type . 's';
        $this->setName("meta:sync:$category")
            ->setDescription("Fetches meta data of all new and changed $category")
            ->addOption('update-all', 'u', InputOption::VALUE_NONE, 'Update all metadata; otherwise, we only update what has changed')
            ->addOption('skip-newer-than-secs', null, InputOption::VALUE_REQUIRED, 'Skip downloading metadata pulled more recently than N seconds')
            ->addOption($category, null, InputOption::VALUE_OPTIONAL, "List of $category (separated by commas) to explicitly update");
    }

    protected function fetch(string $slug): void
    {
        try {
            $this->log->debug("FETCH", ['slug' => $slug]);
            $request  = $this->makeRequest($slug);
            $reply    = $this->api->send($request);
            $response = $reply->getPsrResponse();

            $code   = $response->getStatusCode();
            $reason = $response->getReasonPhrase();
            $code !== 200 and $this->debug("$slug ... $code $reason");

            $metadata = json_decode($response->getBody()->getContents(), assoc: true);
            $status   = match ($code) {
                200 => 'open',
                404 => 'not-found',
                default => 'error',
            };
            $metadata = [
                'slug'   => $slug,
                'name'   => $slug,
                'status' => $status,
                ...$metadata,
            ];
        } catch (Exception $e) {
            $this->error("$slug ... ERROR: {$e->getMessage()}");
            return;
        }
        $error = $metadata['error'] ?? null;

        $this->meta->save($metadata);

        if (! empty($metadata['versions'])) {
            $this->info("$slug ... [" . count($metadata['versions']) . ' versions]');
        } elseif (isset($metadata['version'])) {
            $this->info("$slug ... [1 version]");
        } elseif (isset($metadata['skipped'])) {
            $this->info((string) $metadata['skipped']);
        } elseif ($error) {
            if ($error === 'closed') {
                $this->info("$slug ... [closed]");
            } elseif ($code === 404) {
                $this->info("$slug ... [not found]");
            } else {
                $this->error(message: "$slug ... ERROR: $error");
            }
            if ('429' === (string) $error) {
                $this->progressiveBackoff();
                $this->fetch($slug);
                return;
            }
        } else {
            $this->info("$slug ... No versions found");
        }

        $this->iterateProgressiveBackoffLevel(self::ITERATE_DOWN);
    }
}
