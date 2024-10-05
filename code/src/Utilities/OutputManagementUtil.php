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

    /**
     * @param string|iterable<int, string> $message
     */
    public static function error(string|iterable $message): string
    {
        return self::formatMessage(self::ERROR, $message);
    }

    /**
     * @param string|iterable<int, string> $message
     */
    public static function warning(string|iterable $message): string
    {
        return self::formatMessage(self::WARNING, $message);
    }

    /**
     * @param string|iterable<int, string> $message
     */
    public static function notice(string|iterable $message): string
    {
        return self::formatMessage(self::NOTICE, $message);
    }

    /**
     * @param string|iterable<int, string> $message
     */
    public static function info(string|iterable $message): string
    {
        return self::formatMessage(self::INFO, $message);
    }

    /**
     * @param string|iterable<int, string> $message
     */
    public static function debug(string|iterable $message): string
    {
        return self::formatMessage(self::DEBUG, $message);
    }

    /**
     * @param string|iterable<int, string> $message
     */
    public static function success(string|iterable $message): string
    {
        return self::formatMessage(self::SUCCESS, $message);
    }

    /**
     * @param string|iterable<int, string> $message
     */
    public static function failure(string|iterable $message): string
    {
        return self::formatMessage(self::FAILURE, $message);
    }

    /**
     * @param string|iterable<int, string> $messages
     */
    private static function formatMessage(string $type, string|iterable $messages): string
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

    /**
     * @param string|iterable<int, string> $messages
     */
    public static function generic(string|iterable $messages): string
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
