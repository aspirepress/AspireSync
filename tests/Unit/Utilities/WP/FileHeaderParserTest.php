<?php

declare(strict_types=1);

namespace App\Tests\Unit\Utilities\WP;

use App\Utilities\WP\FileHeaderParser;
use PHPUnit\Framework\TestCase;

class FileHeaderParserTest extends TestCase
{
    public function testReadPluginHeader(): void
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

        $headers = FileHeaderParser::readPluginHeader($header);

        $this->assertEquals('Plugin Directory', $headers['Name']);
        $this->assertEquals('https://wordpress.org/plugins/', $headers['PluginURI']);
        $this->assertEquals('Transforms a WordPress site in The Official Plugin Directory.', $headers['Description']);
        $this->assertEquals('3.0', $headers['Version']);
        $this->assertEquals('the WordPress team', $headers['Author']);
        $this->assertEquals('https://wordpress.org/', $headers['AuthorURI']);
        $this->assertEquals('wporg-plugins', $headers['TextDomain']);
        $this->assertEquals('GPLv2', $headers['License']);
        $this->assertEquals('https://opensource.org/licenses/gpl-2.0.php', $headers['LicenseURI']);
        $this->assertEquals('', $headers['DomainPath']);
        $this->assertEquals('', $headers['Network']);
        $this->assertEquals('', $headers['RequiresWP']);
        $this->assertEquals('', $headers['RequiresPHP']);
        $this->assertEquals('', $headers['UpdateURI']);
        $this->assertEquals('', $headers['RequiresPlugins']);
    }

    /** @noinspection HttpUrlsUsage */
    public function testThemeHeader(): void
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

        $headers = FileHeaderParser::readThemeHeader($header);

        $this->assertEquals('Twenty Twenty-Five', $headers['Name']);
        $this->assertEquals('https://wp.org/themes/twentytwentyfive/', $headers['ThemeURI']);
        $this->assertEquals('the WordPress team', $headers['Author']);
        $this->assertEquals('https://wp.org', $headers['AuthorURI']);
        $this->assertStringStartsWith('Twenty Twenty-Five emphasizes simplicity and adaptability.', $headers['Description']);
        $this->assertEquals('6.7', $headers['RequiresWP']);
        $this->assertEquals('6.7', $headers['TestedUpTo']);
        $this->assertEquals('7.2.24', $headers['RequiresPHP']);
        $this->assertEquals('1.0', $headers['Version']);
        $this->assertEquals('GNU General Public License v2 or later', $headers['License']);
        $this->assertEquals('http://www.gnu.org/licenses/gpl-2.0.html', $headers['LicenseURI']);
        $this->assertEquals('twentytwentyfive', $headers['TextDomain']);
        $this->assertStringStartsWith('one-column, custom-colors, custom-menu, custom-logo, editor-style', $headers['Tags']);
    }
}
