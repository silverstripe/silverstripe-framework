<?php

namespace SilverStripe\ORM\Tests;

use InvalidArgumentException;
use LogicException;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Connect\MySQLDatabase;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\SQLite\SQLite3Database;
use SilverStripe\PostgreSQL\PostgreSQLDatabase;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\ORM\Connect\DatabaseException;
use SilverStripe\ORM\Tests\SQLSelectTest\CteDatesObject;
use SilverStripe\ORM\Tests\SQLSelectTest\CteRecursiveObject;
use PHPUnit\Framework\Attributes\DataProvider;

class SQLSelectTest extends SapphireTest
{

    protected static $fixture_file = 'SQLSelectTest.yml';

    protected static $extra_dataobjects = [
        SQLSelectTest\TestObject::class,
        SQLSelectTest\TestBase::class,
        SQLSelectTest\TestChild::class,
        SQLSelectTest\CteDatesObject::class,
        SQLSelectTest\CteRecursiveObject::class,
    ];

    protected $oldDeprecation = null;

    public function testCount()
    {

        //basic counting
        $qry = SQLSelectTest\TestObject::get()->dataQuery()->getFinalisedQuery();
        $ids = $this->allFixtureIDs(SQLSelectTest\TestObject::class);
        $count = $qry->count('"SQLSelectTest_DO"."ID"');
        $this->assertEquals(count($ids ?? []), $count);
        $this->assertIsInt($count);
        //test with `having`
        if (DB::get_conn() instanceof MySQLDatabase) {
            $qry->setSelect([
                'Date' => 'MAX("Date")',
                'Common' => '"Common"',
            ]);
            $qry->setGroupBy('"Common"');
            $qry->setHaving('"Date" > 2012-02-01');
            $count = $qry->count('"SQLSelectTest_DO"."ID"');
            $this->assertEquals(1, $count);
            $this->assertIsInt($count);
        }
    }
    public function testUnlimitedRowCount()
    {
        //basic counting
        $qry = SQLSelectTest\TestObject::get()->dataQuery()->getFinalisedQuery();
        $ids = $this->allFixtureIDs(SQLSelectTest\TestObject::class);
        $qry->setLimit(1);
        $count = $qry->unlimitedRowCount('"SQLSelectTest_DO"."ID"');
        $this->assertEquals(count($ids ?? []), $count);
        $this->assertIsInt($count);
        // Test without column - SQLSelect has different logic for this
        $count = $qry->unlimitedRowCount();
        $this->assertEquals(2, $count);
        $this->assertIsInt($count);
        //test with `having`
        if (DB::get_conn() instanceof MySQLDatabase) {
            $qry->setHaving('"Date" > 2012-02-01');
            $count = $qry->unlimitedRowCount('"SQLSelectTest_DO"."ID"');
            $this->assertEquals(1, $count);
            $this->assertIsInt($count);
        }
    }

