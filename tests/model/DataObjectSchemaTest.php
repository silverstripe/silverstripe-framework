<?php

use SilverStripe\ORM\DataObject;

/**
 * Tests schema inspection of DataObjects
 */
class DataObjectSchemaTest extends SapphireTest
{
	protected static $fixture_file = 'DataObjectSchemaTest.yml';

	protected $extraDataObjects = array(
		// Classes in base namespace
		'DataObjectSchemaTest_BaseClass',
		'DataObjectSchemaTest_BaseDataClass',
		'DataObjectSchemaTest_ChildClass',
		'DataObjectSchemaTest_GrandChildClass',
		'DataObjectSchemaTest_HasFields',
		'DataObjectSchemaTest_NoFields',
		'DataObjectSchemaTest_WithCustomTable',
		'DataObjectSchemaTest_WithRelation',
		// Classes in sub-namespace (See DataObjectSchemaTest_Namespacejd.php)
		'Namespaced\DOST\MyObject',
		'Namespaced\DOST\MyObject_CustomTable',
		'Namespaced\DOST\MyObject_NestedObject',
		'Namespaced\DOST\MyObject_NamespacedTable',
		'Namespaced\DOST\MyObject_Namespaced_Subclass',
		'Namespaced\DOST\MyObject_NoFields',
	);

	/**
	 * Test table name generation
	 */
	public function testTableName() {
		$schema = DataObject::getSchema();

		// Non-namespaced tables
		$this->assertEquals(
			'DataObjectSchemaTest_WithRelation',
			$schema->tableName('DataObjectSchemaTest_WithRelation')
		);
		$this->assertEquals(
			'DOSTWithCustomTable',
			$schema->tableName('DataObjectSchemaTest_WithCustomTable')
		);

		// Namespaced tables
		$this->assertEquals(
			'Namespaced\DOST\MyObject',
			$schema->tableName('Namespaced\DOST\MyObject')
		);
		$this->assertEquals(
			'CustomNamespacedTable',
			$schema->tableName('Namespaced\DOST\MyObject_CustomTable')
		);
		$this->assertEquals(
			'Namespaced\DOST\MyObject_NestedObject',
			$schema->tableName('Namespaced\DOST\MyObject_NestedObject')
		);
		$this->assertEquals(
			'Custom\NamespacedTable',
			$schema->tableName('Namespaced\DOST\MyObject_NamespacedTable')
		);
		$this->assertEquals(
			'Custom\SubclassedTable',
			$schema->tableName('Namespaced\DOST\MyObject_Namespaced_Subclass')
		);
		$this->assertEquals(
			'Namespaced\DOST\MyObject_NoFields',
			$schema->tableName('Namespaced\DOST\MyObject_NoFields')
		);
	}

	/**
	 * Test that the class name is convertable from the table
	 */
	public function testClassNameForTable() {
		$schema = DataObject::getSchema();

		// Tables that aren't classes
		$this->assertNull($schema->tableClass('NotARealTable'));


		// Non-namespaced tables
		$this->assertEquals(
			'DataObjectSchemaTest_WithRelation',
			$schema->tableClass('DataObjectSchemaTest_WithRelation')
		);
		$this->assertEquals(
			'DataObjectSchemaTest_WithCustomTable',
			$schema->tableClass('DOSTWithCustomTable')
		);

		// Namespaced tables
		$this->assertEquals(
			'Namespaced\DOST\MyObject',
			$schema->tableClass('Namespaced\DOST\MyObject')
		);
		$this->assertEquals(
			'Namespaced\DOST\MyObject_CustomTable',
			$schema->tableClass('CustomNamespacedTable')
		);
		$this->assertEquals(
			'Namespaced\DOST\MyObject_NestedObject',
			$schema->tableClass('Namespaced\DOST\MyObject_NestedObject')
		);
		$this->assertEquals(
			'Namespaced\DOST\MyObject_NamespacedTable',
			$schema->tableClass('Custom\NamespacedTable')
		);
		$this->assertEquals(
			'Namespaced\DOST\MyObject_Namespaced_Subclass',
			$schema->tableClass('Custom\SubclassedTable')
		);
		$this->assertEquals(
			'Namespaced\DOST\MyObject_NoFields',
			$schema->tableClass('Namespaced\DOST\MyObject_NoFields')
		);
	}

