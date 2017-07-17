<?php

namespace SilverStripe\Core\Tests\Manifest;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Manifest\VersionProvider;
use SilverStripe\Dev\SapphireTest;

class VersionProviderTest extends SapphireTest
{
    /**
     * @var VersionProvider
     */
    protected $provider;

    public function setUp()
    {
        parent::setUp();
        $this->provider = new VersionProvider;
    }

    public function testGetModules()
    {
        Config::modify()->set(VersionProvider::class, 'modules', [
            'silverstripe/somepackage' => 'Some Package',
            'silverstripe/hidden' => '',
            'silverstripe/another' => 'Another'
        ]);

        $result = $this->provider->getModules();
        $this->assertArrayHasKey('silverstripe/somepackage', $result);
        $this->assertSame('Some Package', $result['silverstripe/somepackage']);
        $this->assertArrayHasKey('silverstripe/another', $result);
        $this->assertArrayNotHasKey('silverstripe/hidden', $result);
    }

    public function testGetModuleVersionFromComposer()
    {
        Config::modify()->set(VersionProvider::class, 'modules', [
            'silverstripe/framework' => 'Framework',
            'silverstripe/siteconfig' => 'SiteConfig'
        ]);

        $result = $this->provider->getModules(['silverstripe/framework']);
        $this->assertArrayHasKey('silverstripe/framework', $result);
        $this->assertNotEmpty($result['silverstripe/framework']);
    }

    public function testGetVersion()
    {
        Config::modify()->set(VersionProvider::class, 'modules', [
            'silverstripe/framework' => 'Framework',
            'silverstripe/siteconfig' => 'SiteConfig'
        ]);

        $result = $this->provider->getVersion();
        $this->assertContains('SiteConfig: ', $result);
        $this->assertContains('Framework: ', $result);
        $this->assertContains(', ', $result);
    }

    public function testGetModulesFromComposerLock()
    {
        $mock = $this->getMockBuilder(VersionProvider::class)
            ->setMethods(['getComposerLock'])
            ->getMock();

        $mock->expects($this->once())
            ->method('getComposerLock')
            ->will($this->returnValue([
                'packages' => [
                    [
                        'name' => 'silverstripe/somepackage',
                        'version' => '1.2.3'
                    ],
                    [
                        'name' => 'silverstripe/another',
                        'version' => '2.3.4'
                    ]
                ]
            ]));

        Config::modify()->set(VersionProvider::class, 'modules', [
            'silverstripe/somepackage' => 'Some Package'
        ]);

        $result = $mock->getVersion();
        $this->assertContains('Some Package: 1.2.3', $result);
    }
}
