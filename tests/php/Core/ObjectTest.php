<?php

namespace SilverStripe\Core\Tests;

use SilverStripe\Core\Object;
use SilverStripe\Core\Injector\Injector;
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
use SilverStripe\Control\Controller;

/**
 * @todo tests for addStaticVars()
 * @todo tests for setting statics which are not defined on the object as built-in PHP statics
 * @todo tests for setting statics through extensions (#2387)
 */
class ObjectTest extends SapphireTest {

	public function setUp() {
		parent::setUp();
		Injector::inst()->unregisterAllObjects();
	}

	public function testHasmethodBehaviour() {
		$obj = new ObjectTest\ExtendTest();

		$this->assertTrue($obj->hasMethod('extendableMethod'), "Extension method found in original spelling");
		$this->assertTrue($obj->hasMethod('ExTendableMethod'), "Extension method found case-insensitive");

		$objs = array();
		$objs[] = new ObjectTest\T2();
		$objs[] = new ObjectTest\T2();
		$objs[] = new ObjectTest\T2();

		// All these methods should exist and return true
		$trueMethods = array('testMethod','otherMethod','someMethod','t1cMethod','normalMethod');

		foreach($objs as $i => $obj) {
			foreach($trueMethods as $method) {
				$methodU = strtoupper($method);
				$methodL = strtoupper($method);
				$this->assertTrue($obj->hasMethod($method), "Test that obj#$i has method $method ($obj->class)");
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

	public function testSingletonCreation() {
		$myObject = singleton(MyObject::class);
		$this->assertEquals($myObject->class, MyObject::class,
			'singletons are creating a correct class instance');
		$this->assertEquals(get_class($myObject), MyObject::class,
			'singletons are creating a correct class instance');

		$mySubObject = singleton(MySubObject::class);
		$this->assertEquals($mySubObject->class, MySubObject::class,
			'singletons are creating a correct subclass instance');
		$this->assertEquals(get_class($mySubObject), MySubObject::class,
			'singletons are creating a correct subclass instance');

		$myFirstObject = singleton(MyObject::class);
		$mySecondObject = singleton(MyObject::class);
		$this->assertTrue($myFirstObject === $mySecondObject,
			'singletons are using the same object on subsequent calls');
	}

	public function testStaticGetterMethod() {
		$obj = singleton(MyObject::class);
		$this->assertEquals(
			'MyObject',
			$obj->stat('mystaticProperty'),
			'Uninherited statics through stat() on a singleton behave the same as built-in PHP statics'
		);
	}

	public function testStaticInheritanceGetters() {
		$subObj = singleton(MyObject::class);
		$this->assertEquals(
			$subObj->stat('mystaticProperty'),
			'MyObject',
			'Statics defined on a parent class are available through stat() on a subclass'
		);
	}

	public function testStaticSettingOnSingletons() {
		$singleton1 = singleton(MyObject::class);
		$singleton2 = singleton(MyObject::class);
		$singleton1->set_stat('mystaticProperty', 'changed');
		$this->assertEquals(
			$singleton2->stat('mystaticProperty'),
			'changed',
			'Statics setting is populated throughout singletons without explicitly clearing cache'
		);
	}

	public function testStaticSettingOnInstances() {
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
	public function testCreateWithArgs() {
		$createdObj = ObjectTest\CreateTest::create('arg1', 'arg2', array(), null, 'arg5');
		$this->assertEquals($createdObj->constructArguments, array('arg1', 'arg2', array(), null, 'arg5'));
	}

	public function testCreateLateStaticBinding() {
		$createdObj = ObjectTest\CreateTest::create('arg1', 'arg2', array(), null, 'arg5');
		$this->assertEquals($createdObj->constructArguments, array('arg1', 'arg2', array(), null, 'arg5'));
	}

	/**
	 * Tests {@link Object::singleton()}
	 */
	public function testSingleton() {
		$inst = Controller::singleton();
		$this->assertInstanceOf(Controller::class, $inst);
		$inst2 = Controller::singleton();
		$this->assertSame($inst2, $inst);
	}

	public function testGetExtensions() {
		$this->assertEquals(
			array(
				'SilverStripe\\Core\\Tests\\oBjEcTTEST\\EXTENDTest1',
				"SilverStripe\\Core\\Tests\\ObjectTest\\ExtendTest2",
			),
			Object::get_extensions(ExtensionTest::class)
		);
		$this->assertEquals(
			array(
				'SilverStripe\\Core\\Tests\\oBjEcTTEST\\EXTENDTest1',
				"SilverStripe\\Core\\Tests\\ObjectTest\\ExtendTest2('FOO', 'BAR')",
			),
			Object::get_extensions(ExtensionTest::class, true)
		);
		$inst = new ExtensionTest();
		$extensions = $inst->getExtensionInstances();
		$this->assertEquals(count($extensions), 2);
		$this->assertInstanceOf(
			ExtendTest1::class,
			$extensions[ExtendTest1::class]
		);
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
	public function testHasAndAddExtension() {
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
		ExtensionTest::add_extension(ExtendTest3::class .'("Param")');
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

	public function testRemoveExtension() {
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

	public function testRemoveExtensionWithParameters() {
		ObjectTest\ExtensionRemoveTest::add_extension(ExtendTest2::class.'("MyParam")');

		$this->assertTrue(
			ObjectTest\ExtensionRemoveTest::has_extension(ExtendTest2::class),
			"Extension added through \$add_extension() are added correctly"
		);

		ObjectTest\ExtensionRemoveTest::remove_extension(ExtendTest2::class);
		$this->assertFalse(
			Object::has_extension(ExtensionRemoveTest::class, ExtendTest2::class),
			"Extension added through \$add_extension() are detected as removed in has_extension()"
		);

		$objectTest_ExtensionRemoveTest = new ObjectTest\ExtensionRemoveTest();
		$this->assertFalse(
			$objectTest_ExtensionRemoveTest->hasExtension(ExtendTest2::class),
			"Extensions added through \$extensions are detected as removed in instances through hasExtension()"
		);
	}

	public function testParentClass() {
		$this->assertEquals(ObjectTest\MyObject::create()->parentClass(), 'SilverStripe\\Core\\Object');
	}

	public function testIsA() {
		$this->assertTrue(ObjectTest\MyObject::create() instanceof Object);
		$this->assertTrue(ObjectTest\MyObject::create() instanceof ObjectTest\MyObject);
	}

	/**
	 * Tests {@link Object::hasExtension() and Object::getExtensionInstance()}
	 */
	public function testExtInstance() {
		$obj = new ExtensionTest2();

		$this->assertTrue($obj->hasExtension(TestExtension::class));
		$this->assertTrue($obj->getExtensionInstance(TestExtension::class) instanceof ObjectTest\TestExtension);
	}

	public function testCacheToFile() {
		$this->markTestIncomplete();
		/*
		// This doesn't run properly on our build slave.
		$obj = new ObjectTest_CacheTest();

		$obj->clearCache('cacheMethod');
		$obj->clearCache('cacheMethod', null, array(true));
		$obj->clearCache('incNumber');

		$this->assertEquals('noarg', $obj->cacheToFile('cacheMethod', -1));
		$this->assertEquals('hasarg', $obj->cacheToFile('cacheMethod', -1, null, array(true)));
		$this->assertEquals('hasarg', $obj->cacheToFile('cacheMethod', 3600, null, array(true)));

		// -1 lifetime will ensure that the cache isn't read - number incremented
		$this->assertEquals(1, $obj->cacheToFile('incNumber', -1));
		// -1 lifetime will ensure that the cache isn't read - number incremented
		$this->assertEquals(2, $obj->cacheToFile('incNumber', -1));
		// Number shouldn't be incremented now because we're using the cached version
		$this->assertEquals(2, $obj->cacheToFile('incNumber'));
		*/
	}

	public function testExtend() {
		$object   = new ObjectTest\ExtendTest();
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
		$this->assertEquals (
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

	public function testParseClassSpec() {
		// Simple case
		$this->assertEquals(
			array('SilverStripe\\ORM\\Versioning\\Versioned',array('Stage', 'Live')),
			Object::parse_class_spec("SilverStripe\\ORM\\Versioning\\Versioned('Stage','Live')")
		);
		// String with commas
		$this->assertEquals(
			array('SilverStripe\\ORM\\Versioning\\Versioned',array('Stage,Live', 'Stage')),
			Object::parse_class_spec("SilverStripe\\ORM\\Versioning\\Versioned('Stage,Live','Stage')")
		);
		// String with quotes
		$this->assertEquals(
			array('SilverStripe\\ORM\\Versioning\\Versioned',array('Stage\'Stage,Live\'Live', 'Live')),
			Object::parse_class_spec("SilverStripe\\ORM\\Versioning\\Versioned('Stage\'Stage,Live\'Live','Live')")
		);

		// True, false and null values
		$this->assertEquals(
			array('ClassName', array('string', true, array('string', false))),
			Object::parse_class_spec('ClassName("string", true, array("string", false))')
		);
		$this->assertEquals(
			array('ClassName', array(true, false, null)),
			Object::parse_class_spec('ClassName(true, false, null)')
		);

		// Array
		$this->assertEquals(
			array('Enum',array(array('Accepted', 'Pending', 'Declined', 'Unsubmitted'), 'Unsubmitted')),
			Object::parse_class_spec("Enum(array('Accepted', 'Pending', 'Declined', 'Unsubmitted'), 'Unsubmitted')")
		);
		// Nested array
		$this->assertEquals(
			array('Enum',array(array('Accepted', 'Pending', 'Declined', array('UnsubmittedA','UnsubmittedB')),
				'Unsubmitted')),
			Object::parse_class_spec(
				"Enum(array('Accepted', 'Pending', 'Declined', array('UnsubmittedA','UnsubmittedB')), 'Unsubmitted')")
		);
		// 5.4 Shorthand Array
		$this->assertEquals(
			array('Enum',array(array('Accepted', 'Pending', 'Declined', 'Unsubmitted'), 'Unsubmitted')),
			Object::parse_class_spec("Enum(['Accepted', 'Pending', 'Declined', 'Unsubmitted'], 'Unsubmitted')")
		);
		// 5.4 Nested shorthand array
		$this->assertEquals(
			array('Enum',array(array('Accepted', 'Pending', 'Declined', array('UnsubmittedA','UnsubmittedB')),
				'Unsubmitted')),
			Object::parse_class_spec(
				"Enum(['Accepted', 'Pending', 'Declined', ['UnsubmittedA','UnsubmittedB']], 'Unsubmitted')")
		);

		// Associative array
		$this->assertEquals(
			array('Varchar', array(255, array('nullifyEmpty' => false))),
			Object::parse_class_spec("Varchar(255, array('nullifyEmpty' => false))")
		);
		// Nested associative array
		$this->assertEquals(
			array('Test', array('string', array('nested' => array('foo' => 'bar')))),
			Object::parse_class_spec("Test('string', array('nested' => array('foo' => 'bar')))")
		);
		// 5.4 shorthand associative array
		$this->assertEquals(
			array('Varchar', array(255, array('nullifyEmpty' => false))),
			Object::parse_class_spec("Varchar(255, ['nullifyEmpty' => false])")
		);
		// 5.4 shorthand nested associative array
		$this->assertEquals(
			array('Test', array('string', array('nested' => array('foo' => 'bar')))),
			Object::parse_class_spec("Test('string', ['nested' => ['foo' => 'bar']])")
		);

		// Namespaced class
		$this->assertEquals(
			array('Test\MyClass', array()),
			Object::parse_class_spec('Test\MyClass')
		);
		// Fully qualified namespaced class
		$this->assertEquals(
			array('\Test\MyClass', array()),
			Object::parse_class_spec('\Test\MyClass')
		);
	}
}


