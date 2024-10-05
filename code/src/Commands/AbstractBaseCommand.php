<?php

declare(strict_types=1);

namespace AssetGrabber\Commands;

use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractBaseCommand extends Command
{
    protected const ITERATE_UP   = 1;
    protected const ITERATE_DOWN = 2;

    private int $progressiveBackoffLevel = 1;

    private float $startTime;

    private float $endTime;
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
}
