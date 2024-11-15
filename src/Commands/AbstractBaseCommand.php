<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands;

use AspirePress\AspireSync\Utilities\ErrorWritingTrait;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Service\Attribute\Required;

abstract class AbstractBaseCommand extends Command
{
    use ErrorWritingTrait;

    #[Required]
    public LoggerInterface $log;

    protected const int ITERATE_UP   = 1;
    protected const int ITERATE_DOWN = 2;

    private int $progressiveBackoffLevel = 1;

    private ?float $startTime = null;
    private ?float $endTime = null;

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
     * @return string[]
     */
    protected function getRunInfo(array $info = []): array
    {
        $output   = [];
        $time     = round($this->getElapsedTime(), 4);
        $output[] = "Time elapsed: $time seconds";

        return array_merge($output, $info);
    }

    protected function progressiveBackoff(): void
    {
        $sleep = $this->progressiveBackoffLevel * 2;

        if ($sleep >= 120) {
            throw new RuntimeException('Progressive backoff exceeded maximum sleep time of 120 seconds...');
        }

        $this->info('Backing Off; Sleeping for ' . $sleep . ' seconds...');
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

    protected function getDebugContext(): array
    {
        return ['name' => $this->getName(), 'startTime' => $this->startTime, 'endTime' => $this->endTime];
    }
}
