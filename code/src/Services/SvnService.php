<?php

declare(strict_types=1);

namespace AssetGrabber\Services;

use AssetGrabber\Services\Interfaces\SvnServiceInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use RuntimeException;
use SimpleXMLElement;
use Symfony\Component\Process\Process;

class SvnService implements SvnServiceInterface
{
    public function getRevisionForType(string $type, int $prevRevision, int $lastRevision): ?SimpleXMLElement
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
            throw new RuntimeException('Unable to get list of ' . $type . ' to update' . $process->getErrorOutput());
        }

        return simplexml_load_string($process->getOutput());
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
