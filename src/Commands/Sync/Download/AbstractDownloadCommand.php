<?php

declare(strict_types=1);

namespace App\Commands\Sync\Download;

use App\Commands\AbstractBaseCommand;
use App\ResourceType;
use App\Services\Download\DownloadServiceInterface;
use App\Services\List\ListServiceInterface;
use App\Services\Metadata\MetadataServiceInterface;
use App\Utilities\StringUtil;
use App\Utilities\VersionUtil;
use Generator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractDownloadCommand extends AbstractBaseCommand
{
    public function __construct(
        protected readonly ListServiceInterface $listService,
        protected readonly MetadataServiceInterface $meta,
        protected readonly DownloadServiceInterface $downloadService,
        protected readonly ResourceType $resourceType,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $category = $this->resourceType->plural();
        $this->setName("sync:download:$category")
            ->setDescription("Grabs $category (with number of specified versions or explicitly specified $category) from the origin repo")
            ->addArgument('num-versions', InputArgument::OPTIONAL, 'Number of versions to request', 'latest')
            ->addOption($category, null, InputOption::VALUE_OPTIONAL, "List of $category to request")
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force download even if file exists')
            ->addOption('download-all', null, InputOption::VALUE_NONE, "Download all $category");
        $this->listService->setName($this->getName());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $category = $this->resourceType->plural();

        $this->log->notice("Running command {$this->getName()}");
        $this->startTimer();
        $force       = $input->getOption('force');
        $numVersions = $input->getArgument('num-versions');
        $requested   = array_fill_keys(StringUtil::explodeAndTrim($input->getOption($category) ?? ''), []);

        if ($requested) {
            $pending = $requested;
        } elseif ($input->getOption('download-all')) {
            $this->log->debug("Getting list of all $category...");
            $pending = $this->listService->getItems();
        } else {
            $this->log->debug("Getting list of updated $category...");
            $pending = $this->listService->getUpdatedItems();
        }

        $this->log->debug(count($pending) . " $category to download...");
        if (count($pending) === 0) {
            $this->log->info("No $category to download; exiting.");
            return Command::SUCCESS;
        }

        $this->downloadService->downloadBatch($this->generateSlugsAndVersions($pending, $numVersions), $force);

        $this->endTimer();
        return Command::SUCCESS;
    }

    /**
     * @param array<string, string[]> $pending Array with slugs as keys and versions as values.
     * @return Generator<array{string, string}> yields [$slug, $version]
     */
    protected function generateSlugsAndVersions(array $pending, string $numVersions): Generator
    {
        foreach ($pending as $slug => $versions) {
            $versions = $this->determineVersionsToDownload($slug, $versions, $numVersions);
            // $vcount   = count($versions);
            // $this->log->debug("version count for $slug: $vcount", ['slug' => $slug, 'versions' => $versions]);
            foreach ($versions as $version) {
                [$version, $message] = VersionUtil::cleanVersion($version);
                if (!$version) {
                    $this->log->notice("Skipping $slug: $message");
                    continue;
                }
                yield [$slug, $version];
            }
        }
    }

    /**
     * @param string[] $versions
     * @return array<int, string>
     */
    protected function determineVersionsToDownload(string $slug, array $versions, string $numToDownload): array
    {
        $download = match ($numToDownload) {
            'all' => $versions,
            'latest' => [VersionUtil::getLatestVersion($versions)],
            default => VersionUtil::limitVersions(VersionUtil::sortVersions($versions), (int) $numToDownload),
        };
        return $this->meta->getUnprocessedVersions($slug, $download);
    }
}
