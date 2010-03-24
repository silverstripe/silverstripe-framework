<?php
/**
 * @package sapphire
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
	}
	
	public function testObjectCustomise() {
		$viewableData    = new ViewableDataTest_Castable();
		$newViewableData = $viewableData->customise(new ViewableDataTest_RequiresCasting());
		
		$this->assertEquals('test', $viewableData->XML_val('test'));
		$this->assertEquals('casted', $viewableData->XML_val('alwaysCasted'));
		
		$this->assertEquals('overwritten', $newViewableData->XML_val('test'));
		$this->assertEquals('casted', $newViewableData->XML_val('alwaysCasted'));
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

}

/**#@+
 * @ignore
 */
class ViewableDataTest_Castable extends ViewableData {
	
	public static $default_cast = 'ViewableData_Caster';
	
	public static $casting = array (
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

/**#@-*/