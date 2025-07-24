<?php

namespace SilverStripe\ORM\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBEnum;
use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\ORM\Tests\DataObjectSchemaGenerationTest\SortedObject;
use SilverStripe\ORM\Tests\DataObjectSchemaGenerationTest\TestIndexObject;
use SilverStripe\ORM\Tests\DataObjectSchemaGenerationTest\TestObject;
use SilverStripe\ORM\Tests\DataObjectSchemaTest\AllIndexes;

class DataObjectSchemaGenerationTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        TestObject::class,
        TestIndexObject::class,
        SortedObject::class,
        AllIndexes::class,
    ];

    public static function setUpBeforeClass(): void
    {
        // Start tests
        static::start();

        parent::setUpBeforeClass();
    }

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
        TestObject::config()->merge(
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
        TestIndexObject::config()->merge(
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

    public function testIndexGetsDropped(): void
    {
        $table = DataObject::getSchema()->tableName(TestIndexObject::class);
        $schema = DB::get_schema();
        DB::quiet();
        $originalIndexes = $schema->indexList($table);
        $this->assertArrayHasKey('SearchFields', $originalIndexes);

        TestIndexObject::config()->merge('indexes', ['SearchFields' => false]);
        $schema->schemaUpdate(function () {
            $obj = new TestIndexObject();
            $obj->requireTable();
        });
        $currentIndexes = $schema->indexList($table);
        $this->assertArrayNotHasKey('SearchFields', $currentIndexes);
    }

    /**
     * Tests the generation of the ClassName spec and ensure it's not unnecessarily influenced
     * by the order of classnames of existing records
     */
    public function testClassNameSpecGeneration()
    {
        $schema = DataObject::getSchema();

        // Test with blank entries
        DBEnum::reset();
        $do1 = new TestObject();
        $fields = $schema->databaseFields(TestObject::class, false);
        // May be overridden from DBClassName to DBClassNameVarchar by config
        $expectedClassName = DataObject::config()->get('fixed_fields')['ClassName'];
        $this->assertEquals($expectedClassName, $fields['ClassName']);
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
        DBEnum::reset();
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
        DBEnum::reset();
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
        DBEnum::reset();
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

    public static function provideSortFieldBecomesIndexes(): array
    {
        return [
            // string sort
            'string, single column no dir' => [
                'defaultSort' => 'Sort',
                'expectedIndexes' => [
                    'Sort' => [
                        'type' => 'index',
                        'columns' => ['Sort'],
                    ],
                ],
            ],
            'string, single field with direction' => [
                'defaultSort' => 'Sort ASC',
                'expectedIndexes' => [
                    'Sort' => [
                        'type' => 'index',
                        'columns' => ['Sort'],
                    ],
                ],
            ],
            'string, single field opposite direction' => [
                'defaultSort' => 'Sort DESC',
                'expectedIndexes' => [
                    'Sort' => [
                        'type' => 'index',
                        'columns' => ['Sort'],
                    ],
                ],
            ],
            'string, single field quoted' => [
                'defaultSort' => '"Sort" DESC',
                'expectedIndexes' => [
                    'Sort' => [
                        'type' => 'index',
                        'columns' => ['Sort'],
                    ],
                ],
            ],
            'string, single field with table name' => [
                'defaultSort' => '"DataObjectSchemaGenerationTest_SortedObject"."Sort" ASC',
                'expectedIndexes' => [
                    'Sort' => [
                        'type' => 'index',
                        'columns' => ['Sort'],
                    ],
                ],
            ],
            'string, multiple fields' => [
                'defaultSort' => 'Sort, Title',
                'expectedIndexes' => [
                    'Sort' => [
                        'type' => 'index',
                        'columns' => ['Sort'],
                    ],
                    'Title' => [
                        'type' => 'index',
                        'columns' => ['Title'],
                    ],
                    'default_sort_composite' => [
                        'type' => 'index',
                        'columns' => ['Sort ASC', 'Title ASC'],
                    ],
                ],
            ],
            'string, multiple fields with directions' => [
                'defaultSort' => '"Sort" DESC, "Title" ASC',
                'expectedIndexes' => [
                    'Sort' => [
                        'type' => 'index',
                        'columns' => ['Sort'],
                    ],
                    'Title' => [
                        'type' => 'index',
                        'columns' => ['Title'],
                    ],
                    'default_sort_composite' => [
                        'type' => 'index',
                        'columns' => ['Sort DESC', 'Title ASC'],
                    ],
                ],
            ],
            'string, multiple fields, ID in middle' => [
                'defaultSort' => '"Sort" DESC, ID, "Title" ASC',
                'expectedIndexes' => [
                    'Sort' => [
                        'type' => 'index',
                        'columns' => ['Sort'],
                    ],
                    'Title' => [
                        'type' => 'index',
                        'columns' => ['Title'],
                    ],
                    'default_sort_composite' => [
                        'type' => 'index',
                        'columns' => ['Sort DESC', 'ID ASC', 'Title ASC'],
                    ],
                ],
            ],
            'string, multiple fields, ID at end' => [
                'defaultSort' => '"Sort" DESC, "Title" ASC, ID',
                'expectedIndexes' => [
                    'Sort' => [
                        'type' => 'index',
                        'columns' => ['Sort'],
                    ],
                    'Title' => [
                        'type' => 'index',
                        'columns' => ['Title'],
                    ],
                    'default_sort_composite' => [
                        'type' => 'index',
                        'columns' => ['Sort DESC', 'Title ASC'],
                    ],
                ],
            ],
            // indexed array sort
            'indexed array, single column no dir' => [
                'defaultSort' => ['Sort'],
                'expectedIndexes' => [
                    'Sort' => [
                        'type' => 'index',
                        'columns' => ['Sort'],
                    ],
                ],
            ],
            'indexed array, single field with direction' => [
                'defaultSort' => ['Sort ASC'],
                'expectedIndexes' => [
                    'Sort' => [
                        'type' => 'index',
                        'columns' => ['Sort'],
                    ],
                ],
            ],
            'indexed array, single field opposite direction' => [
                'defaultSort' => ['Sort DESC'],
                'expectedIndexes' => [
                    'Sort' => [
                        'type' => 'index',
                        'columns' => ['Sort'],
                    ],
                ],
            ],
            'indexed array, single field quoted' => [
                'defaultSort' => ['"Sort" DESC'],
                'expectedIndexes' => [
                    'Sort' => [
                        'type' => 'index',
                        'columns' => ['Sort'],
                    ],
                ],
            ],
            'indexed array, single field with table name' => [
                'defaultSort' => ['"DataObjectSchemaGenerationTest_SortedObject"."Sort" ASC'],
                'expectedIndexes' => [
                    'Sort' => [
                        'type' => 'index',
                        'columns' => ['Sort'],
                    ],
                ],
            ],
            'indexed array, multiple fields' => [
                'defaultSort' => ['Sort', 'Title'],
                'expectedIndexes' => [
                    'Sort' => [
                        'type' => 'index',
                        'columns' => ['Sort'],
                    ],
                    'Title' => [
                        'type' => 'index',
                        'columns' => ['Title'],
                    ],
                    'default_sort_composite' => [
                        'type' => 'index',
                        'columns' => ['Sort ASC', 'Title ASC'],
                    ],
                ],
            ],
            'indexed array, multiple fields with directions' => [
                'defaultSort' => [
                    '"Sort" DESC',
                    '"Title" ASC'
                ],
                'expectedIndexes' => [
                    'Sort' => [
                        'type' => 'index',
                        'columns' => ['Sort'],
                    ],
                    'Title' => [
                        'type' => 'index',
                        'columns' => ['Title'],
                    ],
                    'default_sort_composite' => [
                        'type' => 'index',
                        'columns' => ['Sort DESC', 'Title ASC'],
                    ],
                ],
            ],
            'indexed array, multiple fields, ID in middle' => [
                'defaultSort' => [
                    '"Sort" DESC',
                    'ID',
                    '"Title" ASC'
                ],
                'expectedIndexes' => [
                    'Sort' => [
                        'type' => 'index',
                        'columns' => ['Sort'],
                    ],
                    'Title' => [
                        'type' => 'index',
                        'columns' => ['Title'],
                    ],
                    'default_sort_composite' => [
                        'type' => 'index',
                        'columns' => ['Sort DESC', 'ID ASC', 'Title ASC'],
                    ],
                ],
            ],
            'indexed array, multiple fields, ID at end' => [
                'defaultSort' => [
                    '"Sort" DESC',
                    '"Title" ASC',
                    'ID'
                ],
                'expectedIndexes' => [
                    'Sort' => [
                        'type' => 'index',
                        'columns' => ['Sort'],
                    ],
                    'Title' => [
                        'type' => 'index',
                        'columns' => ['Title'],
                    ],
                    'default_sort_composite' => [
                        'type' => 'index',
                        'columns' => ['Sort DESC', 'Title ASC'],
                    ],
                ],
            ],
            // associative array sort
            'associative array, single field with direction' => [
                'defaultSort' => ['Sort' => 'ASC'],
                'expectedIndexes' => [
                    'Sort' => [
                        'type' => 'index',
                        'columns' => ['Sort'],
                    ],
                ],
            ],
            'associative array, single field opposite direction' => [
                'defaultSort' => ['Sort' => 'DESC'],
                'expectedIndexes' => [
                    'Sort' => [
                        'type' => 'index',
                        'columns' => ['Sort'],
                    ],
                ],
            ],
            'associative array, single field quoted' => [
                'defaultSort' => ['"Sort"' => 'DESC'],
                'expectedIndexes' => [
                    'Sort' => [
                        'type' => 'index',
                        'columns' => ['Sort'],
                    ],
                ],
            ],
            'associative array, single field with table name' => [
                'defaultSort' => ['"DataObjectSchemaGenerationTest_SortedObject"."Sort"' => 'ASC'],
                'expectedIndexes' => [
                    'Sort' => [
                        'type' => 'index',
                        'columns' => ['Sort'],
                    ],
                ],
            ],
            'associative array, multiple fields with directions' => [
                'defaultSort' => [
                    '"Sort"' => 'DESC',
                    '"Title"' => 'ASC'
                ],
                'expectedIndexes' => [
                    'Sort' => [
                        'type' => 'index',
                        'columns' => ['Sort'],
                    ],
                    'Title' => [
                        'type' => 'index',
                        'columns' => ['Title'],
                    ],
                    'default_sort_composite' => [
                        'type' => 'index',
                        'columns' => ['Sort DESC', 'Title ASC'],
                    ],
                ],
            ],
            'associative array, multiple fields, ID in middle' => [
                'defaultSort' => [
                    '"Sort"' => 'DESC',
                    'ID',
                    '"Title"' => 'ASC',
                ],
                'expectedIndexes' => [
                    'Sort' => [
                        'type' => 'index',
                        'columns' => ['Sort'],
                    ],
                    'Title' => [
                        'type' => 'index',
                        'columns' => ['Title'],
                    ],
                    'default_sort_composite' => [
                        'type' => 'index',
                        'columns' => ['Sort DESC', 'ID ASC', 'Title ASC'],
                    ],
                ],
            ],
            'associative array, multiple fields, ID at end' => [
                'defaultSort' => [
                    '"Sort"' => 'DESC',
                    '"Title"' => 'ASC',
                    'ID',
                ],
                'expectedIndexes' => [
                    'Sort' => [
                        'type' => 'index',
                        'columns' => ['Sort'],
                    ],
                    'Title' => [
                        'type' => 'index',
                        'columns' => ['Title'],
                    ],
                    'default_sort_composite' => [
                        'type' => 'index',
                        'columns' => ['Sort DESC', 'Title ASC'],
                    ],
                ],
            ],
            // DBText cannot be used in indexes
            'a few different field types in sort' => [
                'defaultSort' => [
                    '"Sort"' => 'DESC',
                    'SomeDBHtmlVarchar' => 'ASC',
                    '"SomeDBText"' => 'DESC',
                    '"SomeDBHtmlText"' => 'ASC',
                    'Title' => 'ASC'
                ],
                'expectedIndexes' => [
                    'Sort' => [
                        'type' => 'index',
                        'columns' => ['Sort'],
                    ],
                    'SomeDBHtmlVarchar' => [
                        'type' => 'index',
                        'columns' => ['SomeDBHtmlVarchar'],
                    ],
                    'Title' => [
                        'type' => 'index',
                        'columns' => ['Title'],
                    ],
                    'default_sort_composite' => [
                        'type' => 'index',
                        'columns' => ['Sort DESC', 'SomeDBHtmlVarchar ASC'],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('provideSortFieldBecomesIndexes')]
    public function testSortFieldBecomeIndexes(string|array $defaultSort, array $expectedIndexes): void
    {
        // Check the default index is what we expect and then reset to prep the test
        $indexes = DataObject::getSchema()->databaseIndexes(SortedObject::class);
        $this->assertContains([
            'type' => 'index',
            'columns' => ['Sort'],
        ], $indexes);
        $this->assertNotContains([
            'type' => 'index',
            'columns' => ['Title'],
        ], $indexes);
        DataObject::getSchema()->reset();

        // Set the test sort value and check the indexes match expected values
        SortedObject::config()->set('default_sort', $defaultSort);
        $indexes = DataObject::getSchema()->databaseIndexes(SortedObject::class);
        foreach ($expectedIndexes as $index => $spec) {
            $this->assertArrayHasKey($index, $indexes);
            $this->assertSame($spec, $indexes[$index]);
        }
    }

    public static function provideSortFieldIndexMode(): array
    {
        return [
            [
                'mode' => DataObjectSchema::SORT_INDEX_MODE_NONE,
                'expectedIndexes' => [
                    'Sort' => null,
                    'Title' => null,
                    'default_sort_composite' => null,
                ],
            ],
            [
                'mode' => DataObjectSchema::SORT_INDEX_MODE_BOTH,
                'expectedIndexes' => [
                    'Sort' => [
                        'type' => 'index',
                        'columns' => ['Sort'],
                    ],
                    'Title' => [
                        'type' => 'index',
                        'columns' => ['Title'],
                    ],
                    'default_sort_composite' => [
                        'type' => 'index',
                        'columns' => ['Sort DESC', 'Title ASC'],
                    ],
                ],
            ],
            [
                'mode' => DataObjectSchema::SORT_INDEX_MODE_COMPOSITE,
                'expectedIndexes' => [
                    'Sort' => null,
                    'Title' => null,
                    'default_sort_composite' => [
                        'type' => 'index',
                        'columns' => ['Sort DESC', 'Title ASC'],
                    ],
                ],
            ],
            [
                'mode' => DataObjectSchema::SORT_INDEX_MODE_SINGLE,
                'expectedIndexes' => [
                    'Sort' => [
                        'type' => 'index',
                        'columns' => ['Sort'],
                    ],
                    'Title' => [
                        'type' => 'index',
                        'columns' => ['Title'],
                    ],
                    'default_sort_composite' => null,
                ],
            ],
        ];
    }

    #[DataProvider('provideSortFieldIndexMode')]
    public function testSortFieldIndexMode(string $mode, array $expectedIndexes): void
    {
        // Check the default index is what we expect and then reset to prep the test
        $indexes = DataObject::getSchema()->databaseIndexes(SortedObject::class);
        $this->assertContains([
            'type' => 'index',
            'columns' => ['Sort'],
        ], $indexes);
        DataObject::getSchema()->reset();

        // Set the test configuration and check the indexes match expected values
        SortedObject::config()->set('default_sort', 'Sort DESC, Title');
        SortedObject::config()->set('default_sort_index_mode', $mode);
        $indexes = DataObject::getSchema()->databaseIndexes(SortedObject::class);
        foreach ($expectedIndexes as $index => $spec) {
            if ($spec === null) {
                $this->assertArrayNotHasKey($index, $indexes);
            } else {
                $this->assertArrayHasKey($index, $indexes);
                $this->assertSame($spec, $indexes[$index]);
            }
        }
    }

    public function testOverrideSortIndex(): void
    {
        // Check the default index is what we expect and then reset to prep the test
        $indexes = DataObject::getSchema()->databaseIndexes(SortedObject::class);
        $this->assertContains([
            'type' => 'index',
            'columns' => ['Sort'],
        ], $indexes);
        DataObject::getSchema()->reset();

        // Set the test index config and check the index matches expected values
        SortedObject::config()->merge('indexes', [
            'Sort' => [
                'type' => 'unique',
                'columns' => ['Sort'],
            ],
        ]);
        $indexes = DataObject::getSchema()->databaseIndexes(SortedObject::class);
        $this->assertArrayHasKey('Sort', $indexes);
        $this->assertSame([
            'type' => 'unique',
            'columns' => ['Sort'],
        ], $indexes['Sort']);
    }

    public function testIndexesDirectionIsRespected(): void
    {
        $table = DataObject::getSchema()->tableName(AllIndexes::class);
        $schema = [
            'type' => 'index',
            'columns' => ['Title DESC'],
        ];
        $indexSchema = DataObject::getSchema()->databaseIndexes(AllIndexes::class);
        $this->assertEquals($schema, $indexSchema['IndexDesc']);

        $schema['name'] = 'IndexDesc';
        $actualSchema = DB::get_schema()->indexList($table);
        // Use assertEqualsCanonicalizing because the order doesn't matter
        // and the indexes in the `columns` array are different.
        $this->assertEqualsCanonicalizing($schema, $actualSchema['IndexDesc']);
    }
}
