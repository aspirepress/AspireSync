<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Download;

use AspirePress\AspireSync\Commands\AbstractBaseCommand;
use AspirePress\AspireSync\Services\List\ListServiceInterface;
use AspirePress\AspireSync\Services\Metadata\MetadataServiceInterface;
use AspirePress\AspireSync\Services\ProcessManager;
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
    public function __construct(
        protected readonly ListServiceInterface $listService,
        protected readonly MetadataServiceInterface $meta,
        protected readonly ProcessManager $processManager,
        protected readonly string $category,
    ) {
        parent::__construct();
        $this->processManager
            ->setNumberOfParallelProcesses(20)  // we rarely reach this many so there's little point increasing it
            ->setPollInterval(20)
            ->setProcessStartCallback($this->onDownloadProcessStarted(...))
            ->setProcessFinishCallback($this->onDownloadProcessFinished(...));
    }

    protected function configure(): void
    {
        $category = $this->category;
        $this->setName("download:$category")
            ->setDescription("Grabs $category (with number of specified versions or explicitly specified $category) from the origin repo")
            ->addArgument('num-versions', InputArgument::OPTIONAL, 'Number of versions to request', 'latest')
            ->addOption($category, null, InputOption::VALUE_OPTIONAL, "List of $category to request")
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force download even if file exists')
            ->addOption('download-all', null, InputOption::VALUE_NONE, "Download all $category");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $category = $this->category;

        $this->always("Running command {$this->getName()}");
        $this->startTimer();
        $numVersions          = $input->getArgument('num-versions');
        $listing              = $input->getOption($category);
        $listing and $listing = StringUtil::explodeAndTrim($listing);

        $this->debug("Getting list of $category...");

        if ($input->getOption('download-all')) {
            $pending = $this->listService->getItems($listing);
        } else {
            $pending = $this->listService->getUpdatedItems($listing);
        }

        $this->debug(count($pending) . " $category to download...");
        if (count($pending) === 0) {
            $this->success("No $category to download...exiting...");
            return Command::SUCCESS;
        }

        $flags                                  = [];
        $input->getOption('force') and $flags[] = '--force';

        $this->log->debug("starting downloads", ['category' => $category, 'pending_count' => count($pending)]);
        $counter = 1;
        foreach ($pending as $slug => $versions) {
            $versions = $this->determineVersionsToDownload($slug, $versions, $numVersions);
            $vcount   = count($versions);
            $this->log->debug("version count for $slug: $vcount", ['slug' => $slug, 'versions' => $versions]);
            foreach ($versions as $version) {
                [$version, $message] = VersionUtil::cleanVersion($version);
                if (! $version) {
                    $this->notice("Skipping $slug: $message");
                    continue;
                }
                $command = ['bin/aspiresync', "download:$category:single", $slug, $version, ...$flags];
                $this->log->debug("Queueing download", [
                    'command_line' => implode(' ', $command),
                    'slug'         => $slug,
                    'version'      => $version,
                    'queue_count'  => $counter,
                ]);
                $process = new Process($command);
                $this->processManager->addProcess($process);
                $counter++;
            }
        }

        $this->processManager->waitForAllProcesses();

        $this->endTimer();
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
        // WTF: this crashes with $this->io being uninitialized.  leaving it as is, pending an async rewrite
        // $this->debug("START: " . str_replace("'", "", $process->getCommandLine()));
    }

    protected function onDownloadProcessFinished(Process $process): void
    {
        $process->wait();
        $stdout = $process->getOutput();
        $stderr = $process->getErrorOutput();
        // same issue as the other process hooks, $this->io is uninitialized
        // $stderr and $this->error($stderr);
        if ($stderr) {
            echo "ERR: $stderr\n";
        }
        echo $stdout;
    }
}
