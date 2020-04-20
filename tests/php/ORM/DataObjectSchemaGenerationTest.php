<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\Connect\MySQLSchemaManager;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBEnum;
use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Tests\DataObjectSchemaGenerationTest\SortedObject;
use SilverStripe\ORM\Tests\DataObjectSchemaGenerationTest\TestIndexObject;
use SilverStripe\ORM\Tests\DataObjectSchemaGenerationTest\TestObject;

class DataObjectSchemaGenerationTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        TestObject::class,
        TestIndexObject::class,
        SortedObject::class,
    ];

    public static function setUpBeforeClass()
    {
        // Start tests
        static::start();

        parent::setUpBeforeClass();
    }

    /**
     * @skipUpgrade
     */
    public function testTableCaseFixed()
    {
        DB::quiet();

        // Modify table case
        DB::get_schema()->renameTable(
            'DataObjectSchemaGenerationTest_DO',
            '__TEMP__DataOBJECTSchemaGenerationTest_do'
        );
        DB::get_schema()->renameTable(
            '__TEMP__DataOBJECTSchemaGenerationTest_do',
            'DataOBJECTSchemaGenerationTest_do'
        );

        // Check table
        $tables = DB::table_list();
        $this->assertEquals(
            'DataOBJECTSchemaGenerationTest_do',
            $tables['dataobjectschemagenerationtest_do']
        );

        // Rebuild table
        DB::get_schema()->schemaUpdate(
            function () {
                TestObject::singleton()->requireTable();
            }
        );

        // Check table
        $tables = DB::table_list();
        $this->assertEquals(
            'DataObjectSchemaGenerationTest_DO',
            $tables['dataobjectschemagenerationtest_do']
        );
    }

    /**
     * Check that once a schema has been generated, then it doesn't need any more updating
     */
    public function testFieldsDontRerequestChanges()
    {
        $schema = DB::get_schema();
        $test = $this;
        DB::quiet();

        // Table will have been initially created by the $extraDataObjects setting

        // Verify that it doesn't need to be recreated
        $schema->schemaUpdate(
            function () use ($test, $schema) {
                $obj = new TestObject();
                $obj->requireTable();
                $needsUpdating = $schema->doesSchemaNeedUpdating();
                $schema->cancelSchemaUpdate();
                $test->assertFalse($needsUpdating);
            }
        );
    }

    /**
     * Check that updates to a class fields are reflected in the database
     */
    public function testFieldsRequestChanges()
    {
        $schema = DB::get_schema();
        $test = $this;
        DB::quiet();

        // Table will have been initially created by the $extraDataObjects setting

        // Let's insert a new field here
        TestObject::config()->update(
            'db',
            [
            'SecretField' => 'Varchar(100)'
            ]
        );

        // Verify that the above extra field triggered a schema update
        $schema->schemaUpdate(
            function () use ($test, $schema) {
                $obj = new TestObject();
                $obj->requireTable();
                $needsUpdating = $schema->doesSchemaNeedUpdating();
                $schema->cancelSchemaUpdate();
                $test->assertTrue($needsUpdating);
            }
        );
    }

    /**
     * Check that indexes on a newly generated class do not subsequently request modification
     */
    public function testIndexesDontRerequestChanges()
    {
        $schema = DB::get_schema();
        $test = $this;
        DB::quiet();

        // Table will have been initially created by the $extraDataObjects setting

        // Verify that it doesn't need to be recreated
        $schema->schemaUpdate(
            function () use ($test, $schema) {
                $obj = new TestIndexObject();
                $obj->requireTable();
                $needsUpdating = $schema->doesSchemaNeedUpdating();
                $schema->cancelSchemaUpdate();
                $test->assertFalse($needsUpdating);
            }
        );

        // Test with alternate index format, although these indexes are the same
        $config = TestIndexObject::config();
        $config->set('indexes', $config->get('indexes_alt'));

        // Verify that it still doesn't need to be recreated
        $schema->schemaUpdate(
            function () use ($test, $schema) {
                $obj2 = new TestIndexObject();
                $obj2->requireTable();
                $needsUpdating = $schema->doesSchemaNeedUpdating();
                $schema->cancelSchemaUpdate();
                $test->assertFalse($needsUpdating);
            }
        );
    }

    /**
     * Check that updates to a dataobject's indexes are reflected in DDL
     */
    public function testIndexesRerequestChanges()
    {
        $schema = DB::get_schema();
        $test = $this;
        DB::quiet();

        // Table will have been initially created by the $extraDataObjects setting

        // Update the SearchFields index here
        TestIndexObject::config()->update(
            'indexes',
            [
                'SearchFields' => [
                    'columns' => ['Title'],
                ],
            ]
        );

        // Verify that the above index change triggered a schema update
        $schema->schemaUpdate(
            function () use ($test, $schema) {
                $obj = new TestIndexObject();
                $obj->requireTable();
                $needsUpdating = $schema->doesSchemaNeedUpdating();
                $schema->cancelSchemaUpdate();
                $test->assertTrue($needsUpdating);
            }
        );
    }

    /**
     * Tests the generation of the ClassName spec and ensure it's not unnecessarily influenced
     * by the order of classnames of existing records
     * @skipUpgrade
     */
    public function testClassNameSpecGeneration()
    {
        $schema = DataObject::getSchema();

        // Test with blank entries
        DBEnum::flushCache();
        $do1 = new TestObject();
        $fields = $schema->databaseFields(TestObject::class, false);
        $this->assertEquals("DBClassName", $fields['ClassName']);
        $this->assertEquals(
            [
                TestObject::class,
                TestIndexObject::class,
            ],
            $do1->dbObject('ClassName')->getEnum()
        );


        // Test with instance of subclass
        $item1 = new TestIndexObject();
        $item1->write();
        DBEnum::flushCache();
        $this->assertEquals(
            [
                TestObject::class,
                TestIndexObject::class,
            ],
            $item1->dbObject('ClassName')->getEnum()
        );
        $item1->delete();

        // Test with instance of main class
        $item2 = new TestObject();
        $item2->write();
        DBEnum::flushCache();
        $this->assertEquals(
            [
                TestObject::class,
                TestIndexObject::class,
            ],
            $item2->dbObject('ClassName')->getEnum()
        );
        $item2->delete();

        // Test with instances of both classes
        $item1 = new TestIndexObject();
        $item1->write();
        $item2 = new TestObject();
        $item2->write();
        DBEnum::flushCache();
        $this->assertEquals(
            [
                TestObject::class,
                TestIndexObject::class,
            ],
            $item1->dbObject('ClassName')->getEnum()
        );
        $item1->delete();
        $item2->delete();
    }

    public function testSortFieldBecomeIndexes()
    {
        $indexes = DataObject::getSchema()->databaseIndexes(SortedObject::class);
        $this->assertContains([
            'type' => 'index',
            'columns' => ['Sort'],
        ], $indexes);
        DataObject::getSchema()->reset();
        Config::inst()->update(SortedObject::class, 'default_sort', 'Sort ASC');
        $indexes = DataObject::getSchema()->databaseIndexes(SortedObject::class);
        $this->assertContains([
            'type' => 'index',
            'columns' => ['Sort'],
        ], $indexes);
        DataObject::getSchema()->reset();
        Config::inst()->update(SortedObject::class, 'default_sort', 'Sort DESC');
        $indexes = DataObject::getSchema()->databaseIndexes(SortedObject::class);
        $this->assertContains([
            'type' => 'index',
            'columns' => ['Sort'],
        ], $indexes);
        DataObject::getSchema()->reset();
        Config::inst()->update(SortedObject::class, 'default_sort', '"Sort" DESC');
        $indexes = DataObject::getSchema()->databaseIndexes(SortedObject::class);
        $this->assertContains([
            'type' => 'index',
            'columns' => ['Sort'],
        ], $indexes);
        DataObject::getSchema()->reset();
        Config::inst()->update(SortedObject::class, 'default_sort', '"DataObjectSchemaGenerationTest_SortedObject"."Sort" ASC');
        $indexes = DataObject::getSchema()->databaseIndexes(SortedObject::class);
        $this->assertContains([
            'type' => 'index',
            'columns' => ['Sort'],
        ], $indexes);
        DataObject::getSchema()->reset();
        Config::inst()->update(SortedObject::class, 'default_sort', '"Sort" DESC, "Title" ASC');
        $indexes = DataObject::getSchema()->databaseIndexes(SortedObject::class);
        $this->assertContains([
            'type' => 'index',
            'columns' => ['Sort'],
        ], $indexes);
        $this->assertContains([
            'type' => 'index',
            'columns' => ['Title'],
        ], $indexes);
        DataObject::getSchema()->reset();
        // make sure that specific indexes aren't overwritten
        Config::inst()->update(SortedObject::class, 'indexes', [
            'Sort' => [
                'type' => 'unique',
                'columns' => ['Sort'],
            ],
        ]);
        $indexes = DataObject::getSchema()->databaseIndexes(SortedObject::class);
        $this->assertContains([
            'type' => 'unique',
            'columns' => ['Sort'],
        ], $indexes);
    }
}