	/**
	 * Test non-namespaced tables
	 */
	public function testTableForObjectField() {
		$schema = DataObject::getSchema();
		$this->assertEquals(
			'DataObjectSchemaTest_WithRelation',
			$schema->tableForField('DataObjectSchemaTest_WithRelation', 'RelationID')
		);

		$this->assertEquals(
			'DataObjectSchemaTest_WithRelation',
			$schema->tableForField('DataObjectSchemaTest_withrelation', 'RelationID')
		);

		$this->assertEquals(
			'DataObjectSchemaTest_BaseDataClass',
			$schema->tableForField('DataObjectSchemaTest_BaseDataClass', 'Title')
		);

		$this->assertEquals(
			'DataObjectSchemaTest_BaseDataClass',
			$schema->tableForField('DataObjectSchemaTest_HasFields', 'Title')
		);

		$this->assertEquals(
			'DataObjectSchemaTest_BaseDataClass',
			$schema->tableForField('DataObjectSchemaTest_NoFields', 'Title')
		);

		$this->assertEquals(
			'DataObjectSchemaTest_BaseDataClass',
			$schema->tableForField('DataObjectSchemaTest_nofields', 'Title')
		);

		$this->assertEquals(
			'DataObjectSchemaTest_HasFields',
			$schema->tableForField('DataObjectSchemaTest_HasFields', 'Description')
		);

		// Class and table differ for this model
		$this->assertEquals(
			'DOSTWithCustomTable',
			$schema->tableForField('DataObjectSchemaTest_WithCustomTable', 'Description')
		);
		$this->assertEquals(
			'DataObjectSchemaTest_WithCustomTable',
			$schema->classForField('DataObjectSchemaTest_WithCustomTable', 'Description')
		);
		$this->assertNull(
			$schema->tableForField('DataObjectSchemaTest_WithCustomTable', 'NotAField')
		);
		$this->assertNull(
			$schema->classForField('DataObjectSchemaTest_WithCustomTable', 'NotAField')
		);

		// Non-existant fields shouldn't match any table
		$this->assertNull(
			$schema->tableForField('DataObjectSchemaTest_BaseClass', 'Nonexist')
		);

		$this->assertNull(
			$schema->tableForField('Object', 'Title')
		);

		// Test fixed fields
		$this->assertEquals(
			'DataObjectSchemaTest_BaseDataClass',
			$schema->tableForField('DataObjectSchemaTest_HasFields', 'ID')
		);
		$this->assertEquals(
			'DataObjectSchemaTest_BaseDataClass',
			$schema->tableForField('DataObjectSchemaTest_NoFields', 'Created')
		);
	}

	/**
	 * Check table for fields with namespaced objects can be found
	 */
	public function testTableForNamespacedObjectField() {
		$schema = DataObject::getSchema();

		// MyObject
		$this->assertEquals(
			'Namespaced\DOST\MyObject',
			$schema->tableForField('Namespaced\DOST\MyObject', 'Title')
		);

		// MyObject_CustomTable
		$this->assertEquals(
			'CustomNamespacedTable',
			$schema->tableForField('Namespaced\DOST\MyObject_CustomTable', 'Title')
		);

		// MyObject_NestedObject
		$this->assertEquals(
			'Namespaced\DOST\MyObject',
			$schema->tableForField('Namespaced\DOST\MyObject_NestedObject', 'Title')
		);
		$this->assertEquals(
			'Namespaced\DOST\MyObject_NestedObject',
			$schema->tableForField('Namespaced\DOST\MyObject_NestedObject', 'Content')
		);

		// MyObject_NamespacedTable
		$this->assertEquals(
			'Custom\NamespacedTable',
			$schema->tableForField('Namespaced\DOST\MyObject_NamespacedTable', 'Description')
		);
		$this->assertEquals(
			'Custom\NamespacedTable',
			$schema->tableForField('Namespaced\DOST\MyObject_NamespacedTable', 'OwnerID')
		);

		// MyObject_Namespaced_Subclass
		$this->assertEquals(
			'Custom\NamespacedTable',
			$schema->tableForField('Namespaced\DOST\MyObject_Namespaced_Subclass', 'OwnerID')
		);
		$this->assertEquals(
			'Custom\NamespacedTable',
			$schema->tableForField('Namespaced\DOST\MyObject_Namespaced_Subclass', 'Title')
		);
		$this->assertEquals(
			'Custom\NamespacedTable',
			$schema->tableForField('Namespaced\DOST\MyObject_Namespaced_Subclass', 'ID')
		);
		$this->assertEquals(
			'Custom\SubclassedTable',
			$schema->tableForField('Namespaced\DOST\MyObject_Namespaced_Subclass', 'Details')
		);

		// MyObject_NoFields
		$this->assertEquals(
			'Namespaced\DOST\MyObject_NoFields',
			$schema->tableForField('Namespaced\DOST\MyObject_NoFields', 'Created')
		);
	}

