<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands;

use AspirePress\AspireSync\Commands\AbstractBaseCommand;
use AspirePress\AspireSync\Services\Interfaces\CacheServiceInterface;
use AspirePress\AspireSync\Services\Interfaces\WpEndpointClientInterface;
use AspirePress\AspireSync\Services\Plugins\PluginListService;
use AspirePress\AspireSync\Services\Plugins\PluginMetadataService;
use AspirePress\AspireSync\Services\SubversionService;
use AspirePress\AspireSync\Utilities\StringUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SandboxCommand extends AbstractBaseCommand
{
    public function __construct() {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('sandbox')
            ->setDescription('do whatever testing in a command')
            ->setHidden();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->log->info("Brillant!", ['foo' => 123, 'bar' => ['baz' => 'xyzzy']]);
        $this->error("Boom!");
        return Command::SUCCESS;
    }
}
