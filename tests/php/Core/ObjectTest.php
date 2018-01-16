<?php

namespace SilverStripe\Core\Tests;

use SilverStripe\Control\Controller;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Tests\ObjectTest\BaseObject;
use SilverStripe\Core\Tests\ObjectTest\ExtendTest1;
use SilverStripe\Core\Tests\ObjectTest\ExtendTest2;
use SilverStripe\Core\Tests\ObjectTest\ExtendTest3;
use SilverStripe\Core\Tests\ObjectTest\ExtendTest4;
use SilverStripe\Core\Tests\ObjectTest\ExtensionRemoveTest;
use SilverStripe\Core\Tests\ObjectTest\ExtensionTest;
use SilverStripe\Core\Tests\ObjectTest\ExtensionTest2;
use SilverStripe\Core\Tests\ObjectTest\ExtensionTest3;
use SilverStripe\Core\Tests\ObjectTest\MyObject;
use SilverStripe\Core\Tests\ObjectTest\MySubObject;
use SilverStripe\Core\Tests\ObjectTest\TestExtension;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Versioned\Versioned;

/**
 * @todo tests for addStaticVars()
 * @todo tests for setting statics which are not defined on the object as built-in PHP statics
 * @todo tests for setting statics through extensions (#2387)
 * @skipUpgrade
 */
class ObjectTest extends SapphireTest
{

    protected function setUp()
    {
        parent::setUp();
        Injector::inst()->unregisterObjects([
            Extension::class,
            BaseObject::class,
        ]);
    }

    public function testHasmethodBehaviour()
    {
        $obj = new ObjectTest\ExtendTest();

        $this->assertTrue($obj->hasMethod('extendableMethod'), "Extension method found in original spelling");
        $this->assertTrue($obj->hasMethod('ExTendableMethod'), "Extension method found case-insensitive");

        $objs = array();
        $objs[] = new ObjectTest\T2();
        $objs[] = new ObjectTest\T2();
        $objs[] = new ObjectTest\T2();

        // All these methods should exist and return true
        $trueMethods = [
            'testMethod',
            'otherMethod',
            'someMethod',
            't1cMethod',
            'normalMethod',
            'failoverCallback'
        ];

        foreach ($objs as $i => $obj) {
            foreach ($trueMethods as $method) {
                $methodU = strtoupper($method);
                $methodL = strtoupper($method);
                $this->assertTrue($obj->hasMethod($method), "Test that obj#$i has method $method");
                $this->assertTrue($obj->hasMethod($methodU), "Test that obj#$i has method $methodU");
                $this->assertTrue($obj->hasMethod($methodL), "Test that obj#$i has method $methodL");

                $this->assertTrue($obj->$method(), "Test that obj#$i can call method $method");
                $this->assertTrue($obj->$methodU(), "Test that obj#$i can call method $methodU");
                $this->assertTrue($obj->$methodL(), "Test that obj#$i can call method $methodL");
            }

            $this->assertTrue($obj->hasMethod('Wrapping'), "Test that obj#$i has method Wrapping");
            $this->assertTrue($obj->hasMethod('WRAPPING'), "Test that obj#$i has method WRAPPING");
            $this->assertTrue($obj->hasMethod('wrapping'), "Test that obj#$i has method wrapping");

            $this->assertEquals("Wrapping", $obj->Wrapping(), "Test that obj#$i can call method Wrapping");
            $this->assertEquals("Wrapping", $obj->WRAPPING(), "Test that obj#$i can call method WRAPPIGN");
            $this->assertEquals("Wrapping", $obj->wrapping(), "Test that obj#$i can call method wrapping");
        }
    }

    public function testSingletonCreation()
    {
        $myObject = MyObject::singleton();
        $this->assertInstanceOf(
            MyObject::class,
            $myObject,
            'singletons are creating a correct class instance'
        );
        $mySubObject = MySubObject::singleton();
        $this->assertInstanceOf(
            MySubObject::class,
            $mySubObject,
            'singletons are creating a correct subclass instance'
        );

        $myFirstObject = MyObject::singleton();
        $mySecondObject = MyObject::singleton();
        $this->assertTrue(
            $myFirstObject === $mySecondObject,
            'singletons are using the same object on subsequent calls'
        );
    }

