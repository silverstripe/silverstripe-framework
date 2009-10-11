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
	
	public function testCastingProperties() {
		$caster = new ViewableData_CastingProperties();
		$caster->buildCastingCache($cache);
		
		$this->assertTrue(isset($cache['Foo']));
		$this->assertEquals('Bar', $cache['Foo']['className']);
		$this->assertEquals('Bar', $caster->castingClass('Foo'));
	}
	
	public function testCastingXMLVal() {
		$caster = new ViewableDataTest_Castable();
		
		$this->assertEquals('casted', $caster->XML_val('alwaysCasted'));
		$this->assertEquals('noCastingInformation', $caster->XML_val('noCastingInformation'));
		
		// test automatic escaping is only applied by casted classes
		$this->assertEquals('<foo>', $caster->XML_val('unsafeXML'));
		$this->assertEquals('&lt;foo&gt;', $caster->XML_val('castedUnsafeXML'));
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

class ViewableData_CastingProperties extends ViewableData {
	
	public static $casting_properties = array (
		'test'
	);
	
	public static $test = array (
		'Foo' => 'Bar'
	);
	
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