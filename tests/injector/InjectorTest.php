<?php

define('TEST_SERVICES', dirname(__FILE__) . '/testservices');

/**
 * Tests for the dependency injector
 *
 * Note that these are SS conversions of the existing Simpletest unit tests
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class InjectorTest extends SapphireTest {

	protected $nestingLevel = 0;

	public function setUp() {
		parent::setUp();

		$this->nestingLevel = 0;
	}

	public function tearDown() {

		while($this->nestingLevel > 0) {
			$this->nestingLevel--;
			Config::unnest();
		}

		parent::tearDown();
	}

	public function testCorrectlyInitialised() {
		$injector = Injector::inst();
		$this->assertTrue($injector->getConfigLocator() instanceof SilverStripeServiceConfigurationLocator,
			'Failure most likely because the injector has been referenced BEFORE being initialised in Core.php');
	}

	public function testBasicInjector() {
		$injector = new Injector();
		$injector->setAutoScanProperties(true);
		$config = array(array('src' => TEST_SERVICES . '/SampleService.php',));

		$injector->load($config);
		$this->assertTrue($injector->hasService('SampleService') == 'SampleService');

		$myObject = new TestObject();
		$injector->inject($myObject);

		$this->assertEquals(get_class($myObject->sampleService), 'SampleService');
	}

	public function testConfiguredInjector() {
		$injector = new Injector();
		$services = array(
			array(
				'src' => TEST_SERVICES . '/AnotherService.php',
				'properties' => array('config_property' => 'Value'),
			),
			array(
				'src' => TEST_SERVICES . '/SampleService.php',
			)
		);

		$injector->load($services);
		$this->assertTrue($injector->hasService('SampleService') == 'SampleService');
		// We expect a false because the 'AnotherService' is actually
		// just a replacement of the SampleService
		$this->assertTrue($injector->hasService('AnotherService') == 'AnotherService');

		$item = $injector->get('AnotherService');

		$this->assertEquals('Value', $item->config_property);
	}

	public function testIdToNameMap() {
		$injector = new Injector();
		$services = array(
			'FirstId' => 'AnotherService',
			'SecondId' => 'SampleService',
		);

		$injector->load($services);

		$this->assertTrue($injector->hasService('FirstId') == 'FirstId');
		$this->assertTrue($injector->hasService('SecondId') == 'SecondId');

		$this->assertTrue($injector->get('FirstId') instanceof AnotherService);
		$this->assertTrue($injector->get('SecondId') instanceof SampleService);
	}

	public function testReplaceService() {
		$injector = new Injector();
		$injector->setAutoScanProperties(true);

		$config = array(array('src' => TEST_SERVICES . '/SampleService.php'));

		// load
		$injector->load($config);

		// inject
		$myObject = new TestObject();
		$injector->inject($myObject);

		$this->assertEquals(get_class($myObject->sampleService), 'SampleService');

		// also tests that ID can be the key in the array
		$config = array('SampleService' => array('src' => TEST_SERVICES . '/AnotherService.php'));
		// , 'id' => 'SampleService'));
		// load
		$injector->load($config);

		$injector->inject($myObject);
		$this->assertEquals('AnotherService', get_class($myObject->sampleService));
	}

	public function testUpdateSpec() {
		$injector = new Injector();
		$services = array(
			'AnotherService' => array(
				'src' => TEST_SERVICES . '/AnotherService.php',
				'properties' => array(
					'filters' => array(
						'One',
						'Two',
					)
				),
			)
		);

		$injector->load($services);

		$injector->updateSpec('AnotherService', 'filters', 'Three');
		$another = $injector->get('AnotherService');

		$this->assertEquals(3, count($another->filters));
		$this->assertEquals('Three', $another->filters[2]);
	}

	public function testAutoSetInjector() {
		$injector = new Injector();
		$injector->setAutoScanProperties(true);
		$injector->addAutoProperty('auto', 'somevalue');
		$config = array(array('src' => TEST_SERVICES . '/SampleService.php',));
		$injector->load($config);

		$this->assertTrue($injector->hasService('SampleService') == 'SampleService');
		// We expect a false because the 'AnotherService' is actually
		// just a replacement of the SampleService

		$myObject = new TestObject();

		$injector->inject($myObject);

		$this->assertEquals(get_class($myObject->sampleService), 'SampleService');
		$this->assertEquals($myObject->auto, 'somevalue');
	}

	public function testSettingSpecificProperty() {
		$injector = new Injector();
		$config = array('AnotherService');
		$injector->load($config);
		$injector->setInjectMapping('TestObject', 'sampleService', 'AnotherService');
		$testObject = $injector->get('TestObject');

		$this->assertEquals(get_class($testObject->sampleService), 'AnotherService');
	}

	public function testSettingSpecificMethod() {
		$injector = new Injector();
		$config = array('AnotherService');
		$injector->load($config);
		$injector->setInjectMapping('TestObject', 'setSomething', 'AnotherService', 'method');

		$testObject = $injector->get('TestObject');

		$this->assertEquals(get_class($testObject->sampleService), 'AnotherService');
	}

	public function testInjectingScopedService() {
		$injector = new Injector();

		$config = array(
			'AnotherService',
			'AnotherService.DottedChild'	=> 'SampleService',
		);

		$injector->load($config);

		$service = $injector->get('AnotherService.DottedChild');
		$this->assertEquals(get_class($service), 'SampleService');

		$service = $injector->get('AnotherService.Subset');
		$this->assertEquals(get_class($service), 'AnotherService');

		$injector->setInjectMapping('TestObject', 'sampleService', 'AnotherService.Geronimo');
		$testObject = $injector->create('TestObject');
		$this->assertEquals(get_class($testObject->sampleService), 'AnotherService');

		$injector->setInjectMapping('TestObject', 'sampleService', 'AnotherService.DottedChild.AnotherDown');
		$testObject = $injector->create('TestObject');
		$this->assertEquals(get_class($testObject->sampleService), 'SampleService');

	}

	public function testInjectUsingConstructor() {
		$injector = new Injector();
		$config = array(array(
				'src' => TEST_SERVICES . '/SampleService.php',
				'constructor' => array(
					'val1',
					'val2',
				)
				));

		$injector->load($config);
		$sample = $injector->get('SampleService');
		$this->assertEquals($sample->constructorVarOne, 'val1');
		$this->assertEquals($sample->constructorVarTwo, 'val2');

		$injector = new Injector();
		$config = array(
			'AnotherService',
			array(
				'src' => TEST_SERVICES . '/SampleService.php',
				'constructor' => array(
					'val1',
					'%$AnotherService',
				)
			)
		);

		$injector->load($config);
		$sample = $injector->get('SampleService');
		$this->assertEquals($sample->constructorVarOne, 'val1');
		$this->assertEquals(get_class($sample->constructorVarTwo), 'AnotherService');

		$injector = new Injector();
		$config = array(array(
				'src' => TEST_SERVICES . '/SampleService.php',
				'constructor' => array(
					'val1',
					'val2',
				)
				));

		$injector->load($config);
		$sample = $injector->get('SampleService');
		$this->assertEquals($sample->constructorVarOne, 'val1');
		$this->assertEquals($sample->constructorVarTwo, 'val2');

		// test constructors on prototype
		$injector = new Injector();
		$config = array(array(
			'type'	=> 'prototype',
			'src' => TEST_SERVICES . '/SampleService.php',
			'constructor' => array(
				'val1',
				'val2',
			)
		));

		$injector->load($config);
		$sample = $injector->get('SampleService');
		$this->assertEquals($sample->constructorVarOne, 'val1');
		$this->assertEquals($sample->constructorVarTwo, 'val2');

		$again = $injector->get('SampleService');
		$this->assertFalse($sample === $again);

		$this->assertEquals($sample->constructorVarOne, 'val1');
		$this->assertEquals($sample->constructorVarTwo, 'val2');
	}

	public function testInjectUsingSetter() {
		$injector = new Injector();
		$injector->setAutoScanProperties(true);
		$config = array(array('src' => TEST_SERVICES . '/SampleService.php',));

		$injector->load($config);
		$this->assertTrue($injector->hasService('SampleService') == 'SampleService');

		$myObject = new OtherTestObject();
		$injector->inject($myObject);

		$this->assertEquals(get_class($myObject->s()), 'SampleService');

		// and again because it goes down a different code path when setting things
		// based on the inject map
		$myObject = new OtherTestObject();
		$injector->inject($myObject);

		$this->assertEquals(get_class($myObject->s()), 'SampleService');
	}

	// make sure we can just get any arbitrary object - it should be created for us
	public function testInstantiateAnObjectViaGet() {
		$injector = new Injector();
		$injector->setAutoScanProperties(true);
		$config = array(array('src' => TEST_SERVICES . '/SampleService.php',));

		$injector->load($config);
		$this->assertTrue($injector->hasService('SampleService') == 'SampleService');

		$myObject = $injector->get('OtherTestObject');
		$this->assertEquals(get_class($myObject->s()), 'SampleService');

		// and again because it goes down a different code path when setting things
		// based on the inject map
		$myObject = $injector->get('OtherTestObject');
		$this->assertEquals(get_class($myObject->s()), 'SampleService');
	}

	public function testCircularReference() {
		$services = array('CircularOne', 'CircularTwo');
		$injector = new Injector($services);
		$injector->setAutoScanProperties(true);

		$obj = $injector->get('NeedsBothCirculars');

		$this->assertTrue($obj->circularOne instanceof CircularOne);
		$this->assertTrue($obj->circularTwo instanceof CircularTwo);
	}

	public function testPrototypeObjects() {
		$services = array('CircularOne', 'CircularTwo', array('class' => 'NeedsBothCirculars', 'type' => 'prototype'));
		$injector = new Injector($services);
		$injector->setAutoScanProperties(true);
		$obj1 = $injector->get('NeedsBothCirculars');
		$obj2 = $injector->get('NeedsBothCirculars');

		// if this was the same object, then $obj1->var would now be two
		$obj1->var = 'one';
		$obj2->var = 'two';

		$this->assertTrue($obj1->circularOne instanceof CircularOne);
		$this->assertTrue($obj1->circularTwo instanceof CircularTwo);

		$this->assertEquals($obj1->circularOne, $obj2->circularOne);
		$this->assertNotEquals($obj1, $obj2);
	}

	public function testSimpleInstantiation() {
		$services = array('CircularOne', 'CircularTwo');
		$injector = new Injector($services);

		// similar to the above, but explicitly instantiating this object here
		$obj1 = $injector->create('NeedsBothCirculars');
		$obj2 = $injector->create('NeedsBothCirculars');

		// if this was the same object, then $obj1->var would now be two
		$obj1->var = 'one';
		$obj2->var = 'two';

		$this->assertEquals($obj1->circularOne, $obj2->circularOne);
		$this->assertNotEquals($obj1, $obj2);
	}

	public function testCreateWithConstructor() {
		$injector = new Injector();
		$obj = $injector->create('CircularTwo', 'param');
		$this->assertEquals($obj->otherVar, 'param');
	}

	public function testSimpleSingleton() {
		$injector = new Injector();

		$one = $injector->create('CircularOne');
		$two = $injector->create('CircularOne');

		$this->assertFalse($one === $two);

		$one = $injector->get('CircularTwo');
		$two = $injector->get('CircularTwo');

		$this->assertTrue($one === $two);
	}

	public function testOverridePriority() {
		$injector = new Injector();
		$injector->setAutoScanProperties(true);
		$config = array(
			array(
				'src' => TEST_SERVICES . '/SampleService.php',
				'priority' => 10,
			)
		);

		// load
		$injector->load($config);

		// inject
		$myObject = new TestObject();
		$injector->inject($myObject);

		$this->assertEquals(get_class($myObject->sampleService), 'SampleService');

		$config = array(
			array(
				'src' => TEST_SERVICES . '/AnotherService.php',
				'id' => 'SampleService',
				'priority' => 1,
			)
		);
		// load
		$injector->load($config);

		$injector->inject($myObject);
		$this->assertEquals('SampleService', get_class($myObject->sampleService));
	}

	/**
	 * Specific test method to illustrate various ways of setting a requirements backend
	 */
	public function testRequirementsSettingOptions() {
		$injector = new Injector();
		$config = array(
			'OriginalRequirementsBackend',
			'NewRequirementsBackend',
			'DummyRequirements' => array(
				'constructor' => array(
					'%$OriginalRequirementsBackend'
				)
			)
		);

		$injector->load($config);

		$requirements = $injector->get('DummyRequirements');
		$this->assertEquals('OriginalRequirementsBackend', get_class($requirements->backend));

		// just overriding the definition here
		$injector->load(array(
			'DummyRequirements' => array(
				'constructor' => array(
					'%$NewRequirementsBackend'
				)
			)
		));

		// requirements should have been reinstantiated with the new bean setting
		$requirements = $injector->get('DummyRequirements');
		$this->assertEquals('NewRequirementsBackend', get_class($requirements->backend));
	}

	/**
	 * disabled for now
	 */
	public function testStaticInjections() {
		$injector = new Injector();
		$config = array(
			'NewRequirementsBackend',
		);

		$injector->load($config);

		$si = $injector->get('TestStaticInjections');
		$this->assertEquals('NewRequirementsBackend', get_class($si->backend));
	}

	public function testSetterInjections() {
		$injector = new Injector();
		$config = array(
			'NewRequirementsBackend',
		);

		$injector->load($config);

		$si = $injector->get('TestSetterInjections');
		$this->assertEquals('NewRequirementsBackend', get_class($si->getBackend()));
	}

	public function testCustomObjectCreator() {
		$injector = new Injector();
		$injector->setObjectCreator(new SSObjectCreator($injector));
		$config = array(
			'OriginalRequirementsBackend',
			'DummyRequirements' => array(
				'class' => 'DummyRequirements(\'%$OriginalRequirementsBackend\')'
			)
		);
		$injector->load($config);

		$requirements = $injector->get('DummyRequirements');
		$this->assertEquals('OriginalRequirementsBackend', get_class($requirements->backend));
	}

	public function testInheritedConfig() {
		
		// Test top-down caching of config inheritance
		$injector = new Injector(array('locator' => 'SilverStripeServiceConfigurationLocator'));
		Config::inst()->update('Injector', 'MyParentClass', array('properties' => array('one' => 'the one')));
		Config::inst()->update('Injector', 'MyChildClass', array('properties' => array('one' => 'the two')));
		$obj = $injector->get('MyParentClass');
		$this->assertEquals($obj->one, 'the one');

		$obj = $injector->get('MyChildClass');
		$this->assertEquals($obj->one, 'the two');
		
		$obj = $injector->get('MyGrandChildClass');
		$this->assertEquals($obj->one, 'the two');
		
		$obj = $injector->get('MyGreatGrandChildClass');
		$this->assertEquals($obj->one, 'the two');
		
		// Test bottom-up caching of config inheritance
		$injector = new Injector(array('locator' => 'SilverStripeServiceConfigurationLocator'));
		Config::inst()->update('Injector', 'MyParentClass', array('properties' => array('one' => 'the three')));
		Config::inst()->update('Injector', 'MyChildClass', array('properties' => array('one' => 'the four')));
		
		$obj = $injector->get('MyGreatGrandChildClass');
		$this->assertEquals($obj->one, 'the four');
		
		$obj = $injector->get('MyGrandChildClass');
		$this->assertEquals($obj->one, 'the four');
		
		$obj = $injector->get('MyChildClass');
		$this->assertEquals($obj->one, 'the four');
		
		$obj = $injector->get('MyParentClass');
		$this->assertEquals($obj->one, 'the three');
	}

	public function testSameNamedSingeltonPrototype() {
		$injector = new Injector();

		// get a singleton object
		$object = $injector->get('NeedsBothCirculars');
		$object->var = 'One';

		$again = $injector->get('NeedsBothCirculars');
		$this->assertEquals($again->var, 'One');

		// create a NEW instance object
		$new = $injector->create('NeedsBothCirculars');
		$this->assertNull($new->var);

		// this will trigger a problem below
		$new->var = 'Two';

		$again = $injector->get('NeedsBothCirculars');
		$this->assertEquals($again->var, 'One');
	}

	public function testConvertServicePropertyOnCreate() {
		// make sure convert service property is not called on direct calls to create, only on configured
		// declarations to avoid un-needed function calls
		$injector = new Injector();
		$item = $injector->create('ConstructableObject', '%$TestObject');
		$this->assertEquals('%$TestObject', $item->property);

		// do it again but have test object configured as a constructor dependency
		$injector = new Injector();
		$config = array(
			'ConstructableObject' => array(
				'constructor' => array(
					'%$TestObject'
				)
			)
		);

		$injector->load($config);
		$item = $injector->get('ConstructableObject');
		$this->assertTrue($item->property instanceof TestObject);

		// and with a configured object defining TestObject to be something else!
		$injector = new Injector(array('locator' => 'InjectorTestConfigLocator'));
		$config = array(
			'ConstructableObject' => array(
				'constructor' => array(
					'%$TestObject'
				)
			),
		);

		$injector->load($config);
		$item = $injector->get('ConstructableObject');
		$this->assertTrue($item->property instanceof ConstructableObject);

		$this->assertInstanceOf('OtherTestObject', $item->property->property);
	}

	public function testNamedServices() {
		$injector = new Injector();
		$service  = new stdClass();

		$injector->registerService($service, 'NamedService');
		$this->assertEquals($service, $injector->get('NamedService'));
	}

	public function testCreateConfiggedObjectWithCustomConstructorArgs() {
		// need to make sure that even if the config defines some constructor params,
		// that we take our passed in constructor args instead
		$injector = new Injector(array('locator' => 'InjectorTestConfigLocator'));

		$item = $injector->create('ConfigConstructor', 'othervalue');
		$this->assertEquals($item->property, 'othervalue');
	}

	/**
	 * Tests creating a service with a custom factory.
	 */
	public function testCustomFactory() {
		$injector = new Injector(array(
			'service' => array('factory' => 'factory', 'constructor' => array(1, 2, 3))
		));

		$factory = $this->getMock('SilverStripe\\Framework\\Injector\\Factory');
		$factory
			->expects($this->once())
			->method('create')
			->with($this->equalTo('service'), $this->equalTo(array(1, 2, 3)))
			->will($this->returnCallback(function($args) {
				return new TestObject();
			}));

		$injector->registerService($factory, 'factory');

		$this->assertInstanceOf('TestObject', $injector->get('service'));
	}

	/**
	 * Test nesting of injector
	 */
	public function testNest() {

		// Outer nest to avoid interference with other
		Injector::nest();
		$this->nestingLevel++;

		// Test services
		$config = array(
			'NewRequirementsBackend',
		);
		Injector::inst()->load($config);
		$si = Injector::inst()->get('TestStaticInjections');
		$this->assertInstanceOf('TestStaticInjections', $si);
		$this->assertInstanceOf('NewRequirementsBackend', $si->backend);
		$this->assertInstanceOf('MyParentClass', Injector::inst()->get('MyParentClass'));
		$this->assertInstanceOf('MyChildClass', Injector::inst()->get('MyChildClass'));

		// Test that nested injector values can be overridden
		Injector::nest();
		$this->nestingLevel++;
		Injector::inst()->unregisterAllObjects();
		$newsi = Injector::inst()->get('TestStaticInjections');
		$newsi->backend = new OriginalRequirementsBackend();
		Injector::inst()->registerService($newsi, 'TestStaticInjections');
		Injector::inst()->registerService(new MyChildClass(), 'MyParentClass');

		// Check that these overridden values are retrievable
		$si = Injector::inst()->get('TestStaticInjections');
		$this->assertInstanceOf('TestStaticInjections', $si);
		$this->assertInstanceOf('OriginalRequirementsBackend', $si->backend);
		$this->assertInstanceOf('MyParentClass', Injector::inst()->get('MyParentClass'));
		$this->assertInstanceOf('MyParentClass', Injector::inst()->get('MyChildClass'));

		// Test that unnesting restores expected behaviour
		Injector::unnest();
		$this->nestingLevel--;
		$si = Injector::inst()->get('TestStaticInjections');
		$this->assertInstanceOf('TestStaticInjections', $si);
		$this->assertInstanceOf('NewRequirementsBackend', $si->backend);
		$this->assertInstanceOf('MyParentClass', Injector::inst()->get('MyParentClass'));
		$this->assertInstanceOf('MyChildClass', Injector::inst()->get('MyChildClass'));

		// Test reset of cache
		Injector::inst()->unregisterAllObjects();
		$si = Injector::inst()->get('TestStaticInjections');
		$this->assertInstanceOf('TestStaticInjections', $si);
		$this->assertInstanceOf('NewRequirementsBackend', $si->backend);
		$this->assertInstanceOf('MyParentClass', Injector::inst()->get('MyParentClass'));
		$this->assertInstanceOf('MyChildClass', Injector::inst()->get('MyChildClass'));

		// Return to nestingLevel 0
		Injector::unnest();
		$this->nestingLevel--;
	}

}

