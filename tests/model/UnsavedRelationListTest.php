<?php

class UnsavedRelationListTest extends SapphireTest {
	protected static $fixture_file = 'UnsavedRelationListTest.yml';

	protected $extraDataObjects = ['UnsavedRelationListTest_DataObject'];

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

		$this->assertDOSEquals([
			['Name' => 'A'],
			['Name' => 'B'],
			['Name' => 'C']
		], $children);

		$object->write();

		$this->assertNotEquals($children, $object->Children());

		$this->assertDOSEquals([
			['Name' => 'A'],
			['Name' => 'B'],
			['Name' => 'C']
		], $object->Children());
	}

	public function testManyManyExisting() {
		$object = new UnsavedRelationListTest_DataObject;

		$Siblings = $object->Siblings();
		$Siblings->add($this->objFromFixture('UnsavedRelationListTest_DataObject', 'ObjectA'));
		$Siblings->add($this->objFromFixture('UnsavedRelationListTest_DataObject', 'ObjectB'));
		$Siblings->add($this->objFromFixture('UnsavedRelationListTest_DataObject', 'ObjectC'));

		$siblings = $object->Siblings();

		$this->assertDOSEquals([
			['Name' => 'A'],
			['Name' => 'B'],
			['Name' => 'C']
		], $siblings);

		$object->write();

		$this->assertNotEquals($siblings, $object->Siblings());

		$this->assertDOSEquals([
			['Name' => 'A'],
			['Name' => 'B'],
			['Name' => 'C']
		], $object->Siblings());
	}

	public function testHasManyNew() {
		$object = new UnsavedRelationListTest_DataObject;

		$children = $object->Children();
		$children->add(new UnsavedRelationListTest_DataObject(['Name' => 'A']));
		$children->add(new UnsavedRelationListTest_DataObject(['Name' => 'B']));
		$children->add(new UnsavedRelationListTest_DataObject(['Name' => 'C']));

		$children = $object->Children();

		$this->assertDOSEquals([
			['Name' => 'A'],
			['Name' => 'B'],
			['Name' => 'C']
		], $children);

		$object->write();

		$this->assertNotEquals($children, $object->Children());

		$this->assertDOSEquals([
			['Name' => 'A'],
			['Name' => 'B'],
			['Name' => 'C']
		], $object->Children());
	}

	public function testHasManyPolymorphic() {
		$object = new UnsavedRelationListTest_DataObject;

		$children = $object->RelatedObjects();
		$children->add(new UnsavedRelationListTest_DataObject(['Name' => 'A']));
		$children->add(new UnsavedRelationListTest_DataObject(['Name' => 'B']));
		$children->add(new UnsavedRelationListTest_DataObject(['Name' => 'C']));

		$children = $object->RelatedObjects();

		$this->assertDOSEquals([
			['Name' => 'A'],
			['Name' => 'B'],
			['Name' => 'C']
		], $children);

		$object->write();

		$this->assertNotEquals($children, $object->RelatedObjects());

		$this->assertDOSEquals([
			['Name' => 'A'],
			['Name' => 'B'],
			['Name' => 'C']
		], $object->RelatedObjects());
	}

	public function testManyManyNew() {
		$object = new UnsavedRelationListTest_DataObject;

		$Siblings = $object->Siblings();
		$Siblings->add(new UnsavedRelationListTest_DataObject(['Name' => 'A']));
		$Siblings->add(new UnsavedRelationListTest_DataObject(['Name' => 'B']));
		$Siblings->add(new UnsavedRelationListTest_DataObject(['Name' => 'C']));

		$siblings = $object->Siblings();

		$this->assertDOSEquals([
			['Name' => 'A'],
			['Name' => 'B'],
			['Name' => 'C']
		], $siblings);

		$object->write();

		$this->assertNotEquals($siblings, $object->Siblings());

		$this->assertDOSEquals([
			['Name' => 'A'],
			['Name' => 'B'],
			['Name' => 'C']
		], $object->Siblings());
	}

	public function testManyManyExtraFields() {
		$object = new UnsavedRelationListTest_DataObject;

		$Siblings = $object->Siblings();
		$Siblings->add(new UnsavedRelationListTest_DataObject(['Name' => 'A']), ['Number' => 1]);
		$Siblings->add(new UnsavedRelationListTest_DataObject(['Name' => 'B']), ['Number' => 2]);
		$Siblings->add(new UnsavedRelationListTest_DataObject(['Name' => 'C']), ['Number' => 3]);

		$siblings = $object->Siblings();

		$this->assertDOSEquals([
			['Name' => 'A', 'Number' => 1],
			['Name' => 'B', 'Number' => 2],
			['Name' => 'C', 'Number' => 3]
		], $siblings);

		$object->write();

		$this->assertNotEquals($siblings, $object->Siblings());

		$this->assertDOSEquals([
			['Name' => 'A', 'Number' => 1],
			['Name' => 'B', 'Number' => 2],
			['Name' => 'C', 'Number' => 3]
		], $object->Siblings());
	}

	public function testGetIDList() {
		$object = new UnsavedRelationListTest_DataObject;

		$children = $object->Children();
		$this->assertEquals($children->getIDList(), []);
		$children->add($child1 = new UnsavedRelationListTest_DataObject(['Name' => 'A']));
		$children->add($child2 = new UnsavedRelationListTest_DataObject(['Name' => 'B']));
		$children->add($child3 = new UnsavedRelationListTest_DataObject(['Name' => 'C']));
		$children->add($child1);

		$this->assertEquals($children->getIDList(), []);

		$child1->write();
		$this->assertEquals($children->getIDList(), [
			$child1->ID => $child1->ID
		]);

		$child2->write();
		$child3->write();
		$this->assertEquals($children->getIDList(), [
			$child1->ID => $child1->ID,
			$child2->ID => $child2->ID,
			$child3->ID => $child3->ID
		]);
	}

	public function testColumn() {
		$object = new UnsavedRelationListTest_DataObject;

		$children = $object->Children();
		$children->add(new UnsavedRelationListTest_DataObject(['Name' => 'A']));
		$children->add(new UnsavedRelationListTest_DataObject(['Name' => 'B']));
		$children->add(new UnsavedRelationListTest_DataObject(['Name' => 'C']));

		$children = $object->Children();

		$this->assertDOSEquals([
			['Name' => 'A'],
			['Name' => 'B'],
			['Name' => 'C']
		], $children);

		$this->assertEquals($children->column('Name'), [
			'A',
			'B',
			'C'
		]);
	}
}

class UnsavedRelationListTest_DataObject extends DataObject implements TestOnly {
	private static $db = [
		'Name' => 'Varchar',
	];

	private static $has_one = [
		'Parent' => 'UnsavedRelationListTest_DataObject',
		'RelatedObject' => 'DataObject'
	];

	private static $has_many = [
		'Children' => 'UnsavedRelationListTest_DataObject.Parent',
		'RelatedObjects' => 'UnsavedRelationListTest_DataObject.RelatedObject'
	];

	private static $many_many = [
		'Siblings' => 'UnsavedRelationListTest_DataObject',
	];

	private static $many_many_extraFields = [
		'Siblings' => [
			'Number' => 'Int',
		],
	];
}
