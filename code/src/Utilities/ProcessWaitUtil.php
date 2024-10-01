<?php

declare(strict_types=1);

namespace AssetGrabber\Utilities;

use Symfony\Component\Process\Process;

abstract class ProcessWaitUtil
{
    /**
     * @param Process[] $processes
     */
    public static function wait(array &$processes): string
    {
        while (count($processes) >= 24) {
            foreach ($processes as $k => $process) {
                if (! $process->isRunning()) {
                    $procOutput = $process->getOutput();
                    unset($processes[$k]);
                    return $procOutput;
                }
            }
        }

        return 'All processes exited; we should never get here!';
    }

    /**
     * @param Process[] $processes
     */
    public static function waitAtEndOfScript(array $processes): void
    {
        while (count($processes) > 0) {
            foreach ($processes as $k => $process) {
                if (! $process->isRunning()) {
                    unset($processes[$k]);
                }
            }
        }
    }
}
