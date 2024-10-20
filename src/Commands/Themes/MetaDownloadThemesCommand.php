<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Themes;

use AspirePress\AspireSync\Commands\AbstractBaseCommand;
use AspirePress\AspireSync\Services\StatsMetadataService;
use AspirePress\AspireSync\Services\Themes\ThemeListService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MetaDownloadThemesCommand extends AbstractBaseCommand
{
    /** @var array<string, int> */
    private array $stats = [
        'themes'       => 0,
        'versions'     => 0,
        'errors'       => 0,
        'rate_limited' => 0,
    ];

    public function __construct(private ThemeListService $themeListService, private StatsMetadataService $statsMetadataService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('meta:download:themes')
            ->setAliases(['themes:meta'])
            ->setDescription('Fetches the meta data of the themes')
            ->addOption('update-all', 'u', InputOption::VALUE_NONE, 'Update all theme meta-data; otherwise, we only update what has changed')
            ->addOption('themes', null, InputOption::VALUE_OPTIONAL, 'List of themes (separated by commas) to explicitly update');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->writeMessage("Running command " . $this->getName());
        $this->startTimer();
        $themes         = [];
        $themesToUpdate = $input->getOption('themes');
        if ($themesToUpdate) {
            $themes = explode(',', $themesToUpdate);
            array_walk($themes, function (&$value) {
                $value = trim($value);
            });
        }

        $this->info('Getting list of themes...');
        $themesToUpdate = $this->themeListService->getItemsForAction($themes, $this->getName());
        $this->info(count($themesToUpdate) . ' themes to download metadata for...');

        if (count($themesToUpdate) === 0) {
            $this->error('No theme metadata to download...exiting...');
            return Command::SUCCESS;
        }

        $previous = null;
        foreach ($themesToUpdate as $theme => $versions) {
            $this->fetchThemeDetails($input, $output, (string) $theme, $versions);
        }

        $this->themeListService->preserveRevision($this->getName());
        $this->endTimer();

        $this->always($this->getRunInfo($this->calculateStats()));
        $this->statsMetadataService->logStats($this->getName(), $this->stats);
        return Command::SUCCESS;
    }

    /**
     * @return string[]
     */
    private function calculateStats(): array
    {
        return [
            'Stats:',
            'Total Themes Found:     ' . $this->stats['themes'],
            'Total Versions Found:   ' . $this->stats['versions'],
            'Total Rate Limits:      ' . $this->stats['rate_limited'],
            'Total Failed Downloads: ' . $this->stats['errors'],
        ];
    }

    /**
     * @param array<int, string> $versions
     */
    private function fetchThemeDetails(InputInterface $input, OutputInterface $output, string $theme, array $versions): void
    {
        $filename = "/opt/aspiresync/data/theme-raw-data/{$theme}.json";
        if (file_exists($filename) && !$input->getOption('update-all')) {
            $this->info("Skipping Theme $theme (metadata file already exists)");
            return;
        }

        $this->stats['themes']++;
        $data = $this->themeListService->getItemMetadata((string) $theme);

        if (isset($data['versions']) && ! empty($data['versions'])) {
            $this->info("Theme $theme has " . count($data['versions']) . ' versions');
            $this->stats['versions'] += count($data['versions']);
        } elseif (isset($data['version'])) {
            $this->info("Theme $theme has 1 version");
            $this->stats['versions'] += 1;
        } elseif (isset($data['skipped'])) {
            $this->notice($data['skipped']);
        } elseif (isset($data['error'])) {
            $this->error("Error fetching metadata for theme $theme: " . $data['error']);
            if ('429' === (string) $data['error']) {
                $this->progressiveBackoff($output);
                $this->fetchThemeDetails($input, $output, $theme, $versions);
                $this->stats['rate_limited']++;
                return;
            }
            if ('404' === (string) $data['error']) {
                $this->themeListService->markItemNotFound($theme);
            }
            $this->stats['errors']++;
        } else {
            $this->notice("No versions found for theme $theme");
        }

        $this->iterateProgressiveBackoffLevel(self::ITERATE_DOWN);
    }
}
