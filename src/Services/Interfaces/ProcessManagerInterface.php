<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use Symfony\Component\Process\Process;

/**
 * The interface of the process manager.
 */
interface ProcessManagerInterface
{
    /**
     * Adds a process to the manager.
     *
     * @param Process<string> $process
     * @param array<mixed> $env
     * @return $this
     */
    public function addProcess(Process $process, ?callable $callback = null, array $env = []): static;

    /**
     * Waits for all processes to be finished.
     *
     * @return $this
     */
    public function waitForAllProcesses(): static;

    /**
     * Returns whether the manager still has unfinished processes.
     */
    public function hasUnfinishedProcesses(): bool;
}
