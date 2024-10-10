<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Plugins;

use AspirePress\AspireSync\Commands\AbstractBaseCommand;
use AspirePress\AspireSync\Services\Plugins\PluginDownloadFromWpService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InternalPluginDownloadCommand extends AbstractBaseCommand
{
    public function __construct(private PluginDownloadFromWpService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('internal:plugin-download')
            ->setDescription('Download all versions of a given plugin')
            ->setHidden(true)
            ->addArgument('plugin', InputArgument::REQUIRED, 'Plugin name')
            ->addArgument('version-list', InputArgument::REQUIRED, 'List of versions to download')
            ->addArgument('num-versions', InputArgument::OPTIONAL, 'Number of versions to download', 'all')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force download even if file exists');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $plugin      = $input->getArgument('plugin');
        $numVersions = $input->getArgument('num-versions');

        $this->debug("Running {$this->getName()} for $plugin");

        $this->debug('Determining versions of ' . $plugin . '...');
        $versions = explode(',', $input->getArgument('version-list'));

        array_walk($versions, function (&$value) {
            $value = trim($value);
        });

        $versionsToDownload = $this->determineDownloadedVersions($versions, $numVersions);
        $this->debug('Downloading ' . $versionsToDownload . ' versions...');
        $responses = $this->service->download($plugin, $versions, $numVersions, $input->getOption('force'));
        foreach ($responses as $responseCode => $versions) {
            $this->always($plugin . ' ' . $responseCode . ': ' . count($versions));
        }

        return Command::SUCCESS;
    }

    /**
     * @param array<int, string> $versions
     */
    private function determineDownloadedVersions(array $versions, string|int $numToDownload): int
    {
        switch ($numToDownload) {
            case 'all':
                return count($versions);
            case 'latest':
                return 1;
            default:
                return count($versions) > $numToDownload ? (int) $numToDownload : count($versions);
        }
    }
}
