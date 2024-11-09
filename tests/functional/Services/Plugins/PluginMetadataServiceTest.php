<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Tests\Functional\Services\Plugins;

use AspirePress\AspireSync\Services\Plugins\PluginMetadataService;
use AspirePress\AspireSync\Tests\Functional\AbstractFunctionalTestBase;
use AspirePress\AspireSync\Tests\Helpers\FunctionalTestHelper;

class PluginMetadataServiceTest extends AbstractFunctionalTestBase
{
    private PluginMetadataService $sut;

    protected function setUp(): void
    {
        $this->sut = new PluginMetadataService(FunctionalTestHelper::getDb());
    }

    public function testSaveOpenPluginWithListOfVersions(): void
    {
        $metadata = [
            'name'          => 'Foo',
            'slug'          => 'foo',
            'version'       => '1.0',
            'download_link' => 'foo.com',
            'last_updated'  => '2024-01-01 00:00:00',
            'versions'      => [
                '1.0' => 'foo.com',
                '0.9' => 'bar.com',
            ],
        ];

        $result = $this->sut->saveOpenPlugin($metadata, date('c'));
        $this->assertEmpty($result['error']);

        $data = $this->sut->getData();
        $this->assertEquals(['foo'], array_keys($data));

        $versions = $this->sut->getVersionData($data['foo']);
        $this->assertCount(2, $versions);

        $versions = $this->sut->getVersionData($data['foo'], '0.9');
        $this->assertCount(1, $versions);

        $versions = $this->sut->getVersionData($data['foo'], '0.8');
        $this->assertCount(0, $versions);
    }

    public function testSaveOpenPluginWithNoListOfVersions(): void
    {
        $metadata = [
            'name'          => 'Foo',
            'slug'          => 'foo',
            'version'       => '1.0',
            'download_link' => 'foo.com',
            'last_updated'  => '2024-01-01 00:00:00',
            'versions'      => [],
        ];

        $result = $this->sut->saveOpenPlugin($metadata, date('c'));
        $this->assertEmpty($result['error']);

        $data = $this->sut->getData();
        $this->assertEquals(['foo'], array_keys($data));

        $versions = $this->sut->getVersionData($data['foo']);
        $this->assertCount(1, $versions);
        $this->assertEquals('1.0', $versions[0]['version']);
    }

    public function testSaveClosedPlugin(): void
    {
        $metadata = [
            'error'       => 'closed',
            'name'        => 'Foo',
            'slug'        => 'foo',
            'closed_date' => date('c'),
        ];

        $result = $this->sut->saveErrorPlugin($metadata, date('c'));
        $this->assertEmpty($result['error']);

        $data = $this->sut->getData();
        $this->assertEmpty($data);

        $db     = FunctionalTestHelper::getDb();
        $result = $db->fetchAll('SELECT * FROM sync_plugins');
        $this->assertCount(1, $result);
        $this->assertEquals('closed', $result[0]['status']);

        $result = $db->fetchOne('SELECT COUNT(*) as count FROM sync_plugin_files');
        $this->assertEquals(0, $result['count']);
    }

    public function testUnprocessedVersionsReturnedCorrectly(): void
    {
        $metadata = [
            'name'          => 'Foo',
            'slug'          => 'foo',
            'version'       => '1.0',
            'download_link' => 'foo.com',
            'last_updated'  => '2024-01-01 00:00:00',
            'versions'      => [
                '1.0' => 'foo.com',
                '0.9' => 'bar.com',
            ],
        ];

        $result = $this->sut->saveOpenPlugin($metadata, date('c'));
        $this->assertEmpty($result['error']);

        $data = $this->sut->getData();
        $this->assertEquals(['foo'], array_keys($data));

        $this->sut->setVersionToDownloaded('foo', '1.0');

        $versions = $this->sut->getUnprocessedVersions('foo', ['0.9', '1.0']);
        $this->assertCount(1, $versions);

        $versions = $this->sut->getVersionData($data['foo']);
        $this->assertCount(2, $versions);
    }

    public function testGetStorageDir(): void
    {
        $storageDir = $this->sut->getStorageDir();
        $this->assertEquals('/opt/aspiresync/data/plugins', $storageDir);
    }

    public function testGetS3Path(): void
    {
        $storageDir = $this->sut->getS3Path();
        $this->assertEquals('/plugins/', $storageDir);
    }

