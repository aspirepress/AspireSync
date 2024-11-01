<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services;

use AspirePress\AspireSync\Services\Interfaces\SvnServiceInterface;
use AspirePress\AspireSync\Utilities\FileUtil;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use RuntimeException;
use Symfony\Component\Process\Process;

use function Safe\filemtime;

class SvnService implements SvnServiceInterface
{
    public function __construct(private readonly GuzzleClient $guzzle) {}

    public function getRevisionForType(string $type, int $prevRevision, int $lastRevision): array
    {
        $targetRev = (int)$lastRevision;
        $currentRev = 'HEAD';

        if ($targetRev === $prevRevision) {
            return [
                'revision' => $targetRev,
                'items' => [],
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
        // svn log -v -q --xml https://plugins.svn.wordpress.org -r 3179185:HEAD

        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException('Unable to get list of ' . $type . ' to update' . $process->getErrorOutput());
        }

        $output = simplexml_load_string($process->getOutput());

        $itemsToUpdate = [];
        $entries = $output->logentry;

        $revision = $lastRevision;
        foreach ($entries as $entry) {
            $revision = (int)$entry->attributes()['revision'];
            $path = (string)$entry->paths->path[0];
            preg_match('#/([A-z\-_]+)/#', $path, $matches);
            if ($matches) {
                $item = trim($matches[1]);
                $itemsToUpdate[$item] = [];
            }
        }
        return [
            'revision' => $revision,
            'items' => $itemsToUpdate,
        ];
    }

    public function pullWholeItemsList(string $type): array
    {
        $html = FileUtil::cacheFile(
            "/opt/aspiresync/data/raw-svn-$type-list",
            86400,
            fn() => $this->fetchRawSvnHtml($type)
        );

        $matches = [];
        preg_match_all('#<li><a href="([^/]+)/">([^/]+)/</a></li>#', $html, $matches);
        $items = $matches[1];

        $itemsToReturn = [];
        foreach ($items as $item) {
            $itemsToReturn[$item] = [];
        }

        preg_match('/Revision (\d+):/', $html, $matches);
        $revision = (int)$matches[1];

        FileUtil::writeLines("/opt/aspiresync/data/raw-$type-list", $items);

        return ['items' => $itemsToReturn, 'revision' => $revision];
    }

    private function fetchRawSvnHtml(string $type): string
    {
        try {
            return $this->guzzle
                ->get("https://$type.svn.wordpress.org/")
                ->getBody()
                ->getContents();
        } catch (ClientException $e) {
            throw new RuntimeException("Unable to download $type list: " . $e->getMessage());
        }
    }
}
