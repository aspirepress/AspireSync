<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services;

use DateTime;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use RuntimeException;
use Stringable;

/**
 * An "opinionated" PSR-3 logger class that logs in json format to a file, and does (almost) nothing else.
 * The lack of features and configurability is because it's expected that the log file/stream will
 * be processed by something like OpenTelemetry or fluentbit, making complex setups like monolog moot.
 *
 * Based on the MIT-licensed https://raw.githubusercontent.com/katzgrau/KLogger by Kenny Katzgrau
 */
class JsonLogger extends AbstractLogger
{
    public string $dateFormat = 'Y-m-d\TH:i:s.up';  // ISO8601 with microseconds

    protected string $filename;

    /** @var resource */
    protected $fileHandle;

    protected const LEVELS = [
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT     => 1,
        LogLevel::CRITICAL  => 2,
        LogLevel::ERROR     => 3,
        LogLevel::WARNING   => 4,
        LogLevel::NOTICE    => 5,
        LogLevel::INFO      => 6,
        LogLevel::DEBUG     => 7,
    ];

    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        $level = strtolower($level);
        if (static::LEVELS[$this->threshold] < static::LEVELS[$level]) {
            return;
        }
        $timestamp = (new DateTime())->format($this->dateFormat);
        $json      = json_encode(compact('timestamp', 'level', 'message', 'context'), JSON_THROW_ON_ERROR);
        $this->writeToLogFile($json . PHP_EOL);
    }

    public function __construct(string $path = 'php://stdout', public string $threshold = LogLevel::DEBUG)
    {
        $this->setLogFile($path);
    }

    public function setLogFile(string $path): void
    {
        $this->closeLogFile();
        $this->filename = $path;
        $this->openLogFile();
    }

    public function __destruct()
    {
        $this->closeLogFile();
    }

    protected function openLogFile(): void
    {
        $file = $this->filename;
        $mode = str_starts_with($file, 'php://') ? 'w+' : 'a';
        $fh   = fopen($file, $mode);
        if (! $fh) {
            $errorMessage = error_get_last()['message'] ?? '(no error information available)';
            throw new RuntimeException('Unable to open $file: ' . $errorMessage);
        }
        $this->fileHandle = $fh;
    }

    protected function closeLogFile(): void
    {
        if (! $this->fileHandle || str_starts_with($this->filename, 'php://')) {
            return;
        }
        fclose($this->fileHandle);
    }

    protected function writeToLogFile(string|Stringable $message): void
    {
        $this->fileHandle or throw new RuntimeException('Cannot write to $this->filename: file is closed.');
        if (! fwrite($this->fileHandle, $message)) {
            $errorMessage = error_get_last()['message'] ?? '(no error information available)';
            throw new RuntimeException('Cannot write to $this->filename: ' . $errorMessage);
        }
        fflush($this->fileHandle);
    }
}
