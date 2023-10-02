<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\ORM\Tests\DataQueryTest\ObjectE;
use SilverStripe\Security\Member;

class DataQueryTest extends SapphireTest
{
    protected static $fixture_file = 'DataQueryTest.yml';

    protected static $extra_dataobjects = [
        DataQueryTest\DataObjectAddsToQuery::class,
        DataQueryTest\DateAndPriceObject::class,
        DataQueryTest\ObjectA::class,
        DataQueryTest\ObjectB::class,
        DataQueryTest\ObjectC::class,
        DataQueryTest\ObjectD::class,
        DataQueryTest\ObjectE::class,
        DataQueryTest\ObjectF::class,
        DataQueryTest\ObjectG::class,
        DataQueryTest\ObjectH::class,
        DataQueryTest\ObjectI::class,
        SQLSelectTest\CteRecursiveObject::class,
        SQLSelectTest\TestObject::class,
        SQLSelectTest\TestBase::class,
        SQLSelectTest\TestChild::class,
    ];

    public function testSortByJoinedFieldRetainsSourceInformation()
    {
        $bar = new DataQueryTest\ObjectC();
        $bar->Title = "Bar";
        $bar->write();

        $foo = new DataQueryTest\ObjectB();
        $foo->Title = "Foo";
        $foo->TestC = $bar->ID;
        $foo->write();

        $query = new DataQuery(DataQueryTest\ObjectB::class);
        $result = $query->leftJoin(
            'DataQueryTest_C',
            "\"DataQueryTest_B\".\"TestCID\" = \"DataQueryTest_B\".\"ID\""
        )->sort('"DataQueryTest_B"."Title"', 'ASC');

        $result = $result->execute()->record();
        $this->assertEquals('Foo', $result['Title']);
    }

    public function provideJoins()
    {
        return [
            [
                'joinMethod' => 'innerJoin',
                'joinType' => 'INNER',
            ],
            [
                'joinMethod' => 'leftJoin',
                'joinType' => 'LEFT',
            ],
            [
                'joinMethod' => 'rightJoin',
                'joinType' => 'RIGHT',
            ],
        ];
    }

    /**
     * @dataProvider provideJoins
     */
    public function testJoins($joinMethod, $joinType)
    {
        $dq = new DataQuery(Member::class);
        $dq->$joinMethod("Group_Members", "\"Group_Members\".\"MemberID\" = \"Member\".\"ID\"");
        $this->assertSQLContains(
            "$joinType JOIN \"Group_Members\" ON \"Group_Members\".\"MemberID\" = \"Member\".\"ID\"",
            $dq->sql($parameters)
        );
    }

    public function testApplyRelation()
    {
        // Test applyRelation with two has_ones pointing to the same class
        $dq = new DataQuery(DataQueryTest\ObjectB::class);
        $dq->applyRelation('TestC');
        $this->assertTrue($dq->query()->isJoinedTo('testc_DataQueryTest_C'));
        $this->assertStringContainsString('"testc_DataQueryTest_C"."ID" = "DataQueryTest_B"."TestCID"', $dq->sql());

        $dq = new DataQuery(DataQueryTest\ObjectB::class);
        $dq->applyRelation('TestCTwo');
        $this->assertTrue($dq->query()->isJoinedTo('testctwo_DataQueryTest_C'));
        $this->assertStringContainsString('"testctwo_DataQueryTest_C"."ID" = "DataQueryTest_B"."TestCTwoID"', $dq->sql());
    }

