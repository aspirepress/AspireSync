<?php

declare(strict_types=1);

namespace App\Services;

// Based on https://github.com/BluePsyduck/symfony-process-manager/tree/master

use App\Services\Interfaces\ProcessManagerInterface;
use Closure;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/** The process manager for executing multiple processes in parallel. */
class ProcessManager implements ProcessManagerInterface
{
    /**
     * The processes currently waiting to be executed.
     *
     * @var array<array{Process<string>, callable|null, array<mixed>}>
     */
    private array $pendingProcessData = [];

    /**
     * The processes currently running.
     *
     * @var array<Process<string>>
     */
    private array $runningProcesses = [];

    public function __construct(
        protected int $numberOfParallelProcesses = 1,
        protected int $pollInterval = 100,
        protected int $processStartDelay = 0,
        protected ?Closure $processStartCallback = null,
        protected ?Closure $processFinishCallback = null,
        protected ?Closure $processTimeoutCallback = null,
        protected ?Closure $processCheckCallback = null,
    ) {
    }

    /**
     * Sets the number of processes to run in parallel.
     *
     * @return $this
     */
    public function setNumberOfParallelProcesses(int $numberOfParallelProcesses): static
    {
        $this->numberOfParallelProcesses = $numberOfParallelProcesses;
        $this->executeNextPendingProcess(); // Start new processes in case we increased the limit.
        return $this;
    }

    /**
     * Sets the interval to wait between the polls of the processes, in milliseconds.
     *
     * @return $this
     */
    public function setPollInterval(int $pollInterval): static
    {
        $this->pollInterval = $pollInterval;
        return $this;
    }

    /**
     * Sets the time to delay the start of processes to space them out, in milliseconds.
     *
     * @return $this
     */
    public function setProcessStartDelay(int $processStartDelay): static
    {
        $this->processStartDelay = $processStartDelay;
        return $this;
    }

    /**
     * Sets the callback for when a process is about to be started.
     *
     * @param callable|null $processStartCallback The callback, accepting a Process as only argument.
     * @return $this
     */
    public function setProcessStartCallback(?callable $processStartCallback): static
    {
        $this->processStartCallback = $processStartCallback;
        return $this;
    }

    /**
     * Sets the callback for when a process has finished.
     *
     * @param callable|null $processFinishCallback The callback, accepting a Process as only argument.
     * @return $this
     */
    public function setProcessFinishCallback(?callable $processFinishCallback): static
    {
        $this->processFinishCallback = $processFinishCallback;
        return $this;
    }

    /**
     * Sets the callback for when a process timed out.
     *
     * @return $this
     */
    public function setProcessTimeoutCallback(?callable $processTimeoutCallback): static
    {
        $this->processTimeoutCallback = $processTimeoutCallback;
        return $this;
    }

    /**
     * Sets the callback for when a process is checked.
     *
     * @return $this
     */
    public function setProcessCheckCallback(?callable $processCheckCallback): static
    {
        $this->processCheckCallback = $processCheckCallback;
        return $this;
    }

    /**
     * Invokes the callback if it is an callable.
     *
     * @param Process<string> $process
     */
    protected function invokeCallback(?callable $callback, Process $process): void
    {
        if (is_callable($callback)) {
            $callback($process);
        }
    }

    /**
     * Adds a process to the manager.
     *
     * @param Process<string> $process
     * @param array<mixed> $env
     * @return $this
     */
    public function addProcess(Process $process, ?callable $callback = null, array $env = []): static
    {
        $this->pendingProcessData[] = [$process, $callback, $env];
        $this->executeNextPendingProcess();
        $this->checkRunningProcesses();
        return $this;
    }

    protected function executeNextPendingProcess(): void
    {
        if ($this->canExecuteNextPendingRequest()) {
            $this->sleep($this->processStartDelay);

            $data = array_shift($this->pendingProcessData);
            if ($data !== null) {
                [$process, $callback, $env] = $data;
                /** @var Process $process */
                $this->invokeCallback($this->processStartCallback, $process);
                $process->start($callback, $env);

                $pid = $process->getPid();
                if ($pid === null) {
                    // The process finished before we were able to check its process id.
                    $this->checkRunningProcess($pid, $process);
                } else {
                    $this->runningProcesses[$pid] = $process;
                }
            }
        }
    }

    protected function canExecuteNextPendingRequest(): bool
    {
        return count($this->runningProcesses) < $this->numberOfParallelProcesses
            && count($this->pendingProcessData) > 0;
    }

    protected function checkRunningProcesses(): void
    {
        foreach ($this->runningProcesses as $pid => $process) {
            $this->checkRunningProcess($pid, $process);
        }
    }

    /** @param Process<string> $process */
    protected function checkRunningProcess(?int $pid, Process $process): void
    {
        $this->invokeCallback($this->processCheckCallback, $process);
        $this->checkProcessTimeout($process);
        if (! $process->isRunning()) {
            $this->invokeCallback($this->processFinishCallback, $process);

            if ($pid !== null) {
                unset($this->runningProcesses[$pid]);
            }
            $this->executeNextPendingProcess();
        }
    }

    /** @param Process<string> $process */
    protected function checkProcessTimeout(Process $process): void
    {
        try {
            $process->checkTimeout();
        } catch (ProcessTimedOutException $e) {
            $this->invokeCallback($this->processTimeoutCallback, $process);
        }
    }

    /** @return $this */
    public function waitForAllProcesses(): static
    {
        while ($this->hasUnfinishedProcesses()) {
            $this->sleep($this->pollInterval);
            $this->checkRunningProcesses();
        }
        return $this;
    }

    protected function sleep(int $milliseconds): void
    {
        usleep($milliseconds * 1000);
    }

    public function hasUnfinishedProcesses(): bool
    {
        return count($this->pendingProcessData) > 0 || count($this->runningProcesses) > 0;
    }
}
