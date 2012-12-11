<?php
/**
 * @package framework
 * @subpackage tests
 */
class FixtureBlueprintTest extends SapphireTest {

	protected $usesDatabase = true;

	protected $extraDataObjects = array(
		'FixtureFactoryTest_DataObject',
		'FixtureFactoryTest_DataObjectRelation'
	);

	public function testCreateWithoutData() {
		$blueprint = new FixtureBlueprint('FixtureFactoryTest_DataObject');
		$obj = $blueprint->createObject('one');
		$this->assertNotNull($obj);
		$this->assertGreaterThan(0, $obj->ID);
		$this->assertEquals('', $obj->Name);
	}

	public function testCreateWithData() {
		$blueprint = new FixtureBlueprint('FixtureFactoryTest_DataObject');
		$obj = $blueprint->createObject('one', array('Name' => 'My Name'));
		$this->assertNotNull($obj);
		$this->assertGreaterThan(0, $obj->ID);
		$this->assertEquals('My Name', $obj->Name);
	}

	public function testCreateWithRelationship() {
		$blueprint = new FixtureBlueprint('FixtureFactoryTest_DataObject');

		$relation1 = new FixtureFactoryTest_DataObjectRelation();
		$relation1->write();
		$relation2 = new FixtureFactoryTest_DataObjectRelation();
		$relation2->write();

		$obj = $blueprint->createObject(
			'one',
			array(
				'ManyMany' => 
					'=>FixtureFactoryTest_DataObjectRelation.relation1,' .
					'=>FixtureFactoryTest_DataObjectRelation.relation2'
			),
			array(
				'FixtureFactoryTest_DataObjectRelation' => array(
					'relation1' => $relation1->ID,
					'relation2' => $relation2->ID
				)
			)
		);

		$this->assertEquals(2, $obj->ManyMany()->Count());
		$this->assertNotNull($obj->ManyMany()->find('ID', $relation1->ID));
		$this->assertNotNull($obj->ManyMany()->find('ID', $relation2->ID));
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage No fixture definitions found
	 */
	public function testCreateWithInvalidRelationName() {
		$blueprint = new FixtureBlueprint('FixtureFactoryTest_DataObject');

		$obj = $blueprint->createObject(
			'one',
			array(
				'ManyMany' => '=>UnknownClass.relation1'
			),
			array(
				'FixtureFactoryTest_DataObjectRelation' => array(
					'relation1' => 99
				)
			)
		);
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage No fixture definitions found
	 */
	public function testCreateWithInvalidRelationIdentifier() {
		$blueprint = new FixtureBlueprint('FixtureFactoryTest_DataObject');

		$obj = $blueprint->createObject(
			'one',
			array(
				'ManyMany' => '=>FixtureFactoryTest_DataObjectRelation.unknown_identifier'
			),
			array(
				'FixtureFactoryTest_DataObjectRelation' => array(
					'relation1' => 99
				)
			)
		);
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Invalid format
	 */
	public function testCreateWithInvalidRelationFormat() {
		$factory = new FixtureFactory();
		$blueprint = new FixtureBlueprint('FixtureFactoryTest_DataObject');

		$relation1 = new FixtureFactoryTest_DataObjectRelation();
		$relation1->write();

		$obj = $blueprint->createObject(
			'one',
			array(
				'ManyMany' => 'FixtureFactoryTest_DataObjectRelation.relation1'
			),
			array(
				'FixtureFactoryTest_DataObjectRelation' => array(
					'relation1' => $relation1->ID
				)
			)
		);
	}

	public function testCreateWithId() {
		$blueprint = new FixtureBlueprint('FixtureFactoryTest_DataObject');
		$obj = $blueprint->createObject('ninetynine', array('ID' => 99));
		$this->assertNotNull($obj);
		$this->assertEquals(99, $obj->ID);
	}

	function testCallbackOnBeforeCreate() {
		$blueprint = new FixtureBlueprint('FixtureFactoryTest_DataObject');
		$this->_called = 0;
		$self = $this;
		$cb = function($identifier, $data, $fixtures) use($self) {
			$self->_called = $self->_called + 1;
		};
		$blueprint->addCallback('beforeCreate', $cb);
		$this->assertEquals(0, $this->_called);
		$obj1 = $blueprint->createObject('one');
		$this->assertEquals(1, $this->_called);
		$obj2 = $blueprint->createObject('two');
		$this->assertEquals(2, $this->_called);

		$this->_called = 0;
	}

	function testCallbackOnAfterCreate() {
		$blueprint = new FixtureBlueprint('FixtureFactoryTest_DataObject');
		$this->_called = 0;
		$self = $this;
		$cb = function($obj, $identifier, $data, $fixtures) use($self) {
			$self->_called = $self->_called + 1;
		};
		$blueprint->addCallback('afterCreate', $cb);
		$this->assertEquals(0, $this->_called);
		$obj1 = $blueprint->createObject('one');
		$this->assertEquals(1, $this->_called);
		$obj2 = $blueprint->createObject('two');
		$this->assertEquals(2, $this->_called);

		$this->_called = 0;
	}

	function testDefineWithDefaultCustomSetters() {
		$blueprint = new FixtureBlueprint(
			'FixtureFactoryTest_DataObject', 
			null,
			array(
			'Name' => function($obj, $data, $fixtures) {
				return 'Default Name';
			}
		));
		
		$obj1 = $blueprint->createObject('one');
		$this->assertEquals('Default Name', $obj1->Name);

		$obj2 = $blueprint->createObject('one', array('Name' => 'Override Name'));
		$this->assertEquals('Override Name', $obj2->Name);
	}
	
}