class InjectorTestConfigLocator extends SilverStripeServiceConfigurationLocator implements TestOnly {
	
	protected function configFor($name) {
		
		switch($name) {
			case 'TestObject':
				return $this->configs[$name] = array(
					'class' => 'ConstructableObject',
					'constructor' => array('%$OtherTestObject')
				);
				
			case 'ConfigConstructor':
				return $this->configs[$name] = array(
					'class' => 'ConstructableObject',
					'constructor' => array('value')
				);
		}

		return parent::configFor($name);
	}
}

class ConstructableObject implements TestOnly {
	public $property;

	public function __construct($prop) {
		$this->property = $prop;
	}
}

class TestObject implements TestOnly {

	public $sampleService;

	public function setSomething($v) {
		$this->sampleService = $v;
	}

}

class OtherTestObject implements TestOnly {

	private $sampleService;

	public function setSampleService($s) {
		$this->sampleService = $s;
	}

	public function s() {
		return $this->sampleService;
	}

}

class CircularOne implements TestOnly {

	public $circularTwo;

}

class CircularTwo implements TestOnly {

	public $circularOne;

	public $otherVar;

	public function __construct($value = null) {
		$this->otherVar = $value;
	}
}

class NeedsBothCirculars implements TestOnly{

	public $circularOne;
	public $circularTwo;
	public $var;

}

