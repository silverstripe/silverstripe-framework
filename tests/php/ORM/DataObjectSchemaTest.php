<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Core\Object;
use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\ORM\Tests\DataObjectSchemaTest\BaseClass;
use SilverStripe\ORM\Tests\DataObjectSchemaTest\BaseDataClass;
use SilverStripe\ORM\Tests\DataObjectSchemaTest\ChildClass;
use SilverStripe\ORM\Tests\DataObjectSchemaTest\GrandChildClass;
use SilverStripe\ORM\Tests\DataObjectSchemaTest\HasFields;
use SilverStripe\ORM\Tests\DataObjectSchemaTest\NoFields;
use SilverStripe\ORM\Tests\DataObjectSchemaTest\WithCustomTable;
use SilverStripe\ORM\Tests\DataObjectSchemaTest\WithRelation;

/**
 * Tests schema inspection of DataObjects
 * @skipUpgrade
 */
class DataObjectSchemaTest extends SapphireTest
{
	protected $extraDataObjects = array(
		// Classes in base namespace
		BaseClass::class,
		BaseDataClass::class,
		ChildClass::class,
		GrandChildClass::class,
		HasFields::Class,
		NoFields::class,
		WithCustomTable::class,
		WithRelation::class
	);

	/**
	 * Test table name generation
	 */
	public function testTableName() {
		$schema = DataObject::getSchema();

		$this->assertEquals(
			'DataObjectSchemaTest_WithRelation',
			$schema->tableName(WithRelation::class)
		);
		$this->assertEquals(
			'DOSTWithCustomTable',
			$schema->tableName(WithCustomTable::class)
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
			WithRelation::class,
			$schema->tableClass('DataObjectSchemaTest_WithRelation')
		);
		$this->assertEquals(
			WithCustomTable::class,
			$schema->tableClass('DOSTWithCustomTable')
		);
	}

	public function testTableForObjectField() {
		$schema = DataObject::getSchema();
		$this->assertEquals(
			'DataObjectSchemaTest_WithRelation',
			$schema->tableForField(WithRelation::class, 'RelationID')
		);

		$this->assertEquals(
			'DataObjectSchemaTest_WithRelation',
			$schema->tableForField(WithRelation::class, 'RelationID')
		);

		$this->assertEquals(
			'DataObjectSchemaTest_BaseDataClass',
			$schema->tableForField(BaseDataClass::class, 'Title')
		);

		$this->assertEquals(
			'DataObjectSchemaTest_BaseDataClass',
			$schema->tableForField(HasFields::class, 'Title')
		);

		$this->assertEquals(
			'DataObjectSchemaTest_BaseDataClass',
			$schema->tableForField(NoFields::class, 'Title')
		);

		$this->assertEquals(
			'DataObjectSchemaTest_BaseDataClass',
			$schema->tableForField(NoFields::class, 'Title')
		);

		$this->assertEquals(
			'DataObjectSchemaTest_HasFields',
			$schema->tableForField(HasFields::Class, 'Description')
		);

		// Class and table differ for this model
		$this->assertEquals(
			'DOSTWithCustomTable',
			$schema->tableForField(WithCustomTable::class, 'Description')
		);
		$this->assertEquals(
			WithCustomTable::class,
			$schema->classForField(WithCustomTable::class, 'Description')
		);
		$this->assertNull(
			$schema->tableForField(WithCustomTable::class, 'NotAField')
		);
		$this->assertNull(
			$schema->classForField(WithCustomTable::class, 'NotAField')
		);

		// Non-existant fields shouldn't match any table
		$this->assertNull(
			$schema->tableForField(BaseClass::class, 'Nonexist')
		);

		$this->assertNull(
			$schema->tableForField(Object::class, 'Title')
		);

		// Test fixed fields
		$this->assertEquals(
			'DataObjectSchemaTest_BaseDataClass',
			$schema->tableForField(HasFields::class, 'ID')
		);
		$this->assertEquals(
			'DataObjectSchemaTest_BaseDataClass',
			$schema->tableForField(NoFields::class, 'Created')
		);
	}

