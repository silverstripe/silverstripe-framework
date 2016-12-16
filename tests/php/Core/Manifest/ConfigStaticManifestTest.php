<?php

namespace SilverStripe\Core\Tests\Manifest;

use SilverStripe\Core\Manifest\ConfigStaticManifest;
use SilverStripe\Dev\SapphireTest;

class ConfigStaticManifestTest extends SapphireTest
{

    private static $testString = 'string';

    private static $testArray = array('foo' => 'bar');

    protected static $ignored = true;

    public function testGet()
    {
        $manifest = new ConfigStaticManifest();

        // Test madeup value
        $this->assertNull($manifest->get(__CLASS__, 'madeup', null));

        // Test string value
        $this->assertEquals('string', $manifest->get(__CLASS__, 'testString'));

        // Test array value
        $this->assertEquals(array('foo' => 'bar'), $manifest->get(__CLASS__, 'testArray'));

        // Test to ensure we're only picking up private statics
        $this->assertNull($manifest->get(__CLASS__, 'ignored', null));

        // Test madeup class
        if (!class_exists('aonsffgrgx')) {
            $this->assertNull($manifest->get('aonsffgrgx', 'madeup', null));
        }
    }
}
