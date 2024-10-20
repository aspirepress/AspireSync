<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services;

use AspirePress\AspireSync\Services\Interfaces\SvnServiceInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use League\Flysystem\Filesystem;
use RuntimeException;
use Symfony\Component\Process\Process;

class SvnService implements SvnServiceInterface
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly GuzzleClient $guzzle,
    ) {}

    public function getRevisionForType(string $type, int $prevRevision, int $lastRevision): array
    {
        $targetRev  = (int) $lastRevision;
        $currentRev = 'HEAD';

        if ($targetRev === $prevRevision) {
            return [
                'revision' => $targetRev,
                'items'    => [],
            ];
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
            throw new RuntimeException('Unable to get list of ' . $type . ' to update' . $process->getErrorOutput());
        }

        $output = simplexml_load_string($process->getOutput());

        $itemsToUpdate = [];
            $entries   = $output->logentry;

            $revision = $lastRevision;
        foreach ($entries as $entry) {
            $revision = (int) $entry->attributes()['revision'];
            $path     = (string) $entry->paths->path[0];
            preg_match('#/([A-z\-_]+)/#', $path, $matches);
            if ($matches) {
                $item                 = trim($matches[1]);
                $itemsToUpdate[$item] = [];
            }
        }
        return [
            'revision' => $revision,
            'items'    => $itemsToUpdate,
        ];
    }

    /**
     * @inheritDoc
     */
    public function pullWholeItemsList(string $type): array
    {
        $fs = $this->filesystem;
        $filename = "/opt/aspiresync/data/raw-svn-$type-list";
        $tmpname = $filename . ".tmp";
        if ($fs->fileExists($filename) && $fs->lastModified($filename) > time() - 86400) {
            $items    = $fs->read($filename);
            $contents = $items;
        } else {
            try {
                $items    = $this->guzzle->get('https://' . $type . '.svn.wordpress.org/', ['headers' => ['AspireSync']]);
                $contents = $items->getBody()->getContents();
                // $fs->write($tmpname, $contents);
                // $fs->move($tmpname, $filename);
                file_put_contents($filename, $contents);
                rename($tmpname, $filename);
                $items = $contents;
            } catch (ClientException $e) {
                throw new RuntimeException("Unable to download $type list: " . $e->getMessage());
            }
        }
        preg_match_all('#<li><a href="([^/]+)/">([^/]+)/</a></li>#', $items, $matches);
        $items = $matches[1];

        $itemsToReturn = [];
        foreach ($items as $item) {
            $itemsToReturn[$item] = [];
        }

        preg_match('/Revision ([0-9]+)\:/', $contents, $matches);
        $revision = (int) $matches[1];

        $filename = "/opt/aspiresync/data/raw-$type-list";
        $tmpname = $filename . ".tmp";
        // $fs->write($tmpname, implode(PHP_EOL, $items));
        // $fs->move($tmpname, $filename);
        file_put_contents($filename, implode(PHP_EOL, $items));
        rename($tmpname, $filename);

        return ['items' => $itemsToReturn, 'revision' => $revision];
    }
}
