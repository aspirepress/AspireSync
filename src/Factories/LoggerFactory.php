<?php

declare(strict_types=1);

namespace App\Factories;

use App\Services\JsonLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class LoggerFactory
{
    public function __invoke(
        #[Autowire(param: 'log_file')] string $file,
        #[Autowire(param: 'log_level')] string $level,
    ): LoggerInterface {
        return new JsonLogger($file, strtolower($level));
    }
}
