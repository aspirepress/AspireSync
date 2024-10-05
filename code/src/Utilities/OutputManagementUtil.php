<?php

declare(strict_types=1);

namespace AssetGrabber\Utilities;

abstract class OutputManagementUtil
{
    private const ERROR   = '[ERROR]   ';
    private const WARNING = '[WARNING] ';
    private const NOTICE  = '[NOTICE]  ';
    private const INFO    = '[INFO]    ';
    private const DEBUG   = '[DEBUG]   ';

    private const SUCCESS = '[SUCCESS] ';

    private const FAILURE = '[FAILURE] ';

    public static function error(string|Iterable $message): string
    {
        return self::formatMessage(self::ERROR, $message);
    }

    public static function warning(string|Iterable $message): string
    {
        return self::formatMessage(self::WARNING, $message);
    }

    public static function notice(string|Iterable $message): string
    {
        return self::formatMessage(self::NOTICE, $message);
    }

    public static function info(string|Iterable $message): string
    {
        return self::formatMessage(self::INFO, $message);
    }

    public static function debug(string|Iterable $message): string
    {
        return self::formatMessage(self::DEBUG, $message);
    }

    public static function success(string|Iterable $message): string
    {
        return self::formatMessage(self::SUCCESS, $message);
    }

    public static function failure(string|Iterable $message): string
    {
        return self::formatMessage(self::FAILURE, $message);
    }

    private static function formatMessage(string $type, string|Iterable $messages)
    {
        $output = '';
        if (is_iterable($messages)) {
            foreach ($messages as $msg) {
                $output .= $type . $msg . PHP_EOL;
            }
            return $output;
        }

        return $type . $messages;
    }

    public static function generic(string|Iterable $messages): string
    {
        if (is_iterable($messages)) {
            $output = '';
            foreach ($messages as $msg) {
                $output .= $msg . PHP_EOL;
            }
            return $output;
        }

        return $messages;
    }
}
