<?php

declare(strict_types=1);

namespace AssetGrabber\Services;

use AssetGrabber\Services\Interfaces\SvnServiceInterface;
use Symfony\Component\Process\Process;

class SvnService implements SvnServiceInterface
{
    public function getRevisionForType(string $type, int $prevRevision, int $lastRevision): ?\SimpleXMLElement
    {
        $targetRev  = (int) $lastRevision;
        $currentRev = 'HEAD';

        if ($targetRev === $prevRevision) {
            return null;
        }

        $command = [
            'svn',
            'log',
            '-v',
            '-q',
            '--xml',
            'https://' . $type . '.svn.wordpress.org',
            "-r",
            "$targetRev:$currentRev",
        ];

        $process = new Process($command);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Unable to get list of plugins to update' . $process->getErrorOutput());
        }

        $output  = simplexml_load_string($process->getOutput());
        return $output;
    }
}