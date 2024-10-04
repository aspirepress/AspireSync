<?php

declare(strict_types=1);

namespace AssetGrabber\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class UtilCleanDataCommand extends Command
{
    /** @var int[] */
    private $filesDirsCount = [
        'files' => 0,
        'dirs'  => 0,
        'size'  => 0,
    ];

    protected function configure(): void
    {
        $this->setName('util:clean-data')
            ->setDescription('Clean data directory and reset to original');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Calculating the size and number of files in the data directory... (this may take a while)');

        $details = $this->countFilesAndDirectories('/opt/assetgrabber/data');
        $files   = $details['files'];
        $dirs    = $details['dirs'];
        $size    = $this->convertBytesToUnit($details['size']);

        /** @var QuestionHelper $question */
        $question = $this->getHelper('question');
        $output->writeln("There are $files files and $dirs directories totalling $size.");
        $q = new ConfirmationQuestion('Are you SURE you want to clean data? THERE WILL BE NO OTHER CONFIRMATION.');
        if (! $question->ask($input, $output, $q)) {
            $output->writeln('Aborting!');
            return self::SUCCESS;
        }

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

    /**
     * @return int[]
     */
    private function countFilesAndDirectories(string $dir): array
    {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === '.gitkeep') {
                continue;
            }
            if (is_dir($dir . '/' . $file)) {
                $this->filesDirsCount['dirs']++;
                $this->countFilesAndDirectories($dir . '/' . $file);
            } else {
                $this->filesDirsCount['files']++;
                $this->filesDirsCount['size'] += filesize($dir . '/' . $file);
            }
        }

        return $this->filesDirsCount;
    }

    private function convertBytesToUnit(int $bytes): string
    {
        switch (true) {
            case $bytes > 1073741824:
                $size = number_format($bytes / 1073741824, 2) . ' GB';
                break;

            case $bytes > 1048576:
                $size = number_format($bytes / 1048576, 2) . ' MB';
                break;

            case $bytes > 1024:
                $size = number_format($bytes / 1024, 2) . ' KB';
                break;

            default:
                $size = $bytes . ' bytes';
        }

        return $size;
    }
}
