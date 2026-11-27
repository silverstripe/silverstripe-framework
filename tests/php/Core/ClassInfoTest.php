<?php

namespace SilverStripe\Core\Tests;

use DateTime;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Tests\ClassInfoTest\AttributeInterface;
use SilverStripe\Core\Tests\ClassInfoTest\BaseClass;
use SilverStripe\Core\Tests\ClassInfoTest\BaseDataClass;
use SilverStripe\Core\Tests\ClassInfoTest\BaseObject;
use SilverStripe\Core\Tests\ClassInfoTest\ChildClass;
use SilverStripe\Core\Tests\ClassInfoTest\ClassAttribute1;
use SilverStripe\Core\Tests\ClassInfoTest\ClassAttribute2;
use SilverStripe\Core\Tests\ClassInfoTest\ClassAttribute3;
use SilverStripe\Core\Tests\ClassInfoTest\ClassWithAttributes1;
use SilverStripe\Core\Tests\ClassInfoTest\ClassWithAttributes2;
use SilverStripe\Core\Tests\ClassInfoTest\ClassWithAttributes3;
use SilverStripe\Core\Tests\ClassInfoTest\ExtendTest1;
use SilverStripe\Core\Tests\ClassInfoTest\ExtendTest2;
use SilverStripe\Core\Tests\ClassInfoTest\ExtendTest3;
use SilverStripe\Core\Tests\ClassInfoTest\ExtensionTest1;
use SilverStripe\Core\Tests\ClassInfoTest\ExtensionTest2;
use SilverStripe\Core\Tests\ClassInfoTest\GrandChildClass;
use SilverStripe\Core\Tests\ClassInfoTest\HasFields;
use SilverStripe\Core\Tests\ClassInfoTest\HasMethod;
use SilverStripe\Core\Tests\ClassInfoTest\NoFields;
use SilverStripe\Core\Tests\ClassInfoTest\MethodAttribute1;
use SilverStripe\Core\Tests\ClassInfoTest\MethodAttribute2;
use SilverStripe\Core\Tests\ClassInfoTest\MethodAttribute3;
use SilverStripe\Core\Tests\ClassInfoTest\PropertyAttribute1;
use SilverStripe\Core\Tests\ClassInfoTest\PropertyAttribute2;
use SilverStripe\Core\Tests\ClassInfoTest\PropertyAttribute3;
use SilverStripe\Core\Tests\ClassInfoTest\WithCustomTable;
use SilverStripe\Core\Tests\ClassInfoTest\WithRelation;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Model\ModelData;
use SilverStripe\ORM\DataObject;

