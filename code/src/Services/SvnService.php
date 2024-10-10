<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services;

use AspirePress\AspireSync\Services\Interfaces\SvnServiceInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use RuntimeException;
use Symfony\Component\Process\Process;

class SvnService implements SvnServiceInterface
{
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
        if (file_exists("/opt/assetgrabber/data/raw-svn-$type-list") && filemtime("/opt/assetgrabber/data/raw-svn-$type-list") > time() - 86400) {
            $items    = file_get_contents("/opt/assetgrabber/data/raw-svn-$type-list");
            $contents = $items;
        } else {
            try {
                $client   = new Client();
                $items    = $client->get('https://' . $type . '.svn.wordpress.org/', ['headers' => ['AssetGrabber']]);
                $contents = $items->getBody()->getContents();
                file_put_contents("/opt/assetgrabber/data/raw-svn-$type-list", $contents);
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

        file_put_contents("/opt/assetgrabber/data/raw-$type-list", implode(PHP_EOL, $items));
        return ['items' => $itemsToReturn, 'revision' => $revision];
    }
}
