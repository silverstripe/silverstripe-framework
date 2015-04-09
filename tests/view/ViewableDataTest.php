<?php
/**
 * See {@link SSViewerTest->testCastingHelpers()} for more tests related to casting and ViewableData behaviour,
 * from a template-parsing perspective.
 *
 * @package framework
 * @subpackage tests
 */
class ViewableDataTest extends SapphireTest {

	public function testRequiresCasting() {
		$caster = new ViewableDataTest_Castable();

		$this->assertTrue($caster->obj('alwaysCasted') instanceof ViewableDataTest_RequiresCasting);
		$this->assertTrue($caster->obj('noCastingInformation') instanceof ViewableData_Caster);

		$this->assertTrue($caster->obj('alwaysCasted', null, false) instanceof ViewableDataTest_RequiresCasting);
		$this->assertFalse($caster->obj('noCastingInformation', null, false) instanceof ViewableData_Caster);
	}

	public function testFailoverRequiresCasting() {
		$caster = new ViewableDataTest_Castable();
		$container = new ViewableDataTest_Container($caster);

		$this->assertTrue($container->obj('alwaysCasted') instanceof ViewableDataTest_RequiresCasting);
		$this->assertTrue($caster->obj('alwaysCasted', null, false) instanceof ViewableDataTest_RequiresCasting);

		/* @todo This currently fails, because the default_cast static variable is always taken from the topmost
		 * 	     object, not the failover object the field actually came from. Should we fix this, or declare current
		 *       behaviour as correct?
		 *
		 * $this->assertTrue($container->obj('noCastingInformation') instanceof ViewableData_Caster);
		 * $this->assertFalse($caster->obj('noCastingInformation', null, false) instanceof ViewableData_Caster);
		*/
	}

	public function testCastingXMLVal() {
		$caster = new ViewableDataTest_Castable();

		$this->assertEquals('casted', $caster->XML_val('alwaysCasted'));
		$this->assertEquals('noCastingInformation', $caster->XML_val('noCastingInformation'));

		// test automatic escaping is only applied by casted classes
		$this->assertEquals('<foo>', $caster->XML_val('unsafeXML'));
		$this->assertEquals('&lt;foo&gt;', $caster->XML_val('castedUnsafeXML'));
	}

	public function testUncastedXMLVal() {
		$caster = new ViewableDataTest_Castable();
		$this->assertEquals($caster->XML_val('uncastedZeroValue'), 0);
	}

	public function testArrayCustomise() {
		$viewableData    = new ViewableDataTest_Castable();
		$newViewableData = $viewableData->customise(array (
			'test'         => 'overwritten',
			'alwaysCasted' => 'overwritten'
		));

		$this->assertEquals('test', $viewableData->XML_val('test'));
		$this->assertEquals('casted', $viewableData->XML_val('alwaysCasted'));

		$this->assertEquals('overwritten', $newViewableData->XML_val('test'));
		$this->assertEquals('overwritten', $newViewableData->XML_val('alwaysCasted'));

		$this->assertEquals('castable', $viewableData->forTemplate());
		$this->assertEquals('castable', $newViewableData->forTemplate());
	}

	public function testObjectCustomise() {
		$viewableData    = new ViewableDataTest_Castable();
		$newViewableData = $viewableData->customise(new ViewableDataTest_RequiresCasting());

		$this->assertEquals('test', $viewableData->XML_val('test'));
		$this->assertEquals('casted', $viewableData->XML_val('alwaysCasted'));

		$this->assertEquals('overwritten', $newViewableData->XML_val('test'));
		$this->assertEquals('casted', $newViewableData->XML_val('alwaysCasted'));

		$this->assertEquals('castable', $viewableData->forTemplate());
		$this->assertEquals('casted', $newViewableData->forTemplate());
	}

	public function testDefaultValueWrapping() {
		$data = new ArrayData(array('Title' => 'SomeTitleValue'));
		// this results in a cached raw string in ViewableData:
		$this->assertTrue($data->hasValue('Title'));
		$this->assertFalse($data->hasValue('SomethingElse'));
		// this should cast the raw string to a StringField since we are
		// passing true as the third argument:
		$obj = $data->obj('Title', null, true);
		$this->assertTrue(is_object($obj));
		// and the string field should have the value of the raw string:
		$this->assertEquals('SomeTitleValue', $obj->forTemplate());
	}

	public function testRAWVal() {
		$data = new ViewableDataTest_Castable();
		$data->test = 'This &amp; This';
		$this->assertEquals($data->RAW_val('test'), 'This & This');
	}

	public function testSQLVal() {
		$data = new ViewableDataTest_Castable();
		$this->assertEquals($data->SQL_val('test'), 'test');
	}