    public function testApplyRelationDeepInheritance()
    {
        //test has_one relation
        $newDQ = new DataQuery(DataQueryTest\ObjectE::class);
        //apply a relation to a relation from an ancestor class
        $newDQ->applyRelation('TestA');
        $this->assertTrue($newDQ->query()->isJoinedTo('DataQueryTest_C'));
        $this->assertStringContainsString('"testa_DataQueryTest_A"."ID" = "DataQueryTest_C"."TestAID"', $newDQ->sql($params));

        //test many_many relation

        //test many_many with separate inheritance
        $newDQ = new DataQuery(DataQueryTest\ObjectC::class);
        $baseDBTable = DataObject::getSchema()->baseDataTable(DataQueryTest\ObjectC::class);
        $newDQ->applyRelation('ManyTestAs');
        //check we are "joined" to the DataObject's table (there is no distinction between FROM or JOIN clauses)
        $this->assertTrue($newDQ->query()->isJoinedTo($baseDBTable));
        //check we are explicitly selecting "FROM" the DO's table
        $this->assertStringContainsString("FROM \"$baseDBTable\"", $newDQ->sql());

        //test many_many with shared inheritance
        $newDQ = new DataQuery(DataQueryTest\ObjectE::class);
        $baseDBTable = DataObject::getSchema()->baseDataTable(DataQueryTest\ObjectE::class);
        //check we are "joined" to the DataObject's table (there is no distinction between FROM or JOIN clauses)
        $this->assertTrue($newDQ->query()->isJoinedTo($baseDBTable));
        //check we are explicitly selecting "FROM" the DO's table
        $this->assertStringContainsString("FROM \"$baseDBTable\"", $newDQ->sql(), 'The FROM clause is missing from the query');
        $newDQ->applyRelation('ManyTestGs');
        //confirm we are still joined to the base table
        $this->assertTrue($newDQ->query()->isJoinedTo($baseDBTable));
        //double check it is the "FROM" clause
        $this->assertStringContainsString("FROM \"$baseDBTable\"", $newDQ->sql(), 'The FROM clause has been removed from the query');
        //another (potentially less crude check) for checking "FROM" clause
        $fromTables = $newDQ->query()->getFrom();
        $this->assertEquals('"' . $baseDBTable . '"', $fromTables[$baseDBTable]);
    }

    public function testRelationReturn()
    {
        $dq = new DataQuery(DataQueryTest\ObjectC::class);
        $this->assertEquals(
            DataQueryTest\ObjectA::class,
            $dq->applyRelation('TestA'),
            'DataQuery::applyRelation should return the name of the related object.'
        );
        $this->assertEquals(
            DataQueryTest\ObjectA::class,
            $dq->applyRelation('TestAs'),
            'DataQuery::applyRelation should return the name of the related object.'
        );
        $this->assertEquals(
            DataQueryTest\ObjectA::class,
            $dq->applyRelation('ManyTestAs'),
            'DataQuery::applyRelation should return the name of the related object.'
        );

        $this->assertEquals(
            DataQueryTest\ObjectB::class,
            $dq->applyRelation('TestB'),
            'DataQuery::applyRelation should return the name of the related object.'
        );
        $this->assertEquals(
            DataQueryTest\ObjectB::class,
            $dq->applyRelation('TestBs'),
            'DataQuery::applyRelation should return the name of the related object.'
        );
        $this->assertEquals(
            DataQueryTest\ObjectB::class,
            $dq->applyRelation('ManyTestBs'),
            'DataQuery::applyRelation should return the name of the related object.'
        );
        $newDQ = new DataQuery(DataQueryTest\ObjectE::class);
        $this->assertEquals(
            DataQueryTest\ObjectA::class,
            $newDQ->applyRelation('TestA'),
            'DataQuery::applyRelation should return the name of the related object.'
        );
    }

    public function testRelationOrderWithCustomJoin()
    {
        $dataQuery = new DataQuery(DataQueryTest\ObjectB::class);
        $dataQuery->innerJoin('DataQueryTest_D', '"DataQueryTest_D"."RelationID" = "DataQueryTest_B"."ID"');
        $dataQuery->execute();
        $this->assertTrue(true);
    }

    public function provideFieldCollision()
    {
        return [
            'allow collisions' => [true],
            'disallow collisions' => [false],
        ];
    }

    /**
     * @dataProvider provideFieldCollision
     */
    public function testFieldCollision($allowCollisions)
    {
        $dataQuery = new DataQuery(DataQueryTest\ObjectB::class);
        $dataQuery->selectField('COALESCE(NULL, 1) AS "Title"');
        $dataQuery->setAllowCollidingFieldStatements($allowCollisions);

        if ($allowCollisions) {
            $this->assertSQLContains('THEN "DataQueryTest_B"."Title" WHEN COALESCE(NULL, 1) AS "Title" IS NOT NULL THEN COALESCE(NULL, 1) AS "Title" ELSE NULL END AS "Title"', $dataQuery->sql());
        } else {
            $this->expectError();
            $this->expectErrorMessageMatches('/^Bad collision item /');
        }

        $dataQuery->getFinalisedQuery();
    }

