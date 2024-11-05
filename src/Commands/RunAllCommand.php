<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands;

use Exception;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunAllCommand extends AbstractBaseCommand
{
    protected function configure(): void
    {
        $this->setName('run:all')
            ->setDescription('Runs all commands for a particular call (plugins, themes, or all)')
            ->addArgument('asset-type', InputArgument::OPTIONAL, 'What assets to get', 'all');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $assetType = $input->getArgument('asset-type');
        $this->startTimer();
        switch ($assetType) {
            case 'plugins':
                $this->always('RUN:ALL: Performing plugin commands...');
                $result = $this->runPlugins();
                break;

            case 'themes':
                $this->always('RUN:ALL: Performing theme commands...');
                $result = $this->runThemes();
                break;

            case 'all':
                $this->always('RUN:ALL: Performing all commands...');
                $result1 = $this->runPlugins();
                $result2 = $this->runThemes();
                $result  = $result1 === $result2 ? self::SUCCESS : self::FAILURE;
                break;

            default:
                $this->error('Unknown asset type: ' . $assetType);
                return self::FAILURE;
        }
        $this->endTimer();

        $this->always(array_merge(['RUN:ALL: '], $this->getRunInfo()));
        return $result;
    }

    private function runPlugins(): int
    {
        $commands = [
            'plugins:meta',
            'download:plugins',
            'util:upload',
        ];

        foreach ($commands as $command) {
            $result = $this->runCommand($command, 'plugins');
            if ($result) {
                return $result;
            }
        }

        return self::SUCCESS;
    }

    private function runThemes(): int
    {
        $commands = [
            'themes:meta',
            'download:themes',
            'util:upload',
        ];

        foreach ($commands as $command) {
            $result = $this->runCommand($command, 'themes');
            if ($result) {
                return $result;
            }
        }

        return self::SUCCESS;
    }

    private function runCommand(string $command, string $type): int
    {
        $commandArgs = ['command' => $command];

        if ($command === 'util:upload') {
            $commandArgs['action'] = $type;
        }

        $input = new ArrayInput($commandArgs);
        $app   = $this->getApplication();
        assert($app !== null);

        try {
            return $app->doRun($input, $this->io);
        } catch (Exception $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
