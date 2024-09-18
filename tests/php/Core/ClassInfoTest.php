<?php

namespace SilverStripe\Core\Tests;

use DateTime;
use Exception;
use ReflectionException;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Tests\ClassInfoTest\BaseClass;
use SilverStripe\Core\Tests\ClassInfoTest\BaseDataClass;
use SilverStripe\Core\Tests\ClassInfoTest\BaseObject;
use SilverStripe\Core\Tests\ClassInfoTest\ChildClass;
use SilverStripe\Core\Tests\ClassInfoTest\ExtendTest1;
use SilverStripe\Core\Tests\ClassInfoTest\ExtendTest2;
use SilverStripe\Core\Tests\ClassInfoTest\ExtendTest3;
use SilverStripe\Core\Tests\ClassInfoTest\ExtensionTest1;
use SilverStripe\Core\Tests\ClassInfoTest\ExtensionTest2;
use SilverStripe\Core\Tests\ClassInfoTest\GrandChildClass;
use SilverStripe\Core\Tests\ClassInfoTest\HasFields;
use SilverStripe\Core\Tests\ClassInfoTest\HasMethod;
use SilverStripe\Core\Tests\ClassInfoTest\NoFields;
use SilverStripe\Core\Tests\ClassInfoTest\WithCustomTable;
use SilverStripe\Core\Tests\ClassInfoTest\WithRelation;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ViewableData;
use PHPUnit\Framework\Attributes\DataProvider;

class ClassInfoTest extends SapphireTest
{

    protected static $extra_dataobjects = [
        BaseClass::class,
        BaseDataClass::class,
        ChildClass::class,
        GrandChildClass::class,
        HasFields::class,
        NoFields::class,
        WithCustomTable::class,
        WithRelation::class,
        BaseObject::class,
        ExtendTest1::class,
        ExtendTest2::class,
        ExtendTest3::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        ClassInfo::reset_db_cache();
    }

    public function testExists()
    {
        $this->assertTrue(ClassInfo::exists(ClassInfo::class));
        $this->assertTrue(ClassInfo::exists('SilverStripe\\Core\\classinfo'));
        $this->assertTrue(ClassInfo::exists('SilverStripe\\Core\\Tests\\ClassInfoTest'));
        $this->assertTrue(ClassInfo::exists('SilverStripe\\Core\\Tests\\CLASSINFOTEST'));
        $this->assertTrue(ClassInfo::exists('stdClass'));
        $this->assertTrue(ClassInfo::exists('stdCLASS'));
        $this->assertFalse(ClassInfo::exists('SomeNonExistantClass'));
    }

    public function testSubclassesFor()
    {
        $subclasses = [
            'silverstripe\\core\\tests\\classinfotest\\baseclass' => BaseClass::class,
            'silverstripe\\core\\tests\\classinfotest\\childclass' => ChildClass::class,
            'silverstripe\\core\\tests\\classinfotest\\grandchildclass' => GrandChildClass::class,
        ];
        $subclassesWithoutBase = [
            'silverstripe\\core\\tests\\classinfotest\\childclass' => ChildClass::class,
            'silverstripe\\core\\tests\\classinfotest\\grandchildclass' => GrandChildClass::class,
        ];
        $this->assertEquals(
            $subclasses,
            ClassInfo::subclassesFor(BaseClass::class),
            'ClassInfo::subclassesFor() returns only direct subclasses and doesnt include base class'
        );
        ClassInfo::reset_db_cache();
        $this->assertEquals(
            $subclasses,
            ClassInfo::subclassesFor('silverstripe\\core\\tests\\classinfotest\\baseclass'),
            'ClassInfo::subclassesFor() is acting in a case sensitive way when it should not'
        );
        ClassInfo::reset_db_cache();
        $this->assertEquals(
            $subclassesWithoutBase,
            ClassInfo::subclassesFor('silverstripe\\core\\tests\\classinfotest\\baseclass', false)
        );

        // Check that core classes are present (eg: Email subclasses)
        $emailClasses = ClassInfo::subclassesFor(\SilverStripe\Control\Email\Email::class);
        $this->assertArrayHasKey(
            'silverstripe\\control\\tests\\email\\emailtest\\emailsubclass',
            $emailClasses,
            'It contains : ' . json_encode($emailClasses)
        );
    }