    public function testDisjunctiveGroup()
    {
        $dq = new DataQuery(DataQueryTest\ObjectA::class);

        $dq->where('DataQueryTest_A.ID = 2');
        $subDq = $dq->disjunctiveGroup();
        $subDq->where('DataQueryTest_A.Name = \'John\'');
        $subDq->where('DataQueryTest_A.Name = \'Bob\'');

        $this->assertSQLContains(
            "WHERE (DataQueryTest_A.ID = 2) AND ((DataQueryTest_A.Name = 'John') OR (DataQueryTest_A.Name = 'Bob'))",
            $dq->sql($parameters)
        );
    }

    public function testConjunctiveGroup()
    {
        $dq = new DataQuery(DataQueryTest\ObjectA::class);

        $dq->where('DataQueryTest_A.ID = 2');
        $subDq = $dq->conjunctiveGroup();
        $subDq->where('DataQueryTest_A.Name = \'John\'');
        $subDq->where('DataQueryTest_A.Name = \'Bob\'');

        $this->assertSQLContains(
            "WHERE (DataQueryTest_A.ID = 2) AND ((DataQueryTest_A.Name = 'John') AND (DataQueryTest_A.Name = 'Bob'))",
            $dq->sql($parameters)
        );
    }

    /**
     * @todo Test paramaterised
     */
    public function testNestedGroups()
    {
        $dq = new DataQuery(DataQueryTest\ObjectA::class);

        $dq->where('DataQueryTest_A.ID = 2');
        $subDq = $dq->disjunctiveGroup();
        $subDq->where('DataQueryTest_A.Name = \'John\'');
        $subSubDq = $subDq->conjunctiveGroup();
        $subSubDq->where('DataQueryTest_A.Age = 18');
        $subSubDq->where('DataQueryTest_A.Age = 50');
        $subDq->where('DataQueryTest_A.Name = \'Bob\'');

        $this->assertSQLContains(
            "WHERE (DataQueryTest_A.ID = 2) AND ((DataQueryTest_A.Name = 'John') OR ((DataQueryTest_A.Age = 18) "
                . "AND (DataQueryTest_A.Age = 50)) OR (DataQueryTest_A.Name = 'Bob'))",
            $dq->sql($parameters)
        );
    }

    public function testEmptySubgroup()
    {
        $dq = new DataQuery(DataQueryTest\ObjectA::class);
        $dq->conjunctiveGroup();

        // Empty groups should have no where condition at all
        $this->assertSQLNotContains('WHERE', $dq->sql($parameters));
    }

    public function testSubgroupHandoff()
    {
        $dq = new DataQuery(DataQueryTest\ObjectA::class);
        $subDq = $dq->disjunctiveGroup();

        $orgDq = clone $dq;

        $subDq->sort('"DataQueryTest_A"."Name"');
        $orgDq->sort('"DataQueryTest_A"."Name"');

        $this->assertSQLEquals($dq->sql($parameters), $orgDq->sql($parameters));

        $subDq->limit(5, 7);
        $orgDq->limit(5, 7);

        $this->assertSQLEquals($dq->sql($parameters), $orgDq->sql($parameters));
    }

    public function testOrderByMultiple()
    {
        $dq = new DataQuery(SQLSelectTest\TestObject::class);
        $dq = $dq->sort('"Name" ASC, MID("Name", 8, 1) DESC');
        $this->assertStringContainsString(
            'ORDER BY "SQLSelectTest_DO"."Name" ASC, "_SortColumn0" DESC',
            $dq->sql($parameters)
        );
    }

    public function testDefaultSort()
    {
        $query = new DataQuery(DataQueryTest\ObjectE::class);
        $result = $query->column('Title');
        $this->assertEquals(['First', 'Second', 'Last'], $result);
    }

    public function testDistinct()
    {
        $query = new DataQuery(DataQueryTest\ObjectE::class);
        $this->assertStringContainsString('SELECT DISTINCT', $query->sql($params), 'Query is set as distinct by default');

        $query = $query->distinct(false);
        $this->assertStringNotContainsString('SELECT DISTINCT', $query->sql($params), 'Query does not contain distinct');

        $query = $query->distinct(true);
        $this->assertStringContainsString('SELECT DISTINCT', $query->sql($params), 'Query contains distinct');
    }

