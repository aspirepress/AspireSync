<?php

declare(strict_types=1);

namespace App\Utilities;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @property LoggerInterface $log
 */
trait ErrorWritingTrait
{
    protected const int ERROR   = 1;
    protected const int WARNING = 2;
    protected const int NOTICE  = 3;
    protected const int INFO    = 4;
    protected const int DEBUG   = 5;

    protected const int SUCCESS_MSG = 6;

    protected const int FAILURE_MSG = 7;

    protected const int ALWAYS_WRITE = 8;

    protected OutputInterface $io;  // must be set in consuming class's ->initialize() method

    /**
     * @param string|iterable<int, string> $message
     */
    protected function writeMessage(string|iterable $message, int $level = self::ALWAYS_WRITE): void
    {
        $this->log->log($level, $message, ['command' => $this->getDebugContext()]);
        switch ($level) {
            case self::ERROR:
                $this->io->writeln("<fg=black;bg=red>" . OutputManagementUtil::error($message) . "</>");
                break;

            case self::FAILURE_MSG:
                $this->io->writeln("<fg=black;bg=red>" . OutputManagementUtil::failure($message) . "</>");
                break;

            case self::WARNING:
                $this->io->writeln("<fg=black;bg=yellow>" . OutputManagementUtil::warning($message) . "</>", OutputInterface::VERBOSITY_VERBOSE);
                break;

            case self::INFO:
                $this->io->writeln("<fg=green>" . OutputManagementUtil::info($message) . "</>", OutputInterface::VERBOSITY_VERBOSE);
                break;

            case self::NOTICE:
                $this->io->writeln("<fg=yellow>" . OutputManagementUtil::notice($message) . "</>", OutputInterface::VERBOSITY_VERY_VERBOSE);
                break;

            case self::DEBUG:
                $this->io->writeln("<fg=yellow>" . OutputManagementUtil::debug($message) . "</>", OutputInterface::VERBOSITY_DEBUG);
                break;

            case self::SUCCESS_MSG:
                $this->io->writeln("<fg=black;bg=green>" . OutputManagementUtil::success($message) . "</>");
                break;

            case self::ALWAYS_WRITE:
                $this->io->writeln("<fg=green>" . OutputManagementUtil::generic($message) . "</>", OutputInterface::VERBOSITY_QUIET);
        }
    }

    /**
     * @param string|iterable<int, string> $message
     */
    protected function error(string|iterable $message): void
    {
        $this->writeMessage($message, self::ERROR);
    }

    /**
     * @param string|iterable<int, string> $message
     */
    protected function warning(string|iterable $message): void
    {
        $this->writeMessage($message, self::WARNING);
    }

    /**
     * @param string|iterable<int, string> $message
     */
    protected function notice(string|iterable $message): void
    {
        $this->writeMessage($message, self::NOTICE);
    }

    /**
     * @param string|iterable<int, string> $message
     */
    protected function debug(string|iterable $message): void
    {
        $this->writeMessage($message, self::DEBUG);
    }

    /**
     * @param string|iterable<int, string> $message
     */
    protected function info(string|iterable $message): void
    {
        $this->writeMessage($message, self::INFO);
    }

    /**
     * @param string|iterable<int, string> $message
     */
    protected function success(string|iterable $message): void
    {
        $this->writeMessage($message, self::SUCCESS_MSG);
    }

    /**
     * @param string|iterable<int, string> $message
     */
    protected function failure(string|iterable $message): void
    {
        $this->writeMessage($message, self::FAILURE_MSG);
    }

    /**
     * @param string|iterable<int, string> $message
     */
    protected function always(string|iterable $message): void
    {
        $this->writeMessage($message);
    }
}
