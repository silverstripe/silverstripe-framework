<?php

namespace SilverStripe\Core\Tests\Manifest;

use SilverStripe\Core\Manifest\ClassManifest;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\Dev\SapphireTest;

/**
 * Tests for the {@link ClassManifest} class.
 */
class ClassLoaderTest extends SapphireTest
{

    /**
     * @var string
     */
    protected $baseManifest1;

    /**
     * @var string
     */
    protected $baseManifest2;

    /**
     * @var ClassManifest
     */
    protected $testManifest1;

    /**
     * @var ClassManifest
     */
    protected $testManifest2;

    protected function setUp()
    {
        parent::setUp();

        $this->baseManifest1 = __DIR__ . '/fixtures/classmanifest';
        $this->baseManifest2 = __DIR__ . '/fixtures/classmanifest_other';
        $this->testManifest1 = new ClassManifest($this->baseManifest1);
        $this->testManifest2 = new ClassManifest($this->baseManifest2);
        $this->testManifest1->init();
        $this->testManifest2->init();
    }

    public function testExclusive()
    {
        $loader = new ClassLoader();

        $loader->pushManifest($this->testManifest1);
        $this->assertTrue((bool)$loader->getItemPath('ClassA'));
        $this->assertFalse((bool)$loader->getItemPath('OtherClassA'));

        $loader->pushManifest($this->testManifest2);
        $this->assertFalse((bool)$loader->getItemPath('ClassA'));
        $this->assertTrue((bool)$loader->getItemPath('OtherClassA'));

        $loader->popManifest();
        $loader->pushManifest($this->testManifest2, false);
        $this->assertTrue((bool)$loader->getItemPath('ClassA'));
        $this->assertTrue((bool)$loader->getItemPath('OtherClassA'));
    }

    public function testGetItemPath()
    {
        $loader = new ClassLoader();

        $loader->pushManifest($this->testManifest1);
        $this->assertEquals(
            realpath($this->baseManifest1 . '/module/classes/ClassA.php'),
            realpath($loader->getItemPath('ClassA'))
        );
        $this->assertEquals(
            false,
            $loader->getItemPath('UnknownClass')
        );
        $this->assertEquals(
            false,
            $loader->getItemPath('OtherClassA')
        );

        $loader->pushManifest($this->testManifest2);
        $this->assertEquals(
            false,
            $loader->getItemPath('ClassA')
        );
        $this->assertEquals(
            false,
            $loader->getItemPath('UnknownClass')
        );
        $this->assertEquals(
            realpath($this->baseManifest2 . '/module/classes/OtherClassA.php'),
            realpath($loader->getItemPath('OtherClassA'))
        );
    }
}