    public function testComparisonClauseInt()
    {
        DB::query("INSERT INTO \"DataQueryTest_F\" (\"SortOrder\") VALUES (2)");
        $query = new DataQuery(DataQueryTest\ObjectF::class);
        $query->where(DB::get_conn()->comparisonClause('"SortOrder"', '2'));
        $this->assertGreaterThan(0, $query->count(), "Couldn't find SortOrder");
        static::resetDBSchema(true);
    }

    public function testComparisonClauseDateFull()
    {
        DB::query("INSERT INTO \"DataQueryTest_F\" (\"MyDate\") VALUES ('1988-03-04 06:30')");
        $query = new DataQuery(DataQueryTest\ObjectF::class);
        $query->where(DB::get_conn()->comparisonClause('"MyDate"', '1988-03-04%'));
        $this->assertGreaterThan(0, $query->count(), "Couldn't find MyDate");
        static::resetDBSchema(true);
    }

    public function testSurrogateFieldSort()
    {
        $query = new DataQuery(DataQueryTest\ObjectE::class);
        $query->sort(
            sprintf(
                '(case when "Title" = %s then 1 else 0 end)',
                DB::get_conn()->quoteString('Second')
            ),
            'DESC',
            true
        );
        $query->sort('SortOrder', 'ASC', false);
        $query->sort(
            sprintf(
                '(case when "Title" = %s then 0 else 1 end)',
                DB::get_conn()->quoteString('Fourth')
            ),
            'DESC',
            false
        );
        $this->assertEquals(
            $query->execute()->column('Title'),
            $query->column('Title')
        );
    }

    public function testCustomFieldWithAliasSort()
    {
        $query = new DataQuery(DataQueryTest\ObjectE::class);
        $query->selectField(sprintf(
            '(case when "Title" = %s then 1 else 0 end)',
            DB::get_conn()->quoteString('Second')
        ), 'CustomColumn');
        $query->sort('CustomColumn', 'DESC', true);
        $query->sort('SortOrder', 'ASC', false);
        $this->assertEquals(
            ['Second', 'First', 'Last'],
            $query->column('Title')
        );
    }

    public function testComparisonClauseDateStartsWith()
    {
        DB::query("INSERT INTO \"DataQueryTest_F\" (\"MyDate\") VALUES ('1988-03-04 06:30')");
        $query = new DataQuery(DataQueryTest\ObjectF::class);
        $query->where(DB::get_conn()->comparisonClause('"MyDate"', '1988%'));
        $this->assertGreaterThan(0, $query->count(), "Couldn't find MyDate");
        static::resetDBSchema(true);
    }

    public function testComparisonClauseDateStartsPartial()
    {
        DB::query("INSERT INTO \"DataQueryTest_F\" (\"MyDate\") VALUES ('1988-03-04 06:30')");
        $query = new DataQuery(DataQueryTest\ObjectF::class);
        $query->where(DB::get_conn()->comparisonClause('"MyDate"', '%03-04%'));
        $this->assertGreaterThan(0, $query->count(), "Couldn't find MyDate");
        static::resetDBSchema(true);
    }

    public function testComparisonClauseTextCaseInsensitive()
    {
        DB::query("INSERT INTO \"DataQueryTest_F\" (\"MyString\") VALUES ('HelloWorld')");
        $query = new DataQuery(DataQueryTest\ObjectF::class);
        $query->where(DB::get_conn()->comparisonClause('"MyString"', 'helloworld'));
        $this->assertGreaterThan(0, $query->count(), "Couldn't find MyString");
        static::resetDBSchema(true);
    }

    public function testComparisonClauseTextCaseSensitive()
    {
        DB::query("INSERT INTO \"DataQueryTest_F\" (\"MyString\") VALUES ('HelloWorld')");
        $query = new DataQuery(DataQueryTest\ObjectF::class);
        $query->where(DB::get_conn()->comparisonClause('"MyString"', 'HelloWorld', false, false, true));
        $this->assertGreaterThan(0, $query->count(), "Couldn't find MyString");

        $query2 = new DataQuery(DataQueryTest\ObjectF::class);
        $query2->where(DB::get_conn()->comparisonClause('"MyString"', 'helloworld', false, false, true));
        $this->assertEquals(0, $query2->count(), "Found mystring. Shouldn't be able too.");
        static::resetDBSchema(true);
    }

