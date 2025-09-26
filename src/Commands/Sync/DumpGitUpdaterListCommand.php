<?php

declare(strict_types=1);

namespace App\Commands\Sync;

use App\Commands\AbstractBaseCommand;
use App\Integrations\GitUpdater\GitUpdaterConnector;
use App\Integrations\GitUpdater\UpdateApiRequest;
use App\Utilities\RegexUtil;
use Saloon\Http\Response;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DumpGitUpdaterListCommand extends AbstractBaseCommand
{
    private string $type;

    protected function configure(): void
    {
        $this->setName('sync:dump:git-updater-list')
            ->setDescription('Dump jsonl of metadata from list of Git Updater URLs')
            ->addArgument('file', InputArgument::OPTIONAL, 'List of URLs to process', 'php://stdin')
            ->addOption('type', null, InputArgument::OPTIONAL, 'Type of update', 'plugin');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->type = $input->getOption('type');
        $updates = $this->updatesFromFile($input->getArgument('file'));

        foreach ($updates as $base => $slugs) {
            $count = count($slugs);
            $this->log->info("Processing $count slugs from $base");
            $api = new GitUpdaterConnector($base);
            // $api->debugRequest();
            // $api->debugResponse();

            $requests = array_map(fn($slug) => new UpdateApiRequest($slug), $slugs);

            $pool = $api->pool(
                requests: $requests,
                concurrency: 2,
                responseHandler: $this->handleResponse(...),
                exceptionHandler: $this->handleException(...),
            );
            $pool->send()->wait();
        }

        return Command::SUCCESS;
    }

    /** @return array<string, array<string>> */
    private function updatesFromFile(string $filename): array
    {
        $raw = \Safe\file_get_contents($filename);
        $lines = explode(PHP_EOL, $raw);

        $updates = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $m = RegexUtil::match('!^(https://.*?/git-updater/v1)/update-api.*slug=([-_a-z0-9]+)!', $line);
            if (!$m) {
                $this->log->warning("Unmatched line: $line");
            } else {
                [, $base, $slug] = $m;
                $this->log->debug("base=$base slug=$slug");
                $updates[$base] ??= [];
                $updates[$base][] = $slug;
            }
        }
        return $updates;
    }

    private function handleResponse(Response $response): void
    {
        $metadata = $response->json();
        $aspiresync_meta = [
            // AspireCloud requires these be present
            'slug' => $metadata['slug'],
            'type' => $this->type,
            'status' => 'open',
            'origin' => 'gu',

            // Not required, just good documentation
            'gu_base' => $response->getConnector()->resolveBaseUrl(),
        ];

        $metadata['aspiresync_meta'] = $aspiresync_meta;
        $line = \Safe\json_encode($metadata);
        $line = str_replace(PHP_EOL, '', $line); // AC's lame ndjson parser requires one physical line per object
        echo $line . PHP_EOL;
    }

    private function handleException(\Throwable $exception): void
    {
        $this->log->error($exception->getMessage());
    }
}
