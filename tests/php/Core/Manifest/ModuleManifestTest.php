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
        $this->manifest = new ModuleManifest($this->base, false);
    }

    public function testGetModules()
    {
        $modules = $this->manifest->getModules();
        $this->assertEquals(
            [
                'module',
                'silverstripe/awesome-module',
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

    /*
     * Note: Tests experimental API
     * @internal
     */
    public function testGetResource()
    {
        $module = $this->manifest->getModule('moduleb');
        $this->assertTrue($module->hasResource('composer.json'));
        $this->assertFalse($module->hasResource('package.json'));
        $this->assertEquals(
            'moduleb/composer.json',
            $module->getResourcePath('composer.json')
        );
    }
}