    public function testAddToQueryIsCalled()
    {
        // Including filter on parent table only doesn't pull in second
        $query = new DataQuery(DataQueryTest\DataObjectAddsToQuery::class);
        $result = $query->getFinalisedQuery();
        // The `DBFieldAddsToQuery` test field adds a new field to the select query
        $this->assertArrayHasKey('FieldTwo2', $result->getSelect());
        $this->assertSame('"DataQueryTest_AddsToQuery"."FieldTwo"', $result->getSelect()['FieldTwo2']);
    }

    /**
     * Tests that getFinalisedQuery can include all tables
     */
    public function testConditionsIncludeTables()
    {
        // Including filter on parent table only doesn't pull in second
        $query = new DataQuery(DataQueryTest\ObjectC::class);
        $query->sort('"SortOrder"');
        $query->where(
            [
            '"DataQueryTest_C"."Title" = ?' => ['First']
            ]
        );
        $result = $query->getFinalisedQuery(['Title']);
        $from = $result->getFrom();
        $this->assertContains('DataQueryTest_C', array_keys($from ?? []));
        $this->assertNotContains('DataQueryTest_E', array_keys($from ?? []));

        // Including filter on sub-table requires it
        $query = new DataQuery(DataQueryTest\ObjectC::class);
        $query->sort('"SortOrder"');
        $query->where(
            ['"DataQueryTest_C"."Title" = ? OR "DataQueryTest_E"."SortOrder" > ?' => ['First', 2]]
        );
        $result = $query->getFinalisedQuery(['Title']);
        $from = $result->getFrom();

        // Check that including "SortOrder" prompted inclusion of DataQueryTest_E table
        $this->assertContains('DataQueryTest_C', array_keys($from ?? []));
        $this->assertContains('DataQueryTest_E', array_keys($from ?? []));
        $arrayResult = iterator_to_array($result->execute());
        $first = array_shift($arrayResult);
        $this->assertNotNull($first);
        $this->assertEquals('First', $first['Title']);
        $second = array_shift($arrayResult);
        $this->assertNotNull($second);
        $this->assertEquals('Last', $second['Title']);
        $this->assertEmpty(array_shift($arrayResult));
    }

    public function testColumnReturnsAllValues()
    {
        $first = new DataQueryTest\ObjectA();
        $first->Name = 'Bar';
        $first->write();

        $second = new DataQueryTest\ObjectA();
        $second->Name = 'Foo';
        $second->write();

        $third = new DataQueryTest\ObjectA();
        $third->Name = 'Bar';
        $third->write();

        $result = DataQueryTest\ObjectA::get()->column('Name');
        $this->assertEquals(['Bar', 'Foo', 'Bar'], $result);
    }

    public function testColumnUniqueReturnsAllValues()
    {
        $first = new DataQueryTest\ObjectA();
        $first->Name = 'Bar';
        $first->write();

        $second = new DataQueryTest\ObjectA();
        $second->Name = 'Foo';
        $second->write();

        $third = new DataQueryTest\ObjectA();
        $third->Name = 'Bar';
        $third->write();

        $result = DataQueryTest\ObjectA::get()->columnUnique('Name');
        $this->assertCount(2, $result);
        $this->assertContains('Bar', $result);
        $this->assertContains('Foo', $result);
    }

    /**
     * Tests that sorting against multiple relationships is working
     */
    public function testMultipleRelationSort()
    {
        $query = new DataQuery(DataQueryTest\ObjectH::class);
        $query->applyRelation('ManyTestEs');
        $query->applyRelation('ManyTestIs');
        $query->sort([
            '"manytestes_DataQueryTest_E"."SortOrder"',
            '"manytestis_DataQueryTest_I"."SortOrder"',
            '"SortOrder"',
        ]);

        $titles = $query->column('Name');

        $this->assertEquals('First', $titles[0]);
        $this->assertEquals('Second', $titles[1]);
        $this->assertEquals('Last', $titles[2]);
    }

