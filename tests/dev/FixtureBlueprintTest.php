<?php
/**
 * @package framework
 * @subpackage tests
 */
class FixtureBlueprintTest extends SapphireTest {

	protected $usesDatabase = true;

	protected $extraDataObjects = array(
		'FixtureFactoryTest_DataObject',
		'FixtureFactoryTest_DataObjectRelation',
		'FixtureBlueprintTest_SiteTree',
		'FixtureBlueprintTest_Page'
	);

	public function testCreateWithRelationshipExtraFields() {
		$blueprint = new FixtureBlueprint('FixtureFactoryTest_DataObject');

		$relation1 = new FixtureFactoryTest_DataObjectRelation();
		$relation1->write();
		$relation2 = new FixtureFactoryTest_DataObjectRelation();
		$relation2->write();

		// in YAML these look like
		// RelationName:
		//   - =>Relational.obj:
		//     ExtraFieldName: test
		//   - =>..
		$obj = $blueprint->createObject(
			'one',
			array(
				'ManyManyRelation' =>
					array(
						array(
							"=>FixtureFactoryTest_DataObjectRelation.relation1" => array(),
							"Label" => 'This is a label for relation 1'
						),
						array(
							"=>FixtureFactoryTest_DataObjectRelation.relation2" => array(),
							"Label" => 'This is a label for relation 2'
						)
					)
			),
			array(
				'FixtureFactoryTest_DataObjectRelation' => array(
					'relation1' => $relation1->ID,
					'relation2' => $relation2->ID
				)
			)
		);

		$this->assertEquals(2, $obj->ManyManyRelation()->Count());
		$this->assertNotNull($obj->ManyManyRelation()->find('ID', $relation1->ID));
		$this->assertNotNull($obj->ManyManyRelation()->find('ID', $relation2->ID));

		$this->assertEquals(
			array('Label' => 'This is a label for relation 1'),
			$obj->ManyManyRelation()->getExtraData('ManyManyRelation', $relation1->ID)
		);

		$this->assertEquals(
			array('Label' => 'This is a label for relation 2'),
			$obj->ManyManyRelation()->getExtraData('ManyManyRelation', $relation2->ID)
		);
	}


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
				'ManyManyRelation' =>
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

		$this->assertEquals(2, $obj->ManyManyRelation()->Count());
		$this->assertNotNull($obj->ManyManyRelation()->find('ID', $relation1->ID));
		$this->assertNotNull($obj->ManyManyRelation()->find('ID', $relation2->ID));
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
				'ManyManyRelation' => '=>UnknownClass.relation1'
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
				'ManyManyRelation' => '=>FixtureFactoryTest_DataObjectRelation.unknown_identifier'
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
				'ManyManyRelation' => 'FixtureFactoryTest_DataObjectRelation.relation1'
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

	public function testCreateWithLastEdited() {
		$extpectedDate = '2010-12-14 16:18:20';
		$blueprint = new FixtureBlueprint('FixtureFactoryTest_DataObject');
		$obj = $blueprint->createObject('lastedited', array('LastEdited' => $extpectedDate));
		$this->assertNotNull($obj);
		$this->assertEquals($extpectedDate, $obj->LastEdited);

		$obj = FixtureFactoryTest_DataObject::get()->byID($obj->ID);
		$this->assertEquals($extpectedDate, $obj->LastEdited);
	}

	public function testCreateWithClassAncestry() {
		$data = array(
			'Title' => 'My Title',
			'Created' => '2010-12-14 16:18:20',
			'LastEdited' => '2010-12-14 16:18:20',
			'PublishDate' => '2015-12-09 06:03:00'
		);
		$blueprint = new FixtureBlueprint('FixtureBlueprintTest_Article');
		$obj = $blueprint->createObject('home', $data);
		$this->assertNotNull($obj);
		$this->assertEquals($data['Title'], $obj->Title);
		$this->assertEquals($data['Created'], $obj->Created);
		$this->assertEquals($data['LastEdited'], $obj->LastEdited);
		$this->assertEquals($data['PublishDate'], $obj->PublishDate);

		$obj = FixtureBlueprintTest_Article::get()->byID($obj->ID);
		$this->assertNotNull($obj);
		$this->assertEquals($data['Title'], $obj->Title);
		$this->assertEquals($data['Created'], $obj->Created);
		$this->assertEquals($data['LastEdited'], $obj->LastEdited);
		$this->assertEquals($data['PublishDate'], $obj->PublishDate);
	}

	public function testCallbackOnBeforeCreate() {
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

	public function testCallbackOnAfterCreate() {
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

	public function testDefineWithDefaultCustomSetters() {
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

/**
 * @package framework
 * @subpackage tests
 */
class FixtureBlueprintTest_SiteTree extends DataObject implements TestOnly {

	private static $db = array(
		"Title" => "Varchar"
	);
}

/**
 * @package framework
 * @subpackage tests
 */
class FixtureBlueprintTest_Page extends FixtureBlueprintTest_SiteTree {

	private static $db = array(
		'PublishDate' => 'SS_DateTime'
	);
}

/**
 * @package framework
 * @subpackage tests
 */
class FixtureBlueprintTest_Article extends FixtureBlueprintTest_Page {
}
