<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Tests\DataQueryTest\ObjectE;
use SilverStripe\Security\Member;

class DataQueryTest extends SapphireTest
{

    protected static $fixture_file = 'DataQueryTest.yml';

    protected static $extra_dataobjects = [
        DataQueryTest\DataObjectAddsToQuery::class,
        DataQueryTest\ObjectA::class,
        DataQueryTest\ObjectB::class,
        DataQueryTest\ObjectC::class,
        DataQueryTest\ObjectD::class,
        DataQueryTest\ObjectE::class,
        DataQueryTest\ObjectF::class,
        DataQueryTest\ObjectG::class,
        DataQueryTest\ObjectH::class,
        DataQueryTest\ObjectI::class,
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

    /**
     * Test the leftJoin() and innerJoin method of the DataQuery object
     */
    public function testJoins()
    {
        $dq = new DataQuery(Member::class);
        $dq->innerJoin("Group_Members", "\"Group_Members\".\"MemberID\" = \"Member\".\"ID\"");
        $this->assertSQLContains(
            "INNER JOIN \"Group_Members\" ON \"Group_Members\".\"MemberID\" = \"Member\".\"ID\"",
            $dq->sql($parameters)
        );

        $dq = new DataQuery(Member::class);
        $dq->leftJoin("Group_Members", "\"Group_Members\".\"MemberID\" = \"Member\".\"ID\"");
        $this->assertSQLContains(
            "LEFT JOIN \"Group_Members\" ON \"Group_Members\".\"MemberID\" = \"Member\".\"ID\"",
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
}