    public function testStaticGetterMethod()
    {
        $obj = singleton(MyObject::class);
        $this->assertEquals(
            'MyObject',
            $obj->stat('mystaticProperty'),
            'Uninherited statics through stat() on a singleton behave the same as built-in PHP statics'
        );
    }

    public function testStaticInheritanceGetters()
    {
        $subObj = singleton(MyObject::class);
        $this->assertEquals(
            $subObj->stat('mystaticProperty'),
            'MyObject',
            'Statics defined on a parent class are available through stat() on a subclass'
        );
    }

    public function testStaticSettingOnSingletons()
    {
        $singleton1 = singleton(MyObject::class);
        $singleton2 = singleton(MyObject::class);
        $singleton1->set_stat('mystaticProperty', 'changed');
        $this->assertEquals(
            $singleton2->stat('mystaticProperty'),
            'changed',
            'Statics setting is populated throughout singletons without explicitly clearing cache'
        );
    }

    public function testStaticSettingOnInstances()
    {
        $instance1 = new ObjectTest\MyObject();
        $instance2 = new ObjectTest\MyObject();
        $instance1->set_stat('mystaticProperty', 'changed');
        $this->assertEquals(
            $instance2->stat('mystaticProperty'),
            'changed',
            'Statics setting through set_stat() is populated throughout instances without explicitly clearing cache'
        );
    }

    /**
     * Tests that {@link Object::create()} correctly passes all arguments to the new object
     */
    public function testCreateWithArgs()
    {
        $createdObj = ObjectTest\CreateTest::create('arg1', 'arg2', array(), null, 'arg5');
        $this->assertEquals($createdObj->constructArguments, array('arg1', 'arg2', array(), null, 'arg5'));
    }

    public function testCreateLateStaticBinding()
    {
        $createdObj = ObjectTest\CreateTest::create('arg1', 'arg2', array(), null, 'arg5');
        $this->assertEquals($createdObj->constructArguments, array('arg1', 'arg2', array(), null, 'arg5'));
    }

    /**
     * Tests {@link Object::singleton()}
     */
    public function testSingleton()
    {
        $inst = Controller::singleton();
        $this->assertInstanceOf(Controller::class, $inst);
        $inst2 = Controller::singleton();
        $this->assertSame($inst2, $inst);
    }

    public function testGetExtensions()
    {
        $this->assertEquals(
            array(
                'SilverStripe\\Core\\Tests\\oBjEcTTEST\\EXTENDTest1',
                "SilverStripe\\Core\\Tests\\ObjectTest\\ExtendTest2",
            ),
            ExtensionTest::get_extensions()
        );
        $this->assertEquals(
            array(
                'SilverStripe\\Core\\Tests\\oBjEcTTEST\\EXTENDTest1',
                "SilverStripe\\Core\\Tests\\ObjectTest\\ExtendTest2('FOO', 'BAR')",
            ),
            ExtensionTest::get_extensions(null, true)
        );
        $inst = new ExtensionTest();
        $extensions = $inst->getExtensionInstances();
        $this->assertCount(2, $extensions);
        $this->assertArrayHasKey(ExtendTest1::class, $extensions);
        $this->assertInstanceOf(
            ExtendTest1::class,
            $extensions[ExtendTest1::class]
        );
        $this->assertArrayHasKey(ExtendTest2::class, $extensions);
        $this->assertInstanceOf(
            ExtendTest2::class,
            $extensions[ExtendTest2::class]
        );
        $this->assertInstanceOf(
            ExtendTest1::class,
            $inst->getExtensionInstance(ExtendTest1::class)
        );
        $this->assertInstanceOf(
            ExtendTest2::class,
            $inst->getExtensionInstance(ExtendTest2::class)
        );
    }

