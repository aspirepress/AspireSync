<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Themes;

use AspirePress\AspireSync\Commands\AbstractBaseCommand;
use AspirePress\AspireSync\Services\StatsMetadataService;
use AspirePress\AspireSync\Services\Themes\ThemesMetadataService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MetaImportThemesCommand extends AbstractBaseCommand
{
    /** @var array<string, int> */
    private array $stats = [
        'unwritable' => 0,
        'error'      => 0,
        'success'    => 0,
        'update'     => 0,
        'write'      => 0,
        'skips'      => 0,
        'total'      => 0,
    ];

    public function __construct(private ThemesMetadataService $themeMetadata, private StatsMetadataService $statsMetadata)
    {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->setName('meta:import:themes')
            ->setAliases(['themes:import-meta'])
            ->setDescription('Import metadata from JSON files into Postgres')
            ->addOption('update-list', null, InputOption::VALUE_OPTIONAL, 'List the specific themes to update');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->startTimer();
        if (file_exists('/opt/aspiresync/data/theme-raw-data') && is_readable('/opt/aspiresync/data/theme-raw-data')) {
            $files = scandir('/opt/aspiresync/data/theme-raw-data');
        } else {
            $this->error('Unable to open source directory for theme metadata!');
            return self::FAILURE;
        }

        if ($input->getOption('update-list')) {
            $updateList = explode(',', $input->getOption('update-list'));
            $files      = $this->filterFiles($files, $updateList);
            $count      = count($files);
        } else {
            $fileCount = count($files);
            if ($fileCount > 2) {
                $count = $fileCount - 2;
            } else {
                $count = 0;
            }
        }
        $this->debug('Importing ' . $count . ' files...');

        foreach ($files as $file) {
            if (strpos($file, '.json') === false) {
                continue;
            }

            $this->stats['total']++;

            $fileContents = file_get_contents('/opt/aspiresync/data/theme-raw-data/' . $file);
            $fileContents = json_decode($fileContents, true);

            if (isset($fileContents['error'])) {
                $this->stats['unwritable']++;
                $this->error("Could not write theme $file because it does not exist.");
                continue;
            }

            $pulledAt = date('c', filemtime('/opt/aspiresync/data/theme-raw-data/' . $file));

            // Check for existing
            $existing = $this->themeMetadata->checkThemeInDatabase($fileContents['slug'] ?? '');
            if ($existing) {
                if (strtotime($existing['pulled_at']) < strtotime($pulledAt)) {
                    $this->notice('Updating theme ' . $fileContents['slug'] . ' as newer metadata exists...');
                    $result = $this->themeMetadata->updateThemeFromWP($fileContents, $pulledAt);
                    $this->handleResponse($result, $fileContents['slug'], 'open', 'update', $output);
                } else {
                    $this->stats['skips']++;
                    $this->notice('Skipping theme ' . $fileContents['slug'] . ' as it exists in DB already...');
                }
            } else {
                $this->notice('Writing theme ' . $fileContents['slug']);
                $result = $this->themeMetadata->saveThemeFromWP($fileContents, $pulledAt);
                $this->handleResponse($result, $fileContents['slug'], 'open', 'write', $output);
            }
        }

        $this->endTimer();

        $this->always($this->getRunInfo([
            'Stats:',
            'Errors:     ' . $this->stats['error'],
            'Unwritable: ' . $this->stats['unwritable'],
            'Successes:  ' . $this->stats['success'],
            'Updates:    ' . $this->stats['update'],
            'Writes:     ' . $this->stats['write'],
            'Skips:      ' . $this->stats['skips'],
            'Total:      ' . $this->stats['total'],
        ]));

        $this->statsMetadata->logStats($this->getName(), $this->stats);
        return self::SUCCESS;
    }

    /**
     * @param string[]|array $result
     */
    private function handleResponse(array $result, string $slug, string $themeState, string $action, OutputInterface $output): void
    {
        if (! empty($result['error'])) {
            $this->error($result['error']);
            $this->error('Unable to ' . $action . ' theme ' . $slug);
            $this->stats['error']++;
        } else {
            $this->success('Completed ' . $action . ' for theme ' . $slug);
            $this->stats[$action]++;
            $this->stats['success']++;
        }
    }

    /**
     * @param array<int, string> $files
     * @param string[] $updateList
     * @return string[]
     */
    private function filterFiles(array $files, array $updateList): array
    {
        $filtered = [];
        foreach ($updateList as $file) {
            if (in_array($file . '.json', $files)) {
                $filtered[] = $file . '.json';
            }
        }

        return $filtered;
    }
}
