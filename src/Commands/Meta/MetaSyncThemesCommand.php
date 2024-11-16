<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Meta;

use AspirePress\AspireSync\Commands\Meta\AbstractMetaSyncCommand;
use AspirePress\AspireSync\Integrations\Wordpress\ThemeRequest;
use AspirePress\AspireSync\Integrations\Wordpress\WordpressApiConnector;
use AspirePress\AspireSync\Resource;
use AspirePress\AspireSync\Services\Themes\ThemeListService;
use AspirePress\AspireSync\Services\Themes\ThemeMetadataService;
use AspirePress\AspireSync\Utilities\StringUtil;
use Saloon\Http\Request;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MetaSyncThemesCommand extends AbstractMetaSyncCommand
{
    public function __construct(
        ThemeListService $listService,
        ThemeMetadataService $meta,
        WordpressApiConnector $api,
    ) {
        parent::__construct($listService, $meta, $api);
    }

    protected Resource $resource = Resource::Theme;

    protected function makeRequest($slug): Request
    {
        return new ThemeRequest($slug);
    }

    protected function configure(): void
    {
        $this->setName('meta:sync:themes')
            ->setDescription('Fetches the meta data of the themes')
            ->addOption('update-all', 'u', InputOption::VALUE_NONE, 'Update all theme meta-data; otherwise, we only update what has changed')
            ->addOption('skip-newer-than-secs', null, InputOption::VALUE_REQUIRED, 'Skip downloading metadata pulled more recently than N seconds')
            ->addOption('themes', null, InputOption::VALUE_OPTIONAL, 'List of themes (separated by commas) to explicitly update');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->writeMessage("Running command {$this->getName()}");
        $this->startTimer();

        $themes  = StringUtil::explodeAndTrim($input->getOption('themes') ?? '');
        $min_age = (int) $input->getOption('skip-newer-than-secs') ?: null;

        $this->debug('Getting list of themes...');
        $themesToUpdate = $this->listService->getItems($themes, $min_age);
        $this->info(count($themesToUpdate) . ' themes to download metadata for...');

        if (count($themesToUpdate) === 0) {
            $this->error('No theme metadata to download...exiting...');
            return Command::SUCCESS;
        }

        foreach ($themesToUpdate as $theme => $versions) {
            $this->fetch((string) $theme);
        }

        if ($input->getOption('themes')) {
            $this->debug("Not saving revision when --themes was specified");
        } else {
            $revision = $this->listService->preserveRevision($this->getName());
            $this->debug("Updated current revision to $revision");
        }
        $this->endTimer();

        return Command::SUCCESS;
    }
}
