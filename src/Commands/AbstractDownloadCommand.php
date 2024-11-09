<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands;

use AspirePress\AspireSync\Services\Interfaces\ListServiceInterface;
use AspirePress\AspireSync\Services\Interfaces\MetadataServiceInterface;
use AspirePress\AspireSync\Services\ProcessManager;
use AspirePress\AspireSync\Services\StatsMetadataService;
use AspirePress\AspireSync\Utilities\HasStats;
use AspirePress\AspireSync\Utilities\StringUtil;
use AspirePress\AspireSync\Utilities\VersionUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

abstract class AbstractDownloadCommand extends AbstractBaseCommand
{
    use HasStats;

    public function __construct(
        protected readonly ListServiceInterface $listService,
        protected readonly MetadataServiceInterface $meta,
        protected readonly StatsMetadataService $statsMeta,
        protected readonly ProcessManager $processManager,
    ) {
        parent::__construct();
        $this->processManager->setNumberOfParallelProcesses(20);
        $this->processManager->setProcessStartCallback($this->onDownloadProcessStarted(...));
        $this->processManager->setProcessFinishCallback($this->onDownloadProcessFinished(...));
    }

    abstract protected function getCategory(): string;

    protected function configure(): void
    {
        $category = $this->getCategory();
        $this->setName("$category:download")
            ->setDescription("Grabs $category (with number of specified versions or explicitly specified $category) from the origin repo")
            ->addArgument('num-versions', InputArgument::OPTIONAL, 'Number of versions to request', 'latest')
            ->addOption($category, null, InputOption::VALUE_OPTIONAL, "List of $category to request")
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force download even if file exists')
            ->addOption('download-all', null, InputOption::VALUE_NONE, "Download all $category");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $category = $this->getCategory();

        $this->always("Running command {$this->getName()}");
        $this->startTimer();
        $numVersions          = $input->getArgument('num-versions');
        $listing              = $input->getOption($category);
        $listing and $listing = StringUtil::explodeAndTrim($listing);

        $this->debug("Getting list of $category...");

        if ($input->getOption('download-all')) {
            $pending = $this->listService->getUpdatedListOfItems($listing, 'default');
        } else {
            $pending = $this->listService->getUpdatedListOfItems($listing);
        }

        $this->debug(count($pending) . " $category to download...");
        if (count($pending) === 0) {
            $this->success("No $category to download...exiting...");
            return Command::SUCCESS;
        }

        $flags                                  = [];
        $input->getOption('force') and $flags[] = '--force';

        $counter = 1;
        foreach ($pending as $slug => $versions) {
            $versions = $this->determineVersionsToDownload($slug, $versions, $numVersions);
            if (! $versions) {
                $this->debug("No downloadable versions found for $slug");
            }
            foreach ($versions as $version) {
                [$version, $message] = VersionUtil::cleanVersion($version);
                if (! $version) {
                    $this->notice("Skipping $slug: $message");
                    continue;
                }
                $command = ['aspiresync', "$category:download:single", $slug, $version, ...$flags];
                $this->debug("QUEUE #$counter: " . implode(' ', $command));
                $process = new Process($command);
                $this->processManager->addProcess($process);
                $counter++;
            }
        }

        $this->processManager->waitForAllProcesses();

        $this->endTimer();
        $this->always($this->getRunInfo($this->getCalculatedStats()));
        $this->statsMeta->logStats($this->getName(), $this->stats);
        return Command::SUCCESS;
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

    protected function onDownloadProcessStarted(Process $process): void
    {
        $this->debug("START: " . str_replace("'", "", $process->getCommandLine()));
    }

    protected function onDownloadProcessFinished(Process $process): void
    {
        echo $process->getOutput();
        $process->wait();
    }
}
