<?php
/**
 * @package sapphire
 * @subpackage tests
 */

class GridFieldConfigTest extends SapphireTest {

	function testGetComponents() {
		$config = GridFieldConfig::create();
		$this->assertType('ArrayList', $config->getComponents());
		$this->assertEquals($config->getComponents()->Count(), 0);

		$config
			->addComponent($c1 = new GridFieldConfigTest_MyComponent())
			->addComponent($c2 = new GridFieldConfigTest_MyOtherComponent())
			->addComponent($c3 = new GridFieldConfigTest_MyOtherComponent());

		$this->assertEquals(
			new ArrayList(array($c1, $c2, $c3)), 
			$config->getComponents()
		);
	}

	function testGetComponentsByType() {
		$config = GridFieldConfig::create()
			->addComponent($c1 = new GridFieldConfigTest_MyComponent())
			->addComponent($c2 = new GridFieldConfigTest_MyOtherComponent())
			->addComponent($c3 = new GridFieldConfigTest_MyOtherComponent());

		$this->assertEquals(
			new ArrayList(array($c1)), 
			$config->getComponentsByType('GridFieldConfigTest_MyComponent')
		);
		$this->assertEquals(
			new ArrayList(array($c2, $c3)),
			$config->getComponentsByType('GridFieldConfigTest_MyOtherComponent')
		);
		$this->assertEquals(
			new ArrayList(array($c1, $c2, $c3)),
			$config->getComponentsByType('GridField_URLHandler')
		);
		$this->assertEquals(
			new ArrayList(),
			$config->getComponentsByType('GridFieldConfigTest_UnknownComponent')
		);
	}	

	function testGetComponentByType() {
		$config = GridFieldConfig::create()
			->addComponent($c1 = new GridFieldConfigTest_MyComponent())
			->addComponent($c2 = new GridFieldConfigTest_MyOtherComponent())
			->addComponent($c3 = new GridFieldConfigTest_MyOtherComponent());

		$this->assertEquals(
			$c1, 
			$config->getComponentByType('GridFieldConfigTest_MyComponent')
		);
		$this->assertEquals(
			$c2,
			$config->getComponentByType('GridFieldConfigTest_MyOtherComponent')
		);
		$this->assertNull(
			$config->getComponentByType('GridFieldConfigTest_UnknownComponent')
		);
	}
	
}

class GridFieldConfigTest_MyComponent implements GridField_URLHandler, TestOnly {
	function getURLHandlers($gridField) {return array();}
}

class GridFieldConfigTest_MyOtherComponent implements GridField_URLHandler, TestOnly {
	function getURLHandlers($gridField) {return array();}
}