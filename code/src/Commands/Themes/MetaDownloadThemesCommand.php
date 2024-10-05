<?php

declare(strict_types=1);

namespace AssetGrabber\Commands\Themes;

use AssetGrabber\Commands\AbstractBaseCommand;
use AssetGrabber\Services\Themes\ThemeListService;
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

    public function __construct(private ThemeListService $themeListService)
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
        $this->startTimer();
        $themes         = [];
        $themesToUpdate = $input->getOption('themes');
        if ($themesToUpdate) {
            $themes = explode(',', $themesToUpdate);
            array_walk($themes, function (&$value) {
                $value = trim($value);
            });
        }

        $output->writeln('Getting list of themes...');
        $themesToUpdate = $this->themeListService->getItemsForAction($themes, $this->getName());
        $output->writeln(count($themesToUpdate) . ' themes to download metadata for...');

        if (count($themesToUpdate) === 0) {
            $output->writeln('No theme metadata to download...exiting...');
            return Command::SUCCESS;
        }

        $previous = null;
        foreach ($themesToUpdate as $theme => $versions) {
            $this->fetchThemeDetails($output, (string) $theme, $versions);
        }

        $this->themeListService->preserveRevision($this->getName());
        $this->endTimer();

        $output->writeln($this->getRunInfo($this->calculateStats()));
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
    private function fetchThemeDetails(OutputInterface $output, string $theme, array $versions): void
    {
        $this->stats['themes']++;
        $data = $this->themeListService->getItemMetadata((string) $theme);

        if (isset($data['versions']) && ! empty($data['versions'])) {
            $output->writeln("Theme $theme has " . count($data['versions']) . ' versions');
            $this->stats['versions'] += count($data['versions']);
        } elseif (isset($data['version'])) {
            $output->writeln("Theme $theme has 1 version");
            $this->stats['versions'] += 1;
        } elseif (isset($data['error'])) {
            $output->writeln("Error fetching metadata for theme $theme: " . $data['error']);
            if ('429' === (string) $data['error']) {
                $this->progressiveBackoff($output);
                $this->fetchThemeDetails($output, $theme, $versions);
                $this->stats['rate_limited']++;
                return;
            }
            $this->stats['errors']++;
        } else {
            $output->writeln("No versions found for theme $theme");
        }

        $this->iterateProgressiveBackoffLevel(self::ITERATE_DOWN);
    }
}