use function strtolower;

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
            'silverstripe\\model\\modeldata' => ModelData::class,
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

    public function testHasTable()
    {
        $this->assertFalse(ClassInfo::hasTable(null));
        $this->assertFalse(ClassInfo::hasTable(''));
        $this->assertFalse(ClassInfo::hasTable('UnknownTableName'));
        $this->assertTrue(ClassInfo::hasTable('Member'));
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

    public function testClassesWithAttribute(): void
    {
        $this->assertEquals(
            [
                strtolower(ClassWithAttributes1::class) => ClassWithAttributes1::class,
            ],
            ClassInfo::classesWithAttribute(ClassAttribute1::class)
        );

        $this->assertEquals(
            [
                strtolower(ClassWithAttributes1::class) => ClassWithAttributes1::class,
                strtolower(ClassWithAttributes2::class) => ClassWithAttributes2::class,
            ],
            ClassInfo::classesWithAttribute(ClassAttribute2::class, false)
        );

        $this->assertEquals(
            [
                strtolower(ClassWithAttributes1::class) => ClassWithAttributes1::class,
                strtolower(ClassWithAttributes2::class) => ClassWithAttributes2::class,
                strtolower(ClassWithAttributes3::class) => ClassWithAttributes3::class,
            ],
            ClassInfo::classesWithAttribute(ClassAttribute2::class)
        );

        $this->assertEquals(
            [
                strtolower(ClassWithAttributes1::class) => ClassWithAttributes1::class,
                strtolower(ClassWithAttributes3::class) => ClassWithAttributes3::class,
            ],
            ClassInfo::classesWithAttribute(ClassAttribute3::class)
        );

        $this->assertEquals(
            [
                strtolower(ClassWithAttributes1::class) => ClassWithAttributes1::class,
                strtolower(ClassWithAttributes2::class) => ClassWithAttributes2::class,
                strtolower(ClassWithAttributes3::class) => ClassWithAttributes3::class,
            ],
            ClassInfo::classesWithAttribute(AttributeInterface::class)
        );

        $this->assertEquals(
            [
                strtolower(ClassWithAttributes1::class) => ClassWithAttributes1::class,
                strtolower(ClassWithAttributes2::class) => ClassWithAttributes2::class,
            ],
            ClassInfo::classesWithAttribute(AttributeInterface::class, false)
        );
    }

    public function testClassAttributes(): void
    {
        $attributes1 = ClassInfo::getClassAttributes(ClassWithAttributes1::class, ClassAttribute1::class);
        $attributes2 = ClassInfo::getClassAttributes(ClassWithAttributes1::class, ClassAttribute2::class);
        $attributes3 = ClassInfo::getClassAttributes(ClassWithAttributes1::class, ClassAttribute2::class, false);
        $attributes4 = ClassInfo::getClassAttributes(ClassWithAttributes1::class, ClassAttribute3::class);
        $attributes5 = ClassInfo::getClassAttributes(ClassWithAttributes1::class, AttributeInterface::class);

        $reflection1 = new ReflectionClass(ClassWithAttributes1::class);

        $attr1 = new ClassAttribute1('Test1');
        $attr1->setOwner($reflection1);

        $attr2 = new ClassAttribute2('Test2');
        $attr2->setOwner($reflection1);

        $attr3 = new ClassAttribute3('Test3');
        $attr3->setOwner($reflection1);

        $this->assertEquals([$attr1], $attributes1);
        $this->assertEquals([$attr2, $attr3], $attributes2);
        $this->assertEquals([$attr2], $attributes3);
        $this->assertEquals([$attr3], $attributes4);
        $this->assertEquals([$attr1, $attr2, $attr3], $attributes5);
    }

    public function testMethodAttributes(): void
    {
        $attributes1 = ClassInfo::getMethodsWithAttribute(ClassWithAttributes1::class, MethodAttribute1::class);
        $attributes2 = ClassInfo::getMethodsWithAttribute(ClassWithAttributes1::class, MethodAttribute2::class);
        $attributes3 = ClassInfo::getMethodsWithAttribute(ClassWithAttributes1::class, MethodAttribute2::class, includeSubClasses: false);
        $attributes4 = ClassInfo::getMethodsWithAttribute(ClassWithAttributes1::class, MethodAttribute3::class);
        $attributes5 = ClassInfo::getMethodsWithAttribute(ClassWithAttributes1::class, AttributeInterface::class);
        $attributes6 = ClassInfo::getMethodsWithAttribute(ClassWithAttributes1::class, AttributeInterface::class, ReflectionMethod::IS_PUBLIC);
        $attributes7 = ClassInfo::getMethodsWithAttribute(ClassWithAttributes1::class, AttributeInterface::class, ReflectionMethod::IS_PROTECTED);

        $reflection1 = new ReflectionClass(ClassWithAttributes1::class);
        $method1 = $reflection1->getMethod('firstMethod');
        $method2 = $reflection1->getMethod('secondMethod');
        $method3 = $reflection1->getMethod('thirdMethod');

        $attr1 = new MethodAttribute1('Test1');
        $attr1->setOwner($method1);

        $attr2 = new MethodAttribute2('Test2');
        $attr2->setOwner($method1);

        $attr3 = new MethodAttribute1('Test3');
        $attr3->setOwner($method2);

        $attr4 = new MethodAttribute3('Test4');
        $attr4->setOwner($method3);

        $this->assertEquals(['firstMethod' => [$attr1], 'secondMethod' => [$attr3]], $attributes1);
        $this->assertEquals(['firstMethod' => [$attr2], 'thirdMethod' => [$attr4]], $attributes2);
        $this->assertEquals(['firstMethod' => [$attr2]], $attributes3);
        $this->assertEquals(['thirdMethod' => [$attr4]], $attributes4);
        $this->assertEquals(['firstMethod' => [$attr1, $attr2], 'secondMethod' => [$attr3], 'thirdMethod' => [$attr4]], $attributes5);
        $this->assertEquals(['firstMethod' => [$attr1, $attr2]], $attributes6);
        $this->assertEquals(['secondMethod' => [$attr3], 'thirdMethod' => [$attr4]], $attributes7);
    }

    public function testPropertyAttributes(): void
    {
        $attributes1 = ClassInfo::getPropertiesWithAttribute(ClassWithAttributes1::class, PropertyAttribute1::class);
        $attributes2 = ClassInfo::getPropertiesWithAttribute(ClassWithAttributes1::class, PropertyAttribute2::class);
        $attributes3 = ClassInfo::getPropertiesWithAttribute(ClassWithAttributes1::class, PropertyAttribute2::class, includeSubClasses: false);
        $attributes4 = ClassInfo::getPropertiesWithAttribute(ClassWithAttributes1::class, PropertyAttribute3::class);
        $attributes5 = ClassInfo::getPropertiesWithAttribute(ClassWithAttributes1::class, AttributeInterface::class);
        $attributes6 = ClassInfo::getPropertiesWithAttribute(ClassWithAttributes1::class, AttributeInterface::class, ReflectionProperty::IS_PUBLIC);
        $attributes7 = ClassInfo::getPropertiesWithAttribute(ClassWithAttributes1::class, AttributeInterface::class, ReflectionProperty::IS_PROTECTED);

        $reflection1 = new ReflectionClass(ClassWithAttributes1::class);
        $property1 = $reflection1->getProperty('prop1');
        $property2 = $reflection1->getProperty('prop2');
        $property3 = $reflection1->getProperty('prop3');

        $attr1 = new PropertyAttribute1('Test1');
        $attr1->setOwner($property1);

        $attr2 = new PropertyAttribute2('Test2');
        $attr2->setOwner($property1);

        $attr3 = new PropertyAttribute1('Test3');
        $attr3->setOwner($property2);

        $attr4 = new PropertyAttribute3('Test4');
        $attr4->setOwner($property3);

        $this->assertEquals(['prop1' => [$attr1], 'prop2' => [$attr3]], $attributes1);
        $this->assertEquals(['prop1' => [$attr2], 'prop3' => [$attr4]], $attributes2);
        $this->assertEquals(['prop1' => [$attr2]], $attributes3);
        $this->assertEquals(['prop3' => [$attr4]], $attributes4);
        $this->assertEquals(['prop1' => [$attr1, $attr2], 'prop2' => [$attr3], 'prop3' => [$attr4]], $attributes5);
        $this->assertEquals(['prop1' => [$attr1, $attr2]], $attributes6);
        $this->assertEquals(['prop2' => [$attr3], 'prop3' => [$attr4]], $attributes7);
    }
}