    /**
     * Tests {@link Object::has_extension()}, {@link Object::add_extension()}
     */
    public function testHasAndAddExtension()
    {
        // ObjectTest_ExtendTest1 is built in via $extensions
        $this->assertTrue(
            ExtensionTest::has_extension('SilverStripe\\Core\\Tests\\oBjEcTTEST\\EXTENDTest1'),
            "Extensions are detected when set on Object::\$extensions on has_extension() without case-sensitivity"
        );
        $this->assertTrue(
            ExtensionTest::has_extension(ExtendTest1::class),
            "Extensions are detected when set on Object::\$extensions on has_extension() without case-sensitivity"
        );
        $this->assertTrue(
            singleton(ExtensionTest::class)->hasExtension(ExtendTest1::class),
            "Extensions are detected when set on Object::\$extensions on instance hasExtension() without"
            . " case-sensitivity"
        );

        // ObjectTest_ExtendTest2 is built in via $extensions (with parameters)
        $this->assertTrue(
            ExtensionTest::has_extension(ExtendTest2::class),
            "Extensions are detected with static has_extension() when set on Object::\$extensions with"
            . " additional parameters"
        );
        $this->assertTrue(
            singleton(ExtensionTest::class)->hasExtension(ExtendTest2::class),
            "Extensions are detected with instance hasExtension() when set on Object::\$extensions with"
            . " additional parameters"
        );
        $this->assertFalse(
            ExtensionTest::has_extension(ExtendTest3::class),
            "Other extensions available in the system are not present unless explicitly added to this object"
            . " when checking through has_extension()"
        );
        $this->assertFalse(
            singleton(ExtensionTest::class)->hasExtension(ExtendTest3::class),
            "Other extensions available in the system are not present unless explicitly added to this object"
            . " when checking through instance hasExtension()"
        );

        // ObjectTest_ExtendTest3 is added manually
        ExtensionTest::add_extension(ExtendTest3::class . '("Param")');
        $this->assertTrue(
            ExtensionTest::has_extension(ExtendTest3::class),
            "Extensions are detected with static has_extension() when added through add_extension()"
        );
        // ExtendTest4 is added manually
        ExtensionTest3::add_extension(ExtendTest4::class . '("Param")');
        // test against ObjectTest_ExtendTest3, not ObjectTest_ExtendTest3
        $this->assertTrue(
            ExtensionTest3::has_extension(ExtendTest4::class),
            "Extensions are detected with static has_extension() when added through add_extension()"
        );
        // test against ObjectTest_ExtendTest3, not ExtendTest4 to test if it picks up
        // the sub classes of ObjectTest_ExtendTest3
        $this->assertTrue(
            ExtensionTest3::has_extension(ExtendTest3::class),
            "Sub-Extensions are detected with static has_extension() when added through add_extension()"
        );
        // strictly test against ObjectTest_ExtendTest3, not ExtendTest4 to test if it picks up
        // the sub classes of ObjectTest_ExtendTest3
        $this->assertFalse(
            ExtensionTest3::has_extension(ExtendTest3::class, null, true),
            "Sub-Extensions are detected with static has_extension() when added through add_extension()"
        );
        // a singleton() wouldn't work as its already initialized
        $objectTest_ExtensionTest = new ExtensionTest();
        $this->assertTrue(
            $objectTest_ExtensionTest->hasExtension(ExtendTest3::class),
            "Extensions are detected with instance hasExtension() when added through add_extension()"
        );

        // @todo At the moment, this does NOT remove the extension due to parameterized naming,
        //  meaning the extension will remain added in further test cases
        ExtensionTest::remove_extension(ExtendTest3::class);
    }

    public function testRemoveExtension()
    {
        // manually add ObjectTest_ExtendTest2
        ObjectTest\ExtensionRemoveTest::add_extension(ExtendTest2::class);
        $this->assertTrue(
            ObjectTest\ExtensionRemoveTest::has_extension(ExtendTest2::class),
            "Extension added through \$add_extension() are added correctly"
        );

        ObjectTest\ExtensionRemoveTest::remove_extension(ExtendTest2::class);
        $this->assertFalse(
            ObjectTest\ExtensionRemoveTest::has_extension(ExtendTest2::class),
            "Extension added through \$add_extension() are detected as removed in has_extension()"
        );
        $this->assertFalse(
            singleton(ExtensionRemoveTest::class)->hasExtension(ExtendTest2::class),
            "Extensions added through \$add_extension() are detected as removed in instances through hasExtension()"
        );

        // ObjectTest_ExtendTest1 is already present in $extensions
        ObjectTest\ExtensionRemoveTest::remove_extension(ExtendTest1::class);

        $this->assertFalse(
            ObjectTest\ExtensionRemoveTest::has_extension(ExtendTest1::class),
            "Extension added through \$extensions are detected as removed in has_extension()"
        );

        $objectTest_ExtensionRemoveTest = new ObjectTest\ExtensionRemoveTest();
        $this->assertFalse(
            $objectTest_ExtensionRemoveTest->hasExtension(ExtendTest1::class),
            "Extensions added through \$extensions are detected as removed in instances through hasExtension()"
        );
    }

