<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;

/**
 * @skipUpgrade
 */
class DataQueryTest extends SapphireTest
{

    protected static $fixture_file = 'DataQueryTest.yml';

    protected static $extra_dataobjects = array(
        DataQueryTest\ObjectA::class,
        DataQueryTest\ObjectB::class,
        DataQueryTest\ObjectC::class,
        DataQueryTest\ObjectD::class,
        DataQueryTest\ObjectE::class,
        DataQueryTest\ObjectF::class,
        DataQueryTest\ObjectG::class,
        SQLSelectTest\TestObject::class,
        SQLSelectTest\TestBase::class,
        SQLSelectTest\TestChild::class,
    );

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
        $this->assertContains('"testc_DataQueryTest_C"."ID" = "DataQueryTest_B"."TestCID"', $dq->sql());

        $dq = new DataQuery(DataQueryTest\ObjectB::class);
        $dq->applyRelation('TestCTwo');
        $this->assertTrue($dq->query()->isJoinedTo('testctwo_DataQueryTest_C'));
        $this->assertContains('"testctwo_DataQueryTest_C"."ID" = "DataQueryTest_B"."TestCTwoID"', $dq->sql());
    }

    public function testApplyRelationDeepInheritance()
    {
        //test has_one relation
        $newDQ = new DataQuery(DataQueryTest\ObjectE::class);
        //apply a relation to a relation from an ancestor class
        $newDQ->applyRelation('TestA');
        $this->assertTrue($newDQ->query()->isJoinedTo('DataQueryTest_C'));
        $this->assertContains('"testa_DataQueryTest_A"."ID" = "DataQueryTest_C"."TestAID"', $newDQ->sql($params));

        //test many_many relation

        //test many_many with separate inheritance
        $newDQ = new DataQuery(DataQueryTest\ObjectC::class);
        $baseDBTable = DataObject::getSchema()->baseDataTable(DataQueryTest\ObjectC::class);
        $newDQ->applyRelation('ManyTestAs');
        //check we are "joined" to the DataObject's table (there is no distinction between FROM or JOIN clauses)
        $this->assertTrue($newDQ->query()->isJoinedTo($baseDBTable));
        //check we are explicitly selecting "FROM" the DO's table
        $this->assertContains("FROM \"$baseDBTable\"", $newDQ->sql());

        //test many_many with shared inheritance
        $newDQ = new DataQuery(DataQueryTest\ObjectE::class);
        $baseDBTable = DataObject::getSchema()->baseDataTable(DataQueryTest\ObjectE::class);
        //check we are "joined" to the DataObject's table (there is no distinction between FROM or JOIN clauses)
        $this->assertTrue($newDQ->query()->isJoinedTo($baseDBTable));
        //check we are explicitly selecting "FROM" the DO's table
        $this->assertContains("FROM \"$baseDBTable\"", $newDQ->sql(), 'The FROM clause is missing from the query');
        $newDQ->applyRelation('ManyTestGs');
        //confirm we are still joined to the base table
        $this->assertTrue($newDQ->query()->isJoinedTo($baseDBTable));
        //double check it is the "FROM" clause
        $this->assertContains("FROM \"$baseDBTable\"", $newDQ->sql(), 'The FROM clause has been removed from the query');
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
        $this->assertContains(
            'ORDER BY "SQLSelectTest_DO"."Name" ASC, "_SortColumn0" DESC',
            $dq->sql($parameters)
        );
    }

    public function testDefaultSort()
    {
        $query = new DataQuery(DataQueryTest\ObjectE::class);
        $result = $query->column('Title');
        $this->assertEquals(array('First', 'Second', 'Last'), $result);
    }

    public function testDistinct()
    {
        $query = new DataQuery(DataQueryTest\ObjectE::class);
        $this->assertContains('SELECT DISTINCT', $query->sql($params), 'Query is set as distinct by default');

        $query = $query->distinct(false);
        $this->assertNotContains('SELECT DISTINCT', $query->sql($params), 'Query does not contain distinct');

        $query = $query->distinct(true);
        $this->assertContains('SELECT DISTINCT', $query->sql($params), 'Query contains distinct');
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

    /**
     * Tests that getFinalisedQuery can include all tables
     */
    public function testConditionsIncludeTables()
    {
        // Including filter on parent table only doesn't pull in second
        $query = new DataQuery(DataQueryTest\ObjectC::class);
        $query->sort('"SortOrder"');
        $query->where(
            array(
            '"DataQueryTest_C"."Title" = ?' => array('First')
            )
        );
        $result = $query->getFinalisedQuery(array('Title'));
        $from = $result->getFrom();
        $this->assertContains('DataQueryTest_C', array_keys($from));
        $this->assertNotContains('DataQueryTest_E', array_keys($from));

        // Including filter on sub-table requires it
        $query = new DataQuery(DataQueryTest\ObjectC::class);
        $query->sort('"SortOrder"');
        $query->where(
            array(
            '"DataQueryTest_C"."Title" = ? OR "DataQueryTest_E"."SortOrder" > ?' => array(
                'First', 2
            )
            )
        );
        $result = $query->getFinalisedQuery(array('Title'));
        $from = $result->getFrom();

        // Check that including "SortOrder" prompted inclusion of DataQueryTest_E table
        $this->assertContains('DataQueryTest_C', array_keys($from));
        $this->assertContains('DataQueryTest_E', array_keys($from));
        $arrayResult = iterator_to_array($result->execute());
        $first = array_shift($arrayResult);
        $this->assertNotNull($first);
        $this->assertEquals('First', $first['Title']);
        $second = array_shift($arrayResult);
        $this->assertNotNull($second);
        $this->assertEquals('Last', $second['Title']);
        $this->assertEmpty(array_shift($arrayResult));
    }
}