    public static function provideIsEmpty()
    {
        return [
            [
                'query' => new SQLSelect(),
                'expected' => true,
            ],
            [
                'query' => new SQLSelect(from: 'someTable'),
                'expected' => false,
            ],
            [
                'query' => new SQLSelect(''),
                'expected' => true,
            ],
            [
                'query' => new SQLSelect('', 'someTable'),
                'expected' => true,
            ],
            [
                'query' => new SQLSelect('column', 'someTable'),
                'expected' => false,
            ],
            [
                'query' => new SQLSelect('value'),
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('provideIsEmpty')]
    public function testIsEmpty(SQLSelect $query, $expected)
    {
        $this->assertSame($expected, $query->isEmpty());
    }

    public function testEmptyQueryReturnsNothing()
    {
        $query = new SQLSelect();
        $this->assertSQLEquals('', $query->sql($parameters));
    }

    public static function provideSelectFrom()
    {
        return [
            [
                'from' => ['MyTable'],
                'expected' => 'SELECT * FROM MyTable',
            ],
            [
                'from' => ['MyTable', 'MySecondTable'],
                'expected' => 'SELECT * FROM MyTable, MySecondTable',
            ],
            [
                'from' => ['MyTable', 'INNER JOIN AnotherTable on AnotherTable.ID = MyTable.SomeFieldID'],
                'expected' => 'SELECT * FROM MyTable INNER JOIN AnotherTable on AnotherTable.ID = MyTable.SomeFieldID',
            ],
            [
                'from' => ['MyTable', 'MySecondTable', 'INNER JOIN AnotherTable on AnotherTable.ID = MyTable.SomeFieldID'],
                'expected' => 'SELECT * FROM MyTable, MySecondTable INNER JOIN AnotherTable on AnotherTable.ID = MyTable.SomeFieldID',
            ],
        ];
    }

    #[DataProvider('provideSelectFrom')]
    public function testSelectFrom(array $from, string $expected)
    {
        $query = new SQLSelect();
        $query->setFrom($from);
        $this->assertSQLEquals($expected, $query->sql($parameters));
    }

    public function testSelectFromUserSpecifiedFields()
    {
        $query = new SQLSelect();
        $query->setSelect(["Name", "Title", "Description"]);
        $query->setFrom("MyTable");
        $this->assertSQLEquals("SELECT Name, Title, Description FROM MyTable", $query->sql($parameters));
    }

    public function testSelectWithWhereClauseFilter()
    {
        $query = new SQLSelect();
        $query->setSelect(["Name","Meta"]);
        $query->setFrom("MyTable");
        $query->setWhere("Name = 'Name'");
        $query->addWhere("Meta = 'Test'");
        $this->assertSQLEquals(
            "SELECT Name, Meta FROM MyTable WHERE (Name = 'Name') AND (Meta = 'Test')",
            $query->sql($parameters)
        );
    }

    public function testSelectWithConstructorParameters()
    {
        $query = new SQLSelect(["Foo", "Bar"], "FooBarTable");
        $this->assertSQLEquals("SELECT Foo, Bar FROM FooBarTable", $query->sql($parameters));
        $query = new SQLSelect(["Foo", "Bar"], "FooBarTable", ["Foo = 'Boo'"]);
        $this->assertSQLEquals("SELECT Foo, Bar FROM FooBarTable WHERE (Foo = 'Boo')", $query->sql($parameters));
    }

    public function testSelectWithChainedMethods()
    {
        $query = new SQLSelect();
        $query->setSelect("Name", "Meta")->setFrom("MyTable")->setWhere("Name = 'Name'")->addWhere("Meta = 'Test'");
        $this->assertSQLEquals(
            "SELECT Name, Meta FROM MyTable WHERE (Name = 'Name') AND (Meta = 'Test')",
            $query->sql($parameters)
        );
    }

    public function testCanSortBy()
    {
        $query = new SQLSelect();
        $query->setSelect("Name", "Meta")->setFrom("MyTable")->setWhere("Name = 'Name'")->addWhere("Meta = 'Test'");
        $this->assertTrue($query->canSortBy('Name ASC'));
        $this->assertTrue($query->canSortBy('Name'));
    }

    /**
     * Test multiple order by SQL clauses.
     */
    public function testAddOrderBy()
    {
        $query = new SQLSelect();
        $query->setSelect('ID', "Title")->setFrom('Page')->addOrderBy('(ID % 2)  = 0', 'ASC')->addOrderBy('ID > 50', 'ASC');
        $this->assertSQLEquals(
            'SELECT ID, Title, (ID % 2)  = 0 AS "_SortColumn0", ID > 50 AS "_SortColumn1" FROM Page ORDER BY "_SortColumn0" ASC, "_SortColumn1" ASC',
            $query->sql($parameters)
        );
    }

    public function testSelectWithChainedFilterParameters()
    {
        $query = new SQLSelect();
        $query->setSelect(["Name","Meta"])->setFrom("MyTable");
        $query->setWhere("Name = 'Name'")->addWhere("Meta = 'Test'")->addWhere("Beta != 'Gamma'");
        $this->assertSQLEquals(
            "SELECT Name, Meta FROM MyTable WHERE (Name = 'Name') AND (Meta = 'Test') AND (Beta != 'Gamma')",
            $query->sql($parameters)
        );
    }

    public function testSelectWithLimitClause()
    {
        if (!(DB::get_conn() instanceof MySQLDatabase || DB::get_conn() instanceof SQLite3Database
            || DB::get_conn() instanceof PostgreSQLDatabase)
        ) {
            $this->markTestIncomplete();
        }

        $query = new SQLSelect();
        $query->setFrom("MyTable");
        $query->setLimit(99);
        $this->assertSQLEquals("SELECT * FROM MyTable LIMIT 99", $query->sql($parameters));

        // array limit with start (MySQL specific)
        $query = new SQLSelect();
        $query->setFrom("MyTable");
        $query->setLimit(99, 97);
        $this->assertSQLEquals("SELECT * FROM MyTable LIMIT 99 OFFSET 97", $query->sql($parameters));
    }


    public function testSelectWithOrderbyClause()
    {
        $query = new SQLSelect();
        $query->setFrom("MyTable");
        $query->setOrderBy('MyName');
        $this->assertSQLEquals('SELECT * FROM MyTable ORDER BY MyName ASC', $query->sql($parameters));

        $query = new SQLSelect();
        $query->setFrom("MyTable");
        $query->setOrderBy('MyName desc');
        $this->assertSQLEquals('SELECT * FROM MyTable ORDER BY MyName DESC', $query->sql($parameters));

        $query = new SQLSelect();
        $query->setFrom("MyTable");
        $query->setOrderBy('MyName ASC, Color DESC');
        $this->assertSQLEquals('SELECT * FROM MyTable ORDER BY MyName ASC, Color DESC', $query->sql($parameters));

        $query = new SQLSelect();
        $query->setFrom("MyTable");
        $query->setOrderBy('MyName ASC, Color');
        $this->assertSQLEquals('SELECT * FROM MyTable ORDER BY MyName ASC, Color ASC', $query->sql($parameters));

        $query = new SQLSelect();
        $query->setFrom("MyTable");
        $query->setOrderBy(['MyName' => 'desc']);
        $this->assertSQLEquals('SELECT * FROM MyTable ORDER BY MyName DESC', $query->sql($parameters));

        $query = new SQLSelect();
        $query->setFrom("MyTable");
        $query->setOrderBy(['MyName' => 'desc', 'Color']);
        $this->assertSQLEquals('SELECT * FROM MyTable ORDER BY MyName DESC, Color ASC', $query->sql($parameters));

        $query = new SQLSelect();
        $query->setFrom("MyTable");
        $query->setOrderBy('implode("MyName","Color")');
        $this->assertSQLEquals(
            'SELECT *, implode("MyName","Color") AS "_SortColumn0" FROM MyTable ORDER BY "_SortColumn0" ASC',
            $query->sql($parameters)
        );

        $query = new SQLSelect();
        $query->setFrom("MyTable");
        $query->setOrderBy('implode("MyName","Color") DESC');
        $this->assertSQLEquals(
            'SELECT *, implode("MyName","Color") AS "_SortColumn0" FROM MyTable ORDER BY "_SortColumn0" DESC',
            $query->sql($parameters)
        );

        $query = new SQLSelect();
        $query->setFrom("MyTable");
        $query->setOrderBy('RAND()');
        $this->assertSQLEquals(
            'SELECT *, RAND() AS "_SortColumn0" FROM MyTable ORDER BY "_SortColumn0" ASC',
            $query->sql($parameters)
        );

        $query = new SQLSelect();
        $query->setFrom("MyTable");
        $query->addFrom('INNER JOIN SecondTable USING (ID)');
        $query->addFrom('INNER JOIN ThirdTable USING (ID)');
        $query->setOrderBy('MyName');
        $this->assertSQLEquals(
            'SELECT * FROM MyTable '
            . 'INNER JOIN SecondTable USING (ID) '
            . 'INNER JOIN ThirdTable USING (ID) '
            . 'ORDER BY MyName ASC',
            $query->sql($parameters)
        );
    }

    public function testNullLimit()
    {
        $query = new SQLSelect();
        $query->setFrom("MyTable");
        $query->setLimit(null);

        $this->assertSQLEquals(
            'SELECT * FROM MyTable',
            $query->sql($parameters)
        );
    }

    public function testZeroLimit()
    {
        $query = new SQLSelect();
        $query->setFrom("MyTable");
        $query->setLimit(0);

        $this->assertSQLEquals(
            'SELECT * FROM MyTable LIMIT 0',
            $query->sql($parameters)
        );
    }

    public function testNegativeLimit()
    {
        $this->expectException(\InvalidArgumentException::class);
        $query = new SQLSelect();
        $query->setLimit(-10);
    }

    public function testNegativeOffset()
    {
        $this->expectException(\InvalidArgumentException::class);
        $query = new SQLSelect();
        $query->setLimit(1, -10);
    }

    public function testNegativeOffsetAndLimit()
    {
        $this->expectException(\InvalidArgumentException::class);
        $query = new SQLSelect();
        $query->setLimit(-10, -10);
    }

    public function testReverseOrderBy()
    {
        $query = new SQLSelect();
        $query->setFrom('MyTable');

        // default is ASC
        $query->setOrderBy("Name");
        $query->reverseOrderBy();

        $this->assertSQLEquals('SELECT * FROM MyTable ORDER BY Name DESC', $query->sql($parameters));

        $query->setOrderBy("Name DESC");
        $query->reverseOrderBy();

        $this->assertSQLEquals('SELECT * FROM MyTable ORDER BY Name ASC', $query->sql($parameters));

        $query->setOrderBy(["Name" => "ASC"]);
        $query->reverseOrderBy();

        $this->assertSQLEquals('SELECT * FROM MyTable ORDER BY Name DESC', $query->sql($parameters));

        $query->setOrderBy(["Name" => 'DESC', 'Color' => 'asc']);
        $query->reverseOrderBy();

        $this->assertSQLEquals('SELECT * FROM MyTable ORDER BY Name ASC, Color DESC', $query->sql($parameters));

        $query->setOrderBy('implode("MyName","Color") DESC');
        $query->reverseOrderBy();

        $this->assertSQLEquals(
            'SELECT *, implode("MyName","Color") AS "_SortColumn0" FROM MyTable ORDER BY "_SortColumn0" ASC',
            $query->sql($parameters)
        );
    }

    public function testFiltersOnID()
    {
        $query = new SQLSelect();
        $query->setWhere("ID = 5");
        $this->assertTrue(
            $query->filtersOnID(),
            "filtersOnID() is true with simple unquoted column name"
        );

        $query = new SQLSelect();
        $query->setWhere('"ID" = 5');
        $this->assertTrue(
            $query->filtersOnID(),
            "filtersOnID() is true with simple quoted column name"
        );

        $query = new SQLSelect();
        $query->setWhere(['"ID"' => 4]);
        $this->assertTrue(
            $query->filtersOnID(),
            "filtersOnID() is true with parameterised quoted column name"
        );

        $query = new SQLSelect();
        $query->setWhere(['"ID" = ?' => 4]);
        $this->assertTrue(
            $query->filtersOnID(),
            "filtersOnID() is true with parameterised quoted column name"
        );

        $query = new SQLSelect();
        $query->setWhere('"ID" IN (5,4)');
        $this->assertTrue(
            $query->filtersOnID(),
            "filtersOnID() is true with WHERE ID IN"
        );

        $query = new SQLSelect();
        $query->setWhere(['"ID" IN ?' => [1,2]]);
        $this->assertTrue(
            $query->filtersOnID(),
            "filtersOnID() is true with parameterised WHERE ID IN"
        );

        $query = new SQLSelect();
        $query->setWhere("ID=5");
        $this->assertTrue(
            $query->filtersOnID(),
            "filtersOnID() is true with simple unquoted column name and no spaces in equals sign"
        );

        $query = new SQLSelect();
        $query->setWhere("Identifier = 5");
        $this->assertFalse(
            $query->filtersOnID(),
            "filtersOnID() is false with custom column name (starting with 'id')"
        );

        $query = new SQLSelect();
        $query->setWhere("ParentID = 5");
        $this->assertFalse(
            $query->filtersOnID(),
            "filtersOnID() is false with column name ending in 'ID'"
        );

        $query = new SQLSelect();
        $query->setWhere("MyTable.ID = 5");
        $this->assertTrue(
            $query->filtersOnID(),
            "filtersOnID() is true with table and column name"
        );

        $query = new SQLSelect();
        $query->setWhere("MyTable.ID = 5");
        $this->assertTrue(
            $query->filtersOnID(),
            "filtersOnID() is true with table and quoted column name "
        );
    }

    public function testFiltersOnFK()
    {
        $query = new SQLSelect();
        $query->setWhere("ID = 5");
        $this->assertFalse(
            $query->filtersOnFK(),
            "filtersOnFK() is true with simple unquoted column name"
        );

        $query = new SQLSelect();
        $query->setWhere("Identifier = 5");
        $this->assertFalse(
            $query->filtersOnFK(),
            "filtersOnFK() is false with custom column name (starting with 'id')"
        );

        $query = new SQLSelect();
        $query->setWhere("MyTable.ParentID = 5");
        $this->assertTrue(
            $query->filtersOnFK(),
            "filtersOnFK() is true with table and column name"
        );

        $query = new SQLSelect();
        $query->setWhere("MyTable.`ParentID`= 5");
        $this->assertTrue(
            $query->filtersOnFK(),
            "filtersOnFK() is true with table and quoted column name "
        );
    }

    public function testJoinSQL()
    {
        $query = new SQLSelect();
        $query->setFrom('MyTable');
        $query->addInnerJoin('MyOtherTable', 'MyOtherTable.ID = 2');
        $query->addRightJoin('MySecondTable', 'MyOtherTable.ID = MySecondTable.ID');
        $query->addLeftJoin('MyLastTable', 'MyOtherTable.ID = MyLastTable.ID');

        $this->assertSQLEquals(
            'SELECT * FROM MyTable ' .
            'INNER JOIN "MyOtherTable" ON MyOtherTable.ID = 2 ' .
            'RIGHT JOIN "MySecondTable" ON MyOtherTable.ID = MySecondTable.ID ' .
            'LEFT JOIN "MyLastTable" ON MyOtherTable.ID = MyLastTable.ID',
            $query->sql($parameters)
        );

        $query = new SQLSelect();
        $query->setFrom('MyTable');
        $query->addInnerJoin('MyOtherTable', 'MyOtherTable.ID = 2', 'table1');
        $query->addRightJoin('MySecondTable', 'MyOtherTable.ID = MySecondTable.ID', 'table2');
        $query->addLeftJoin('MyLastTable', 'MyOtherTable.ID = MyLastTable.ID', 'table3');

        $this->assertSQLEquals(
            'SELECT * FROM MyTable ' .
            'INNER JOIN "MyOtherTable" AS "table1" ON MyOtherTable.ID = 2 ' .
            'RIGHT JOIN "MySecondTable" AS "table2" ON MyOtherTable.ID = MySecondTable.ID ' .
            'LEFT JOIN "MyLastTable" AS "table3" ON MyOtherTable.ID = MyLastTable.ID',
            $query->sql($parameters)
        );
    }

    public function testJoinSubSelect()
    {

        $query = new SQLSelect();
        $query->setFrom('MyTable');
        $query->addInnerJoin(
            '(SELECT * FROM MyOtherTable)',
            'Mot.MyTableID = MyTable.ID',
            'Mot'
        );
        $query->addLeftJoin(
            '(SELECT MyLastTable.MyOtherTableID, COUNT(1) as MyLastTableCount FROM MyLastTable '
            . 'GROUP BY MyOtherTableID)',
            'Mlt.MyOtherTableID = Mot.ID',
            'Mlt'
        );
        $query->setOrderBy('COALESCE(Mlt.MyLastTableCount, 0) DESC');

        $this->assertSQLEquals(
            'SELECT *, COALESCE(Mlt.MyLastTableCount, 0) AS "_SortColumn0" FROM MyTable ' .
            'INNER JOIN (SELECT * FROM MyOtherTable) AS "Mot" ON Mot.MyTableID = MyTable.ID ' .
            'LEFT JOIN (SELECT MyLastTable.MyOtherTableID, COUNT(1) as MyLastTableCount FROM MyLastTable '
            . 'GROUP BY MyOtherTableID) AS "Mlt" ON Mlt.MyOtherTableID = Mot.ID ' .
            'ORDER BY "_SortColumn0" DESC',
            $query->sql($parameters)
        );
    }

    public function testSetWhereAny()
    {
        $query = new SQLSelect();
        $query->setFrom('MyTable');

        $query->setWhereAny(
            [
            'Monkey' => 'Chimp',
            'Color' => 'Brown'
            ]
        );
        $sql = $query->sql($parameters);
        $this->assertSQLEquals("SELECT * FROM MyTable WHERE ((Monkey = ?) OR (Color = ?))", $sql);
        $this->assertEquals(['Chimp', 'Brown'], $parameters);
    }

    public function testSelectFirst()
    {
        // Test first from sequence
        $query = new SQLSelect();
        $query->setFrom('"SQLSelectTest_DO"');
        $query->setOrderBy('"Name"');
        $result = $query->firstRow()->execute();

        $records = [];
        foreach ($result as $row) {
            $records[] = $row;
        }

        $this->assertCount(1, $records);
        $this->assertEquals('Object 1', $records[0]['Name']);

        // Test first from empty sequence
        $query = new SQLSelect();
        $query->setFrom('"SQLSelectTest_DO"');
        $query->setOrderBy('"Name"');
        $query->setWhere(['"Name"' => 'Nonexistent Object']);
        $result = $query->firstRow()->execute();

        $records = [];
        foreach ($result as $row) {
            $records[] = $row;
        }

        $this->assertCount(0, $records);

        // Test that given the last item, the 'first' in this list matches the last
        $query = new SQLSelect();
        $query->setFrom('"SQLSelectTest_DO"');
        $query->setOrderBy('"Name"');
        $query->setLimit(1, 1);
        $result = $query->firstRow()->execute();

        $records = [];
        foreach ($result as $row) {
            $records[] = $row;
        }

        $this->assertCount(1, $records);
        $this->assertEquals('Object 2', $records[0]['Name']);
    }

    public function testSelectLast()
    {
        // Test last in sequence
        $query = new SQLSelect();
        $query->setFrom('"SQLSelectTest_DO"');
        $query->setOrderBy('"Name"');
        $result = $query->lastRow()->execute();

        $records = [];
        foreach ($result as $row) {
            $records[] = $row;
        }

        $this->assertCount(1, $records);
        $this->assertEquals('Object 2', $records[0]['Name']);

        // Test last from empty sequence
        $query = new SQLSelect();
        $query->setFrom('"SQLSelectTest_DO"');
        $query->setOrderBy('"Name"');
        $query->setWhere(["\"Name\" = 'Nonexistent Object'"]);
        $result = $query->lastRow()->execute();

        $records = [];
        foreach ($result as $row) {
            $records[] = $row;
        }

        $this->assertCount(0, $records);

        // Test that given the first item, the 'last' in this list matches the first
        $query = new SQLSelect();
        $query->setFrom('"SQLSelectTest_DO"');
        $query->setOrderBy('"Name"');
        $query->setLimit(1);
        $result = $query->lastRow()->execute();

        $records = [];
        foreach ($result as $row) {
            $records[] = $row;
        }

        $this->assertCount(1, $records);
        $this->assertEquals('Object 1', $records[0]['Name']);
    }

    /**
     * Tests aggregate() function
     */
    public function testAggregate()
    {
        $query = new SQLSelect('"Common"');
        $query->setFrom('"SQLSelectTest_DO"');
        $query->setGroupBy('"Common"');

        $queryClone = $query->aggregate('COUNT(*)', 'cnt');
        $result = $queryClone->execute();
        $this->assertEquals([2], $result->column('cnt'));
    }

    /**
     * Tests that an ORDER BY is only added if a LIMIT is set.
     */
    public function testAggregateNoOrderByIfNoLimit()
    {
        $query = new SQLSelect();
        $query->setFrom('"SQLSelectTest_DO"');
        $query->setOrderBy('Common');
        $query->setLimit([]);

        $aggregate = $query->aggregate('MAX("ID")');
        $limit = $aggregate->getLimit();
        $this->assertEquals([], $aggregate->getOrderBy());
        $this->assertEquals([], $limit);

        $query = new SQLSelect();
        $query->setFrom('"SQLSelectTest_DO"');
        $query->setOrderBy('Common');
        $query->setLimit(2);

        $aggregate = $query->aggregate('MAX("ID")');
        $limit = $aggregate->getLimit();
        $this->assertEquals(['Common' => 'ASC'], $aggregate->getOrderBy());
        $this->assertEquals(['start' => 0, 'limit' => 2], $limit);
    }

    /**
     * Test that "_SortColumn0" is added for an aggregate in the ORDER BY
     * clause, in combination with a LIMIT and GROUP BY clause.
     * For some databases, like MSSQL, this is a complicated scenario
     * because a subselect needs to be done to query paginated data.
     */
    public function testOrderByContainingAggregateAndLimitOffset()
    {
        $query = new SQLSelect();
        $query->setSelect(['"Name"', '"Meta"']);
        $query->setFrom('"SQLSelectTest_DO"');
        $query->setOrderBy(['MAX("Date")']);
        $query->setGroupBy(['"Name"', '"Meta"']);
        $query->setLimit('1', '1');

        $records = [];
        foreach ($query->execute() as $record) {
            $records[] = $record;
        }

        $this->assertCount(1, $records);

        $this->assertEquals('Object 2', $records[0]['Name']);
        $this->assertEquals('2012-05-01 09:00:00', $records['0']['_SortColumn0']);
    }

    /**
     * Test that multiple order elements are maintained in the given order
     */
    public function testOrderByMultiple()
    {
        if (DB::get_conn() instanceof MySQLDatabase) {
            $query = new SQLSelect();
            $query->setSelect(['"Name"', '"Meta"']);
            $query->setFrom('"SQLSelectTest_DO"');
            $query->setOrderBy(['MID("Name", 8, 1) DESC', '"Name" ASC']);

            $records = [];
            foreach ($query->execute() as $record) {
                $records[] = $record;
            }

            $this->assertCount(2, $records);

            $this->assertEquals('Object 2', $records[0]['Name']);
            $this->assertEquals('2', $records[0]['_SortColumn0']);

            $this->assertEquals('Object 1', $records[1]['Name']);
            $this->assertEquals('1', $records[1]['_SortColumn0']);
        }
    }

    public function testSelect()
    {
        $query = new SQLSelect('"Title"', '"MyTable"');
        $query->addSelect('"TestField"');
        $this->assertSQLEquals(
            'SELECT "Title", "TestField" FROM "MyTable"',
            $query->sql()
        );

        // Test replacement of select
        $query->setSelect(
            [
            'Field' => '"Field"',
            'AnotherAlias' => '"AnotherField"'
            ]
        );
        $this->assertSQLEquals(
            'SELECT "Field", "AnotherField" AS "AnotherAlias" FROM "MyTable"',
            $query->sql()
        );

        // Check that ' as ' selects don't get mistaken as aliases
        $query->addSelect(
            [
            'Relevance' => "MATCH (Title, MenuTitle) AGAINST ('Two as One')"
            ]
        );
        $this->assertSQLEquals(
            'SELECT "Field", "AnotherField" AS "AnotherAlias", MATCH (Title, MenuTitle) AGAINST (' .
            '\'Two as One\') AS "Relevance" FROM "MyTable"',
            $query->sql()
        );
    }

    public function testSelectWithNoTable()
    {
        $query = new SQLSelect('200');
        $this->assertSQLEquals('SELECT 200 AS "200"', $query->sql());
        $this->assertSame([['200' => 200]], iterator_to_array($query->execute(), true));
    }

    /**
     * Test passing in a LIMIT with OFFSET clause string.
     */
    public function testLimitSetFromClauseString()
    {
        $query = new SQLSelect();
        $query->setSelect('*');
        $query->setFrom('"SQLSelectTest_DO"');

        $query->setLimit('20 OFFSET 10');
        $limit = $query->getLimit();
        $this->assertEquals(20, $limit['limit']);
        $this->assertEquals(10, $limit['start']);
    }

    public static function provideParameterisedJoinSQL()
    {
        return [
            [
                'joinMethod' => 'addInnerJoin',
                'joinType' => 'INNER',
            ],
            [
                'joinMethod' => 'addLeftJoin',
                'joinType' => 'LEFT',
            ],
            [
                'joinMethod' => 'addRightJoin',
                'joinType' => 'RIGHT',
            ],
        ];
    }

    #[DataProvider('provideParameterisedJoinSQL')]
    public function testParameterisedJoinSQL($joinMethod, $joinType)
    {
        $query = new SQLSelect();
        $query->setSelect(['"SQLSelectTest_DO"."Name"', '"SubSelect"."Count"']);
        $query->setFrom('"SQLSelectTest_DO"');
        $query->$joinMethod(
            '(SELECT "Title", COUNT(*) AS "Count" FROM "SQLSelectTestBase" GROUP BY "Title" HAVING "Title" NOT LIKE ?)',
            '"SQLSelectTest_DO"."Name" = "SubSelect"."Title"',
            'SubSelect',
            20,
            ['%MyName%']
        );
        $query->addWhere(['"SQLSelectTest_DO"."Date" > ?' => '2012-08-08 12:00']);

        $this->assertSQLEquals(
            'SELECT "SQLSelectTest_DO"."Name", "SubSelect"."Count"
			FROM "SQLSelectTest_DO" ' . $joinType . ' JOIN (SELECT "Title", COUNT(*) AS "Count" FROM "SQLSelectTestBase"
		   GROUP BY "Title" HAVING "Title" NOT LIKE ?) AS "SubSelect" ON "SQLSelectTest_DO"."Name" =
		   "SubSelect"."Title"
			WHERE ("SQLSelectTest_DO"."Date" > ?)',
            $query->sql($parameters)
        );
        $this->assertEquals(['%MyName%', '2012-08-08 12:00'], $parameters);
        $query->execute();
    }

    public static function provideUnion()
    {
        return [
            // Note that a default (null) UNION is identical to a DISTINCT UNION
            [
                'unionQuery' => new SQLSelect([1, 2]),
                'type' => null,
                'expected' => [
                    [1 => 1, 2 => 2],
                ],
            ],
            [
                'unionQuery' => new SQLSelect([1, 2]),
                'type' => SQLSelect::UNION_DISTINCT,
                'expected' => [
                    [1 => 1, 2 => 2],
                ],
            ],
            [
                'unionQuery' => new SQLSelect([1, 2]),
                'type' => SQLSelect::UNION_ALL,
                'expected' => [
                    [1 => 1, 2 => 2],
                    [1 => 1, 2 => 2],
                ],
            ],
            [
                'unionQuery' => new SQLSelect([1, 2]),
                'type' => 'tulips',
                'expected' => LogicException::class,
            ],
        ];
    }

    #[DataProvider('provideUnion')]
    public function testUnion(SQLSelect $unionQuery, ?string $type, string|array $expected)
    {
        if (is_string($expected)) {
            $this->expectException($expected);
            $this->expectExceptionMessage('Union $type must be one of the constants UNION_ALL or UNION_DISTINCT.');
        }

        $query = new SQLSelect([1, 2]);
        $query->addUnion($unionQuery, $type);

        $this->assertSame($expected, iterator_to_array($query->execute(), true));
    }

    public function testBaseTableAliases()
    {
        $query = SQLSelect::create('*', ['"MyTableAlias"' => '"MyTable"']);
        $sql = $query->sql();

        $this->assertSQLEquals('SELECT * FROM "MyTable" AS "MyTableAlias"', $sql);

        $query = SQLSelect::create('*', ['MyTableAlias' => '"MyTable"']);
        $sql = $query->sql();

        $this->assertSQLEquals('SELECT * FROM "MyTable" AS "MyTableAlias"', $sql);

        $query = SQLSelect::create('*', ['"MyTableAlias"' => '"MyTable"']);
        $query->addLeftJoin('OtherTable', '"Thing" = "OtherThing"', 'OtherTableAlias');
        $sql = $query->sql();

        $this->assertSQLEquals(
            'SELECT *
              FROM "MyTable" AS "MyTableAlias"
              LEFT JOIN "OtherTable" AS "OtherTableAlias" ON "Thing" = "OtherThing"',
            $sql
        );

        // This feature is a bug that used to exist in SS4 and was removed in SS5
        // so now we test it does not exist and we end up with incorrect SQL because of that
        // In SS4 the "explicitAlias" would be ignored
        $query = SQLSelect::create('*', [
            'MyTableAlias' => '"MyTable"',
            'explicitAlias' => '(SELECT * FROM "MyTable" where "something" = "whatever") as "CrossJoin"'
        ]);
        $sql = $query->sql();

        $this->assertSQLEquals(
            'SELECT * FROM "MyTable" AS "MyTableAlias", ' .
            '(SELECT * FROM "MyTable" where "something" = "whatever") as "CrossJoin" AS "explicitAlias"',
            $sql
        );
    }

    public static function provideWith()
    {
        // Each of these examples shows it working with aliased implicit columns, and with explicit CTE columns.
        // Most of these examples are derived from https://dev.mysql.com/doc/refman/8.4/en/with.html
        return [
            // Just a CTE, no union
            'basic CTE with aliased columns' => [
                'name' => 'cte',
                'query' => new SQLSelect(['col1' => 1, 'col2' => 2]),
                'cteFields' => [],
                'recursive' => false,
                'selectFields' => ['col1', 'col2'],
                'selectFrom' => 'cte',
                'extraManipulations' => [],
                'expected' => [['col1' => 1, 'col2' => 2]],
            ],
            'basic CTE with explicit columns' => [
                'name' => 'cte',
                'query' => new SQLSelect([1, 2]),
                'cteFields' => ['col1', 'col2'],
                'recursive' => false,
                'selectFields' => ['col1', 'col2'],
                'selectFrom' => 'cte',
                'extraManipulations' => [],
                'expected' => [['col1' => 1, 'col2' => 2]],
            ],
            // CTE with a simple union, non-recursive
            'basic unioned CTE with aliased columns' => [
                'name' => 'cte',
                'query' => (new SQLSelect(['col1' => 1, 'col2' => 2]))->addUnion(
                    new SQLSelect(['ignoredAlias1' => '3', 'ignoredAlias2' => '4']),
                    SQLSelect::UNION_ALL
                ),
                'cteFields' => [],
                'recursive' => false,
                'selectFields' => ['col1', 'col2'],
                'selectFrom' => 'cte',
                'extraManipulations' => [],
                'expected' => [
                    ['col1' => 1, 'col2' => 2],
                    ['col1' => 3, 'col2' => 4],
                ],
            ],
            'basic unioned CTE with explicit columns' => [
                'name' => 'cte',
                'query' => (new SQLSelect([1, 2]))->addUnion(new SQLSelect(['3', '4']), SQLSelect::UNION_ALL),
                'cteFields' => ['col1', 'col2'],
                'recursive' => false,
                'selectFields' => ['col1', 'col2'],
                'selectFrom' => 'cte',
                'extraManipulations' => [],
                'expected' => [
                    ['col1' => 1, 'col2' => 2],
                    ['col1' => 3, 'col2' => 4],
                ],
            ],
            // Recursive CTE with only one field in it
            'basic recursive CTE with aliased columns' => [
                'name' => 'cte',
                'query' => (new SQLSelect(['str' => "CAST('abc' AS CHAR(20))"]))->addUnion(
                    new SQLSelect(['ignoredAlias' => 'CONCAT(str, str)'], 'cte', ['LENGTH(str) < 10']),
                    SQLSelect::UNION_ALL
                ),
                'cteFields' => [],
                'recursive' => true,
                'selectFields' => '*',
                'selectFrom' => 'cte',
                'extraManipulations' => [],
                'expected' => [
                    ['str' => 'abc'],
                    ['str' => 'abcabc'],
                    ['str' => 'abcabcabcabc'],
                ],
            ],
            'basic recursive CTE with explicit columns' => [
                'name' => 'cte',
                'query' => (new SQLSelect("CAST('abc' AS CHAR(20))"))->addUnion(
                    new SQLSelect('CONCAT(str, str)', 'cte', ['LENGTH(str) < 10']),
                    SQLSelect::UNION_ALL
                ),
                'cteFields' => ['str'],
                'recursive' => true,
                'selectFields' => '*',
                'selectFrom' => 'cte',
                'extraManipulations' => [],
                'expected' => [
                    ['str' => 'abc'],
                    ['str' => 'abcabc'],
                    ['str' => 'abcabcabcabc'],
                ],
            ],
            // More complex recursive CTE
            'medium recursive CTE with aliased columns' => [
                'name' => 'fibonacci',
                'query' => (new SQLSelect(['n' => 1, 'fib_n' => 0, 'next_fib_n' => 1]))->addUnion(
                    new SQLSelect(['n + 1', 'next_fib_n', 'fib_n + next_fib_n'], 'fibonacci', ['n < 6']),
                    SQLSelect::UNION_ALL
                ),
                'cteFields' => [],
                'recursive' => true,
                'selectFields' => '*',
                'selectFrom' => 'fibonacci',
                'extraManipulations' => [],
                'expected' => [
                    ['n' => 1, 'fib_n' => 0, 'next_fib_n' => 1],
                    ['n' => 2, 'fib_n' => 1, 'next_fib_n' => 1],
                    ['n' => 3, 'fib_n' => 1, 'next_fib_n' => 2],
                    ['n' => 4, 'fib_n' => 2, 'next_fib_n' => 3],
                    ['n' => 5, 'fib_n' => 3, 'next_fib_n' => 5],
                    ['n' => 6, 'fib_n' => 5, 'next_fib_n' => 8],
                ],
            ],
            // SQLSelect dedupes select fields. Because of that, for this test we have to start from a sequence
            // that doesn't select duplicate values - otherwise we end up selecting "1, 0" instead of "1, 0, 1"
            // in the main CTE select expression.
            'medium recursive CTE with explicit columns' => [
                'name' => 'fibonacci',
                'query' => (new SQLSelect([3, 1, 2]))->addUnion(
                    new SQLSelect(['n + 1', 'next_fib_n', 'fib_n + next_fib_n'], 'fibonacci', ['n < 6']),
                    SQLSelect::UNION_ALL
                ),
                'cteFields' => ['n', 'fib_n', 'next_fib_n'],
                'recursive' => true,
                'selectFields' => '*',
                'selectFrom' => 'fibonacci',
                'extraManipulations' => [],
                'expected' => [
                    ['n' => 3, 'fib_n' => 1, 'next_fib_n' => 2],
                    ['n' => 4, 'fib_n' => 2, 'next_fib_n' => 3],
                    ['n' => 5, 'fib_n' => 3, 'next_fib_n' => 5],
                    ['n' => 6, 'fib_n' => 5, 'next_fib_n' => 8],
                ],
            ],
            // Validate that we can have a CTE with multiple fields, while only using one field in the result set
            'medium recursive CTE selecting only one column in the result' => [
                'name' => 'fibonacci',
                'query' => (new SQLSelect(['n' => 1, 'fib_n' => 0, 'next_fib_n' => 1]))->addUnion(
                    new SQLSelect(['n + 1', 'next_fib_n', 'fib_n + next_fib_n'], 'fibonacci', ['n < 6']),
                    SQLSelect::UNION_ALL
                ),
                'cteFields' => [],
                'recursive' => true,
                'selectFields' => 'fib_n',
                'selectFrom' => 'fibonacci',
                'extraManipulations' => [],
                'expected' => [
                    ['fib_n' => 0],
                    ['fib_n' => 1],
                    ['fib_n' => 1],
                    ['fib_n' => 2],
                    ['fib_n' => 3],
                    ['fib_n' => 5],
                ],
            ],
            // Using an actual database table, extrapolate missing data with a recursive query
            'complex recursive CTE with aliased columns' => [
                'name' => 'dates',
                'query' => (new SQLSelect(['date' => 'MIN("Date")'], "SQLSelectTestCteDates"))->addUnion(
                    new SQLSelect(
                        'date + INTERVAL 1 DAY',
                        'dates',
                        ['date + INTERVAL 1 DAY <= (SELECT MAX("Date") FROM "SQLSelectTestCteDates")']
                    ),
                    SQLSelect::UNION_ALL
                ),
                'cteFields' => [],
                'recursive' => true,
                'selectFields' => ['dates.date', 'sum_price' => 'COALESCE(SUM("Price"), 0)'],
                'selectFrom' => 'dates',
                'extraManipulations' => [
                    'addLeftJoin' => ['SQLSelectTestCteDates', 'dates.date = "SQLSelectTestCteDates"."Date"'],
                    'addOrderBy' => ['dates.date'],
                    'addGroupBy' => ['dates.date'],
                ],
                'expected' => [
                    ['date' => '2017-01-03', 'sum_price' => 300],
                    ['date' => '2017-01-04', 'sum_price' => 0],
                    ['date' => '2017-01-05', 'sum_price' => 0],
                    ['date' => '2017-01-06', 'sum_price' => 50],
                    ['date' => '2017-01-07', 'sum_price' => 0],
                    ['date' => '2017-01-08', 'sum_price' => 180],
                    ['date' => '2017-01-09', 'sum_price' => 0],
                    ['date' => '2017-01-10', 'sum_price' => 5],
                ],
            ],
            'complex recursive CTE with explicit columns' => [
                'name' => 'dates',
                'query' => (new SQLSelect('MIN("Date")', "SQLSelectTestCteDates"))->addUnion(
                    new SQLSelect(
                        'date + INTERVAL 1 DAY',
                        'dates',
                        ['date + INTERVAL 1 DAY <= (SELECT MAX("Date") FROM "SQLSelectTestCteDates")']
                    ),
                    SQLSelect::UNION_ALL
                ),
                'cteFields' => ['date'],
                'recursive' => true,
                'selectFields' => ['dates.date', 'sum_price' => 'COALESCE(SUM("Price"), 0)'],
                'selectFrom' => 'dates',
                'extraManipulations' => [
                    'addLeftJoin' => ['SQLSelectTestCteDates', 'dates.date = "SQLSelectTestCteDates"."Date"'],
                    'addOrderBy' => ['dates.date'],
                    'addGroupBy' => ['dates.date'],
                ],
                'expected' => [
                    ['date' => '2017-01-03', 'sum_price' => 300],
                    ['date' => '2017-01-04', 'sum_price' => 0],
                    ['date' => '2017-01-05', 'sum_price' => 0],
                    ['date' => '2017-01-06', 'sum_price' => 50],
                    ['date' => '2017-01-07', 'sum_price' => 0],
                    ['date' => '2017-01-08', 'sum_price' => 180],
                    ['date' => '2017-01-09', 'sum_price' => 0],
                    ['date' => '2017-01-10', 'sum_price' => 5],
                ],
            ],
            // Using an actual database table, get the ancestors of a given record with a recursive query
            'complex hierarchical CTE with aliased columns' => [
                'name' => 'hierarchy',
                'query' => (
                    new SQLSelect(
                        [
                            'parent_id' => '"SQLSelectTestCteRecursive"."ParentID"',
                            'sort_order' => 0,
                        ],
                        "SQLSelectTestCteRecursive",
                        [['"SQLSelectTestCteRecursive"."ParentID" > 0 AND "SQLSelectTestCteRecursive"."Title" = ?' => 'child of child1']]
                    )
                )->addUnion(
                    new SQLSelect(
                        [
                            '"SQLSelectTestCteRecursive"."ParentID"',
                            'sort_order + 1',
                        ],
                        // Note that we select both the CTE and the real table in the FROM statement.
                        // We could also select one of these and JOIN on the other.
                        ['"hierarchy"', '"SQLSelectTestCteRecursive"'],
                        ['"SQLSelectTestCteRecursive"."ParentID" > 0 AND "SQLSelectTestCteRecursive"."ID" = "hierarchy"."parent_id"']
                    ),
                    SQLSelect::UNION_ALL
                ),
                'cteFields' => [],
                'recursive' => true,
                'selectFields' => ['"SQLSelectTestCteRecursive"."Title"'],
                'selectFrom' => '"SQLSelectTestCteRecursive"',
                'extraManipulations' => [
                    'addInnerJoin' => ['hierarchy', '"SQLSelectTestCteRecursive"."ID" = "hierarchy"."parent_id"'],
                    'setOrderBy' => ['sort_order', 'ASC'],
                ],
                'expected' => [
                    ['Title' => 'child1'],
                    ['Title' => 'parent'],
                    ['Title' => 'grandparent'],
                ],
            ],
            'complex hierarchical CTE with explicit columns' => [
                'name' => 'hierarchy',
                'query' => (
                    new SQLSelect(
                        [
                            '"SQLSelectTestCteRecursive"."ParentID"',
                            0
                        ],
                        "SQLSelectTestCteRecursive",
                        [['"SQLSelectTestCteRecursive"."ParentID" > 0 AND "SQLSelectTestCteRecursive"."Title" = ?' => 'child of child1']]
                    )
                )->addUnion(
                    new SQLSelect(
                        [
                            '"SQLSelectTestCteRecursive"."ParentID"',
                            'sort_order + 1'
                        ],
                        ['"hierarchy"', '"SQLSelectTestCteRecursive"'],
                        ['"SQLSelectTestCteRecursive"."ParentID" > 0 AND "SQLSelectTestCteRecursive"."ID" = "hierarchy"."parent_id"']
                    ),
                    SQLSelect::UNION_ALL
                ),
                'cteFields' => ['parent_id', 'sort_order'],
                'recursive' => true,
                'selectFields' => ['"SQLSelectTestCteRecursive"."Title"'],
                'selectFrom' => '"SQLSelectTestCteRecursive"',
                'extraManipulations' => [
                    'addInnerJoin' => ['hierarchy', '"SQLSelectTestCteRecursive"."ID" = "hierarchy"."parent_id"'],
                    'setOrderBy' => ['sort_order', 'ASC'],
                ],
                'expected' => [
                    ['Title' => 'child1'],
                    ['Title' => 'parent'],
                    ['Title' => 'grandparent'],
                ],
            ],
        ];
    }

    #[DataProvider('provideWith')]
    public function testWith(
        string $name,
        SQLSelect $query,
        array $cteFields,
        bool $recursive,
        string|array $selectFields,
        string|array $selectFrom,
        array $extraManipulations,
        array $expected
    ) {
        if (!DB::get_conn()->supportsCteQueries()) {
            $this->markTestSkipped('The current database does not support WITH statements');
        }
        if ($recursive && !DB::get_conn()->supportsCteQueries(true)) {
            $this->markTestSkipped('The current database does not support recursive WITH statements');
        }

        $select = new SQLSelect($selectFields, $selectFrom);
        $select->addWith($name, $query, $cteFields, $recursive);

        foreach ($extraManipulations as $method => $args) {
            $select->$method(...$args);
        }

        $this->assertEquals($expected, iterator_to_array($select->execute(), true));
    }

    /**
     * Tests that we can have multiple WITH statements for a given SQLSelect object, and that
     * subsequent WITH statements can refer to one another.
     */
    public function testMultipleWith()
    {
        if (!DB::get_conn()->supportsCteQueries()) {
            $this->markTestSkipped('The current database does not support WITH statements');
        }

        $cte1 = new SQLSelect('"SQLSelectTestCteDates"."Price"', "SQLSelectTestCteDates");
        $cte2 = new SQLSelect('"SQLSelectTestCteRecursive"."Title"', "SQLSelectTestCteRecursive");
        $cte3 = new SQLSelect(['price' => 'price', 'title' => 'title'], ['cte1', 'cte2']);

        $select = new SQLSelect(['price', 'title'], 'cte3');
        $select->addWith('cte1', $cte1, ['price'])
            ->addWith('cte2', $cte2, ['title'])
            ->addWith('cte3', $cte3)
            ->addOrderBy(['price', 'title']);

        $expected = [];
        foreach (CteDatesObject::get()->sort('Price') as $priceRecord) {
            foreach (CteRecursiveObject::get()->sort('Title') as $titleRecord) {
                $expected[] = [
                    'price' => $priceRecord->Price,
                    'title' => $titleRecord->Title,
                ];
            }
        }

        $this->assertEquals($expected, iterator_to_array($select->execute(), true));
    }

    /**
     * Tests that a second WITH clause with a duplicate name triggers an exception.
     */
    public function testMultipleWithDuplicateName()
    {
        if (!DB::get_conn()->supportsCteQueries()) {
            $this->markTestSkipped('The current database does not support WITH statements');
        }

        $select = new SQLSelect();
        $select->addWith('cte', new SQLSelect());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('WITH clause with name \'cte\' already exists.');

        $select->addWith('cte', new SQLSelect());
    }

    public static function subqueryProvider()
    {
        return [
            'no-explicit-alias-string' => ['( SELECT DISTINCT "SQLSelectTest_DO"."ClassName" FROM "SQLSelectTest_DO") AS "FINAL"'],
            'no-alias-array' => [['( SELECT DISTINCT "SQLSelectTest_DO"."ClassName" FROM "SQLSelectTest_DO") AS "FINAL"']],
            'no-alias-array-numeric-key' => [[0 => '( SELECT DISTINCT "SQLSelectTest_DO"."ClassName" FROM "SQLSelectTest_DO") AS "FINAL"']],
            'explicit-alias-string' => [['FINAL' => '( SELECT DISTINCT "SQLSelectTest_DO"."ClassName" FROM "SQLSelectTest_DO")']],
        ];
    }

    #[DataProvider('subqueryProvider')]
    public function testSubqueries($subquery)
    {
        $query = new SQLSelect('*', $subquery);

        $actualSQL = $query->sql();

        $this->assertSQLEquals(
            'SELECT * FROM ( SELECT DISTINCT "SQLSelectTest_DO"."ClassName" FROM "SQLSelectTest_DO") AS "FINAL"',
            $actualSQL
        );
    }

    public static function addFromProvider()
    {
        return [
            'string' => [
                'MyTable', ['MyTable' => 'MyTable'],
                'Plain table name get alias automatic alias'
            ],
            'string padded with spaces' => [
                ' MyTable  ', [' MyTable  ' => ' MyTable  '],
                'Plain table name get alias automatic alias'
            ],
            'quoted string' => [
                '"MyTable"', ['MyTable' => '"MyTable"'],
                'Quoted table name get alias without the quotes'
            ],
            'underscore in table name string' => [
                '"My_Table_123"', ['My_Table_123' => '"My_Table_123"'],
                'Numbers and underscores are allowed in table names'
            ],
            'backtick string' => [
                '`MyTable`', ['MyTable' => '`MyTable`'],
                'Backtick quoted table name get alias without the quotes'
            ],
            'subquery string' => [
                ' (SELECT * from "FooBar") as FooBar ', [' (SELECT * from "FooBar") as FooBar '],
                'String that don\'t look like table name don\'t get alias'
            ],
            'array' => [
                ['MyTable'], ['MyTable'],
                'Arrays are passed through as is'
            ],
            'array-associative-key' => [
                ['MyTableAlias' => 'MyTable'], ['MyTableAlias' => 'MyTable'],
                'Associative arrays are passed through as is and aliases are preserved'
            ],
        ];
    }

    #[DataProvider('addFromProvider')]
    public function testAddFrom($input, $out, $message = ""): void
    {
        $query = new SQLSelect();
        $query->addFrom($input);
        $this->assertEquals($out, $query->getFrom(), $message);
    }

    public function testAddFromRetainPreviousData()
    {
        // Initial setup
        $query = new SQLSelect();
        $query->addFrom('MyTable');
        $query->addFrom('"MyOtherTable"');

        // This will override some value and add a new one
        $query->addFrom([
            'MyTable' => '(SELECT * FROM "MyTable" where "Foo" = "Bar")',
            'ThirdTable',
        ]);

        $this->assertEquals(
            [
                'MyTable' => '(SELECT * FROM "MyTable" where "Foo" = "Bar")',
                'MyOtherTable' => '"MyOtherTable"',
                'ThirdTable',
            ],
            $query->getFrom(),
            'MyTable entry got merge over, MyOtherTable was retained, ThirdTable was added'
        );
    }
}
