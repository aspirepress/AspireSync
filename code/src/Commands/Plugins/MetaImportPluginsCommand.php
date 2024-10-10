<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Plugins;

use AspirePress\AspireSync\Commands\AbstractBaseCommand;
use AspirePress\AspireSync\Services\Plugins\PluginMetadataService;
use AspirePress\AspireSync\Services\StatsMetadataService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MetaImportPluginsCommand extends AbstractBaseCommand
{
    /** @var array<string, int>  */
    private array $stats = [
        'unwritable' => 0,
        'error'      => 0,
        'success'    => 0,
        'update'     => 0,
        'write'      => 0,
        'skips'      => 0,
        'total'      => 0,
    ];

    public function __construct(private PluginMetadataService $pluginMetadata, private StatsMetadataService $statsMetadataService)
    {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->setName('meta:import:plugins')
            ->setAliases(['plugins:import-meta'])
            ->setDescription('Import metadata from JSON files into Postgres')
            ->addOption('update-list', null, InputOption::VALUE_OPTIONAL, 'List the specific plugins to update');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->always("Running command {$this->getName()}");

        $this->startTimer();
        if (file_exists('/opt/assetgrabber/data/plugin-raw-data') && is_readable('/opt/assetgrabber/data/plugin-raw-data')) {
            $files = scandir('/opt/assetgrabber/data/plugin-raw-data');
        } else {
            $this->error('Unable to read source directory for plugin metadata!');
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

            $fileContents = file_get_contents('/opt/assetgrabber/data/plugin-raw-data/' . $file);
            $fileContents = json_decode($fileContents, true);

            $pulledAt = date('c', filemtime('/opt/assetgrabber/data/plugin-raw-data/' . $file));

            // Check for existing
            $existing = $this->pluginMetadata->checkPluginInDatabase($fileContents['slug'] ?? '');
            if ($existing) {
                if (strtotime($existing['pulled_at']) < strtotime($pulledAt)) {
                    $this->notice('Updating plugin ' . $fileContents['slug'] . ' as newer metadata exists...');
                    $result = $this->pluginMetadata->updatePluginFromWP($fileContents, $pulledAt);
                    $this->handleResponse($result, $fileContents['slug'], 'open', 'update', $output);
                    continue;
                } else {
                    $this->stats['skips']++;
                    $this->notice('Skipping plugin ' . $fileContents['slug'] . ' as it exists in DB already...');
                    continue;
                }
            }
            if (isset($fileContents['error'])) {
                if ($fileContents['error'] !== 'closed') {
                    $this->stats['unwritable']++;
                    $this->notice('Skipping; unable to write file ' . $file);
                    continue;
                }

                $this->notice('Writing CLOSED plugin ' . $fileContents['slug']);
                $result = $this->pluginMetadata->saveClosedPluginFromWP($fileContents, $pulledAt);
                $this->handleResponse($result, $fileContents['slug'], 'closed', 'write', $output);
            } else {
                $this->notice('Writing OPEN plugin ' . $fileContents['slug']);
                $result = $this->pluginMetadata->saveOpenPluginFromWP($fileContents, $pulledAt);
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
        $this->statsMetadataService->logStats($this->getName(), $this->stats);
        return self::SUCCESS;
    }

    /**
     * @param  string[]|array  $result
     */
    private function handleResponse(array $result, string $slug, string $pluginState, string $action, OutputInterface $output): void
    {
        if (! empty($result['error'])) {
            $this->error($result['error']);
            $this->error('Unable to ' . $action . ' ' . $pluginState . ' plugin ' . $slug);
            $this->stats['error']++;
        } else {
            $this->success('Completed ' . $action . ' for ' . $pluginState . ' plugin ' . $slug);
            $this->stats[$action]++;
            $this->stats['success']++;
        }
    }

    /**
     * @param  array<int, string>  $files
     * @param  string[]  $updateList
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
