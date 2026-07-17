<?php

namespace SilverStripe\Core\Tests\Manifest;

use ClassF;
use Exception;
use SilverStripe\Core\Manifest\ClassManifest;
use SilverStripe\Dev\SapphireTest;
use PHPUnit\Framework\Attributes\DataProvider;

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

    protected function setUp(): void
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
    public static function providerTestGetItemPath()
    {
        $paths = [
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

        if (version_compare(phpversion(), '8.1.0', '>')) {
            $paths[] = ['ENUMA', 'module/enums/EnumA.php'];
            $paths[] = ['EnumA', 'module/enums/EnumA.php'];
            $paths[] = ['enuma', 'module/enums/EnumA.php'];
        }

        return $paths;
    }

    /**
     * @param string $name
     * @param string $path
     */
    #[DataProvider('providerTestGetItemPath')]
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
            'classf' => "{$this->base}/module/classes/ClassF.php",
            'classg' => "{$this->base}/module/classes/ClassG.php",
            'classh' => "{$this->base}/module/classes/ClassH.php",
            'classi' => "{$this->base}/module/classes/ClassI.php",
            'customattributea' => "{$this->base}/module/classes/CustomAttributeA.php",
            'customattributeb' => "{$this->base}/module/classes/CustomAttributeB.php",
            'customattributec' => "{$this->base}/module/classes/CustomAttributeC.php",
            'vendorclassa' => "{$this->base}/vendor/silverstripe/modulec/code/VendorClassA.php",
            'vendorclassx' => "{$this->base}/vendor/silverstripe/modulecbetter/code/VendorClassX.php",
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
                'classf' => 'ClassF',
                'classg' => 'ClassG',
                'classh' => 'ClassH',
                'classi' => 'ClassI',
                'customattributea' => 'CustomAttributeA',
                'customattributeb' => 'CustomAttributeB',
                'customattributec' => 'CustomAttributeC',
                'vendorclassa' => 'VendorClassA',
                'vendorclassx' => 'VendorClassX',
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
            'customattributeb' => [
                'customattributec' => 'CustomAttributeC',
            ]
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

    public function testGetInterfaceDescendants()
    {
        $expect = [
            'interfacec' => [
                'interfaced' => 'InterfaceD',
                'interfacee' => 'InterfaceE',
            ],
        ];
        $this->assertEquals($expect, $this->manifest->getInterfaceDescendants());
    }

    public function testGetDescendantsOfInterface()
    {
        $expect = [
            'interfacec' => ['interfaced' => 'InterfaceD', 'interfacee' => 'InterfaceE'],
            'INTERFACEC' => ['interfaced' => 'InterfaceD', 'interfacee' => 'InterfaceE'],
            'InterfaceC' => ['interfaced' => 'InterfaceD', 'interfacee' => 'InterfaceE'],
        ];

        foreach ($expect as $class => $desc) {
            $this->assertEquals($desc, $this->manifest->getDescendantsOfInterface($class));
        }
    }

    public function testGetInterfaces()
    {
        $expect = [
            'interfacea' => "{$this->base}/module/interfaces/InterfaceA.php",
            'interfaceb' => "{$this->base}/module/interfaces/InterfaceB.php",
            'interfacec' => "{$this->base}/module/interfaces/InterfaceC.php",
            'interfaced' => "{$this->base}/module/interfaces/InterfaceD.php",
            'interfacee' => "{$this->base}/module/interfaces/InterfaceE.php",
        ];
        $this->assertEquals($expect, $this->manifest->getInterfaces());
    }

    public function testGetImplementors()
    {
        $expect = [
            'interfacea' => ['classb' => 'ClassB'],
            'interfaceb' => ['classc' => 'ClassC'],
            'interfaced' => ['classf' => 'ClassF'],
            'interfacee' => ['customattributea' => 'CustomAttributeA'],
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
            'interfacec' => [], // Must be empty
            'interfaced' => ['classf' => 'ClassF'],
            'interfacee' => ['customattributea' => 'CustomAttributeA'],
        ];

        foreach ($expect as $interface => $impl) {
            $this->assertEquals($impl, $this->manifest->getImplementorsOf($interface));
        }
    }

    public function testGetImplementorsOfIncludingChildren()
    {
        $expect = [
            'INTERFACEA' => ['classb' => 'ClassB'],
            'interfacea' => ['classb' => 'ClassB'],
            'INTERFACEB' => ['classc' => 'ClassC'],
            'interfaceb' => ['classc' => 'ClassC'],
            'interfacec' => ['classf' => 'ClassF', 'customattributea' => 'CustomAttributeA'],
            'interfaced' => ['classf' => 'ClassF'],
            'interfacee' => ['customattributea' => 'CustomAttributeA'],
        ];

        foreach ($expect as $interface => $impl) {
            $this->assertEquals($impl, $this->manifest->getImplementorsOfIncludingChildren($interface));
        }
    }

    public function testGetEnums()
    {
        if (!version_compare(phpversion(), '8.1.0', '>')) {
            $this->markTestSkipped('Enums are only available on PHP 8.1+');
        }

        $expect = [
            'enuma' => "{$this->base}/module/enums/EnumA.php",
            'enumb' => "{$this->base}/module/enums/EnumB.php",
        ];
        $this->assertEquals($expect, $this->manifest->getEnums());
    }

    public function testGetEnumNames()
    {
        if (!version_compare(phpversion(), '8.1.0', '>')) {
            $this->markTestSkipped('Enums are only available on PHP 8.1+');
        }

        $this->assertEquals(
            [
                'enuma' => 'EnumA',
                'enumb' => 'EnumB',
            ],
            $this->manifest->getEnumNames()
        );
    }

    public function testGetAttributes()
    {
        $attributes = [
            'customattributea' => "{$this->base}/module/classes/CustomAttributeA.php",
            'customattributeb' => "{$this->base}/module/classes/CustomAttributeB.php",
            'customattributec' => "{$this->base}/module/classes/CustomAttributeC.php",
        ];

        $this->assertEquals($attributes, $this->manifest->getAttributes());
    }

    public function testGetAttributesNames()
    {
        $attributes = [
            'customattributea' => 'CustomAttributeA',
            'customattributeb' => 'CustomAttributeB',
            'customattributec' => 'CustomAttributeC',
        ];

        $this->assertEquals($attributes, $this->manifest->getAttributeNames());
    }

    public function testGetAnnotated()
    {
        $attributes = [
            'customattributea' => [
                'classes' => [
                    'classf' => 'ClassF',
                    'classg' => 'ClassG',
                    'classh' => 'ClassH',
                    'classi' => 'ClassI',
                ],
                'interfaces' => [
                    'interfacec' => 'InterfaceC',
                ],
                'traits' => [
                    'testnamespace\testing\testtraitb' => 'TestNamespace\Testing\TestTraitB',
                ],
                'enums' => [
                    'enumb' => 'EnumB',
                ],
            ],
            'customattributeb' => [
                'classes' => [
                    'classg' => 'ClassG',
                ],
                'interfaces' => [],
                'traits' => [],
                'enums' => [],
            ],
            'customattributec' => [
                'classes' => [
                    'classi' => 'ClassI',
                ],
                'interfaces' => [],
                'traits' => [],
                'enums' => [],
            ],
            'attribute' => [
                'classes' => [
                    'customattributea' => 'CustomAttributeA',
                    'customattributeb' => 'CustomAttributeB',
                    'customattributec' => 'CustomAttributeC',
                ],
                'interfaces' => [],
                'traits' => [],
                'enums' => [],
            ],
        ];

        $this->assertEquals($attributes, $this->manifest->getAnnotated());
    }

    public function testAnnotatedBy()
    {
        $expect = [
            'customattributea' => ['classf' => 'ClassF', 'classg' => 'ClassG', 'classh' => 'ClassH', 'classi' => 'ClassI'],
            'customattributeb' => ['classg' => 'ClassG', 'classi' => 'ClassI'],
            'customattributec' => ['classi' => 'ClassI'],
            'interfacee' => ['classf' => 'ClassF', 'classg' => 'ClassG', 'classh' => 'ClassH', 'classi' => 'ClassI'],
        ];

        foreach ($expect as $attr => $impl) {
            $this->assertEquals(
                $impl,
                $this->manifest->getAnnotatedBy($attr, true, 'classes'),
                'Asserting classes annotated by ' . $attr
            );
        }
    }

    public function testAnnotatedByDirect()
    {
        $expect = [
            'customattributea' => ['classf' => 'ClassF', 'classg' => 'ClassG', 'classh' => 'ClassH', 'classi' => 'ClassI'],
            'customattributeb' => ['classg' => 'ClassG'],
            'customattributec' => ['classi' => 'ClassI'],
        ];

        foreach ($expect as $attr => $impl) {
            $this->assertEquals(
                $impl,
                $this->manifest->getAnnotatedBy($attr, false, 'classes'),
                'Asserting classes annotated directly by ' . $attr
            );
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
