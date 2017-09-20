<?php

namespace SilverStripe\Core\Tests\Manifest;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Manifest\ClassManifest;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\Dev\SapphireTest;
use ReflectionMethod;
use SilverStripe\Security\PermissionProvider;

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

    protected function setUp()
    {
        parent::setUp();

        $this->base = dirname(__FILE__) . '/fixtures/namespaced_classmanifest';
        $this->manifest = new ClassManifest($this->base);
        $this->manifest->init();
        ClassLoader::inst()->pushManifest($this->manifest, false);
    }

    protected function tearDown()
    {
        parent::tearDown();
        ClassLoader::inst()->popManifest();
    }

    public function testClassInfoIsCorrect()
    {
        $this->assertContains(
            'SilverStripe\\Framework\\Tests\\ClassI',
            ClassInfo::implementorsOf(PermissionProvider::class)
        );

        // because we're using a nested manifest we have to "coalesce" the descendants again to correctly populate the
        // descendants of the core classes we want to test against - this is a limitation of the test manifest not
        // including all core classes
        $method = new ReflectionMethod($this->manifest, 'coalesceDescendants');
        $method->setAccessible(true);
        $method->invoke($this->manifest, ModelAdmin::class);
        $this->assertContains('SilverStripe\\Framework\\Tests\\ClassI', ClassInfo::subclassesFor(ModelAdmin::class));
    }

    public function testGetItemPath()
    {
        $expect = array(
            'SILVERSTRIPE\\TEST\\CLASSA'     => 'module/classes/ClassA.php',
            'Silverstripe\\Test\\ClassA'     => 'module/classes/ClassA.php',
            'silverstripe\\test\\classa'     => 'module/classes/ClassA.php',
            'SILVERSTRIPE\\TEST\\INTERFACEA' => 'module/interfaces/InterfaceA.php',
            'Silverstripe\\Test\\InterfaceA' => 'module/interfaces/InterfaceA.php',
            'silverstripe\\test\\interfacea' => 'module/interfaces/InterfaceA.php'
        );

        foreach ($expect as $name => $path) {
            $this->assertEquals("{$this->base}/$path", $this->manifest->getItemPath($name));
        }
    }

    public function testGetClasses()
    {
        $expect = array(
            'silverstripe\\test\\classa' => "{$this->base}/module/classes/ClassA.php",
            'silverstripe\\test\\classb' => "{$this->base}/module/classes/ClassB.php",
            'silverstripe\\test\\classc' => "{$this->base}/module/classes/ClassC.php",
            'silverstripe\\test\\classd' => "{$this->base}/module/classes/ClassD.php",
            'silverstripe\\test\\classe' => "{$this->base}/module/classes/ClassE.php",
            'silverstripe\\test\\classf' => "{$this->base}/module/classes/ClassF.php",
            'silverstripe\\test\\classg' => "{$this->base}/module/classes/ClassG.php",
            'silverstripe\\test\\classh' => "{$this->base}/module/classes/ClassH.php",
            'silverstripe\\framework\\tests\\classi' => "{$this->base}/module/classes/ClassI.php",
        );

        $this->assertEquals($expect, $this->manifest->getClasses());
    }

    public function testGetClassNames()
    {
        $this->assertEquals(
            [
                'silverstripe\\test\\classa' => 'silverstripe\\test\\ClassA',
                'silverstripe\\test\\classb' => 'silverstripe\\test\\ClassB',
                'silverstripe\\test\\classc' => 'silverstripe\\test\\ClassC',
                'silverstripe\\test\\classd' => 'silverstripe\\test\\ClassD',
                'silverstripe\\test\\classe' => 'silverstripe\\test\\ClassE',
                'silverstripe\\test\\classf' => 'silverstripe\\test\\ClassF',
                'silverstripe\\test\\classg' => 'silverstripe\\test\\ClassG',
                'silverstripe\\test\\classh' => 'silverstripe\\test\\ClassH',
                'silverstripe\\framework\\tests\\classi' => 'SilverStripe\\Framework\\Tests\\ClassI',
            ],
            $this->manifest->getClassNames()
        );
    }

    public function testGetDescendants()
    {
        $expect = [
            'silverstripe\\test\\classa' => [
                'silverstripe\\test\\classb' => 'silverstripe\test\ClassB',
                'silverstripe\\test\\classh' => 'silverstripe\test\ClassH',
            ],
        ];

        $this->assertEquals($expect, $this->manifest->getDescendants());
    }

    public function testGetDescendantsOf()
    {
        $expect = [
            'SILVERSTRIPE\\TEST\\CLASSA' => [
                'silverstripe\\test\\classb' => 'silverstripe\test\ClassB',
                'silverstripe\\test\\classh' => 'silverstripe\test\ClassH',
            ],
            'silverstripe\\test\\classa' => [
                'silverstripe\\test\\classb' => 'silverstripe\test\ClassB',
                'silverstripe\\test\\classh' => 'silverstripe\test\ClassH',
            ],
        ];

        foreach ($expect as $class => $desc) {
            $this->assertEquals($desc, $this->manifest->getDescendantsOf($class));
        }
    }

    public function testGetInterfaces()
    {
        $expect = array(
            'silverstripe\\test\\interfacea' => "{$this->base}/module/interfaces/InterfaceA.php",
        );
        $this->assertEquals($expect, $this->manifest->getInterfaces());
    }

    public function testGetImplementors()
    {
        $expect = [
            'silverstripe\\test\\interfacea' => [
                'silverstripe\\test\\classe' => 'silverstripe\\test\\ClassE',
            ],
            'interfacea' => [
                'silverstripe\\test\\classf' => 'silverstripe\\test\\ClassF',
            ],
            'silverstripe\\test\\subtest\\interfacea' => [
                'silverstripe\\test\\classg' => 'silverstripe\\test\\ClassG',
            ],
            'silverstripe\\security\\permissionprovider' => [
                'silverstripe\\framework\\tests\\classi' => 'SilverStripe\\Framework\\Tests\\ClassI',
            ],
        ];
        $this->assertEquals($expect, $this->manifest->getImplementors());
    }

    public function testGetImplementorsOf()
    {
        $expect = [
            'SILVERSTRIPE\\TEST\\INTERFACEA' => [
                'silverstripe\\test\\classe' => 'silverstripe\\test\\ClassE',
            ],
            'silverstripe\\test\\interfacea' => [
                'silverstripe\\test\\classe' => 'silverstripe\\test\\ClassE',
            ],
            'INTERFACEA' => [
                'silverstripe\\test\\classf' => 'silverstripe\\test\\ClassF',
            ],
            'interfacea' => [
                'silverstripe\\test\\classf' => 'silverstripe\\test\\ClassF',
            ],
            'SILVERSTRIPE\\TEST\\SUBTEST\\INTERFACEA' => [
                'silverstripe\\test\\classg' => 'silverstripe\\test\\ClassG',
            ],
            'silverstripe\\test\\subtest\\interfacea' => [
                'silverstripe\\test\\classg' => 'silverstripe\\test\\ClassG',
            ],
        ];

        foreach ($expect as $interface => $impl) {
            $this->assertEquals($impl, $this->manifest->getImplementorsOf($interface));
        }
    }
}
