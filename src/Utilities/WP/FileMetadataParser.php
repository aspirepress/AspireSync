<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Utilities\WP;

use AspirePress\AspireSync\Utilities\RegexUtil;

class FileMetadataParser
{
    public static function readPluginMetadata(string $content): array
    {
        // https://developer.wordpress.org/plugins/plugin-basics/header-requirements/
        $headers = [
            'Name'            => 'Plugin Name',
            'PluginURI'       => 'Plugin URI',
            'Description'     => 'Description',
            'Version'         => 'Version',
            'RequiresWP'      => 'Requires at least',
            'RequiresPHP'     => 'Requires PHP',
            'Author'          => 'Author',
            'AuthorURI'       => 'Author URI',
            'License'         => 'License',
            'LicenseURI'      => 'License URI',
            'TextDomain'      => 'Text Domain',
            'DomainPath'      => 'Domain Path',
            'Network'         => 'Network', // if present, only value accepted is true
            'UpdateURI'       => 'Update URI',
            'RequiresPlugins' => 'Requires Plugins',
            'TestedUpTo'      => 'Tested up to', // from Import::add_extra_plugin_headers
            // freaks and misfits
            // '_sitewide   => 'Site Wide Only',  // deprecated since 3.0, use Network instead
            // 'Title'      => 'Plugin Name',     // set by parser, not a header
            // 'AuthorName' => 'Author',          // set by parser, not a header
        ];
        return self::readMetadata($content, $headers);
    }

    public static function readThemeMetadata(string $content): array
    {
        // https://developer.wordpress.org/themes/basics/main-stylesheet-style-css/#explanations
        $headers = [
            // required fields
            'Name'        => 'Theme Name',
            'Author'      => 'Author',
            'Description' => 'Description',
            'Version'     => 'Version',
            'RequiresWP'  => 'Requires at least',
            'RequiresPHP' => 'Requires PHP',
            'TextDomain'  => 'Text Domain',
            // required fields documented on wp.org but not in WP_Theme::$file_headers.
            'TestedUpTo' => 'Tested up to',
            'License'    => 'License',
            'LicenseURI' => 'License URI',
            // optional fields
            'ThemeURI'   => 'Theme URI',
            'AuthorURI'  => 'Author URI',
            'Tags'       => 'Tags',
            'Template'   => 'Template', // required in a child theme (all other fields except name become optional)
            'DomainPath' => 'Domain Path', // default: /languages
            // not documented on .org, presumably generated somewhere else
            'Status'    => 'Status',
            'UpdateURI' => 'Update URI',
        ];
        return self::readMetadata($content, $headers);
    }

    public static function readMetadata(string $content, array $headers): array
    {
        $metadata = [];
        foreach ($headers as $field => $key) {
            $pattern = '/^(?:[ \t]*<\?php)?[ \t\/*#@]*' . $key . ':(.*)$/mi';
            $matches = RegexUtil::match($pattern, $content);
            $value   = $matches[1] ?? '';

            $metadata[$field] = mb_trim(RegexUtil::replace('/\s*(?:\*\/|\?>).*/', '', $value));
        }
        return $metadata;
    }
}
