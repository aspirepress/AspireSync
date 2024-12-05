<?php

declare(strict_types=1);

namespace App\Services;

use App\ResourceType;
use App\Services\Interfaces\SubversionServiceInterface;
use App\Utilities\RegexUtil;
use GuzzleHttp\Client as GuzzleClient;
use RuntimeException;
use Symfony\Component\Process\Process;

class SubversionService implements SubversionServiceInterface
{
    public function __construct(private readonly GuzzleClient $guzzle) {}

    /** @return array{slugs: array<string|int, array{}>, revision: int} */
    public function getUpdatedSlugs(ResourceType $type, int $prevRevision, int $lastRevision): array
    {
        if ($prevRevision === $lastRevision) {
            return ['revision' => $lastRevision, 'slugs' => []];
        }

        $url = "https://{$type->plural()}.svn.wordpress.org";
        $command = ['svn', 'log', '-v', '-q', '--xml', $url, "-r", "$lastRevision:HEAD"];
        // example: svn log -v -q --xml https://plugins.svn.wordpress.org -r 3179185:HEAD

        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException("Unable to get list of {$type->plural()} to update: {$process->getErrorOutput()}");
        }

        $output = \Safe\simplexml_load_string($process->getOutput());

        $slugs   = [];
        $entries = $output->logentry;

        $revision = $lastRevision;
        foreach ($entries as $entry) {
            $revision = (int) $entry->attributes()['revision'];
            $path     = (string) $entry->paths->path[0];
            $matches  = RegexUtil::match('#/([A-z\-_]+)/#', $path);
            if ($matches) {
                $slug         = trim($matches[1]);
                $slugs[$slug] = [];
            }
        }
        return ['slugs' => $slugs, 'revision' => $revision];
    }

    /** @return array{slugs: array<string|int, array{}>, revision: int} */
    public function scrapeSlugsFromIndex(ResourceType $type): array
    {
        $html = $this->guzzle
            ->get("https://{$type->plural()}.svn.wordpress.org/")
            ->getBody()
            ->getContents();

        [, $rawslugs] = RegexUtil::matchAll('#<li><a href="([^/]+)/">([^/]+)/</a></li>#', $html);

        [, $revision] = RegexUtil::match('/Revision (\d+):/', $html);
        $revision = (int) $revision or throw new RuntimeException("Unable to get revision from {$type->plural()} index");

        $slugs = [];
        foreach ($rawslugs as $slug) {
            $slugs[urldecode($slug)] = [];
        }
        return ['slugs' => $slugs, 'revision' => $revision];
    }
}
