<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services;

use DateTime;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use RuntimeException;
use Stringable;

/**
 * An "opinionated" PSR-3 logger class that logs in json format to a file, and that's it.
 * It's expected that something like Fluentd/Grafana/OpenTelemetry will process the log further.
 *
 * Derived from https://raw.githubusercontent.com/katzgrau/KLogger by Kenny Katzgrau.
 */
class JsonLogger extends AbstractLogger
{
    public string $dateFormat = 'Y-m-d\TH:i:s.up';  // ISO8601 with microseconds

    protected string $logFile;

    /** @var resource */
    protected $fileHandle;

    public const LEVELS = [
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
        $level = static::normalizeLevel($level);
        if (static::LEVELS[$this->threshold] < static::LEVELS[$level]) {
            return;
        }
        $timestamp = (new DateTime())->format($this->dateFormat);
        $record = compact('timestamp', 'level', 'message');
        $context and $record['context'] = $context;
        $json      = json_encode($record, JSON_THROW_ON_ERROR);
        $this->writeToLogFile($json . PHP_EOL);
    }

    public function __construct(string $path = 'php://stdout', public string $threshold = LogLevel::DEBUG)
    {
        $this->setLogFile($path);
    }

    public function setLogFile(string $path): void
    {
        $this->closeLogFile();
        $this->logFile = $path;
        $this->openLogFile();
    }

    public static function normalizeLevel(string|int $level): string
    {
        static $keys;
        $keys ??= array_keys(static::LEVELS);

        if (is_int($level)) {
            // clamp level range so -1 = emergency, 999 = debug
            $level = min($level, static::LEVELS['debug']);
            $level = max($level, static::LEVELS['emergency']);
            $level = $keys[$level]; // convert to string

        }
        $level = strtolower($level);
        // an unrecognized level string does turn into 'debug'. we can't safely guess otherwise.
        return $keys[self::LEVELS[$level] ?? 'debug'] ?? 'debug';
    }

    public function __destruct()
    {
        $this->closeLogFile();
    }

    protected function openLogFile(): void
    {
        $file = $this->logFile;
        $mode = str_starts_with($file, 'php://') ? 'w+' : 'a';
        $fh   = fopen($file, $mode);
        if (! $fh) {
            $errorMessage = error_get_last()['message'] ?? '(no error information available)';
            throw new RuntimeException("Unable to open $file: $errorMessage");
        }
        $this->fileHandle = $fh;
    }

    protected function closeLogFile(): void
    {
        if (! $this->fileHandle || str_starts_with($this->logFile, 'php://')) {
            return;
        }
        fclose($this->fileHandle);
    }

    protected function writeToLogFile(string|Stringable $message): void
    {
        $this->fileHandle or throw new RuntimeException("Cannot write to $this->logFile: file is closed.");
        if (! fwrite($this->fileHandle, $message)) {
            $errorMessage = error_get_last()['message'] ?? '(no error information available)';
            throw new RuntimeException("Cannot write to $this->logFile: $errorMessage");
        }
        fflush($this->fileHandle);
    }
}
