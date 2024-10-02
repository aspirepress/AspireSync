<?php

declare(strict_types=1);

namespace AssetGrabber\Commands;

use Symfony\Component\Console\Command\Command;

abstract class AbstractBaseCommand extends Command
{
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

    public function getElapsedTime(): float
    {
        return $this->endTime - $this->startTime;
    }
}
