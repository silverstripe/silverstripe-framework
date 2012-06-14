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
	
	public function testCorrectlyInitialised() {
		$injector = Injector::inst();
		$this->assertTrue($injector->getConfigLocator() instanceof SilverStripeServiceConfigurationLocator,
				'If this fails, it is likely because the injector has been referenced BEFORE being initialised in Core.php');
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
		$config = array('SampleService' => array('src' => TEST_SERVICES . '/AnotherService.php')); // , 'id' => 'SampleService'));
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

	public function testCustomObjectCreator() {
		$injector = new Injector();
		$injector->setObjectCreator(new SSObjectCreator());
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
		$injector = new Injector(array('locator' => 'SilverStripeServiceConfigurationLocator'));
		Config::inst()->update('Injector', 'MyParentClass', array('properties' => array('one' => 'the one')));
		$obj = $injector->get('MyParentClass');
		$this->assertEquals($obj->one, 'the one');
		
		$obj = $injector->get('MyChildClass');
		$this->assertEquals($obj->one, 'the one');
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
}

class TestObject {

	public $sampleService;

	public function setSomething($v) {
		$this->sampleService = $v;
	}

}

class OtherTestObject {

	private $sampleService;

	public function setSampleService($s) {
		$this->sampleService = $s;
	}

	public function s() {
		return $this->sampleService;
	}

}

class CircularOne {

	public $circularTwo;

}

class CircularTwo {

	public $circularOne;

	public $otherVar;
	
	public function __construct($value = null) {
		$this->otherVar = $value;
	}
}

class NeedsBothCirculars {

	public $circularOne;
	public $circularTwo;
	public $var;

}

class MyParentClass {
	public $one;
}

class MyChildClass extends MyParentClass {
	
}

class DummyRequirements {

	public $backend;

	public function __construct($backend) {
		$this->backend = $backend;
	}

	public function setBackend($backend) {
		$this->backend = $backend;
	}

}

class OriginalRequirementsBackend {

}

class NewRequirementsBackend {

}

class TestStaticInjections {

	public $backend;
	static $dependencies = array(
		'backend' => '%$NewRequirementsBackend'
	);

}

/**
 * An example object creator that uses the SilverStripe class(arguments) mechanism for
 * creating new objects
 *
 * @see https://github.com/silverstripe/sapphire
 */
class SSObjectCreator extends InjectionCreator {

	public function create(Injector $injector, $class, $params = array()) {
		if (strpos($class, '(') === false) {
			return parent::create($injector, $class, $params);
		} else {
			list($class, $params) = self::parse_class_spec($class);
			return parent::create($injector, $class, $params);
		}
	}

	/**
	 * Parses a class-spec, such as "Versioned('Stage','Live')", as passed to create_from_string().
	 * Returns a 2-elemnent array, with classname and arguments
	 */
	static function parse_class_spec($classSpec) {
		$tokens = token_get_all("<?php $classSpec");
		$class = null;
		$args = array();
		$passedBracket = false;
		
		// Keep track of the current bucket that we're putting data into
		$bucket = &$args;
		$bucketStack = array();
		
		foreach($tokens as $token) {
			$tName = is_array($token) ? $token[0] : $token;
			// Get the class naem
			if($class == null && is_array($token) && $token[0] == T_STRING) {
				$class = $token[1];
			// Get arguments
			} else if(is_array($token)) {
				switch($token[0]) {
				case T_CONSTANT_ENCAPSED_STRING:
					$argString = $token[1];
					switch($argString[0]) {
						case '"': $argString = stripcslashes(substr($argString,1,-1)); break;
						case "'": $argString = str_replace(array("\\\\", "\\'"),array("\\", "'"), substr($argString,1,-1)); break;
						default: throw new Exception("Bad T_CONSTANT_ENCAPSED_STRING arg $argString");
					}
					$bucket[] = $argString;
					break;
			
				case T_DNUMBER:
					$bucket[] = (double)$token[1];
					break;

				case T_LNUMBER:
					$bucket[] = (int)$token[1];
					break;
			
				case T_STRING:
					switch($token[1]) {
						case 'true': $args[] = true; break;
						case 'false': $args[] = false; break;
						default: throw new Exception("Bad T_STRING arg '{$token[1]}'");
					}
				
				case T_ARRAY:
					// Add an empty array to the bucket
					$bucket[] = array();
					$bucketStack[] = &$bucket;
					$bucket = &$bucket[sizeof($bucket)-1];

				}

			} else {
				if($tName == ')') {
					// Pop-by-reference
					$bucket = &$bucketStack[sizeof($bucketStack)-1];
					array_pop($bucketStack);
				}
			}
		}
	
		return array($class, $args);
	}
}
