<?php

namespace SilverStripe\Core\Tests\Manifest;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Manifest\ClassManifest;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\Dev\SapphireTest;
use ReflectionMethod;

/**
 * Tests for the {@link ClassManifest} class.
 */
class NamespacedClassManifestTest extends SapphireTest
{
    /**
     * @var string
     */
    protected $base;

    /**
     * @var ClassManifest
     */
    protected $manifest;

    public function setUp()
    {
        parent::setUp();

        $this->base = dirname(__FILE__) . '/fixtures/namespaced_classmanifest';
        $this->manifest = new ClassManifest($this->base, false);
        ClassLoader::instance()->pushManifest($this->manifest, false);
    }

    public function tearDown()
    {
        parent::tearDown();
        ClassLoader::instance()->popManifest();
    }

    public function testClassInfoIsCorrect()
    {
        $this->assertContains('SilverStripe\Framework\Tests\ClassI', ClassInfo::implementorsOf('SilverStripe\\Security\\PermissionProvider'));

        // because we're using a nested manifest we have to "coalesce" the descendants again to correctly populate the
        // descendants of the core classes we want to test against - this is a limitation of the test manifest not
        // including all core classes
        $method = new ReflectionMethod($this->manifest, 'coalesceDescendants');
        $method->setAccessible(true);
        $method->invoke($this->manifest, 'SilverStripe\\Admin\\ModelAdmin');

        $this->assertContains('SilverStripe\Framework\Tests\ClassI', ClassInfo::subclassesFor('SilverStripe\\Admin\\ModelAdmin'));
    }

    public function testGetItemPath()
    {
        $expect = array(
            'SILVERSTRIPE\TEST\CLASSA'     => 'module/classes/ClassA.php',
            'Silverstripe\Test\ClassA'     => 'module/classes/ClassA.php',
            'silverstripe\test\classa'     => 'module/classes/ClassA.php',
            'SILVERSTRIPE\TEST\INTERFACEA' => 'module/interfaces/InterfaceA.php',
            'Silverstripe\Test\InterfaceA' => 'module/interfaces/InterfaceA.php',
            'silverstripe\test\interfacea' => 'module/interfaces/InterfaceA.php'
        );

        foreach ($expect as $name => $path) {
            $this->assertEquals("{$this->base}/$path", $this->manifest->getItemPath($name));
        }
    }

    public function testGetClasses()
    {
        $expect = array(
            'silverstripe\test\classa' => "{$this->base}/module/classes/ClassA.php",
            'silverstripe\test\classb' => "{$this->base}/module/classes/ClassB.php",
            'silverstripe\test\classc' => "{$this->base}/module/classes/ClassC.php",
            'silverstripe\test\classd' => "{$this->base}/module/classes/ClassD.php",
            'silverstripe\test\classe' => "{$this->base}/module/classes/ClassE.php",
            'silverstripe\test\classf' => "{$this->base}/module/classes/ClassF.php",
            'silverstripe\test\classg' => "{$this->base}/module/classes/ClassG.php",
            'silverstripe\test\classh' => "{$this->base}/module/classes/ClassH.php",
            'silverstripe\framework\tests\classi' => "{$this->base}/module/classes/ClassI.php",
        );

        $this->assertEquals($expect, $this->manifest->getClasses());
    }

    public function testGetClassNames()
    {
        $this->assertEquals(
            array('silverstripe\test\classa',
                'silverstripe\test\classb', 'silverstripe\test\classc', 'silverstripe\test\classd',
                'silverstripe\test\classe', 'silverstripe\test\classf', 'silverstripe\test\classg',
                'silverstripe\test\classh', 'silverstripe\framework\tests\classi'),
            $this->manifest->getClassNames()
        );
    }

    public function testGetDescendants()
    {
        $expect = array(
            'silverstripe\test\classa' => array('silverstripe\test\ClassB', 'silverstripe\test\ClassH'),
        );

        $this->assertEquals($expect, $this->manifest->getDescendants());
    }

    public function testGetDescendantsOf()
    {
        $expect = array(
            'SILVERSTRIPE\TEST\CLASSA' => array('silverstripe\test\ClassB', 'silverstripe\test\ClassH'),
            'silverstripe\test\classa' => array('silverstripe\test\ClassB', 'silverstripe\test\ClassH'),
        );

        foreach ($expect as $class => $desc) {
            $this->assertEquals($desc, $this->manifest->getDescendantsOf($class));
        }
    }

    public function testGetInterfaces()
    {
        $expect = array(
            'silverstripe\test\interfacea' => "{$this->base}/module/interfaces/InterfaceA.php",
        );
        $this->assertEquals($expect, $this->manifest->getInterfaces());
    }

    public function testGetImplementors()
    {
        $expect = array(
            'silverstripe\test\interfacea' => array('silverstripe\test\ClassE'),
            'interfacea' => array('silverstripe\test\ClassF'),
            'silverstripe\test\subtest\interfacea' => array('silverstripe\test\ClassG'),
            'silverstripe\security\permissionprovider' => array('SilverStripe\Framework\Tests\ClassI'),
        );
        $this->assertEquals($expect, $this->manifest->getImplementors());
    }

    public function testGetImplementorsOf()
    {
        $expect = array(
            'SILVERSTRIPE\TEST\INTERFACEA' => array('silverstripe\test\ClassE'),
            'silverstripe\test\interfacea' => array('silverstripe\test\ClassE'),
            'INTERFACEA' => array('silverstripe\test\ClassF'),
            'interfacea' => array('silverstripe\test\ClassF'),
            'SILVERSTRIPE\TEST\SUBTEST\INTERFACEA' => array('silverstripe\test\ClassG'),
            'silverstripe\test\subtest\interfacea' => array('silverstripe\test\ClassG'),
        );

        foreach ($expect as $interface => $impl) {
            $this->assertEquals($impl, $this->manifest->getImplementorsOf($interface));
        }
    }
}
