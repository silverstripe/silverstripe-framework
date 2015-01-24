<?php
/**
 * @package framework
 * @subpackage tests
 *
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
		$obj = new ObjectTest_ExtendTest();

		$this->assertTrue($obj->hasMethod('extendableMethod'), "Extension method found in original spelling");
		$this->assertTrue($obj->hasMethod('ExTendableMethod'), "Extension method found case-insensitive");

		$objs = array();
		$objs[] = new ObjectTest_T2();
		$objs[] = new ObjectTest_T2();
		$objs[] = new ObjectTest_T2();

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
		$myObject = singleton('ObjectTest_MyObject');
		$this->assertEquals($myObject->class, 'ObjectTest_MyObject',
			'singletons are creating a correct class instance');
		$this->assertEquals(get_class($myObject), 'ObjectTest_MyObject',
			'singletons are creating a correct class instance');

		$mySubObject = singleton('ObjectTest_MySubObject');
		$this->assertEquals($mySubObject->class, 'ObjectTest_MySubObject',
			'singletons are creating a correct subclass instance');
		$this->assertEquals(get_class($mySubObject), 'ObjectTest_MySubObject',
			'singletons are creating a correct subclass instance');

		$myFirstObject = singleton('ObjectTest_MyObject');
		$mySecondObject = singleton('ObjectTest_MyObject');
		$this->assertTrue($myFirstObject === $mySecondObject,
			'singletons are using the same object on subsequent calls');
	}

	public function testStaticGetterMethod() {
		$obj = singleton('ObjectTest_MyObject');
		$this->assertEquals(
			'MyObject',
			$obj->stat('mystaticProperty'),
			'Uninherited statics through stat() on a singleton behave the same as built-in PHP statics'
		);
	}

	public function testStaticInheritanceGetters() {
		$obj = singleton('ObjectTest_MyObject');
		$subObj = singleton('ObjectTest_MyObject');
		$this->assertEquals(
			$subObj->stat('mystaticProperty'),
			'MyObject',
			'Statics defined on a parent class are available through stat() on a subclass'
		);
	}

	public function testStaticSettingOnSingletons() {
		$singleton1 = singleton('ObjectTest_MyObject');
		$singleton2 = singleton('ObjectTest_MyObject');
		$singleton1->set_stat('mystaticProperty', 'changed');
		$this->assertEquals(
			$singleton2->stat('mystaticProperty'),
			'changed',
			'Statics setting is populated throughout singletons without explicitly clearing cache'
		);
	}

	public function testStaticSettingOnInstances() {
		$instance1 = new ObjectTest_MyObject();
		$instance2 = new ObjectTest_MyObject();
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
		$createdObj = ObjectTest_CreateTest::create('arg1', 'arg2', array(), null, 'arg5');
		$this->assertEquals($createdObj->constructArguments, array('arg1', 'arg2', array(), null, 'arg5'));

		$strongObj = Object::strong_create('ObjectTest_CreateTest', 'arg1', 'arg2', array(), null, 'arg5');
		$this->assertEquals($strongObj->constructArguments, array('arg1', 'arg2', array(), null, 'arg5'));
	}

	public function testCreateLateStaticBinding() {
		$createdObj = ObjectTest_CreateTest::create('arg1', 'arg2', array(), null, 'arg5');
		$this->assertEquals($createdObj->constructArguments, array('arg1', 'arg2', array(), null, 'arg5'));
	}

	/**
	 * Tests that {@link Object::useCustomClass()} correnctly replaces normal and strong objects
	 */
	public function testUseCustomClass() {
		$obj1 = ObjectTest_CreateTest::create();
		$this->assertTrue($obj1 instanceof ObjectTest_CreateTest);

		Object::useCustomClass('ObjectTest_CreateTest', 'ObjectTest_CreateTest2');
		$obj2 = ObjectTest_CreateTest::create();
		$this->assertTrue($obj2 instanceof ObjectTest_CreateTest2);

		$obj2_2 = Object::strong_create('ObjectTest_CreateTest');
		$this->assertTrue($obj2_2 instanceof ObjectTest_CreateTest);

		Object::useCustomClass('ObjectTest_CreateTest', 'ObjectTest_CreateTest3', true);
		$obj3 = ObjectTest_CreateTest::create();
		$this->assertTrue($obj3 instanceof ObjectTest_CreateTest3);

		$obj3_2 = Object::strong_create('ObjectTest_CreateTest');
		$this->assertTrue($obj3_2 instanceof ObjectTest_CreateTest3);
	}

	/**
	 * Tests {@link Object::singleton()}
	 */
	public function testSingleton() {
		$inst = Controller::singleton();
		$this->assertInstanceOf('Controller', $inst);
		$inst2 = Controller::singleton();
		$this->assertSame($inst2, $inst);
	}

	public function testGetExtensions() {
		$this->assertEquals(
			Object::get_extensions('ObjectTest_ExtensionTest'),
			array(
				'oBjEcTTEST_ExtendTest1',
				"ObjectTest_ExtendTest2",
			)
		);
		$this->assertEquals(
			Object::get_extensions('ObjectTest_ExtensionTest', true),
			array(
				'oBjEcTTEST_ExtendTest1',
				"ObjectTest_ExtendTest2('FOO', 'BAR')",
			)
		);
		$inst = new ObjectTest_ExtensionTest();
		$extensions = $inst->getExtensionInstances();
		$this->assertEquals(count($extensions), 2);
		$this->assertInstanceOf(
			'ObjectTest_ExtendTest1',
			$extensions['ObjectTest_ExtendTest1']
		);
		$this->assertInstanceOf(
			'ObjectTest_ExtendTest2',
			$extensions['ObjectTest_ExtendTest2']
		);
		$this->assertInstanceOf(
			'ObjectTest_ExtendTest1',
			$inst->getExtensionInstance('ObjectTest_ExtendTest1')
		);
		$this->assertInstanceOf(
			'ObjectTest_ExtendTest2',
			$inst->getExtensionInstance('ObjectTest_ExtendTest2')
		);
	}

	/**
	 * Tests {@link Object::has_extension()}, {@link Object::add_extension()}
	 */
	public function testHasAndAddExtension() {
		// ObjectTest_ExtendTest1 is built in via $extensions
		$this->assertTrue(
			ObjectTest_ExtensionTest::has_extension('OBJECTTEST_ExtendTest1'),
			"Extensions are detected when set on Object::\$extensions on has_extension() without case-sensitivity"
		);
		$this->assertTrue(
			ObjectTest_ExtensionTest::has_extension('ObjectTest_ExtendTest1'),
			"Extensions are detected when set on Object::\$extensions on has_extension() without case-sensitivity"
		);
		$this->assertTrue(
			singleton('ObjectTest_ExtensionTest')->hasExtension('ObjectTest_ExtendTest1'),
			"Extensions are detected when set on Object::\$extensions on instance hasExtension() without"
				. " case-sensitivity"
		);

		// ObjectTest_ExtendTest2 is built in via $extensions (with parameters)
		$this->assertTrue(
			ObjectTest_ExtensionTest::has_extension('ObjectTest_ExtendTest2'),
			"Extensions are detected with static has_extension() when set on Object::\$extensions with"
				. " additional parameters"
		);
		$this->assertTrue(
			singleton('ObjectTest_ExtensionTest')->hasExtension('ObjectTest_ExtendTest2'),
			"Extensions are detected with instance hasExtension() when set on Object::\$extensions with"
				. " additional parameters"
		);
		$this->assertFalse(
			ObjectTest_ExtensionTest::has_extension('ObjectTest_ExtendTest3'),
			"Other extensions available in the system are not present unless explicitly added to this object"
				. " when checking through has_extension()"
		);
		$this->assertFalse(
			singleton('ObjectTest_ExtensionTest')->hasExtension('ObjectTest_ExtendTest3'),
			"Other extensions available in the system are not present unless explicitly added to this object"
				. " when checking through instance hasExtension()"
		);

		// ObjectTest_ExtendTest3 is added manually
		ObjectTest_ExtensionTest::add_extension('ObjectTest_ExtendTest3("Param")');
		$this->assertTrue(
			ObjectTest_ExtensionTest::has_extension('ObjectTest_ExtendTest3'),
			"Extensions are detected with static has_extension() when added through add_extension()"
		);
		// ObjectTest_ExtendTest4 is added manually
		ObjectTest_ExtensionTest3::add_extension('ObjectTest_ExtendTest4("Param")');
		// test against ObjectTest_ExtendTest3, not ObjectTest_ExtendTest3
		$this->assertTrue(
			ObjectTest_ExtensionTest3::has_extension('ObjectTest_ExtendTest4'),
			"Extensions are detected with static has_extension() when added through add_extension()"
		);
		// test against ObjectTest_ExtendTest3, not ObjectTest_ExtendTest4 to test if it picks up
		// the sub classes of ObjectTest_ExtendTest3
		$this->assertTrue(
			ObjectTest_ExtensionTest3::has_extension('ObjectTest_ExtendTest3'),
			"Sub-Extensions are detected with static has_extension() when added through add_extension()"
		);
		// strictly test against ObjectTest_ExtendTest3, not ObjectTest_ExtendTest4 to test if it picks up
		// the sub classes of ObjectTest_ExtendTest3
		$this->assertFalse(
			ObjectTest_ExtensionTest3::has_extension('ObjectTest_ExtendTest3', null, true),
			"Sub-Extensions are detected with static has_extension() when added through add_extension()"
		);
		// a singleton() wouldn't work as its already initialized
		$objectTest_ExtensionTest = new ObjectTest_ExtensionTest();
		$this->assertTrue(
			$objectTest_ExtensionTest->hasExtension('ObjectTest_ExtendTest3'),
			"Extensions are detected with instance hasExtension() when added through add_extension()"
		);

		// @todo At the moment, this does NOT remove the extension due to parameterized naming,
		//  meaning the extension will remain added in further test cases
		ObjectTest_ExtensionTest::remove_extension('ObjectTest_ExtendTest3');
	}

	public function testRemoveExtension() {
		// manually add ObjectTest_ExtendTest2
		ObjectTest_ExtensionRemoveTest::add_extension('ObjectTest_ExtendTest2');
		$this->assertTrue(
			ObjectTest_ExtensionRemoveTest::has_extension('ObjectTest_ExtendTest2'),
			"Extension added through \$add_extension() are added correctly"
		);

		ObjectTest_ExtensionRemoveTest::remove_extension('ObjectTest_ExtendTest2');
		$this->assertFalse(
			ObjectTest_ExtensionRemoveTest::has_extension('ObjectTest_ExtendTest2'),
			"Extension added through \$add_extension() are detected as removed in has_extension()"
		);
		$this->assertFalse(
			singleton('ObjectTest_ExtensionRemoveTest')->hasExtension('ObjectTest_ExtendTest2'),
			"Extensions added through \$add_extension() are detected as removed in instances through hasExtension()"
		);

		// ObjectTest_ExtendTest1 is already present in $extensions
		ObjectTest_ExtensionRemoveTest::remove_extension('ObjectTest_ExtendTest1');

		$this->assertFalse(
			ObjectTest_ExtensionRemoveTest::has_extension('ObjectTest_ExtendTest1'),
			"Extension added through \$extensions are detected as removed in has_extension()"
		);

		$objectTest_ExtensionRemoveTest = new ObjectTest_ExtensionRemoveTest();
		$this->assertFalse(
			$objectTest_ExtensionRemoveTest->hasExtension('ObjectTest_ExtendTest1'),
			"Extensions added through \$extensions are detected as removed in instances through hasExtension()"
		);
	}

	public function testRemoveExtensionWithParameters() {
		ObjectTest_ExtensionRemoveTest::add_extension('ObjectTest_ExtendTest2("MyParam")');

		$this->assertTrue(
			ObjectTest_ExtensionRemoveTest::has_extension('ObjectTest_ExtendTest2'),
			"Extension added through \$add_extension() are added correctly"
		);

		ObjectTest_ExtensionRemoveTest::remove_extension('ObjectTest_ExtendTest2');
		$this->assertFalse(
			Object::has_extension('ObjectTest_ExtensionRemoveTest', 'ObjectTest_ExtendTest2'),
			"Extension added through \$add_extension() are detected as removed in has_extension()"
		);

		$objectTest_ExtensionRemoveTest = new ObjectTest_ExtensionRemoveTest();
		$this->assertFalse(
			$objectTest_ExtensionRemoveTest->hasExtension('ObjectTest_ExtendTest2'),
			"Extensions added through \$extensions are detected as removed in instances through hasExtension()"
		);
	}

	public function testParentClass() {
		$this->assertEquals(ObjectTest_MyObject::create()->parentClass(), 'Object');
	}

	public function testIsA() {
		$this->assertTrue(ObjectTest_MyObject::create() instanceof Object);
		$this->assertTrue(ObjectTest_MyObject::create() instanceof ObjectTest_MyObject);
	}

	/**
	 * Tests {@link Object::hasExtension() and Object::getExtensionInstance()}
	 */
	public function testExtInstance() {
		$obj = new ObjectTest_ExtensionTest2();

		$this->assertTrue($obj->hasExtension('ObjectTest_Extension'));
		$this->assertTrue($obj->getExtensionInstance('ObjectTest_Extension') instanceof ObjectTest_Extension);
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
		$object   = new ObjectTest_ExtendTest();
		$argument = 'test';

		$this->assertEquals($object->extend('extendableMethod'), array('ExtendTest2()'));
		$this->assertEquals($object->extend('extendableMethod', $argument), array('ExtendTest2(modified)'));
		$this->assertEquals($argument, 'modified');

		$this->assertEquals($object->invokeWithExtensions('extendableMethod'), array('ExtendTest()', 'ExtendTest2()'));
		$this->assertEquals (
			$object->invokeWithExtensions('extendableMethod', 'test'),
			array('ExtendTest(test)', 'ExtendTest2(modified)')
		);
	}

	public function testParseClassSpec() {
		// Simple case
		$this->assertEquals(
			array('Versioned',array('Stage', 'Live')),
			Object::parse_class_spec("Versioned('Stage','Live')")
		);
		// String with commas
		$this->assertEquals(
			array('Versioned',array('Stage,Live', 'Stage')),
			Object::parse_class_spec("Versioned('Stage,Live','Stage')")
		);
		// String with quotes
		$this->assertEquals(
			array('Versioned',array('Stage\'Stage,Live\'Live', 'Live')),
			Object::parse_class_spec("Versioned('Stage\'Stage,Live\'Live','Live')")
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
			Object::parse_class_spec("Enum(['Accepted', 'Pending', 'Declined', 'Unsubmitted'), 'Unsubmitted']")
		);
		// 5.4 Nested shorthand array
		$this->assertEquals(
			array('Enum',array(array('Accepted', 'Pending', 'Declined', array('UnsubmittedA','UnsubmittedB')),
				'Unsubmitted')),
			Object::parse_class_spec(
				"Enum(['Accepted', 'Pending', 'Declined', ['UnsubmittedA','UnsubmittedB']], 'Unsubmitted')")
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

/**#@+
 * @ignore
 */

class ObjectTest_T1A extends Object {
	public function testMethod() {
		return true;
	}
	public function otherMethod() {
		return true;
	}
}

class ObjectTest_T1B extends Object {
	public function someMethod() {
		return true;
	}
}

class ObjectTest_T1C extends Object {
	public function t1cMethod() {
		return true;
	}
}

class ObjectTest_T2 extends Object {
	protected $failover;
	protected $failoverArr = array();

	public function __construct() {
		$this->failover = new ObjectTest_T1A();
		$this->failoverArr[0] = new ObjectTest_T1B();
		$this->failoverArr[1] = new ObjectTest_T1C();

		parent::__construct();
	}

	public function defineMethods() {
		$this->addWrapperMethod('Wrapping', 'wrappedMethod');

		$this->addMethodsFrom('failover');
		$this->addMethodsFrom('failoverArr',0);
		$this->addMethodsFrom('failoverArr',1);

		$this->createMethod('testCreateMethod', 'return "created";');
	}

	public function wrappedMethod($val) {
		return $val;
	}

	public function normalMethod() {
		return true;
	}

}

class ObjectTest_MyObject extends Object {
	public $title = 'my object';
	/** @config */
	private static $mystaticProperty = "MyObject";
	static $mystaticArray = array('one');
}

class ObjectTest_MySubObject extends ObjectTest_MyObject {
	public $title = 'my subobject';
	private static $mystaticProperty = "MySubObject";
	static $mystaticSubProperty = "MySubObject";
	static $mystaticArray = array('two');
}

class ObjectTest_CreateTest extends Object {

	public $constructArguments;

	public function __construct() {
		$this->constructArguments = func_get_args();
		parent::__construct();
	}

}

class ObjectTest_CreateTest2 extends Object {}
class ObjectTest_CreateTest3 extends Object {}

class ObjectTest_ExtensionTest extends Object {

	private static $extensions = array (
		'oBjEcTTEST_ExtendTest1',
		"ObjectTest_ExtendTest2('FOO', 'BAR')",
	);

}

class ObjectTest_ExtensionTest2 extends Object {
	private static $extensions = array('ObjectTest_Extension');
}


class ObjectTest_ExtensionTest3 extends Object {
}

class ObjectTest_ExtensionRemoveTest extends Object {

	private static $extensions = array (
		'ObjectTest_ExtendTest1',
	);

}

class ObjectTest_Extension extends Extension {}

class ObjectTest_CacheTest extends Object {

	public $count = 0;

	public function cacheMethod($arg1 = null) {
		return ($arg1) ? 'hasarg' : 'noarg';
	}

	public function incNumber() {
		$this->count++;
		return $this->count;
	}

}

class ObjectTest_ExtendTest extends Object {
	private static $extensions = array('ObjectTest_ExtendTest1', 'ObjectTest_ExtendTest2');
	public function extendableMethod($argument = null) { return "ExtendTest($argument)"; }
}

class ObjectTest_ExtendTest1 extends Extension {
	public function extendableMethod(&$argument = null) {
		if($argument) $argument = 'modified';
		return null;
	}
}

class ObjectTest_ExtendTest2 extends Extension {
	public function extendableMethod($argument = null) { return "ExtendTest2($argument)"; }
}

class ObjectTest_ExtendTest3 extends Extension {
	public function extendableMethod($argument = null) { return "ExtendTest3($argument)"; }
}

class ObjectTest_ExtendTest4 extends ObjectTest_ExtendTest3 {
	public function extendableMethod($argument = null) { return "ExtendTest4($argument)"; }
}

/**#@-*/
