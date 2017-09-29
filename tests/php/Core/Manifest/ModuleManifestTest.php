<?php

namespace SilverStripe\Core\Tests\Manifest;

use SilverStripe\Core\Manifest\ModuleManifest;
use SilverStripe\Dev\SapphireTest;

class ModuleManifestTest extends SapphireTest
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
    }

    public function testGetModules()
    {
        $modules = $this->manifest->getModules();
        $this->assertEquals(
            [
                'module',
                'silverstripe/awesome-module',
                'silverstripe/modulec',
                'silverstripe/root-module',
            ],
            array_keys($modules)
        );
    }

    public function testGetLegacyModule()
    {
        $module = $this->manifest->getModule('module');
        $this->assertNotEmpty($module);
        $this->assertEquals('module', $module->getName());
        $this->assertEquals('module', $module->getShortName());
        $this->assertEquals('module', $module->getRelativePath());
        $this->assertEmpty($module->getComposerName());
    }

    public function testGetComposerModule()
    {
        // Get by installer-name (folder)
        $moduleByShortName = $this->manifest->getModule('moduleb');
        $this->assertNotEmpty($moduleByShortName);

        // Can also get this by full composer name
        $module = $this->manifest->getModule('silverstripe/awesome-module');
        $this->assertNotEmpty($module);
        $this->assertEquals($moduleByShortName->getPath(), $module->getPath());

        // correctly respects vendor
        $this->assertEmpty($this->manifest->getModule('wrongvendor/awesome-module'));
        $this->assertEmpty($this->manifest->getModule('wrongvendor/moduleb'));

        // Properties of module
        $this->assertEquals('silverstripe/awesome-module', $module->getName());
        $this->assertEquals('silverstripe/awesome-module', $module->getComposerName());
        $this->assertEquals('moduleb', $module->getShortName());
        $this->assertEquals('moduleb', $module->getRelativePath());
    }

    public function testGetResourcePath()
    {
        // Root module
        $moduleb = $this->manifest->getModule('moduleb');
        $this->assertTrue($moduleb->getResource('composer.json')->exists());
        $this->assertFalse($moduleb->getResource('package.json')->exists());
        $this->assertEquals(
            'moduleb/composer.json',
            $moduleb->getResource('composer.json')->getRelativePath()
        );
    }

    public function testGetResourcePathsInVendor()
    {
        // Vendor module
        $modulec = $this->manifest->getModule('silverstripe/modulec');
        $this->assertTrue($modulec->getResource('composer.json')->exists());
        $this->assertFalse($modulec->getResource('package.json')->exists());
        $this->assertEquals(
            'vendor/silverstripe/modulec/composer.json',
            $modulec->getResource('composer.json')->getRelativePath()
        );
    }

    public function testGetResourcePathOnRoot()
    {
        $module = $this->manifest->getModule('silverstripe/root-module');
        $this->assertTrue($module->getResource('composer.json')->exists());
        $this->assertEquals(
            'composer.json',
            $module->getResource('composer.json')->getRelativePath()
        );
    }
}
