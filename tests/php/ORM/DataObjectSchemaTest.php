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
     * Test that the class name is convertible from the table
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

        // Non-existent fields shouldn't match any table
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

    /**
     * @dataProvider provideFieldSpec
     */
    public function testFieldSpec(array $args, array $expected): void
    {
        $schema = DataObject::getSchema();
        // May be overridden from DBClassName to DBClassNameVarchar by config
        $expectedClassName = DataObject::config()->get('fixed_fields')['ClassName'];
        if (array_key_exists('ClassName', $expected) && $expectedClassName !== 'DBClassName') {
            $expected['ClassName'] = str_replace('DBClassName', $expectedClassName, $expected['ClassName']);
        }
        $this->assertEquals($expected, $schema->fieldSpecs(...$args));
    }

    public function provideFieldSpec(): array
    {
        return [
            'just pass a class' => [
                'args' => [HasFields::class],
                'expected' => [
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
            ],
            'prefix with classname' => [
                'args' => [HasFields::class, DataObjectSchema::INCLUDE_CLASS],
                'expected' => [
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
            ],
            'DB_ONLY excludes composite field MoneyField' => [
                'args' => [
                    HasFields::class,
                    DataObjectSchema::INCLUDE_CLASS | DataObjectSchema::DB_ONLY,
                ],
                'expected' => [
                    'ID' => DataObjectSchemaTest\HasFields::class . '.PrimaryKey',
                    'ClassName' => DataObjectSchemaTest\BaseDataClass::class . '.DBClassName',
                    'LastEdited' => DataObjectSchemaTest\BaseDataClass::class . '.DBDatetime',
                    'Created' => DataObjectSchemaTest\BaseDataClass::class . '.DBDatetime',
                    'Title' => DataObjectSchemaTest\BaseDataClass::class . '.Varchar',
                    'Description' => DataObjectSchemaTest\HasFields::class . '.Varchar',
                    'MoneyFieldCurrency' => DataObjectSchemaTest\HasFields::class . '.Varchar(3)',
                    'MoneyFieldAmount' => DataObjectSchemaTest\HasFields::class . '.Decimal(19,4)'
                ],
            ],
            'Use all options at once' => [
                'args' => [
                    HasFields::class,
                    DataObjectSchema::INCLUDE_CLASS | DataObjectSchema::DB_ONLY | DataObjectSchema::UNINHERITED
                ],
                'expected' => [
                    'ID' => DataObjectSchemaTest\HasFields::class . '.PrimaryKey',
                    'Description' => DataObjectSchemaTest\HasFields::class . '.Varchar',
                    'MoneyFieldCurrency' => DataObjectSchemaTest\HasFields::class . '.Varchar(3)',
                    'MoneyFieldAmount' => DataObjectSchemaTest\HasFields::class . '.Decimal(19,4)',
                ],
            ],
            'has_one relations are returned correctly' => [
                'args' => [WithRelation::class],
                'expected' => [
                    'ID' => 'PrimaryKey',
                    'ClassName' => 'DBClassName',
                    'LastEdited' => 'DBDatetime',
                    'Created' => 'DBDatetime',
                    'Title' => 'Varchar',
                    'RelationID' => 'ForeignKey',
                    'PolymorphicRelationID' => 'Int',
                    'PolymorphicRelationClass' => "DBClassName('SilverStripe\ORM\DataObject', ['index' => false])",
                    'MultiRelationalRelationID' => 'Int',
                    'MultiRelationalRelationClass' => "DBClassName('SilverStripe\ORM\DataObject', ['index' => false])",
                    'MultiRelationalRelationRelation' => 'Varchar',
                    'PolymorphicRelation' => 'PolymorphicForeignKey',
                    'MultiRelationalRelation' => 'PolymorphicRelationAwareForeignKey',
                    'ArraySyntaxRelationID' => 'ForeignKey',
                ],
            ],
        ];
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

    /**
     * Ensure that records with unique indexes can be written
     */
    public function testWriteUniqueIndexes()
    {
        // Create default object
        $zeroObject = new AllIndexes();
        $zeroObject->Number = 0;
        $zeroObject->write();

        $this->assertListEquals(
            [
                ['Number' => 0],
            ],
            AllIndexes::get()
        );

        // Test a new record can be created without clashing with default value
        $validObject = new AllIndexes();
        $validObject->Number = 1;
        $validObject->write();

        $this->assertListEquals(
            [
                ['Number' => 0],
                ['Number' => 1],
            ],
            AllIndexes::get()
        );
    }

    /**
     * @dataProvider provideHasOneComponent
     */

    public function testHasOneComponent(string $class, string $component, string $expected): void
    {
        $this->assertSame($expected, DataObject::getSchema()->hasOneComponent($class, $component));
    }

    public function provideHasOneComponent(): array
    {
        return [
            [
                'class' => WithRelation::class,
                'component' => 'Relation',
                'expected' => HasFields::class,
            ],
            [
                'class' => WithRelation::class,
                'component' => 'PolymorphicRelation',
                'expected' => DataObject::class,
            ],
            [
                'class' => WithRelation::class,
                'component' => 'ArraySyntaxRelation',
                'expected' => HasFields::class,
            ],
            [
                'class' => WithRelation::class,
                'component' => 'MultiRelationalRelation',
                'expected' => DataObject::class,
            ],
        ];
    }

    /**
     * @dataProvider provideHasOneComponentHandlesMultipleRelations
     */
    public function testHasOneComponentHandlesMultipleRelations(string $class, string $component, bool $expected): void
    {
        $this->assertSame(
            $expected,
            DataObject::getSchema()->hasOneComponentHandlesMultipleRelations($class, $component)
        );
    }

    public function provideHasOneComponentHandlesMultipleRelations(): array
    {
        return [
            [
                'class' => WithRelation::class,
                'component' => 'Relation',
                'expected' => false,
            ],
            [
                'class' => WithRelation::class,
                'component' => 'PolymorphicRelation',
                'expected' => false,
            ],
            [
                'class' => WithRelation::class,
                'component' => 'ArraySyntaxRelation',
                'expected' => false,
            ],
            [
                'class' => WithRelation::class,
                'component' => 'MultiRelationalRelation',
                'expected' => true,
            ],
        ];
    }
}