    public function testExistsCreatesFunctionalQueries()
    {
        $this->assertTrue(
            ObjectE::get()->exists(),
            'Query for ObjectE exists because there\'s more than 1 record'
        );
        $this->assertFalse(
            ObjectE::get()->where(['"Title" = ?' => 'Foo'])->exists(),
            'Query for ObjectE with Title Foo does NOT exists because there\'s no matching record'
        );
        $this->assertTrue(
            ObjectE::get()->dataQuery()->groupby('"SortOrder"')->exists(),
            'Existence of query for ObjectE is not affected by group by'
        );
        $this->assertTrue(
            ObjectE::get()->limit(1)->exists(),
            'Existence of query for ObjectE is not affected by limit if records are returned'
        );
        $this->assertFalse(
            ObjectE::get()->limit(4, 9999)->exists(),
            'Existence of query for ObjectE is affected by limit if no records are returned'
        );

        $query = new DataQuery(ObjectE::class);
        $this->assertTrue(
            $query->exists(),
            'exist returns true if query return results'
        );
        $query = new DataQuery(ObjectE::class);
        $this->assertFalse(
            $query->where(['"Title" = ?' => 'Foo'])->exists(),
            'exist returns false if there\'s no results'
        );
        $query = new DataQuery(ObjectE::class);
        $this->assertTrue(
            $query->groupby('"SortOrder"')->exists(),
            'exist is unaffected by group by'
        );
        $query = new DataQuery(ObjectE::class);
        $this->assertTrue(
            $query->limit(1)->exists(),
            'exist is unaffected by limit as long as one recard is returned'
        );
        $this->assertFalse(
            $query->limit(1, 9999)->exists(),
            'exist is false when a limit returns no results'
        );
    }