class MyParentClass implements TestOnly {
	public $one;
}

class MyChildClass extends MyParentClass implements TestOnly {

}
class MyGrandChildClass extends MyChildClass implements TestOnly {
	
}
class MyGreatGrandChildClass extends MyGrandChildClass implements TestOnly {
	
}

class DummyRequirements implements TestOnly {

	public $backend;

	public function __construct($backend) {
		$this->backend = $backend;
	}

	public function setBackend($backend) {
		$this->backend = $backend;
	}

}

class OriginalRequirementsBackend implements TestOnly {

}

class NewRequirementsBackend implements TestOnly {

}

class TestStaticInjections implements TestOnly {

	public $backend;
	/** @config */
	private static $dependencies = array(
		'backend' => '%$NewRequirementsBackend'
	);

}

/**
 * Make sure DI works with ViewableData's implementation of __isset
 */
class TestSetterInjections extends ViewableData implements TestOnly {

	protected $backend;

	/** @config */
	private static $dependencies = array(
		'backend' => '%$NewRequirementsBackend'
	);

	public function getBackend() {
		return $this->backend;
	}

	public function setBackend($backend) {
		$this->backend = $backend;
	}

}

/**
 * An example object creator that uses the SilverStripe class(arguments) mechanism for
 * creating new objects
 *
 * @see https://github.com/silverstripe/sapphire
 */
class SSObjectCreator extends InjectionCreator {
	private $injector;

	public function __construct($injector) {
		$this->injector = $injector;
	}

	public function create($class, array $params = array()) {
		if (strpos($class, '(') === false) {
			return parent::create($class, $params);
		} else {
			list($class, $params) = Object::parse_class_spec($class);
			$params = $this->injector->convertServiceProperty($params);
			return parent::create($class, $params);
		}
	}
}
