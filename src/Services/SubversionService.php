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

    /** @return array{slugs: array<string, string[]>, revision: int} */
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
                $item         = trim($matches[1]);
                $slugs[$item] = [];
            }
        }
        return ['revision' => $revision, 'slugs' => $slugs];
    }

    /** @return array{slugs: string[], revision: int} */
    public function scrapeSlugsFromIndex(ResourceType $type): array
    {
        $html = $this->guzzle
            ->get("https://{$type->plural()}.svn.wordpress.org/")
            ->getBody()
            ->getContents();

        [, $slugs] = RegexUtil::matchAll('#<li><a href="([^/]+)/">([^/]+)/</a></li>#', $html);

        [, $revision] = RegexUtil::match('/Revision (\d+):/', $html);

        $slugs = array_map(urldecode(...), $slugs);
        // $slugs = array_map(fn ($slug) => (string) urldecode($slug), $slugs);

        return ['slugs' => $slugs, 'revision' => (int) $revision];
    }
}
