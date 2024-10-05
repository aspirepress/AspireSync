<?php

declare(strict_types=1);

namespace AssetGrabber\Commands;

use AssetGrabber\Utilities\OutputManagementUtil;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractBaseCommand extends Command
{
    protected const ERROR = 1;
    protected const WARNING = 2;
    protected const NOTICE = 3;
    protected const INFO = 4;
    protected const DEBUG = 5;

    protected const ITERATE_UP   = 1;
    protected const ITERATE_DOWN = 2;

    private int $progressiveBackoffLevel = 1;

    private float $startTime;

    private float $endTime;

    private OutputInterface $io;

    protected function startTimer(): void
    {
        $this->startTime = microtime(true);
    }

    protected function endTimer(): void
    {
        $this->endTime = microtime(true);
    }

    protected function getElapsedTime(): float
    {
        return $this->endTime - $this->startTime;
    }

    /**
     * @param string[] $info
     * @return string[];
     */
    protected function getRunInfo(array $info = []): array
    {
        $output   = [];
        $time     = $this->getElapsedTime();
        $output[] = "Took $time seconds...";

        return array_merge($output, $info);
    }

    protected function progressiveBackoff(OutputInterface $output): void
    {
        $sleep = $this->progressiveBackoffLevel * 2;

        if ($sleep >= 120) {
            throw new RuntimeException('Progressive backoff exceeded maximum sleep time of 120 seconds...');
        }

        $output->writeln('Backing Off; Sleeping for ' . $sleep . ' seconds...');
        sleep($sleep);
        $this->iterateProgressiveBackoffLevel(self::ITERATE_UP);
    }

    protected function iterateProgressiveBackoffLevel(int $level): void
    {
        switch ($level) {
            case self::ITERATE_UP:
                $this->progressiveBackoffLevel++;
                break;

            case self::ITERATE_DOWN:
                $this->progressiveBackoffLevel--;
                break;

            default:
                throw new InvalidArgumentException('Invalid progress level');
        }

        if ($this->progressiveBackoffLevel <= 0) {
            $this->progressiveBackoffLevel = 1;
        }
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function writeMessage(string $message, int $level = self::SUCCESS): void
    {
        switch ($level) {
            case self::ERROR:
                $this->io->writeln("<fg=black;bg=red>" . OutputManagementUtil::error($message) . "</>");
                break;

            case self::FAILURE:
                $this->io->writeln("<fg=black;bg=red>" . OutputManagementUtil::failure($message) . "</>",);
                break;

            case self::WARNING:
                $this->io->writeln("<fg=black;bg=yellow>" . OutputManagementUtil::warning($message) . "</>", Output::VERBOSITY_VERBOSE);
                break;

            case self::INFO:
                $this->io->writeln("<fg=green>" . OutputManagementUtil::info($message) . "</>", Output::VERBOSITY_VERBOSE);
                break;

            case self::NOTICE:
                $this->io->writeln("<fg=yellow>" . OutputManagementUtil::notice($message) . "</>", Output::VERBOSITY_VERY_VERBOSE);
                break;

            case self::DEBUG:
             $this->io->writeln("<fg=yellow>" . OutputManagementUtil::debug($message) . "</>", Output::VERBOSITY_DEBUG);
                break;

            case self::SUCCESS:
                $this->io->writeln("<fg=black;bg=green>" . OutputManagementUtil::success($message) . "</>");
                break;

            default:
                throw new \InvalidArgumentException('Invalid progress level');
        }
    }
}