    public function testRemoveExtensionWithParameters()
    {
        ObjectTest\ExtensionRemoveTest::add_extension(ExtendTest2::class . '("MyParam")');

        $this->assertTrue(
            ObjectTest\ExtensionRemoveTest::has_extension(ExtendTest2::class),
            "Extension added through \$add_extension() are added correctly"
        );

        ObjectTest\ExtensionRemoveTest::remove_extension(ExtendTest2::class);
        $this->assertFalse(
            ExtensionRemoveTest::has_extension(ExtendTest2::class),
            "Extension added through \$add_extension() are detected as removed in has_extension()"
        );

        $objectTest_ExtensionRemoveTest = new ObjectTest\ExtensionRemoveTest();
        $this->assertFalse(
            $objectTest_ExtensionRemoveTest->hasExtension(ExtendTest2::class),
            "Extensions added through \$extensions are detected as removed in instances through hasExtension()"
        );
    }

    public function testIsA()
    {
        $this->assertTrue(ObjectTest\MyObject::create() instanceof ObjectTest\BaseObject);
        $this->assertTrue(ObjectTest\MyObject::create() instanceof ObjectTest\MyObject);
    }

    /**
     * Tests {@link Object::hasExtension() and Object::getExtensionInstance()}
     */
    public function testExtInstance()
    {
        $obj = new ExtensionTest2();

        $this->assertTrue($obj->hasExtension(TestExtension::class));
        $this->assertTrue($obj->getExtensionInstance(TestExtension::class) instanceof ObjectTest\TestExtension);
    }

    public function testExtend()
    {
        $object = new ObjectTest\ExtendTest();
        $argument = 'test';

        $this->assertEquals($object->extend('extendableMethod'), array('ExtendTest2()'));
        $this->assertEquals($object->extend('extendableMethod', $argument), array('ExtendTest2(modified)'));
        $this->assertEquals($argument, 'modified');

        $this->assertEquals(
            array('ExtendTest()', 'ExtendTest2()'),
            $object->invokeWithExtensions('extendableMethod')
        );
        $arg1 = 'test';
        $arg2 = 'bob';
        $this->assertEquals(
            array('ExtendTest(test,bob)', 'ExtendTest2(modified,objectmodified)'),
            $object->invokeWithExtensions('extendableMethod', $arg1, $arg2)
        );
        $this->assertEquals('modified', $arg1);
        $this->assertEquals('objectmodified', $arg2);

        $object2 = new ObjectTest\Extending();
        $first = 1;
        $second = 2;
        $third = 3;
        $result = $object2->getResults($first, $second, $third);
        $this->assertEquals(
            array(array('before', 'extension', 'after')),
            $result
        );
        $this->assertEquals(31, $first);
        $this->assertEquals(32, $second);
        $this->assertEquals(33, $third);
    }

