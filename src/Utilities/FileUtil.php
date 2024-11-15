<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Utilities;

use Closure;
use RuntimeException;
use Safe\Exceptions\JsonException;

use function Safe\filemtime;
use function Safe\json_decode;
use function Safe\json_encode;

abstract class FileUtil
{
    public static function read(string $path): string
    {
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException("Unable to read file {$path}");
        }
        return $contents;
    }

    /** @return string[] */
    public static function readLines(string $path): array
    {
        return explode(PHP_EOL, static::read($path));
    }

    public static function readJson(string $path): array  // @phpstan-ignore missingType.iterableValue
    {
        $content = json_decode(static::read($path), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($content)) {
            throw new RuntimeException("Cannot decode json file {$path} -- content is not an object or array");
        }
        return $content;
    }

    public static function write(string $path, string $content): void
    {
        $dir = dirname($path);
        is_dir($dir) or throw new RuntimeException("No such directory: $dir");

        $tmpname = tempnam(dirname($path), "tmp_XXXXXXXX");
        $result  = file_put_contents($tmpname, $content);
        if ($result === false) {
            throw new RuntimeException("Unable to write to tempfile {$tmpname}");
        }

        $result = rename($tmpname, $path);
        if ($result === false) {
            throw new RuntimeException("Unable to rename {$tmpname} to $path");
        }
    }

    /**
     * @param string[] $lines
     */
    public static function writeLines(string $path, array $lines): void
    {
        static::write($path, implode(PHP_EOL, $lines));
    }

    /**
     * @param array<string|int,mixed> $data
     * @throws JsonException
     */
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
            throw new RuntimeException("Unable to write file {$path}");
        }
    }

    public static function cacheFile(string $path, int $maxAgeSecs, Closure $callback): string
    {
        if (file_exists($path) && filemtime($path) > time() - $maxAgeSecs) {
            return static::read($path);
        }
        $content = $callback($path);
        static::write($path, $content);
        return $content;
    }
}