    public function provideWith()
    {
        return [
            // Simple scenarios to test auto-join functionality
            'naive CTE query with array join' => [
                'dataClass' => DataQueryTest\DateAndPriceObject::class,
                'name' => 'cte',
                'query' => new SQLSelect(
                    ['"DataQueryTest_DateAndPriceObject"."ID"'],
                    '"DataQueryTest_DateAndPriceObject"',
                    ['"DataQueryTest_DateAndPriceObject"."Price" > 200']
                ),
                'cteFields' => ['cte_id'],
                'recursive' => false,
                'extraManipulations' => [
                    'innerJoin' => ['cte', '"DataQueryTest_DateAndPriceObject"."ID" = "cte"."cte_id"'],
                ],
                'expectedItems' => [
                    'fixtures' => [
                        'obj4',
                        'obj5',
                    ],
                ],
            ],
            'naive CTE query with string join' => [
                'dataClass' => DataQueryTest\DateAndPriceObject::class,
                'name' => 'cte',
                'query' => new SQLSelect('200'),
                'cteFields' => ['value'],
                'recursive' => false,
                'extraManipulations' => [
                    'innerJoin' => ['cte', '"DataQueryTest_DateAndPriceObject"."Price" < "cte"."value"'],
                ],
                'expectedItems' => [
                    'fixtures' => [
                        'nullobj',
                        'obj1',
                        'obj2',
                    ]
                ],
            ],
            // Simple scenario to test where the query is another DataQuery
            'naive CTE query with DataQuery' => [
                'dataClass' => DataQueryTest\DateAndPriceObject::class,
                'name' => 'cte',
                'query' => DataQueryTest\ObjectF::class,
                'cteFields' => ['MyDate'],
                'recursive' => false,
                'extraManipulations' => [
                    'innerJoin' => ['cte', '"DataQueryTest_DateAndPriceObject"."Date" = "cte"."MyDate"'],
                ],
                'expectedItems' => [
                    'fixtures' => [
                        'obj1',
                        'obj2',
                    ]
                ],
            ],
            // Extrapolate missing data with a recursive query
            // Missing data will be returned as records with no ID
            'recursive CTE with extrapolated data' => [
                'dataClass' => DataQueryTest\DateAndPriceObject::class,
                'name' => 'dates',
                'query' => (new SQLSelect(
                    'MIN("DataQueryTest_DateAndPriceObject"."Date")',
                    "DataQueryTest_DateAndPriceObject",
                    '"DataQueryTest_DateAndPriceObject"."Date" IS NOT NULL'
                ))->addUnion(
                    new SQLSelect(
                        'Date + INTERVAL 1 DAY',
                        'dates',
                        ['Date + INTERVAL 1 DAY <= (SELECT MAX("DataQueryTest_DateAndPriceObject"."Date") FROM "DataQueryTest_DateAndPriceObject")']
                    ),
                    SQLSelect::UNION_ALL
                ),
                'cteFields' => ['Date'],
                'recursive' => true,
                'extraManipulations' => [
                    'selectField' => ['COALESCE("DataQueryTest_DateAndPriceObject"."Date", "dates"."Date")', 'Date'],
                    'setAllowCollidingFieldStatements' => [true],
                    'sort' => ['dates.Date'],
                    'rightJoin' => ['dates', '"DataQueryTest_DateAndPriceObject"."Date" = "dates"."Date"'],
                ],
                'expectedItems' => [
                    'data' => [
                        ['fixtureName' => 'obj5'],
                        ['fixtureName' => 'obj4'],
                        ['Date' => '2023-01-06'],
                        ['Date' => '2023-01-05'],
                        ['fixtureName' => 'obj3'],
                        ['Date' => '2023-01-03'],
                        ['fixtureName' => 'obj2'],
                        ['fixtureName' => 'obj1'],
                    ]
                ],
            ],
            // Get the ancestors of a given record with a recursive query
            'complex hierarchical CTE with explicit columns' => [
                'dataClass' => SQLSelectTest\CteRecursiveObject::class,
                'name' => 'hierarchy',
                'query' => (
                    new SQLSelect(
                        '"SQLSelectTestCteRecursive"."ParentID"',
                        "SQLSelectTestCteRecursive",
                        [['"SQLSelectTestCteRecursive"."ParentID" > 0 AND "SQLSelectTestCteRecursive"."Title" = ?' => 'child of child1']]
                    )
                )->addUnion(new SQLSelect(
                    '"SQLSelectTestCteRecursive"."ParentID"',
                    ['"hierarchy"', '"SQLSelectTestCteRecursive"'],
                    ['"SQLSelectTestCteRecursive"."ParentID" > 0 AND "SQLSelectTestCteRecursive"."ID" = "hierarchy"."parent_id"']
                )),
                'cteFields' => ['parent_id'],
                'recursive' => true,
                'extraManipulations' => [
                    'innerJoin' => ['hierarchy', '"SQLSelectTestCteRecursive"."ID" = "hierarchy"."parent_id"'],
                ],
                'expected' => [
                    'fixtures' => [
                        'grandparent',
                        'parent',
                        'child1',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideWith
     */
    public function testWith(
        string $dataClass,
        string $name,
        string|SQLSelect $query,
        array $cteFields,
        bool $recursive,
        array $extraManipulations,
        array $expectedItems
    ) {
        if (!DB::get_conn()->supportsCteQueries()) {
            $this->markTestSkipped('The current database does not support WITH clauses');
        }
        if ($recursive && !DB::get_conn()->supportsCteQueries(true)) {
            $this->markTestSkipped('The current database does not support recursive WITH clauses');
        }

        // We can't instantiate a DataQuery in a provider method because it requires the injector, which isn't
        // initialised that early. So we just pass the dataclass instead and instiate the query here.
        if (is_string($query)) {
            $query = new DataQuery($query);
        }

        $dataQuery = new DataQuery($dataClass);
        $dataQuery->with($name, $query, $cteFields, $recursive);

        foreach ($extraManipulations as $method => $args) {
            $dataQuery->$method(...$args);
        }

        $expected = [];

        if (isset($expectedItems['fixtures'])) {
            foreach ($expectedItems['fixtures'] as $fixtureName) {
                $expected[] = $this->idFromFixture($dataClass, $fixtureName);
            }
            $this->assertEquals($expected, $dataQuery->execute()->column('ID'));
        }

        if (isset($expectedItems['data'])) {
            foreach ($expectedItems['data'] as $data) {
                if (isset($data['fixtureName'])) {
                    $data = $this->objFromFixture($dataClass, $data['fixtureName'])->toMap();
                } else {
                    $data['ClassName'] = null;
                    $data['LastEdited'] = null;
                    $data['Created'] = null;
                    $data['Price'] = null;
                    $data['ID'] = null;
                }
                $expected[] = $data;
            }
            $this->assertListEquals($expected, new ArrayList(iterator_to_array($dataQuery->execute(), true)));
        }
    }

    /**
     * tests the WITH clause, using a DataQuery as the CTE query
     */
    public function testWithUsingDataQuery()
    {
        if (!DB::get_conn()->supportsCteQueries(true)) {
            $this->markTestSkipped('The current database does not support recursive WITH clauses');
        }
        $dataQuery = new DataQuery(SQLSelectTest\CteRecursiveObject::class);
        $cteQuery = new DataQuery(SQLSelectTest\CteRecursiveObject::class);
        $cteQuery->where([
            '"SQLSelectTestCteRecursive"."ParentID" > 0',
            '"SQLSelectTestCteRecursive"."Title" = ?' => 'child of child2'
        ]);
        $cteQuery->union(new SQLSelect(
            '"SQLSelectTestCteRecursive"."ParentID"',
            ['"hierarchy"', '"SQLSelectTestCteRecursive"'],
            [
                '"SQLSelectTestCteRecursive"."ParentID" > 0',
                '"SQLSelectTestCteRecursive"."ID" = "hierarchy"."ParentID"'
            ]
        ));
        $dataQuery->with('hierarchy', $cteQuery, ['ParentID'], true);
        $dataQuery->innerJoin('hierarchy', '"SQLSelectTestCteRecursive"."ID" = "hierarchy"."ParentID"');

        $expectedFixtures = [
            'child2',
            'parent',
            'grandparent',
        ];
        $expectedData = [];
        foreach ($expectedFixtures as $fixtureName) {
            $expectedData[] = $this->objFromFixture(SQLSelectTest\CteRecursiveObject::class, $fixtureName)->toMap();
        }
        $this->assertListEquals($expectedData, new ArrayList(iterator_to_array($dataQuery->execute(), true)));
    }

    /**
     * tests the WITH clause, using a DataQuery as the CTE query and as the unioned recursive query
     */
    public function testWithUsingOnlyDataQueries()
    {
        if (!DB::get_conn()->supportsCteQueries(true)) {
            $this->markTestSkipped('The current database does not support recursive WITH clauses');
        }
        $dataQuery = new DataQuery(SQLSelectTest\CteRecursiveObject::class);
        $cteQuery = new DataQuery(SQLSelectTest\CteRecursiveObject::class);
        $cteQuery->where([
            '"SQLSelectTestCteRecursive"."ParentID" > 0',
            '"SQLSelectTestCteRecursive"."Title" = ?' => 'child of child2'
        ]);
        $cteQuery->union((new DataQuery(SQLSelectTest\CteRecursiveObject::class))
            ->innerJoin('hierarchy', '"SQLSelectTestCteRecursive"."ID" = "hierarchy"."ParentID"')
            ->where('"SQLSelectTestCteRecursive"."ParentID" > 0')
            ->sort(null)
            ->distinct(false));
        // This test exists because previously when $cteFields was empty, it would cause an error with the above setup.
        $dataQuery->with('hierarchy', $cteQuery, [], true);
        $dataQuery->innerJoin('hierarchy', '"SQLSelectTestCteRecursive"."ID" = "hierarchy"."ParentID"');

        $expectedFixtures = [
            'child2',
            'parent',
            'grandparent',
        ];
        $expectedData = [];
        foreach ($expectedFixtures as $fixtureName) {
            $expectedData[] = $this->objFromFixture(SQLSelectTest\CteRecursiveObject::class, $fixtureName)->toMap();
        }
        $this->assertListEquals($expectedData, new ArrayList(iterator_to_array($dataQuery->execute(), true)));
    }

    /**
     * Tests that CTE queries have appropriate JOINs for subclass tables etc.
     * If `$query->query()->` was replaced with `$query->query->` in DataQuery::with(), this test would throw an exception.
     * @doesNotPerformAssertions
     */
    public function testWithUsingDataQueryAppliesRelations()
    {
        if (!DB::get_conn()->supportsCteQueries()) {
            $this->markTestSkipped('The current database does not support WITH clauses');
        }
        $dataQuery = new DataQuery(DataQueryTest\ObjectG::class);
        $cteQuery = new DataQuery(DataQueryTest\ObjectG::class);
        $cteQuery->where(['"DataQueryTest_G"."SubClassOnlyField" = ?' => 'This is the one']);
        $dataQuery->with('test_implicit_joins', $cteQuery, ['ID']);
        $dataQuery->innerJoin('test_implicit_joins', '"DataQueryTest_G"."ID" = "test_implicit_joins"."ID"');
        // This will throw an exception if it fails - it passes if there's no exception.
        $dataQuery->execute();
    }
}
