<?php

namespace SilverStripe\Core\Tests\Manifest;

use Exception;
use SilverStripe\Core\Manifest\ClassManifest;
use SilverStripe\Dev\SapphireTest;

/**
 * Tests for the {@link ClassManifest} class.
 */
class ClassManifestTest extends SapphireTest
{

    /**
     * @var string
     */
    protected $base;

    /**
     * @var ClassManifest
     */
    protected $manifest;

    /**
     * @var ClassManifest
     */
    protected $manifestTests;

    protected function setUp()
    {
        parent::setUp();

        $this->base = dirname(__FILE__) . '/fixtures/classmanifest';
        $this->manifest = new ClassManifest($this->base);
        $this->manifest->init(false);
        $this->manifestTests = new ClassManifest($this->base);
        $this->manifestTests->init(true);
    }

    /**
     * @return array
     */
    public function providerTestGetItemPath()
    {
        return [
            ['CLASSA', 'module/classes/ClassA.php'],
            ['ClassA', 'module/classes/ClassA.php'],
            ['classa', 'module/classes/ClassA.php'],
            ['INTERFACEA', 'module/interfaces/InterfaceA.php'],
            ['InterfaceA', 'module/interfaces/InterfaceA.php'],
            ['interfacea', 'module/interfaces/InterfaceA.php'],
            ['TestTraitA', 'module/traits/TestTraitA.php'],
            ['TestNamespace\\Testing\\TestTraitB', 'module/traits/TestTraitB.php'],
            ['VendorClassA', 'vendor/silverstripe/modulec/code/VendorClassA.php'],
            ['VendorTraitA', 'vendor/silverstripe/modulec/code/VendorTraitA.php'],
        ];
    }

    /**
     * @dataProvider providerTestGetItemPath
     * @param string $name
     * @param string $path
     */
    public function testGetItemPath($name, $path)
    {
        $this->assertEquals("{$this->base}/$path", $this->manifest->getItemPath($name));
    }

    public function testGetClasses()
    {
        $expect = [
            'classa' => "{$this->base}/module/classes/ClassA.php",
            'classb' => "{$this->base}/module/classes/ClassB.php",
            'classc' => "{$this->base}/module/classes/ClassC.php",
            'classd' => "{$this->base}/module/classes/ClassD.php",
            'classe' => "{$this->base}/module/classes/ClassE.php",
            'vendorclassa' => "{$this->base}/vendor/silverstripe/modulec/code/VendorClassA.php",
        ];
        $this->assertEquals($expect, $this->manifest->getClasses());
    }

    public function testGetClassNames()
    {
        $this->assertEquals(
            [
                'classa' => 'ClassA',
                'classb' => 'ClassB',
                'classc' => 'ClassC',
                'classd' => 'ClassD',
                'classe' => 'ClassE',
                'vendorclassa' => 'VendorClassA',
            ],
            $this->manifest->getClassNames()
        );
    }

    public function testGetTraitNames()
    {
        $this->assertEquals(
            [
                'testtraita' => 'TestTraitA',
                'testnamespace\\testing\\testtraitb' => 'TestNamespace\\Testing\\TestTraitB',
                'vendortraita' => 'VendorTraitA',
            ],
            $this->manifest->getTraitNames()
        );
    }

    public function testGetDescendants()
    {
        $expect = [
            'classa' => [
                'classc' => 'ClassC',
                'classd' => 'ClassD',
            ],
            'classc' => [
                'classd' => 'ClassD',
            ],
        ];
        $this->assertEquals($expect, $this->manifest->getDescendants());
    }

    public function testGetDescendantsOf()
    {
        $expect = [
            'CLASSA' => ['classc' => 'ClassC', 'classd' => 'ClassD'],
            'classa' => ['classc' => 'ClassC', 'classd' => 'ClassD'],
            'CLASSC' => ['classd' => 'ClassD'],
            'classc' => ['classd' => 'ClassD'],
        ];

        foreach ($expect as $class => $desc) {
            $this->assertEquals($desc, $this->manifest->getDescendantsOf($class));
        }
    }

    public function testGetInterfaces()
    {
        $expect = array(
            'interfacea' => "{$this->base}/module/interfaces/InterfaceA.php",
            'interfaceb' => "{$this->base}/module/interfaces/InterfaceB.php"
        );
        $this->assertEquals($expect, $this->manifest->getInterfaces());
    }

    public function testGetImplementors()
    {
        $expect = [
            'interfacea' => ['classb' => 'ClassB'],
            'interfaceb' => ['classc' => 'ClassC'],
        ];
        $this->assertEquals($expect, $this->manifest->getImplementors());
    }

    public function testGetImplementorsOf()
    {
        $expect = [
            'INTERFACEA' => ['classb' => 'ClassB'],
            'interfacea' => ['classb' => 'ClassB'],
            'INTERFACEB' => ['classc' => 'ClassC'],
            'interfaceb' => ['classc' => 'ClassC'],
        ];

        foreach ($expect as $interface => $impl) {
            $this->assertEquals($impl, $this->manifest->getImplementorsOf($interface));
        }
    }

    public function testTestManifestIncludesTestClasses()
    {
        $this->assertArrayNotHasKey('testclassa', $this->manifest->getClasses());
        $this->assertArrayHasKey('testclassa', $this->manifestTests->getClasses());
    }

    public function testManifestExcludeFilesPrefixedWithUnderscore()
    {
        $this->assertArrayNotHasKey('ignore', $this->manifest->getClasses());
    }

    /**
     * Assert that ClassManifest throws an exception when it encounters two files
     * which contain classes with the same name
     */
    public function testManifestWarnsAboutDuplicateClasses()
    {
        $this->expectException(Exception::class);
        $manifest = new ClassManifest(dirname(__FILE__) . '/fixtures/classmanifest_duplicates');
        $manifest->init();
    }
}
