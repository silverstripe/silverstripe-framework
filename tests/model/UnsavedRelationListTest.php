<?php

class UnsavedRelationListTest extends SapphireTest {
	public static $fixture_file = 'UnsavedRelationListTest.yml';

	protected $extraDataObjects = array('UnsavedRelationListTest_DataObject');

	public function testReturnedList() {
		$object = new UnsavedRelationListTest_DataObject;
		$children = $object->Children();
		$siblings = $object->Siblings();
		$this->assertEquals($children, $object->Children(),
			'Returned UnsavedRelationList should be the same.');
		$this->assertEquals($siblings, $object->Siblings(),
			'Returned UnsavedRelationList should be the same.');

		$object->write();
		$this->assertInstanceOf('RelationList', $object->Children());
		$this->assertNotEquals($children, $object->Children(),
			'Return should be a RelationList after first write');
		$this->assertInstanceOf('RelationList', $object->Siblings());
		$this->assertNotEquals($siblings, $object->Siblings(),
			'Return should be a RelationList after first write');
	}

	public function testHasManyExisting() {
		$object = new UnsavedRelationListTest_DataObject;

		$children = $object->Children();
		$children->add($this->objFromFixture('UnsavedRelationListTest_DataObject', 'ObjectA'));
		$children->add($this->objFromFixture('UnsavedRelationListTest_DataObject', 'ObjectB'));
		$children->add($this->objFromFixture('UnsavedRelationListTest_DataObject', 'ObjectC'));

		$children = $object->Children();

		$this->assertDOSEquals(array(
			array('Name' => 'A'),
			array('Name' => 'B'),
			array('Name' => 'C')
		), $children);

		$object->write();

		$this->assertNotEquals($children, $object->Children());

		$this->assertDOSEquals(array(
			array('Name' => 'A'),
			array('Name' => 'B'),
			array('Name' => 'C')
		), $object->Children());
	}

	public function testManyManyExisting() {
		$object = new UnsavedRelationListTest_DataObject;

		$Siblings = $object->Siblings();
		$Siblings->add($this->objFromFixture('UnsavedRelationListTest_DataObject', 'ObjectA'));
		$Siblings->add($this->objFromFixture('UnsavedRelationListTest_DataObject', 'ObjectB'));
		$Siblings->add($this->objFromFixture('UnsavedRelationListTest_DataObject', 'ObjectC'));

		$siblings = $object->Siblings();

		$this->assertDOSEquals(array(
			array('Name' => 'A'),
			array('Name' => 'B'),
			array('Name' => 'C')
		), $siblings);

		$object->write();

		$this->assertNotEquals($siblings, $object->Siblings());

		$this->assertDOSEquals(array(
			array('Name' => 'A'),
			array('Name' => 'B'),
			array('Name' => 'C')
		), $object->Siblings());
	}

	public function testHasManyNew() {
		$object = new UnsavedRelationListTest_DataObject;

		$children = $object->Children();
		$children->add(new UnsavedRelationListTest_DataObject(array('Name' => 'A')));
		$children->add(new UnsavedRelationListTest_DataObject(array('Name' => 'B')));
		$children->add(new UnsavedRelationListTest_DataObject(array('Name' => 'C')));

		$children = $object->Children();

		$this->assertDOSEquals(array(
			array('Name' => 'A'),
			array('Name' => 'B'),
			array('Name' => 'C')
		), $children);

		$object->write();

		$this->assertNotEquals($children, $object->Children());

		$this->assertDOSEquals(array(
			array('Name' => 'A'),
			array('Name' => 'B'),
			array('Name' => 'C')
		), $object->Children());
	}

	public function testManyManyNew() {
		$object = new UnsavedRelationListTest_DataObject;

		$Siblings = $object->Siblings();
		$Siblings->add(new UnsavedRelationListTest_DataObject(array('Name' => 'A')));
		$Siblings->add(new UnsavedRelationListTest_DataObject(array('Name' => 'B')));
		$Siblings->add(new UnsavedRelationListTest_DataObject(array('Name' => 'C')));

		$siblings = $object->Siblings();

		$this->assertDOSEquals(array(
			array('Name' => 'A'),
			array('Name' => 'B'),
			array('Name' => 'C')
		), $siblings);

		$object->write();

		$this->assertNotEquals($siblings, $object->Siblings());

		$this->assertDOSEquals(array(
			array('Name' => 'A'),
			array('Name' => 'B'),
			array('Name' => 'C')
		), $object->Siblings());
	}

	public function testManyManyExtraFields() {
		$object = new UnsavedRelationListTest_DataObject;

		$Siblings = $object->Siblings();
		$Siblings->add(new UnsavedRelationListTest_DataObject(array('Name' => 'A')), array('Number' => 1));
		$Siblings->add(new UnsavedRelationListTest_DataObject(array('Name' => 'B')), array('Number' => 2));
		$Siblings->add(new UnsavedRelationListTest_DataObject(array('Name' => 'C')), array('Number' => 3));

		$siblings = $object->Siblings();

		$this->assertDOSEquals(array(
			array('Name' => 'A', 'Number' => 1),
			array('Name' => 'B', 'Number' => 2),
			array('Name' => 'C', 'Number' => 3)
		), $siblings);

		$object->write();

		$this->assertNotEquals($siblings, $object->Siblings());

		$this->assertDOSEquals(array(
			array('Name' => 'A', 'Number' => 1),
			array('Name' => 'B', 'Number' => 2),
			array('Name' => 'C', 'Number' => 3)
		), $object->Siblings());
	}
}

class UnsavedRelationListTest_DataObject extends DataObject implements TestOnly {
	public static $db = array(
		'Name' => 'Varchar',
	);

	public static $has_one = array(
		'Parent' => 'UnsavedRelationListTest_DataObject',
	);

	public static $has_many = array(
		'Children' => 'UnsavedRelationListTest_DataObject',
	);

	public static $many_many = array(
		'Siblings' => 'UnsavedRelationListTest_DataObject',
	);

	public static $many_many_extraFields = array(
		'Siblings' => array(
			'Number' => 'Int',
		),
	);
}