	/**
	 * Test that relations join on the correct columns
	 */
	public function testRelationsQuery() {
		$namespaced1 = $this->objFromFixture('Namespaced\DOST\MyObject_NamespacedTable', 'namespaced1');
		$nofields = $this->objFromFixture('Namespaced\DOST\MyObject_NoFields', 'nofields1');
		$subclass1 = $this->objFromFixture('Namespaced\DOST\MyObject_Namespaced_Subclass', 'subclass1');
		$customtable1 = $this->objFromFixture('Namespaced\DOST\MyObject_CustomTable', 'customtable1');
		$customtable3 = $this->objFromFixture('Namespaced\DOST\MyObject_CustomTable', 'customtable3');

		// Check has_one / has_many
		$this->assertEquals($nofields->ID, $namespaced1->Owner()->ID);
		$this->assertDOSEquals([
			['Title' => 'Namespaced 1'],
		], $nofields->Owns());

		// Check many_many / belongs_many_many
		$this->assertDOSEquals(
			[
				['Title' => 'Custom Table 1'],
				['Title' => 'Custom Table 2'],
			],
			$subclass1->Children()
		);
		$this->assertDOSEquals(
			[
				['Title' => 'Subclass 1', 'Details' => 'Oh, Hi!',]]
			,
			$customtable1->Parents()
		);
		$this->assertEmpty($customtable3->Parents()->count());

	}


	/**
	 * @covers DataObjectSchema::baseDataClass()
	 */
	public function testBaseDataClass() {
		$schema = DataObject::getSchema();

		$this->assertEquals('DataObjectSchemaTest_BaseClass', $schema->baseDataClass('DataObjectSchemaTest_BaseClass'));
		$this->assertEquals('DataObjectSchemaTest_BaseClass', $schema->baseDataClass('DataObjectSchemaTest_baseclass'));
		$this->assertEquals('DataObjectSchemaTest_BaseClass', $schema->baseDataClass('DataObjectSchemaTest_ChildClass'));
		$this->assertEquals('DataObjectSchemaTest_BaseClass', $schema->baseDataClass('DataObjectSchemaTest_CHILDCLASS'));
		$this->assertEquals('DataObjectSchemaTest_BaseClass', $schema->baseDataClass('DataObjectSchemaTest_GrandChildClass'));
		$this->assertEquals('DataObjectSchemaTest_BaseClass', $schema->baseDataClass('DataObjectSchemaTest_GRANDChildClass'));

		$this->setExpectedException('InvalidArgumentException');
		$schema->baseDataClass('SilverStripe\\ORM\\DataObject');
	}
}

class DataObjectSchemaTest_BaseClass extends DataObject implements TestOnly {

}

class DataObjectSchemaTest_ChildClass extends DataObjectSchemaTest_BaseClass {

}

class DataObjectSchemaTest_GrandChildClass extends DataObjectSchemaTest_ChildClass {

}

class DataObjectSchemaTest_BaseDataClass extends DataObject implements TestOnly {

	private static $db = array(
		'Title' => 'Varchar'
	);
}


class DataObjectSchemaTest_NoFields extends DataObjectSchemaTest_BaseDataClass {

}

class DataObjectSchemaTest_HasFields extends DataObjectSchemaTest_NoFields {

	private static $db = array(
		'Description' => 'Varchar'
	);
}

class DataObjectSchemaTest_WithCustomTable extends DataObjectSchemaTest_NoFields {
	private static $table_name = 'DOSTWithCustomTable';
	private static $db = array(
		'Description' => 'Text'
	);
}

class DataObjectSchemaTest_WithRelation extends DataObjectSchemaTest_NoFields {

	private static $has_one = array(
		'Relation' => 'DataObjectSchemaTest_HasFields'
	);
}
