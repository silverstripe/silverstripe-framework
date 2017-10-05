<?php

namespace SilverStripe\Core\Tests\Manifest;

use SilverStripe\Control\Director;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Core\Manifest\ModuleManifest;
use SilverStripe\Dev\SapphireTest;

class ModuleResourceTest extends SapphireTest
{
    /**
     * @var string
     */
    protected $base;

    /**
     * @var ModuleManifest
     */
    protected $manifest;

    protected function setUp()
    {
        parent::setUp();

        $this->base = dirname(__FILE__) . '/fixtures/classmanifest';
        $this->manifest = new ModuleManifest($this->base);
        $this->manifest->init();
        Director::config()->set('alternate_base_url', 'http://www.mysite.com/basefolder/');
    }

    public function testBaseModuleResource()
    {
        $modulea = $this->manifest->getModule('module');
        $resource = $modulea->getResource('client/script.js');

        // Test main resource
        $this->assertTrue($resource->exists());
        $this->assertEquals('module/client/script.js', $resource->getRelativePath());
        $this->assertEquals(
            __DIR__ . '/fixtures/classmanifest/module/client/script.js',
            $resource->getPath()
        );
        $this->assertStringStartsWith(
            '/basefolder/module/client/script.js?m=',
            $resource->getURL()
        );
    }

    public function testVendorModuleResources()
    {
        $modulec = $this->manifest->getModule('silverstripe/modulec');
        $resource = $modulec->getResource('client/script.js');

        // Test main resource
        $this->assertTrue($resource->exists());
        $this->assertEquals('vendor/silverstripe/modulec/client/script.js', $resource->getRelativePath());
        $this->assertEquals(
            __DIR__ . '/fixtures/classmanifest/vendor/silverstripe/modulec/client/script.js',
            $resource->getPath()
        );
        $this->assertStringStartsWith(
            '/basefolder/resources/silverstripe/modulec/client/script.js?m=',
            $resource->getURL()
        );
    }

    public function testRelativeResources()
    {
        $modulec = $this->manifest->getModule('silverstripe/modulec');
        $resource = $modulec
            ->getResource('client')
            ->getRelativeResource('script.js');

        // Test main resource
        $this->assertTrue($resource->exists());
        $this->assertEquals('vendor/silverstripe/modulec/client/script.js', $resource->getRelativePath());
        $this->assertEquals(
            __DIR__ . '/fixtures/classmanifest/vendor/silverstripe/modulec/client/script.js',
            $resource->getPath()
        );
        $this->assertStringStartsWith(
            '/basefolder/resources/silverstripe/modulec/client/script.js?m=',
            $resource->getURL()
        );
    }
}