	public function testFieldSpec() {
		$schema = DataObject::getSchema();
		$this->assertEquals(
			[
				'ID' => 'PrimaryKey',
				'ClassName' => 'DBClassName',
				'LastEdited' => 'DBDatetime',
				'Created' => 'DBDatetime',
				'Title' => 'Varchar',
				'Description' => 'Varchar',
				'MoneyFieldCurrency' => 'Varchar(3)',
				'MoneyFieldAmount' => 'Decimal(19,4)',
				'MoneyField' => 'Money',
			],
			$schema->fieldSpecs(HasFields::class)
		);
		$this->assertEquals(
			[
				'ID' => 'DataObjectSchemaTest_HasFields.PrimaryKey',
				'ClassName' => 'DataObjectSchemaTest_BaseDataClass.DBClassName',
				'LastEdited' => 'DataObjectSchemaTest_BaseDataClass.DBDatetime',
				'Created' => 'DataObjectSchemaTest_BaseDataClass.DBDatetime',
				'Title' => 'DataObjectSchemaTest_BaseDataClass.Varchar',
				'Description' => 'DataObjectSchemaTest_HasFields.Varchar',
				'MoneyFieldCurrency' => 'DataObjectSchemaTest_HasFields.Varchar(3)',
				'MoneyFieldAmount' => 'DataObjectSchemaTest_HasFields.Decimal(19,4)',
				'MoneyField' => 'DataObjectSchemaTest_HasFields.Money',
			],
			$schema->fieldSpecs(HasFields::class, DataObjectSchema::INCLUDE_CLASS)
		);
		// DB_ONLY excludes composite field MoneyField
		$this->assertEquals(
			[
				'ID' => 'DataObjectSchemaTest_HasFields.PrimaryKey',
				'ClassName' => 'DataObjectSchemaTest_BaseDataClass.DBClassName',
				'LastEdited' => 'DataObjectSchemaTest_BaseDataClass.DBDatetime',
				'Created' => 'DataObjectSchemaTest_BaseDataClass.DBDatetime',
				'Title' => 'DataObjectSchemaTest_BaseDataClass.Varchar',
				'Description' => 'DataObjectSchemaTest_HasFields.Varchar',
				'MoneyFieldCurrency' => 'DataObjectSchemaTest_HasFields.Varchar(3)',
				'MoneyFieldAmount' => 'DataObjectSchemaTest_HasFields.Decimal(19,4)'
			],
			$schema->fieldSpecs(
				HasFields::class,
				DataObjectSchema::INCLUDE_CLASS | DataObjectSchema::DB_ONLY
			)
		);

		// Use all options at once
		$this->assertEquals(
			[
				'ID' => 'DataObjectSchemaTest_HasFields.PrimaryKey',
				'Description' => 'DataObjectSchemaTest_HasFields.Varchar',
				'MoneyFieldCurrency' => 'DataObjectSchemaTest_HasFields.Varchar(3)',
				'MoneyFieldAmount' => 'DataObjectSchemaTest_HasFields.Decimal(19,4)',
			],
			$schema->fieldSpecs(
				HasFields::class,
				DataObjectSchema::INCLUDE_CLASS | DataObjectSchema::DB_ONLY | DataObjectSchema::UNINHERITED
			)
		);
	}

	/**
	 * @covers \SilverStripe\ORM\DataObjectSchema::baseDataClass()
	 */
	public function testBaseDataClass() {
		$schema = DataObject::getSchema();

		$this->assertEquals(BaseClass::class, $schema->baseDataClass(BaseClass::class));
		$this->assertEquals(BaseClass::class, $schema->baseDataClass(strtolower(BaseClass::class)));
		$this->assertEquals(BaseClass::class, $schema->baseDataClass(ChildClass::class));
		$this->assertEquals(BaseClass::class, $schema->baseDataClass(strtoupper(ChildClass::class)));
		$this->assertEquals(BaseClass::class, $schema->baseDataClass(GrandChildClass::class));
		$this->assertEquals(BaseClass::class, $schema->baseDataClass(ucfirst(GrandChildClass::class)));

		$this->setExpectedException('InvalidArgumentException');
		$schema->baseDataClass(DataObject::class);
	}
}
