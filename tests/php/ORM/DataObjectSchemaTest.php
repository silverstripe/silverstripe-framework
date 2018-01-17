<?php

namespace SilverStripe\ORM\Tests;

use InvalidArgumentException;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBMoney;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\ORM\Tests\DataObjectSchemaTest\AllIndexes;
use SilverStripe\ORM\Tests\DataObjectSchemaTest\BaseClass;
use SilverStripe\ORM\Tests\DataObjectSchemaTest\BaseDataClass;
use SilverStripe\ORM\Tests\DataObjectSchemaTest\ChildClass;
use SilverStripe\ORM\Tests\DataObjectSchemaTest\DefaultTableName;
use SilverStripe\ORM\Tests\DataObjectSchemaTest\GrandChildClass;
use SilverStripe\ORM\Tests\DataObjectSchemaTest\HasComposites;
use SilverStripe\ORM\Tests\DataObjectSchemaTest\HasFields;
use SilverStripe\ORM\Tests\DataObjectSchemaTest\HasIndexesInFieldSpecs;
use SilverStripe\ORM\Tests\DataObjectSchemaTest\NoFields;
use SilverStripe\ORM\Tests\DataObjectSchemaTest\WithCustomTable;
use SilverStripe\ORM\Tests\DataObjectSchemaTest\WithRelation;

/**
 * Tests schema inspection of DataObjects
 *
 * @skipUpgrade
 */
class DataObjectSchemaTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        // Classes in base namespace
        BaseClass::class,
        BaseDataClass::class,
        ChildClass::class,
        GrandChildClass::class,
        HasFields::Class,
        NoFields::class,
        WithCustomTable::class,
        WithRelation::class,
        DefaultTableName::class,
        AllIndexes::class,
    ];

    /**
     * Test table name generation
     */
    public function testTableName()
    {
        $schema = DataObject::getSchema();

        $this->assertEquals(
            'DataObjectSchemaTest_WithRelation',
            $schema->tableName(WithRelation::class)
        );
        $this->assertEquals(
            'DOSTWithCustomTable',
            $schema->tableName(WithCustomTable::class)
        );
        // Default table name is FQN
        $this->assertEquals(
            'SilverStripe_ORM_Tests_DataObjectSchemaTest_DefaultTableName',
            $schema->tableName(DefaultTableName::class)
        );
    }

    /**
     * Test that the class name is convertable from the table
     */
    public function testClassNameForTable()
    {
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

    public function testTableForObjectField()
    {
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
            $schema->tableForField(ClassInfo::class, 'Title')
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

    public function testFieldSpec()
    {
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
                'ID' => DataObjectSchemaTest\HasFields::class . '.PrimaryKey',
                'ClassName' => DataObjectSchemaTest\BaseDataClass::class . '.DBClassName',
                'LastEdited' => DataObjectSchemaTest\BaseDataClass::class . '.DBDatetime',
                'Created' => DataObjectSchemaTest\BaseDataClass::class . '.DBDatetime',
                'Title' => DataObjectSchemaTest\BaseDataClass::class . '.Varchar',
                'Description' => DataObjectSchemaTest\HasFields::class . '.Varchar',
                'MoneyFieldCurrency' => DataObjectSchemaTest\HasFields::class . '.Varchar(3)',
                'MoneyFieldAmount' => DataObjectSchemaTest\HasFields::class . '.Decimal(19,4)',
                'MoneyField' => DataObjectSchemaTest\HasFields::class . '.Money',
            ],
            $schema->fieldSpecs(HasFields::class, DataObjectSchema::INCLUDE_CLASS)
        );
        // DB_ONLY excludes composite field MoneyField
        $this->assertEquals(
            [
                'ID' => DataObjectSchemaTest\HasFields::class . '.PrimaryKey',
                'ClassName' => DataObjectSchemaTest\BaseDataClass::class . '.DBClassName',
                'LastEdited' => DataObjectSchemaTest\BaseDataClass::class . '.DBDatetime',
                'Created' => DataObjectSchemaTest\BaseDataClass::class . '.DBDatetime',
                'Title' => DataObjectSchemaTest\BaseDataClass::class . '.Varchar',
                'Description' => DataObjectSchemaTest\HasFields::class . '.Varchar',
                'MoneyFieldCurrency' => DataObjectSchemaTest\HasFields::class . '.Varchar(3)',
                'MoneyFieldAmount' => DataObjectSchemaTest\HasFields::class . '.Decimal(19,4)'
            ],
            $schema->fieldSpecs(
                HasFields::class,
                DataObjectSchema::INCLUDE_CLASS | DataObjectSchema::DB_ONLY
            )
        );

        // Use all options at once
        $this->assertEquals(
            [
                'ID' => DataObjectSchemaTest\HasFields::class . '.PrimaryKey',
                'Description' => DataObjectSchemaTest\HasFields::class . '.Varchar',
                'MoneyFieldCurrency' => DataObjectSchemaTest\HasFields::class . '.Varchar(3)',
                'MoneyFieldAmount' => DataObjectSchemaTest\HasFields::class . '.Decimal(19,4)',
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
    public function testBaseDataClass()
    {
        $schema = DataObject::getSchema();

        $this->assertEquals(BaseClass::class, $schema->baseDataClass(BaseClass::class));
        $this->assertEquals(BaseClass::class, $schema->baseDataClass(strtolower(BaseClass::class)));
        $this->assertEquals(BaseClass::class, $schema->baseDataClass(ChildClass::class));
        $this->assertEquals(BaseClass::class, $schema->baseDataClass(strtoupper(ChildClass::class)));
        $this->assertEquals(BaseClass::class, $schema->baseDataClass(GrandChildClass::class));
        $this->assertEquals(BaseClass::class, $schema->baseDataClass(ucfirst(GrandChildClass::class)));

        $this->expectException(InvalidArgumentException::class);

        $schema->baseDataClass(DataObject::class);
    }

    public function testDatabaseIndexes()
    {
        $indexes = DataObject::getSchema()->databaseIndexes(AllIndexes::class);
        $this->assertCount(5, $indexes);
        $this->assertArrayHasKey('ClassName', $indexes);
        $this->assertEquals([
            'type' => 'index',
            'columns' => ['ClassName'],
        ], $indexes['ClassName']);

        $this->assertArrayHasKey('Content', $indexes);
        $this->assertEquals([
            'type' => 'index',
            'columns' => ['Content'],
        ], $indexes['Content']);

        $this->assertArrayHasKey('IndexCols', $indexes);
        $this->assertEquals([
            'type' => 'index',
            'columns' => ['Title', 'Content'],
        ], $indexes['IndexCols']);

        $this->assertArrayHasKey('IndexUnique', $indexes);
        $this->assertEquals([
            'type' => 'unique',
            'columns' => ['Number'],
        ], $indexes['IndexUnique']);

        $this->assertArrayHasKey('IndexNormal', $indexes);
        $this->assertEquals([
            'type' => 'index',
            'columns' => ['Title'],
        ], $indexes['IndexNormal']);
    }

    public function testCompositeDatabaseFieldIndexes()
    {
        $indexes = DataObject::getSchema()->databaseIndexes(HasComposites::class);
        $this->assertCount(3, $indexes);
        $this->assertArrayHasKey('RegularHasOneID', $indexes);
        $this->assertEquals([
            'type' => 'index',
            'columns' => ['RegularHasOneID']
        ], $indexes['RegularHasOneID']);

        $this->assertArrayHasKey('Polymorpheus', $indexes);
        $this->assertEquals([
            'type' => 'index',
            'columns' => ['PolymorpheusID', 'PolymorpheusClass']
        ], $indexes['Polymorpheus']);

        // Check that DBPolymorphicForeignKey's "Class" is not indexed on its own
        $this->assertArrayNotHasKey('PolymorpheusClass', $indexes);
    }

    public function testCompositeFieldsCanBeIndexedByDefaultConfiguration()
    {
        Config::modify()->set(DBMoney::class, 'index', true);
        $indexes = DataObject::getSchema()->databaseIndexes(HasComposites::class);

        $this->assertCount(4, $indexes);
        $this->assertArrayHasKey('Amount', $indexes);
        $this->assertEquals([
            'type' => 'index',
            'columns' => ['AmountCurrency', 'AmountAmount']
        ], $indexes['Amount']);
    }

    public function testIndexTypeIsConfigurable()
    {
        Config::modify()->set(DBMoney::class, 'index', 'unique');

        $indexes = DataObject::getSchema()->databaseIndexes(HasComposites::class);
        $this->assertCount(4, $indexes);
        $this->assertArrayHasKey('Amount', $indexes);
        $this->assertEquals([
            'type' => 'unique',
            'columns' => ['AmountCurrency', 'AmountAmount']
        ], $indexes['Amount']);
    }

    public function testFieldsCanBeIndexedFromFieldSpecs()
    {
        $indexes = DataObject::getSchema()->databaseIndexes(HasIndexesInFieldSpecs::class);

        $this->assertCount(3, $indexes);
        $this->assertArrayHasKey('ClassName', $indexes);

        $this->assertArrayHasKey('IndexedTitle', $indexes);
        $this->assertEquals([
            'type' => 'fulltext',
            'columns' => ['IndexedTitle']
        ], $indexes['IndexedTitle']);

        $this->assertArrayHasKey('IndexedMoney', $indexes);
        $this->assertEquals([
            'type' => 'index',
            'columns' => ['IndexedMoneyCurrency', 'IndexedMoneyAmount']
        ], $indexes['IndexedMoney']);
    }
}
