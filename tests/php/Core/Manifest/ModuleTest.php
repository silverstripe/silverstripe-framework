<?php

namespace SilverStripe\Core\Tests\Manifest;

use SilverStripe\Control\Director;
use SilverStripe\Core\Manifest\Module;
use SilverStripe\Dev\SapphireTest;

class ModuleTest extends SapphireTest
{
    public function testUnsetResourcesDir()
    {
        $path = __DIR__ . '/fixtures/ss-projects/withoutCustomResourcesDir';
        $module = new Module($path, $path);
        $this->assertEquals('', $module->getResourcesDir());
    }

    public function testResourcesDir()
    {
        $path = __DIR__ . '/fixtures/ss-projects/withCustomResourcesDir';
        $module = new Module($path, $path);
        $this->assertEquals('customised-resources-dir', $module->getResourcesDir());
    }
}
