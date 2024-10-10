<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Process\Process;

class UtilCleanCommand extends AbstractBaseCommand
{
    /** @var array<string, int> */
    private array $filesDirsCount = [
        'files' => 0,
        'dirs'  => 0,
        'size'  => 0,
    ];

    protected function configure(): void
    {
        $this->setName('util:clean')
            ->setAliases(['util:clean-data'])
            ->setDescription('Clean data directory and reset to original');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->always('Calculating the size and number of files in the data directory... (this may take a while)');

        $details = $this->countFilesAndDirectories('/opt/assetgrabber/data');
        $files   = $details['files'];
        $dirs    = $details['dirs'];
        $size    = $this->convertBytesToUnit($details['size']);

        /** @var QuestionHelper $question */
        $question = $this->getHelper('question');
        $this->always("There are $files files and $dirs directories totalling $size.");
        $q = new ConfirmationQuestion('Are you SURE you want to clean data? THERE WILL BE NO OTHER CONFIRMATION.');
        if (! $question->ask($input, $output, $q)) {
            $this->info('Aborting!');
            return self::SUCCESS;
        }

        $this->info('Cleaning data directory...');
        $result = $this->deleteFilesAndDirectories('/opt/assetgrabber/data');
        if ($result) {
            $this->success('Cleaned data directory.');
            return Command::SUCCESS;
        }

        $this->error('Cleaned data directory failure!');
        return Command::FAILURE;
    }

    private function deleteFilesAndDirectories(string $dir): bool
    {
        $process = new Process(command: [
            'sh',
            '-c',
            'rm -fr ' . $dir . '/*',
        ], timeout: 300);

        $process->run();
        return $process->isSuccessful();
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
