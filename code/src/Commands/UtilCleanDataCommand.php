<?php

declare(strict_types=1);

namespace AssetGrabber\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UtilCleanDataCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('util:clean-data')
            ->setDescription('Clean data directory and reset to original');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Cleaning data directory...');
        $result = $this->deleteFilesAndDirectories('/opt/assetgrabber/data');
        if ($result) {
            $output->writeln('Cleaned data directory.');
            return Command::SUCCESS;
        }

        $output->writeln('Cleaned data directory failure!');
        return Command::FAILURE;
    }

    private function deleteFilesAndDirectories(string $dir): bool
    {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === '.gitkeep') {
                continue;
            }

            if (is_dir($dir . '/' . $file)) {
                $status = $this->deleteFilesAndDirectories($dir . '/' . $file);
                @rmdir($dir . '/' . $file);
                if (! $status || file_exists($dir . '/' . $file)) {
                    return false;
                }
            } else {
                @unlink($dir . '/' . $file);
                if (file_exists($dir . '/' . $file)) {
                    return false;
                }
            }
        }

        return true;
    }
}
