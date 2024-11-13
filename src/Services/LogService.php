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
class LogService extends AbstractLogger
{
    // TODO: nuke and/or hardwire almost all of this
    protected array $options = [
        'extension'      => 'txt',
        'dateFormat'     => 'Y-m-d G:i:s.u',
        'filename'       => false,
        'flushFrequency' => false,
        'prefix'         => 'log_',
        'logFormat'      => false,
        'appendContext'  => true,
    ];

    private string $logFilePath;

    protected string|int $logLevelThreshold = LogLevel::DEBUG;  // TODO make this just int and translate PSR-3 levels to it

    private int $logLineCount = 0;  // TODO: nuke

    // could be a const, or maybe just made public so one can tweak them in edge cases
    protected array $logLevels = [
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT     => 1,
        LogLevel::CRITICAL  => 2,
        LogLevel::ERROR     => 3,
        LogLevel::WARNING   => 4,
        LogLevel::NOTICE    => 5,
        LogLevel::INFO      => 6,
        LogLevel::DEBUG     => 7,
    ];

    /** @var resource */
    private $fileHandle;

    private string $lastLine = '';  // TODO: nuke (used only in unit tests)

    private int $defaultPermissions = 0777; // modified by umask one hopes.  XXX still no reason to make it +x tho.

    public function __construct(string $logDirectory = 'php://stdout', string $logLevelThreshold = LogLevel::DEBUG, array $options = [])
    {
        $this->logLevelThreshold = $logLevelThreshold;
        $this->options           = array_merge($this->options, $options);

        $logDirectory = rtrim($logDirectory, DIRECTORY_SEPARATOR);
        if (! file_exists($logDirectory)) {
            mkdir($logDirectory, $this->defaultPermissions, true);
        }

        if (str_starts_with($logDirectory, 'php://')) {
            $this->setLogToStdOut($logDirectory);
            $this->setFileHandle('w+');
        } else {
            $this->setLogFilePath($logDirectory);
            if (file_exists($this->logFilePath) && ! is_writable($this->logFilePath)) {
                throw new RuntimeException('The file could not be written to. Check that appropriate permissions have been set.');
            }
            $this->setFileHandle('a');
        }

        if (! $this->fileHandle) {
            throw new RuntimeException('The file could not be opened. Check permissions.');
        }
    }

    public function setLogToStdOut(string $stdOutPath): void
    {
        $this->logFilePath = $stdOutPath;
    }

    public function setLogFilePath(string $logDirectory): void
    {
        if ($this->options['filename']) {
            if (
                str_contains($this->options['filename'], '.log')
                || str_contains($this->options['filename'], '.txt')
            ) {
                $this->logFilePath = $logDirectory . DIRECTORY_SEPARATOR . $this->options['filename'];
            } else {
                $this->logFilePath = $logDirectory
                    . DIRECTORY_SEPARATOR
                    . $this->options['filename']
                    . '.'
                    . $this->options['extension'];
            }
        } else {
            $this->logFilePath = $logDirectory
                . DIRECTORY_SEPARATOR
                . $this->options['prefix']
                . date('Y-m-d')
                . '.'
                . $this->options['extension'];
        }
    }

    public function setFileHandle(string $writeMode): void
    {
        $this->fileHandle = fopen($this->logFilePath, $writeMode);
    }

    public function __destruct()
    {
        if ($this->fileHandle) {
            fclose($this->fileHandle);
        }
    }

    public function setDateFormat(string $dateFormat): void
    {
        $this->options['dateFormat'] = $dateFormat;
    }

    public function setLogLevelThreshold(string $logLevelThreshold): void
    {
        $this->logLevelThreshold = $logLevelThreshold;
    }

    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        if ($this->logLevels[$this->logLevelThreshold] < $this->logLevels[$level]) {
            return;
        }
        $message = $this->formatMessage($level, $message, $context);
        $this->write($message);
    }

    public function write(string|Stringable $message): void
    {
        if (null !== $this->fileHandle) {
            if (fwrite($this->fileHandle, $message) === false) {
                throw new RuntimeException('The file could not be written to. Check that appropriate permissions have been set.');
            } else {
                $this->lastLine = trim($message);
                $this->logLineCount++;

                if ($this->options['flushFrequency'] && $this->logLineCount % $this->options['flushFrequency'] === 0) {
                    fflush($this->fileHandle);
                }
            }
        }
    }

    public function getLogFilePath(): string
    {
        return $this->logFilePath;
    }

    public function getLastLogLine(): string
    {
        return $this->lastLine;
    }

    protected function formatMessage(string $level, string $message, array $context): string
    {
        if ($this->options['logFormat']) {
            $parts   = [
                'date'          => $this->getTimestamp(),
                'level'         => strtoupper($level),
                'level-padding' => str_repeat(' ', 9 - strlen($level)),
                'priority'      => $this->logLevels[$level],
                'message'       => $message,
                'context'       => json_encode($context, JSON_THROW_ON_ERROR),
            ];
            $message = $this->options['logFormat'];
            foreach ($parts as $part => $value) {
                $message = str_replace('{' . $part . '}', $value, $message);
            }
        } else {
            $message = "[{$this->getTimestamp()}] [{$level}] {$message}";
        }

        if ($this->options['appendContext'] && ! empty($context)) {
            $message .= PHP_EOL . $this->indent($this->contextToString($context));
        }

        return $message . PHP_EOL;
    }

    private function getTimestamp(): string
    {
        $originalTime = microtime(true);
        $micro        = sprintf("%06d", ($originalTime - floor($originalTime)) * 1000000);
        $date         = new DateTime(date('Y-m-d H:i:s.' . $micro, (int) $originalTime));

        return $date->format($this->options['dateFormat']);
    }

    // TODO: nuke this with extreme prejudice
    protected function contextToString(array $context): string
    {
        $export = '';
        foreach ($context as $key => $value) {
            $export .= "{$key}: ";
            $export .= preg_replace([
                '/=>\s+([a-zA-Z])/im',
                '/array\(\s+\)/im',
                '/^ {2}|\G {2}/m',
            ], [
                '=> $1',
                'array()',
                '    ',
            ], str_replace('array (', 'array(', var_export($value, true)));
            $export .= PHP_EOL;
        }
        return str_replace(['\\\\', '\\\''], ['\\', '\''], rtrim($export));
    }

    // TODO: nuke (json isn't going to need indenting)
    protected function indent($string, $indent = '    '): string
    {
        return $indent . str_replace("\n", "\n" . $indent, $string);
    }
}
