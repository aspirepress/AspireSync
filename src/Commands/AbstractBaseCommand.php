<?php

declare(strict_types=1);

namespace App\Commands;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Service\Attribute\Required;

abstract class AbstractBaseCommand extends Command
{
    #[Required]
    public LoggerInterface $log;

    private ?float $startTime = null;
    private ?float $endTime   = null;

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

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        ini_set('memory_limit', '4G');
    }

    /** @return array{name: string|null, startTime: float|null, elapsed: float|null} */
    protected function getDebugContext(): array
    {
        return ['name' => $this->getName(), 'startTime' => $this->startTime, 'elapsed' => $this->getElapsedTime()];
    }
}
