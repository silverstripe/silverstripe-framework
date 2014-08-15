<?php
/**
 * @package framework
 * @subpackage tests
 */

class GridFieldConfigTest extends SapphireTest {

	public function testGetComponents() {
		$config = GridFieldConfig::create();
		$this->assertInstanceOf('ArrayList', $config->getComponents());
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

	public function testGetComponentsByType() {
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

	public function testGetComponentByType() {
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

	public function testAddComponents() {
		$config = GridFieldConfig::create()
			->addComponents(
				$c1 = new GridFieldConfigTest_MyComponent(),
				$c2 = new GridFieldConfigTest_MyOtherComponent()
			);

		$this->assertEquals(
			$c1,
			$config->getComponentByType('GridFieldConfigTest_MyComponent')
		);
		$this->assertEquals(
			$c2,
			$config->getComponentByType('GridFieldConfigTest_MyOtherComponent')
		);
	}

	public function testRemoveComponents() {
		$config = GridFieldConfig::create()
			->addComponent($c1 = new GridFieldConfigTest_MyComponent())
			->addComponent($c2 = new GridFieldConfigTest_MyComponent())
			->addComponent($c3 = new GridFieldConfigTest_MyOtherComponent())
			->addComponent($c4 = new GridFieldConfigTest_MyOtherComponent());

		$this->assertEquals(
			4,
			$config->getComponents()->count()
		);

		$config->removeComponent($c1);
		$this->assertEquals(
			3,
			$config->getComponents()->count()
		);

		$config->removeComponentsByType("GridFieldConfigTest_MyComponent");
		$this->assertEquals(
			2,
			$config->getComponents()->count()
		);

		$config->removeComponentsByType("GridFieldConfigTest_MyOtherComponent");
		$this->assertEquals(
			0,
			$config->getComponents()->count()
		);
	}

}

class GridFieldConfigTest_MyComponent implements GridField_URLHandler, TestOnly {
	public function getURLHandlers($gridField) {return array();}
}

class GridFieldConfigTest_MyOtherComponent implements GridField_URLHandler, TestOnly {
	public function getURLHandlers($gridField) {return array();}
}
