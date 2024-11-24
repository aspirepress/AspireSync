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

        // upstream apparently doesn't care about licenses, as we're discovering...
        // $this->assertEquals('GPLv2', $metadata['License']);
        // $this->assertEquals('https://opensource.org/licenses/gpl-2.0.php', $metadata['LicenseURI']);

        $this->assertEquals('', $metadata['DomainPath']);
        $this->assertEquals('', $metadata['Network']);
        $this->assertEquals('', $metadata['RequiresWP']);
        $this->assertEquals('', $metadata['RequiresPHP']);
        $this->assertEquals('', $metadata['UpdateURI']);
        $this->assertEquals('', $metadata['RequiresPlugins']);
        $this->assertEquals('', $metadata['_sitewide']);

    }
}
