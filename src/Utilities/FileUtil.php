<?php

namespace AspirePress\AspireSync\Utilities;

use function Safe\json_decode;
use function Safe\json_encode;

abstract class FileUtil
{
    public static function read(string $path): string
    {
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new \RuntimeException("Unable to read file {$path}");
        }
        return $contents;
    }

    public static function readLines(string $path): array
    {
        return explode(PHP_EOL, static::read($path));
    }

    public static function readJson(string $path): array
    {
        $content = json_decode(static::read($path), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($content)) {
            throw new \RuntimeException("Cannot decode json file {$path} -- content is not an object or array");
        }
        return $content;
    }

    public static function write(string $path, string $content): void
    {
        $tmpname = tempnam(dirname($path), "tmp_XXXXXXXX");
        $result = file_put_contents($tmpname, $content);
        if ($result === false) {
            throw new \RuntimeException("Unable to write to tempfile {$tmpname}");
        }

        $result = rename($tmpname, $path);
        if ($result === false) {
            throw new \RuntimeException("Unable to rename {$tmpname} to $path");
        }
    }

    public static function writeLines(string $path, array $lines): void
    {
        static::write($path, implode(PHP_EOL, $lines));
    }

    public static function writeJson(
        string $path,
        array $data,
        int $flags = JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT,
    ): void {
        static::write($path, json_encode($data, $flags));
    }

    public static function writeRaw(string $path, string $content): void
    {
        $result = file_put_contents($path, $content);
        if ($result === false) {
            throw new \RuntimeException("Unable to write file {$path}");
        }
    }
}