    public function testParseClassSpec()
    {
        // Simple case
        $this->assertEquals(
            array(Versioned::class, array('Stage', 'Live')),
            ClassInfo::parse_class_spec("SilverStripe\\Versioned\\Versioned('Stage','Live')")
        );
        // Case with service identifier
        $this->assertEquals(
            [
                Versioned::class . '.versioned',
                ['Versioned'],
            ],
            ClassInfo::parse_class_spec("SilverStripe\\Versioned\\Versioned.versioned('Versioned')")
        );
        // String with commas
        $this->assertEquals(
            array(Versioned::class, array('Stage,Live', 'Stage')),
            ClassInfo::parse_class_spec("SilverStripe\\Versioned\\Versioned('Stage,Live','Stage')")
        );
        // String with quotes
        $this->assertEquals(
            array(Versioned::class, array('Stage\'Stage,Live\'Live', 'Live')),
            ClassInfo::parse_class_spec("SilverStripe\\Versioned\\Versioned('Stage\\'Stage,Live\\'Live','Live')")
        );

        // True, false and null values
        $this->assertEquals(
            array('ClassName', array('string', true, array('string', false))),
            ClassInfo::parse_class_spec('ClassName("string", true, array("string", false))')
        );
        $this->assertEquals(
            array('ClassName', array(true, false, null)),
            ClassInfo::parse_class_spec('ClassName(true, false, null)')
        );

        // Array
        $this->assertEquals(
            array('Enum', array(array('Accepted', 'Pending', 'Declined', 'Unsubmitted'), 'Unsubmitted')),
            ClassInfo::parse_class_spec("Enum(array('Accepted', 'Pending', 'Declined', 'Unsubmitted'), 'Unsubmitted')")
        );
        // Nested array
        $this->assertEquals(
            [
                'Enum',
                [
                    ['Accepted', 'Pending', 'Declined', ['UnsubmittedA', 'UnsubmittedB']],
                    'Unsubmitted'
                ]
            ],
            ClassInfo::parse_class_spec(
                "Enum(array('Accepted', 'Pending', 'Declined', array('UnsubmittedA','UnsubmittedB')), 'Unsubmitted')"
            )
        );
        // 5.4 Shorthand Array
        $this->assertEquals(
            array('Enum', array(array('Accepted', 'Pending', 'Declined', 'Unsubmitted'), 'Unsubmitted')),
            ClassInfo::parse_class_spec("Enum(['Accepted', 'Pending', 'Declined', 'Unsubmitted'], 'Unsubmitted')")
        );
        // 5.4 Nested shorthand array
        $this->assertEquals(
            [
                'Enum',
                [
                    ['Accepted', 'Pending', 'Declined', ['UnsubmittedA', 'UnsubmittedB']],
                    'Unsubmitted'
                ]
            ],
            ClassInfo::parse_class_spec(
                "Enum(['Accepted', 'Pending', 'Declined', ['UnsubmittedA','UnsubmittedB']], 'Unsubmitted')"
            )
        );

        // Associative array
        $this->assertEquals(
            array('Varchar', array(255, array('nullifyEmpty' => false))),
            ClassInfo::parse_class_spec("Varchar(255, array('nullifyEmpty' => false))")
        );
        // Nested associative array
        $this->assertEquals(
            array('Test', array('string', array('nested' => array('foo' => 'bar')))),
            ClassInfo::parse_class_spec("Test('string', array('nested' => array('foo' => 'bar')))")
        );
        // 5.4 shorthand associative array
        $this->assertEquals(
            array('Varchar', array(255, array('nullifyEmpty' => false))),
            ClassInfo::parse_class_spec("Varchar(255, ['nullifyEmpty' => false])")
        );
        // 5.4 shorthand nested associative array
        $this->assertEquals(
            array('Test', array('string', array('nested' => array('foo' => 'bar')))),
            ClassInfo::parse_class_spec("Test('string', ['nested' => ['foo' => 'bar']])")
        );

        // Namespaced class
        $this->assertEquals(
            array('Test\MyClass', array()),
            ClassInfo::parse_class_spec('Test\MyClass')
        );
        // Fully qualified namespaced class
        $this->assertEquals(
            array('\Test\MyClass', array()),
            ClassInfo::parse_class_spec('\Test\MyClass')
        );
    }

    public function testInjectedExtensions()
    {
        $mockExtension = $this->createMock(TestExtension::class);
        $mockClass = get_class($mockExtension);

        $object = new ExtensionTest2();

        // sanity check
        $this->assertNotEquals(TestExtension::class, $mockClass);

        $this->assertTrue($object->hasExtension(TestExtension::class));
        $this->assertFalse($object->hasExtension($mockClass));
        $this->assertCount(1, $object->getExtensionInstances());
        $this->assertInstanceOf(TestExtension::class, $object->getExtensionInstance(TestExtension::class));
        $this->assertNotInstanceOf($mockClass, $object->getExtensionInstance(TestExtension::class));

        Injector::inst()->registerService($mockExtension, TestExtension::class);

        $object = new ExtensionTest2();

        $this->assertTrue($object->hasExtension(TestExtension::class));
        $this->assertTrue($object->hasExtension($mockClass));
        $this->assertCount(1, $object->getExtensionInstances());
        $this->assertInstanceOf(TestExtension::class, $object->getExtensionInstance(TestExtension::class));
        $this->assertInstanceOf($mockClass, $object->getExtensionInstance(TestExtension::class));
        $this->assertInstanceOf(TestExtension::class, $object->getExtensionInstance($mockClass));
        $this->assertInstanceOf($mockClass, $object->getExtensionInstance($mockClass));
    }
}
