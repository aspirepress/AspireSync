<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Interfaces;

use Symfony\Component\Process\Process;

/**
 * The interface of the process manager.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
interface ProcessManagerInterface
{
    /**
     * Adds a process to the manager.
     * @param Process<string> $process
     * @param callable|null $callback
     * @param array<mixed> $env
     * @return $this
     */
    public function addProcess(Process $process, callable $callback = null, array $env = []): static;

    /**
     * Waits for all processes to be finished.
     * @return $this
     */
    public function waitForAllProcesses(): static;

    /**
     * Returns whether the manager still has unfinished processes.
     * @return bool
     */
    public function hasUnfinishedProcesses(): bool;
}