    public function testClassName()
    {
        $this->assertEquals(
            ClassInfoTest::class,
            ClassInfo::class_name($this)
        );
        $this->assertEquals(
            ClassInfoTest::class,
            ClassInfo::class_name('SilverStripe\\Core\\Tests\\ClassInfoTest')
        );
        $this->assertEquals(
            ClassInfoTest::class,
            ClassInfo::class_name('SilverStripe\\Core\\TESTS\\CLaSsInfOTEsT')
        );
    }

    public function testNonClassName()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Class "?IAmAClassThatDoesNotExist"? does not exist/');
        $this->assertEquals('IAmAClassThatDoesNotExist', ClassInfo::class_name('IAmAClassThatDoesNotExist'));
    }

    public function testClassesForFolder()
    {
        $classes = ClassInfo::classes_for_folder(ltrim(FRAMEWORK_DIR . '/tests', '/'));
        $this->assertArrayHasKey(
            'silverstripe\\core\\tests\\classinfotest',
            $classes,
            'ClassInfo::classes_for_folder() returns classes matching the filename'
        );
        $this->assertContains(
            ClassInfoTest::class,
            $classes,
            'ClassInfo::classes_for_folder() returns classes matching the filename'
        );
        $this->assertArrayHasKey(
            'silverstripe\\core\\tests\\classinfotest\\baseclass',
            $classes,
            'ClassInfo::classes_for_folder() returns additional classes not matching the filename'
        );
        $this->assertContains(
            BaseClass::class,
            $classes,
            'ClassInfo::classes_for_folder() returns additional classes not matching the filename'
        );
    }

    public function testAncestry()
    {
        $ancestry = ClassInfo::ancestry(ChildClass::class);
        $expect = [
            'silverstripe\\view\\viewabledata' => ViewableData::class,
            'silverstripe\\orm\\dataobject' => DataObject::class,
            'silverstripe\\core\tests\classinfotest\\baseclass' => BaseClass::class,
            'silverstripe\\core\tests\classinfotest\\childclass' => ChildClass::class,
        ];
        $this->assertEquals($expect, $ancestry);

        ClassInfo::reset_db_cache();
        $this->assertEquals(
            $expect,
            ClassInfo::ancestry('silverstripe\\core\\tests\\classINFOtest\\Childclass')
        );

        ClassInfo::reset_db_cache();
        $ancestry = ClassInfo::ancestry(ChildClass::class, true);
        $this->assertEquals(
            [
                'silverstripe\\core\tests\classinfotest\\baseclass' => BaseClass::class
            ],
            $ancestry,
            '$tablesOnly option excludes memory-only inheritance classes'
        );
    }

    public function testDataClassesFor()
    {
        $expect = [
            'silverstripe\\core\\tests\\classinfotest\\basedataclass' => BaseDataClass::class,
            'silverstripe\\core\\tests\\classinfotest\\hasfields' => HasFields::class,
            'silverstripe\\core\\tests\\classinfotest\\withrelation' => WithRelation::class,
            'silverstripe\\core\\tests\\classinfotest\\withcustomtable' => WithCustomTable::class,
        ];
        $classes = [
            BaseDataClass::class,
            NoFields::class,
            HasFields::class,
        ];

        ClassInfo::reset_db_cache();
        $this->assertEquals($expect, ClassInfo::dataClassesFor($classes[0]));
        ClassInfo::reset_db_cache();
        $this->assertEquals($expect, ClassInfo::dataClassesFor(strtoupper($classes[0] ?? '')));
        ClassInfo::reset_db_cache();
        $this->assertEquals($expect, ClassInfo::dataClassesFor($classes[1]));

        $expect = [
            'silverstripe\\core\\tests\\classinfotest\\basedataclass' => BaseDataClass::class,
            'silverstripe\\core\\tests\\classinfotest\\hasfields' => HasFields::class,
        ];

        ClassInfo::reset_db_cache();
        $this->assertEquals($expect, ClassInfo::dataClassesFor($classes[2]));
        ClassInfo::reset_db_cache();
        $this->assertEquals($expect, ClassInfo::dataClassesFor(strtolower($classes[2] ?? '')));
    }

    public function testClassesWithExtensionUsingConfiguredExtensions()
    {
        $expect = [
            'silverstripe\\core\\tests\\classinfotest\\extendtest1' => ExtendTest1::class,
            'silverstripe\\core\\tests\\classinfotest\\extendtest2' => ExtendTest2::class,
            'silverstripe\\core\\tests\\classinfotest\\extendtest3' => ExtendTest3::class,
        ];
        $this->assertEquals(
            $expect,
            ClassInfo::classesWithExtension(ExtensionTest1::class, BaseObject::class),
            'ClassInfo::testClassesWithExtension() returns class with extensions applied via class config'
        );

        $expect = [
            'silverstripe\\core\\tests\\classinfotest\\extendtest1' => ExtendTest1::class,
            'silverstripe\\core\\tests\\classinfotest\\extendtest2' => ExtendTest2::class,
            'silverstripe\\core\\tests\\classinfotest\\extendtest3' => ExtendTest3::class,
        ];
        $this->assertEquals(
            $expect,
            ClassInfo::classesWithExtension(ExtensionTest1::class, ExtendTest1::class, true),
            'ClassInfo::testClassesWithExtension() returns class with extensions applied via class config, including the base class'
        );
    }

    public function testClassesWithExtensionUsingDynamicallyAddedExtensions()
    {
        $this->assertEquals(
            [],
            ClassInfo::classesWithExtension(ExtensionTest2::class, BaseObject::class),
            'ClassInfo::testClassesWithExtension() returns no classes for extension that hasn\'t been applied yet.'
        );

        ExtendTest1::add_extension(ExtensionTest2::class);

        $expect = [
            'silverstripe\\core\\tests\\classinfotest\\extendtest2' => ExtendTest2::class,
            'silverstripe\\core\\tests\\classinfotest\\extendtest3' => ExtendTest3::class,
        ];
        $this->assertEquals(
            $expect,
            ClassInfo::classesWithExtension(ExtensionTest2::class, ExtendTest1::class),
            'ClassInfo::testClassesWithExtension() returns class with extra extension dynamically added'
        );
    }

    public function testClassesWithExtensionWithDynamicallyRemovedExtensions()
    {
        ExtendTest1::remove_extension(ExtensionTest1::class);

        $this->assertEquals(
            [],
            ClassInfo::classesWithExtension(ExtensionTest1::class, BaseObject::class),
            'ClassInfo::testClassesWithExtension() returns no classes after an extension being removed'
        );
    }

    #[DataProvider('provideHasMethodCases')]
    public function testHasMethod($object, $method, $output)
    {
        $this->assertEquals(
            $output,
            ClassInfo::hasMethod($object, $method)
        );
    }

    public static function provideHasMethodCases()
    {
        return [
            'Basic object' => [
                new DateTime(),
                'format',
                true,
            ],
            'CustomMethod object' => [
                new HasMethod(),
                'example',
                true,
            ],
            'Class Name' => [
                'DateTime',
                'format',
                true,
            ],
            'FQCN' => [
                '\DateTime',
                'format',
                true,
            ],
            'Invalid FQCN' => [
                '--GreatTime',
                'format',
                false,
            ],
            'Integer' => [
                1,
                'format',
                false,
            ],
            'Array' => [
                ['\DateTime'],
                'format',
                false,
            ],
        ];
    }

    #[DataProvider('provideClassSpecCases')]
    public function testParseClassSpec($input, $output)
    {
        $this->assertEquals(
            $output,
            ClassInfo::parse_class_spec($input)
        );
    }

    public static function provideClassSpecCases()
    {
        return [
            'Standard class' => [
                'SimpleClass',
                ['SimpleClass', []],
            ],
            'Namespaced class' => [
                'Foo\\Bar\\NamespacedClass',
                ['Foo\\Bar\\NamespacedClass', []],
            ],
            'Namespaced class with service name' => [
                'Foo\\Bar\\NamespacedClass.withservicename',
                ['Foo\\Bar\\NamespacedClass.withservicename', []],
            ],
            'Namespaced class with argument' => [
                'Foo\\Bar\\NamespacedClass(["with-arg" => true])',
                ['Foo\\Bar\\NamespacedClass', [["with-arg" => true]]],
            ],
            'Namespaced class with service name and argument' => [
                'Foo\\Bar\\NamespacedClass.withmodifier(["and-arg" => true])',
                ['Foo\\Bar\\NamespacedClass.withmodifier', [["and-arg" => true]]],
            ],
        ];
    }
}
