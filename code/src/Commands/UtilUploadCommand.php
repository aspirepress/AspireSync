<?php

declare(strict_types=1);

namespace AssetGrabber\Commands;

use AssetGrabber\Services\Interfaces\Callback;
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
    private Callback $callback;

    private array $stats = [
        'uploaded' => 0,
        'failed' => 0,
        'skipped' => 0,
        'total' => 0,
    ];

    public function __construct(Callback $callback, private Filesystem $flysystem)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Callable object required for constructor!');
        }
        $this->callback = $callback;
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
        $this->always("Running command {$this->getName()} $action");
        $this->startTimer();

        $metadata = ($this->callback)($action);
        $resultCode = $this->upload($input, $metadata);

        $this->endTimer();

        $output->writeln($this->getRunInfo($this->calcStats()));

        return $resultCode;
    }

    private function upload(InputInterface $input, $metadata): int
    {
        $itemRecords  = ListManagementUtil::explodeCommaSeparatedList($input->getOption('slugs'));
        $cleanUp = $input->getOption('clean');

        $this->debug('Preparing to upload files to S3...');

        $itemRecords = $metadata->getData(filterBy: $itemRecords);

        $dir    = $metadata->getStorageDir();

        if (!file_exists($dir) || !is_readable($dir)) {
            $this->error('Unable to open storage directory!');
            return self::FAILURE;
        }

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
                $itemSlug = $matches[1];
                $version    = $matches[2];
                $itemId   = $itemRecords[$itemSlug];

                if (! $itemId) {
                    $this->error('Unable to determine a valid item ID for the matched values!');
                    $this->debug("File: $file | Item Name: $itemSlug | Version: $version | ID: $itemId");
                    continue;
                }

                $details = $metadata->getVersionData($itemId, $version, 'aws_s3');
                if ($details) {
                    // We've already stored this file
                    $this->notice("Already uploaded $itemSlug; skipping...");
                    $this->stats['skipped']++;
                    $this->stats['total']++;
                    if ($cleanUp) {
                        $this->debug("Removing file for $itemSlug");
                        @unlink($dir . '/' . $file);
                    }
                    continue;
                }

                try {
                    $this->info("Uploading $itemSlug (v. $version) to S3...");
                    $this->flysystem->writeStream('/themes/' . $file, fopen($dir . '/' . $file, 'r'));

                    $versionInfo = [$version => '/themes/' . $file];
                    $metadata->writeVersionProcessed(Uuid::fromString($itemId), $versionInfo, 'aws_s3');
                    $this->success("Uploaded and recorded $itemSlug (v. $version)");
                    $this->stats['uploaded']++;
                    $this->stats['total']++;
                    if ($cleanUp) {
                        $this->debug("Removing file for $itemSlug");
                        @unlink($dir . '/' . $file);
                    }
                } catch (Exception $e) {
                    $this->error("Error writing $itemSlug to S3: " . $e->getMessage());
                    $this->stats['failed']++;
                    $this->stats['total']++;
                }
            }
        }
        return self::SUCCESS;
    }

    private function calcStats()
    {
        return [
            'Stats:',
            'Uploaded: ' . $this->stats['uploaded'],
            'Failed:   ' . $this->stats['failed'],
            'Skipped:  ' . $this->stats['skipped'],
            'Total:    ' . $this->stats['total'],
        ];
    }
}