    public function testGetDataFiltersWhenAsked(): void
    {
        $metadata = [
            'name'          => 'Foo',
            'slug'          => 'foo',
            'version'       => '1.0',
            'download_link' => 'foo.com',
            'last_updated'  => '2024-01-01 00:00:00',
            'versions'      => [
                '1.0' => 'foo.com',
                '0.9' => 'bar.com',
            ],
        ];

        $metadata2 = [
            'name'          => 'Bar',
            'slug'          => 'bar',
            'version'       => '1.0',
            'download_link' => 'foo.com',
            'last_updated'  => '2024-01-01 00:00:00',
            'versions'      => [
                '1.0' => 'foo.com',
                '0.9' => 'bar.com',
            ],
        ];

        $metadata3 = [
            'name'          => 'Baz',
            'slug'          => 'baz',
            'version'       => '1.0',
            'download_link' => 'foo.com',
            'last_updated'  => '2024-01-01 00:00:00',
            'versions'      => [
                '1.0' => 'foo.com',
                '0.9' => 'bar.com',
            ],
        ];

        $this->sut->saveOpenPlugin($metadata, date('c'));
        $this->sut->saveOpenPlugin($metadata2, date('c'));
        $this->sut->saveOpenPlugin($metadata3, date('c'));

        $data = $this->sut->getData();
        $this->assertCount(3, $data);

        $data = $this->sut->getData(['foo']);
        $this->assertCount(1, $data);
        $this->assertTrue(isset($data['foo']));

        $data = $this->sut->getData(['foo', 'bar']);
        $this->assertCount(2, $data);
        $keys = array_keys($data);
        sort($keys);
        $this->assertEquals(['bar', 'foo'], $keys);
    }

    public function testCheckPluginInDatabaseWorksCorrectly(): void
    {
        $metadata = [
            'name'          => 'Bar',
            'slug'          => 'bar',
            'version'       => '1.0',
            'download_link' => 'foo.com',
            'last_updated'  => '2024-01-01 00:00:00',
            'versions'      => [
                '1.0' => 'foo.com',
                '0.9' => 'bar.com',
            ],
        ];

        $this->sut->saveOpenPlugin($metadata, date('c'));

        // We need a new SUT because we load existing plugins at construct time
        $sut = new PluginMetadataService(FunctionalTestHelper::getDb());
        $this->assertEmpty($sut->checkPluginInDatabase('foo'));
        $this->assertNotEmpty($sut->checkPluginInDatabase('bar'));
    }

    public function testUpdatePluginSelectsAndProcessesCorrectly(): void
    {
        $openMeta = [
            'name'          => 'Foo',
            'slug'          => 'foo',
            'version'       => '1.0',
            'download_link' => 'foo.com',
            'last_updated'  => '2024-01-01 00:00:00',
            'versions'      => [
                '1.0' => 'foo.com',
                '0.9' => 'bar.com',
            ],
        ];

        $currentlyOpenMeta = [
            'name'          => 'Bar',
            'slug'          => 'bar',
            'version'       => '1.0',
            'download_link' => 'foo.com',
            'last_updated'  => '2024-01-01 00:00:00',
            'versions'      => [
                '1.0' => 'foo.com',
                '0.9' => 'bar.com',
            ],
        ];

        $closedMeta = [
            'error'       => 'closed',
            'name'        => 'Baz',
            'slug'        => 'baz',
            'closed_date' => date('c'),
        ];

        $changeMeta = [
            'error'       => 'closed',
            'name'        => 'Bar',
            'slug'        => 'bar',
            'closed_date' => date('c'),
        ];

        $this->sut->saveOpenPlugin($openMeta, date('c'));
        $this->sut->saveOpenPlugin($currentlyOpenMeta, date('c'));
        $this->sut->saveErrorPlugin($changeMeta, date('c'));

        $data = $this->sut->getData();
        $this->assertCount(2, $data);
        $this->assertCount(2, $this->sut->getVersionData($data['foo']));

        $openMeta['versions']['1.1'] = 'bingo.com';
        $this->sut->updatePluginFromWP($openMeta, date('c'));
        $this->sut->updatePluginFromWP($changeMeta, date('c'));

        $data = $this->sut->getData();
        $this->assertCount(1, $data);
        $this->assertCount(0, $this->sut->getData(['bar']));

        $versions = $this->sut->getVersionData($data['foo']);
        $this->assertCount(3, $versions);
    }
}
