<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Themes;

use AspirePress\AspireSync\Commands\AbstractBaseCommand;
use AspirePress\AspireSync\Services\Interfaces\WpEndpointClientInterface;
use AspirePress\AspireSync\Services\StatsMetadataService;
use AspirePress\AspireSync\Services\Themes\ThemeListService;
use AspirePress\AspireSync\Services\Themes\ThemeMetadataService;
use AspirePress\AspireSync\Utilities\StringUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ThemesMetaCommand extends AbstractBaseCommand
{
    /** @var array<string, int> */
    private array $stats = [
        'themes'       => 0,
        'versions'     => 0,
        'errors'       => 0,
        'rate_limited' => 0,
    ];

    public function __construct(
        private readonly ThemeListService $themeListService,
        private readonly ThemeMetadataService $themesMetadataService,
        private readonly StatsMetadataService $statsMetadataService,
        private readonly WpEndpointClientInterface $wpClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('themes:meta')
            ->setDescription('Fetches the meta data of the themes')
            ->addOption('update-all', 'u', InputOption::VALUE_NONE, 'Update all theme meta-data; otherwise, we only update what has changed')
            ->addOption('skip-newer-than-secs', null, InputOption::VALUE_REQUIRED, 'Skip downloading metadata pulled more recently than N seconds')
            ->addOption('themes', null, InputOption::VALUE_OPTIONAL, 'List of themes (separated by commas) to explicitly update');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->writeMessage("Running command {$this->getName()}");
        $this->startTimer();

        $themes  = StringUtil::explodeAndTrim($input->getOption('themes') ?? '');
        $min_age = (int) $input->getOption('skip-newer-than-secs') ?: null;

        $this->debug('Getting list of themes...');
        $themesToUpdate = $this->themeListService->getItemsForAction($themes, $this->getName(), $min_age);
        $this->info(count($themesToUpdate) . ' themes to download metadata for...');

        if (count($themesToUpdate) === 0) {
            $this->error('No theme metadata to download...exiting...');
            return Command::SUCCESS;
        }

        foreach ($themesToUpdate as $theme => $versions) {
            $this->fetchThemeDetails($input, $output, (string) $theme, $versions);
        }

        if ($input->getOption('themes')) {
            $this->debug("Not saving revision when --themes was specified");
        } else {
            $revision = $this->themeListService->preserveRevision($this->getName());
            $this->debug("Updated current revision to $revision");
        }
        $this->endTimer();

        $this->always($this->getRunInfo($this->calculateStats()));
        $this->statsMetadataService->logStats($this->getName(), $this->stats);
        return Command::SUCCESS;
    }

    /** @return string[] */
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

    /** @param string[] $versions */
    private function fetchThemeDetails(InputInterface $input, OutputInterface $output, string $slug, array $versions): void
    {
        $this->stats['themes']++;
        $data  = $this->wpClient->getThemeMetadata($slug);
        $error = $data['error'] ?? null;

        $this->themesMetadataService->save($data);

        if (! empty($data['versions'])) {
            $this->info("$slug ... [" . count($data['versions']) . ' versions]');
            $this->stats['versions'] += count($data['versions']);
        } elseif (isset($data['version'])) {
            $this->info("$slug ... [1 version]");
            $this->stats['versions'] += 1;
        } elseif (isset($data['skipped'])) {
            $this->notice((string) $data['skipped']);
        } elseif ($error) {
            if ($error === 'Theme not found') {
                $this->info("$slug ... [not found]");
            } else {
                $this->error(message: "$slug ... ERROR: $error");
            }
            if ('429' === (string) $error) {
                $this->progressiveBackoff();
                $this->fetchThemeDetails($input, $output, $slug, $versions);
                $this->stats['rate_limited']++;
                return;
            }
            $this->stats['errors']++;
        } else {
            $this->info("$slug ... No versions found");
        }

        $this->iterateProgressiveBackoffLevel(self::ITERATE_DOWN);
    }
}