	public function testJSVal() {
		$data = new ViewableDataTest_Castable();
		$data->test = '"this is a test"';
		$this->assertEquals($data->JS_val('test'), '\"this is a test\"');
	}

	public function testATTVal() {
		$data = new ViewableDataTest_Castable();
		$data->test = '"this is a test"';
		$this->assertEquals($data->ATT_val('test'), '&quot;this is a test&quot;');
	}

	public function testCastingClass() {
		$expected = array(
			'NonExistant'   => null,
			'Field'         => 'CastingType',
			'Argument'      => 'ArgumentType',
			'ArrayArgument' => 'ArrayArgumentType'
		);
		$obj = new ViewableDataTest_CastingClass();

		foreach($expected as $field => $class) {
			$this->assertEquals(
				$class,
				$obj->castingClass($field),
				"castingClass() returns correct results for ::\$$field"
			);
		}
	}

	public function testObjWithCachedStringValueReturnsValidObject() {
		$obj = new ViewableDataTest_NoCastingInformation();

		// Save a literal string into cache
		$cache = true;
		$uncastedData = $obj->obj('noCastingInformation', null, false, $cache);

		// Fetch the cached string as an object
		$forceReturnedObject = true;
		$castedData = $obj->obj('noCastingInformation', null, $forceReturnedObject);

		// Uncasted data should always be the nonempty string
		$this->assertNotEmpty($uncastedData, 'Uncasted data was empty.');
		$this->assertTrue(is_string($uncastedData), 'Uncasted data should be a string.');

		// Casted data should be the string wrapped in a DBField-object.
		$this->assertNotEmpty($castedData, 'Casted data was empty.');
		$this->assertInstanceOf('DBField', $castedData, 'Casted data should be instance of DBField.');

		$this->assertEquals($uncastedData, $castedData->getValue(), 'Casted and uncasted strings are not equal.');
	}

	public function testCaching() {
		$objCached = new ViewableDataTest_Cached();
		$objNotCached = new ViewableDataTest_NotCached();

		$objCached->Test = 'AAA';
		$objNotCached->Test = 'AAA';

		$this->assertEquals('AAA', $objCached->obj('Test', null, true, true));
		$this->assertEquals('AAA', $objNotCached->obj('Test', null, true, true));

		$objCached->Test = 'BBB';
		$objNotCached->Test = 'BBB';

		// Cached data must be always the same
		$this->assertEquals('AAA', $objCached->obj('Test', null, true, true));
		$this->assertEquals('BBB', $objNotCached->obj('Test', null, true, true));
	}

}

/**#@+
 * @ignore
 */
class ViewableDataTest_Castable extends ViewableData {

	private static $default_cast = 'ViewableData_Caster';

	private static $casting = array (
		'alwaysCasted'    => 'ViewableDataTest_RequiresCasting',
		'castedUnsafeXML' => 'ViewableData_UnescaptedCaster'
	);

	public $test = 'test';

	public $uncastedZeroValue = 0;

	public function alwaysCasted() {
		return 'alwaysCasted';
	}

	public function noCastingInformation() {
		return 'noCastingInformation';
	}

	public function unsafeXML() {
		return '<foo>';
	}

	public function castedUnsafeXML() {
		return $this->unsafeXML();
	}

	public function forTemplate() {
		return 'castable';
	}
}

class ViewableDataTest_RequiresCasting extends ViewableData {

	public $test = 'overwritten';

	public function forTemplate() {
		return 'casted';
	}

	public function setValue() {}

}

class ViewableData_UnescaptedCaster extends ViewableData {

	protected $value;

	public function setValue($value) {
		$this->value = $value;
	}

	public function forTemplate() {
		return Convert::raw2xml($this->value);
	}

}

class ViewableData_Caster extends ViewableData {

	public function forTemplate() {
		return 'casted';
	}

	public function setValue() {}

}

class ViewableDataTest_Container extends ViewableData {

	public function __construct($failover) {
		$this->failover = $failover;
		parent::__construct();
	}
}

class ViewableDataTest_CastingClass extends ViewableData {
	private static $casting = array(
		'Field'         => 'CastingType',
		'Argument'      => 'ArgumentType(Argument)',
		'ArrayArgument' => 'ArrayArgumentType(array(foo, bar))'
	);
}

class ViewableDataTest_NoCastingInformation extends ViewableData {
	public function noCastingInformation() {
		return "No casting information";
	}
}

class ViewableDataTest_Cached extends ViewableData {
	public $Test;
}

class ViewableDataTest_NotCached extends ViewableData {
	public $Test;

	protected function objCacheGet($key) {
		// Disable caching
		return null;
	}
}

/**#@-*/
