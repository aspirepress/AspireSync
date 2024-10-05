<?php

declare(strict_types=1);

namespace AssetGrabber\Commands;

use AssetGrabber\Services\Plugins\PluginMetadataService;
use AssetGrabber\Services\Themes\ThemesMetadataService;
use AssetGrabber\Utilities\ListManagementUtil;
use Exception;
use League\Flysystem\Filesystem;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UtilUploadCommand extends AbstractBaseCommand
{
    public function __construct(private PluginMetadataService $pluginMetadata, private ThemesMetadataService $themeMetadata, private Filesystem $flysystem)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('util:upload')
            ->setDescription('Upload files to S3')
            ->addArgument('action', InputArgument::REQUIRED, 'Action to perform')
            ->addOption('slugs', null, InputOption::VALUE_OPTIONAL, 'A comma-separated list of slugs to upload')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit the number of slugs to upload')
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset to start uploading from', 0)
            ->addOption('clean', 'c', InputOption::VALUE_NONE, 'Clean up by removing the source after upload');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $action = $input->getArgument('action');

        switch ($action) {
            case 'themes':
                return $this->uploadThemes($input, $output);

            case 'plugins':
                return $this->uploadPlugins($input, $output);

            default:
                $output->writeln('ERROR - Invalid action!');
                return Command::FAILURE;
        }
    }

    private function uploadThemes(InputInterface $input, OutputInterface $output): int
    {
        $this->startTimer();
        $themes = ListManagementUtil::explodeCommaSeparatedList($input->getOption('slugs'));
        $cleanUp = $input->getOption('clean');

        $output->writeln('Preparing to upload files to S3...');

        $themes = $this->themeMetadata->getThemeData(filterBy: $themes);

        $dir    = '/opt/assetgrabber/data/themes';
        $files  = scandir($dir);
        $offset = $input->getOption('offset');

        $limit = $input->getOption('limit');
        if ($limit) {
            $files = array_slice($files, $offset, (int) $limit);
        }

        foreach ($files as $file) {
            if (strpos($file, '.zip') === false) {
                continue;
            }

            preg_match('/([0-9A-z\-_]+)\.([A-z0-9\-_ \.]+).zip/', $file, $matches);
            if (! empty($matches[1]) && ! empty($matches[2])) {
                $pluginName = $matches[1];
                $version    = $matches[2];
                $pluginId   = $themes[$pluginName];

                if (! $pluginId) {
                    $output->writeln('ERROR - Invalid data!');
                    $output->writeln("DEBUG - File: $file | Plugin Name: $pluginName | Version: $version | ID: $pluginId");
                    continue;
                }

                $details = $this->themeMetadata->getVersionData($pluginId, $version, 'aws_s3');
                if ($details) {
                    // We've already stored this file
                    $output->writeln("NOTICE - Already uploaded $pluginName; skipping...");
                    if ($cleanUp) {
                        $output->writeln('INFO - Removing file for ' . $pluginName);
                        @unlink($dir . '/' . $file);
                    }
                    continue;
                }

                try {
                    $output->writeln("INFO - Uploading $pluginName (v. $version) to S3...");
                    $this->flysystem->writeStream('/themes/' . $file, fopen($dir . '/' . $file, 'r'));

                    $versionInfo = [$version => '/themes/' . $file];
                    $this->themeMetadata->writeVersionsForPlugin(Uuid::fromString($pluginId), $versionInfo, 'aws_s3');
                    $output->writeln("SUCCESS - Uploaded and recorded $pluginName (v. $version)");
                    if ($cleanUp) {
                        $output->writeln('INFO - Removing file for ' . $pluginName);
                        @unlink($dir . '/' . $file);
                    }
                } catch (Exception $e) {
                    $output->writeln('ERROR - Error writing ' . $pluginName . ' to S3: ' . $e->getMessage());
                }
            }
        }
        $this->endTimer();

        $output->writeln($this->getRunInfo());

        return self::SUCCESS;
    }

    private function uploadPlugins(InputInterface $input, OutputInterface $output): int
    {
        $this->startTimer();
        $plugins = ListManagementUtil::explodeCommaSeparatedList($input->getOption('slugs'));
        $cleanUp = $input->getOption('clean');

        $output->writeln('Preparing to upload files to S3...');

        $plugins = $this->pluginMetadata->getPluginData(filterBy: $plugins);

        $dir    = '/opt/assetgrabber/data/plugins';
        $files  = scandir($dir);
        $offset = $input->getOption('offset');

        $limit = $input->getOption('limit');
        if ($limit) {
            $files = array_slice($files, $offset, (int) $limit);
        }

        foreach ($files as $file) {
            if (strpos($file, '.zip') === false) {
                continue;
            }

            preg_match('/([0-9A-z\-_]+)\.([A-z0-9\-_ \.]+).zip/', $file, $matches);
            if (! empty($matches[1]) && ! empty($matches[2])) {
                $pluginName = $matches[1];
                $version    = $matches[2];
                $pluginId   = $plugins[$pluginName];

                if (! $pluginId) {
                    $output->writeln('ERROR - Invalid data!');
                    $output->writeln("DEBUG - File: $file | Plugin Name: $pluginName | Version: $version | ID: $pluginId");
                    continue;
                }

                $details = $this->pluginMetadata->getVersionData($pluginId, $version, 'aws_s3');
                if ($details) {
                    // We've already stored this file
                    $output->writeln("NOTICE - Already uploaded $pluginName; skipping...");
                    if ($cleanUp) {
                        $output->writeln('INFO - Removing file for ' . $pluginName);
                        @unlink($dir . '/' . $file);
                    }
                    continue;
                }

                try {
                    $output->writeln("INFO - Uploading $pluginName (v. $version) to S3...");
                    $this->flysystem->writeStream('/plugins/' . $file, fopen($dir . '/' . $file, 'r'));

                    $versionInfo = [$version => '/plugins/' . $file];
                    $this->pluginMetadata->writeVersionsForPlugin(Uuid::fromString($pluginId), $versionInfo, 'aws_s3');
                    $output->writeln("SUCCESS - Uploaded and recorded $pluginName (v. $version)");
                    if ($cleanUp) {
                        $output->writeln('INFO - Removing file for ' . $pluginName);
                        @unlink($dir . '/' . $file);
                    }
                } catch (Exception $e) {
                    $output->writeln('ERROR - Error writing ' . $pluginName . ' to S3: ' . $e->getMessage());
                }
            }
        }
        $this->endTimer();

        $output->writeln($this->getRunInfo());

        return Command::SUCCESS;
    }
}
