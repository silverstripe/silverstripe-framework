<?php

/**
 * @package framework
 * @subpackage tests
 */
class FixtureFactoryTest extends SapphireTest {

	protected $usesDatabase = true;

	protected $extraDataObjects = array(
		'FixtureFactoryTest_DataObject',
		'FixtureFactoryTest_DataObjectRelation'
	);

	public function testCreateRaw() {
		$factory = new FixtureFactory();
		$id = $factory->createRaw('FixtureFactoryTest_DataObject', 'one', array('Name' => 'My Name'));
		$this->assertNotNull($id);
		$this->assertGreaterThan(0, $id);
		$obj = FixtureFactoryTest_DataObject::get()->find('ID', $id);
		$this->assertNotNull($obj);
		$this->assertEquals('My Name', $obj->Name);
	}

	public function testSetId() {
		$factory = new FixtureFactory();
		$obj = new FixtureFactoryTest_DataObject();
		$obj->write();
		$factory->setId('FixtureFactoryTest_DataObject', 'one', $obj->ID);
		$this->assertEquals(
			$obj->ID,
			$factory->getId('FixtureFactoryTest_DataObject', 'one')
		);
	}

	public function testGetId() {
		$factory = new FixtureFactory();
		$obj = $factory->createObject('FixtureFactoryTest_DataObject', 'one');
		$this->assertEquals(
			$obj->ID,
			$factory->getId('FixtureFactoryTest_DataObject', 'one')
		);
	}

	public function testGetIds() {
		$factory = new FixtureFactory();
		$obj = $factory->createObject('FixtureFactoryTest_DataObject', 'one');
		$this->assertEquals(
			array('one' => $obj->ID),
			$factory->getIds('FixtureFactoryTest_DataObject')
		);
	}

	public function testDefine() {
		$factory = new FixtureFactory();
		$this->assertFalse($factory->getBlueprint('FixtureFactoryTest_DataObject'));
		$factory->define('FixtureFactoryTest_DataObject');
		$this->assertInstanceOf(
			'FixtureBluePrint',
			$factory->getBlueprint('FixtureFactoryTest_DataObject')
		);
	}

	public function testDefineWithCustomBlueprint() {
		$blueprint = new FixtureBlueprint('FixtureFactoryTest_DataObject');
		$factory = new FixtureFactory();
		$this->assertFalse($factory->getBlueprint('FixtureFactoryTest_DataObject'));
		$factory->define('FixtureFactoryTest_DataObject', $blueprint);
		$this->assertInstanceOf(
			'FixtureBluePrint',
			$factory->getBlueprint('FixtureFactoryTest_DataObject')
		);
		$this->assertEquals(
			$blueprint,
			$factory->getBlueprint('FixtureFactoryTest_DataObject')
		);
	}

	public function testDefineWithDefaults() {
		$factory = new FixtureFactory();
		$factory->define('FixtureFactoryTest_DataObject', array('Name' => 'Default'));
		$obj = $factory->createObject('FixtureFactoryTest_DataObject', 'one');
		$this->assertEquals('Default', $obj->Name);
	}

	public function testDefineMultipleBlueprintsForClass() {
		$factory = new FixtureFactory();
		$factory->define(
			'FixtureFactoryTest_DataObject',
			new FixtureBlueprint('FixtureFactoryTest_DataObject')
		);
		$factory->define(
			'FixtureFactoryTest_DataObjectWithDefaults',
			new FixtureBlueprint(
				'FixtureFactoryTest_DataObjectWithDefaults',
				'FixtureFactoryTest_DataObject',
				array('Name' => 'Default')
			)
		);

		$obj = $factory->createObject('FixtureFactoryTest_DataObject', 'one');
		$this->assertNull($obj->Name);

		$objWithDefaults = $factory->createObject('FixtureFactoryTest_DataObjectWithDefaults', 'two');
		$this->assertEquals('Default', $objWithDefaults->Name);

		$this->assertEquals(
			$obj->ID,
			$factory->getId('FixtureFactoryTest_DataObject', 'one')
		);
		$this->assertEquals(
			$objWithDefaults->ID,
			$factory->getId('FixtureFactoryTest_DataObject', 'two'),
			'Can access fixtures under class name, not blueprint name'
		);
	}

	public function testClear() {
		$factory = new FixtureFactory();
		$obj1Id = $factory->createRaw('FixtureFactoryTest_DataObject', 'one', array('Name' => 'My Name'));
		$obj2 = $factory->createObject('FixtureFactoryTest_DataObject', 'two');

		$factory->clear();

		$this->assertFalse($factory->getId('FixtureFactoryTest_DataObject', 'one'));
		$this->assertNull(FixtureFactoryTest_DataObject::get()->byId($obj1Id));
		$this->assertFalse($factory->getId('FixtureFactoryTest_DataObject', 'two'));
		$this->assertNull(FixtureFactoryTest_DataObject::get()->byId($obj2->ID));
	}

	public function testClearWithClass() {
		$factory = new FixtureFactory();
		$obj1 = $factory->createObject('FixtureFactoryTest_DataObject', 'object-one');
		$relation1 = $factory->createObject('FixtureFactoryTest_DataObjectRelation', 'relation-one');

		$factory->clear('FixtureFactoryTest_DataObject');

		$this->assertFalse(
			$factory->getId('FixtureFactoryTest_DataObject', 'one')
		);
		$this->assertNull(FixtureFactoryTest_DataObject::get()->byId($obj1->ID));
		$this->assertEquals(
			$relation1->ID,
			$factory->getId('FixtureFactoryTest_DataObjectRelation', 'relation-one')
		);
		$this->assertInstanceOf(
			'FixtureFactoryTest_DataObjectRelation',
			FixtureFactoryTest_DataObjectRelation::get()->byId($relation1->ID)
		);
	}

}

/**
 * @package framework
 * @subpackage tests
 */
class FixtureFactoryTest_DataObject extends DataObject implements TestOnly {

	private static $db = array(
		"Name" => "Varchar"
	);

	private static $many_many = array(
		"ManyManyRelation" => "FixtureFactoryTest_DataObjectRelation"
	);

	private static $many_many_extraFields = array(
		"ManyManyRelation" => array(
			"Label" => "Varchar"
		)
	);
}

/**
 * @package framework
 * @subpackage tests
 */
class FixtureFactoryTest_DataObjectRelation extends DataObject implements TestOnly {

	private static $db = array(
		"Name" => "Varchar"
	);

	private static $belongs_many_many = array(
		"TestParent" => "FixtureFactoryTest_DataObject"
	);
}
