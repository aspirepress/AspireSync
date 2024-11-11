<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services;

use AspirePress\AspireSync\Services\Interfaces\SubversionServiceInterface;
use AspirePress\AspireSync\Utilities\FileUtil;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use RuntimeException;
use Symfony\Component\Process\Process;

class SubversionService implements SubversionServiceInterface
{
    public function __construct(private readonly GuzzleClient $guzzle)
    {
    }

    /** @return array{revision: string, slugs: string[]} */
    public function getUpdatedSlugs(string $type, int $prevRevision, int $lastRevision): array
    {
        if ($prevRevision === $lastRevision) {
            return ['revision' => $lastRevision, 'slugs' => []];
        }

        $command = ['svn', 'log', '-v', '-q', '--xml', "https://$type.svn.wordpress.org", "-r", "$lastRevision:HEAD"];
        // example: svn log -v -q --xml https://plugins.svn.wordpress.org -r 3179185:HEAD

        $process = new Process($command);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException("Unable to get list of $type to update: {$process->getErrorOutput()}");
        }

        $output = simplexml_load_string($process->getOutput());

        $slugs   = [];
        $entries = $output->logentry;

        $revision = $lastRevision;
        foreach ($entries as $entry) {
            $revision = (int) $entry->attributes()['revision'];
            $path     = (string) $entry->paths->path[0];
            preg_match('#/([A-z\-_]+)/#', $path, $matches);
            if ($matches) {
                $item         = trim($matches[1]);
                $slugs[$item] = [];
            }
        }
        return ['revision' => $revision, 'slugs' => $slugs];
    }

    /** @return array{slugs: string[], revision: int} */
    public function pullWholeItemsList(string $type): array
    {
        global $APP_DIR;
        @mkdir("$APP_DIR/data");
        $html = FileUtil::cacheFile(
            "$APP_DIR/data/raw-svn-$type-list",
            86400,
            fn() => $this->fetchRawSvnHtml($type)
        );

        $matches = [];
        preg_match_all('#<li><a href="([^/]+)/">([^/]+)/</a></li>#', $html, $matches);
        $items = $matches[1];

        $slugs = [];
        foreach ($items as $item) {
            $slugs[$item] = [];
        }

        preg_match('/Revision (\d+):/', $html, $matches);
        $revision = (int) $matches[1];

        FileUtil::writeLines("$APP_DIR/data/raw-$type-list", $items);

        return ['slugs' => $slugs, 'revision' => $revision];
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
