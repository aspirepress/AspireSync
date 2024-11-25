<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Tests\Unit\Utilities\WP;

use AspirePress\AspireSync\Utilities\WP\FileMetadataParser;
use PHPUnit\Framework\TestCase;

class FileMetadataParserTest extends TestCase
{
    public function testReadPluginMetadata(): void
    {
        $header = <<<HEADER
        /*
         * Plugin Name: Plugin Directory
         * Plugin URI: https://wordpress.org/plugins/
         * Description: Transforms a WordPress site in The Official Plugin Directory.
         * Version: 3.0
         * Author: the WordPress team
         * Author URI: https://wordpress.org/
         * Text Domain: wporg-plugins
         * License: GPLv2
         * License URI: https://opensource.org/licenses/gpl-2.0.php
        */
        HEADER;

        $metadata = FileMetadataParser::readPluginMetadata($header);

        $this->assertEquals('Plugin Directory', $metadata['Name']);
        $this->assertEquals('https://wordpress.org/plugins/', $metadata['PluginURI']);
        $this->assertEquals('Transforms a WordPress site in The Official Plugin Directory.', $metadata['Description']);
        $this->assertEquals('3.0', $metadata['Version']);
        $this->assertEquals('the WordPress team', $metadata['Author']);
        $this->assertEquals('https://wordpress.org/', $metadata['AuthorURI']);
        $this->assertEquals('wporg-plugins', $metadata['TextDomain']);
        $this->assertEquals('GPLv2', $metadata['License']);
        $this->assertEquals('https://opensource.org/licenses/gpl-2.0.php', $metadata['LicenseURI']);
        $this->assertEquals('', $metadata['DomainPath']);
        $this->assertEquals('', $metadata['Network']);
        $this->assertEquals('', $metadata['RequiresWP']);
        $this->assertEquals('', $metadata['RequiresPHP']);
        $this->assertEquals('', $metadata['UpdateURI']);
        $this->assertEquals('', $metadata['RequiresPlugins']);
    }

    /** @noinspection HttpUrlsUsage */
    public function testThemeMetadata(): void
    {
        $header = <<<HEADER
        /*
        Theme Name: Twenty Twenty-Five
        Theme URI: https://wp.org/themes/twentytwentyfive/
        Author: the WordPress team
        Author URI: https://wp.org
        Description: Twenty Twenty-Five emphasizes simplicity and adaptability. It offers flexible design options, supported by a variety of patterns for different page types, such as services and landing pages, making it ideal for building personal blogs, professional portfolios, online magazines, or business websites. Its templates cater to various blog styles, from text-focused to image-heavy layouts. Additionally, it supports international typography and diverse color palettes, ensuring accessibility and customization for users worldwide.
        Requires at least: 6.7
        Tested up to: 6.7
        Requires PHP: 7.2.24
        Version: 1.0
        License: GNU General Public License v2 or later
        License URI: http://www.gnu.org/licenses/gpl-2.0.html
        Text Domain: twentytwentyfive
        Tags: one-column, custom-colors, custom-menu, custom-logo, editor-style, featured-images, full-site-editing, block-patterns, rtl-language-support, sticky-post, threaded-comments, translation-ready, wide-blocks, block-styles, style-variations, accessibility-ready, blog, portfolio, news
        */
        HEADER;

        $metadata = FileMetadataParser::readThemeMetadata($header);

        $this->assertEquals('Twenty Twenty-Five', $metadata['Name']);
        $this->assertEquals('https://wp.org/themes/twentytwentyfive/', $metadata['ThemeURI']);
        $this->assertEquals('the WordPress team', $metadata['Author']);
        $this->assertEquals('https://wp.org', $metadata['AuthorURI']);
        $this->assertStringStartsWith('Twenty Twenty-Five emphasizes simplicity and adaptability.', $metadata['Description']);
        $this->assertEquals('6.7', $metadata['RequiresWP']);
        $this->assertEquals('6.7', $metadata['TestedUpTo']);
        $this->assertEquals('7.2.24', $metadata['RequiresPHP']);
        $this->assertEquals('1.0', $metadata['Version']);
        $this->assertEquals('GNU General Public License v2 or later', $metadata['License']);
        $this->assertEquals('http://www.gnu.org/licenses/gpl-2.0.html', $metadata['LicenseURI']);
        $this->assertEquals('twentytwentyfive', $metadata['TextDomain']);
        $this->assertStringStartsWith('one-column, custom-colors, custom-menu, custom-logo, editor-style', $metadata['Tags']);
    }
}
