<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Utilities\WP;

use AspirePress\AspireSync\Utilities\RegexUtil;

class FileMetadataParser
{
    public static function readPluginMetadata(string $content): array
    {
        $headers = [
            'Name'            => 'Plugin Name',
            'PluginURI'       => 'Plugin URI',
            'Version'         => 'Version',
            'Description'     => 'Description',
            'Author'          => 'Author',
            'AuthorURI'       => 'Author URI',
            'TextDomain'      => 'Text Domain',
            'DomainPath'      => 'Domain Path',
            'Network'         => 'Network',
            'RequiresWP'      => 'Requires at least',
            'RequiresPHP'     => 'Requires PHP',
            'UpdateURI'       => 'Update URI',
            'RequiresPlugins' => 'Requires Plugins',
            // Site Wide Only is deprecated in favor of Network.
            '_sitewide' => 'Site Wide Only',
        ];
        return self::readMetadata($content, $headers);
    }

    public static function readMetadata(string $content, array $headers): array
    {
        $metadata = [];
        foreach ($headers as $field => $key) {
            $pattern          = '/^(?:[ \t]*<\?php)?[ \t\/*#@]*' . $key . ':(.*)$/mi';
            $matches          = RegexUtil::match($pattern, $content);
            $metadata[$field] = $matches ? self::_cleanup_header_comment($matches[1]) : '';
        }
        return $metadata;
    }

    public static function _cleanup_header_comment(string $str): string
    {
        return mb_trim(RegexUtil::replace('/\s*(?:\*\/|\?>).*/', '', $str));
    }
}
