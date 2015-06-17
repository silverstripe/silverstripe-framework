<?php

/**
 * @package framework
 * @subpackage tests
 */
class SQLQueryTest extends SapphireTest {

	protected static $fixture_file = 'SQLQueryTest.yml';

	protected $extraDataObjects = array(
		'SQLQueryTest_DO',
		'SQLQueryTestBase',
		'SQLQueryTestChild'
	);
	
	protected $oldDeprecation = null;
	
	public function setUp() {
		parent::setUp();
		$this->oldDeprecation = Deprecation::dump_settings();
	}
	
	public function tearDown() {
		Deprecation::restore_settings($this->oldDeprecation);
		parent::tearDown();
	}

	public function testCount() {

		//basic counting
		$qry = SQLQueryTest_DO::get()->dataQuery()->getFinalisedQuery();
		$qry->setGroupBy('Common');
		$ids = $this->allFixtureIDs('SQLQueryTest_DO');
		$this->assertEquals(count($ids), $qry->count('"SQLQueryTest_DO"."ID"'));

		//test with `having`
		if (DB::get_conn() instanceof MySQLDatabase) {
			$qry->setHaving('"Date" > 2012-02-01');
			$this->assertEquals(1, $qry->count('"SQLQueryTest_DO"."ID"'));
		}
	}

	public function testEmptyQueryReturnsNothing() {
		$query = new SQLQuery();
		$this->assertSQLEquals('', $query->sql($parameters));
	}

	public function testSelectFromBasicTable() {
		$query = new SQLQuery();
		$query->setFrom('MyTable');
		$this->assertSQLEquals("SELECT * FROM MyTable", $query->sql($parameters));
		$query->addFrom('MyJoin');
		$this->assertSQLEquals("SELECT * FROM MyTable MyJoin", $query->sql($parameters));
	}

	public function testSelectFromUserSpecifiedFields() {
		$query = new SQLQuery();
		$query->setSelect(array("Name", "Title", "Description"));
		$query->setFrom("MyTable");
		$this->assertSQLEquals("SELECT Name, Title, Description FROM MyTable", $query->sql($parameters));
	}

	public function testSelectWithWhereClauseFilter() {
		$query = new SQLQuery();
		$query->setSelect(array("Name","Meta"));
		$query->setFrom("MyTable");
		$query->setWhere("Name = 'Name'");
		$query->addWhere("Meta = 'Test'");
		$this->assertSQLEquals(
			"SELECT Name, Meta FROM MyTable WHERE (Name = 'Name') AND (Meta = 'Test')",
			$query->sql($parameters)
		);
	}

	public function testSelectWithConstructorParameters() {
		$query = new SQLQuery(array("Foo", "Bar"), "FooBarTable");
		$this->assertSQLEquals("SELECT Foo, Bar FROM FooBarTable", $query->sql($parameters));
		$query = new SQLQuery(array("Foo", "Bar"), "FooBarTable", array("Foo = 'Boo'"));
		$this->assertSQLEquals("SELECT Foo, Bar FROM FooBarTable WHERE (Foo = 'Boo')", $query->sql($parameters));
	}

	public function testSelectWithChainedMethods() {
		$query = new SQLQuery();
		$query->setSelect("Name","Meta")->setFrom("MyTable")->setWhere("Name = 'Name'")->addWhere("Meta = 'Test'");
		$this->assertSQLEquals(
			"SELECT Name, Meta FROM MyTable WHERE (Name = 'Name') AND (Meta = 'Test')",
			$query->sql($parameters)
		);
	}

	public function testCanSortBy() {
		$query = new SQLQuery();
		$query->setSelect("Name","Meta")->setFrom("MyTable")->setWhere("Name = 'Name'")->addWhere("Meta = 'Test'");
		$this->assertTrue($query->canSortBy('Name ASC'));
		$this->assertTrue($query->canSortBy('Name'));
	}

	public function testSelectWithChainedFilterParameters() {
		$query = new SQLQuery();
		$query->setSelect(array("Name","Meta"))->setFrom("MyTable");
		$query->setWhere("Name = 'Name'")->addWhere("Meta = 'Test'")->addWhere("Beta != 'Gamma'");
		$this->assertSQLEquals(
			"SELECT Name, Meta FROM MyTable WHERE (Name = 'Name') AND (Meta = 'Test') AND (Beta != 'Gamma')",
			$query->sql($parameters)
		);
	}

	public function testSelectWithLimitClause() {
		if(!(DB::get_conn() instanceof MySQLDatabase || DB::get_conn() instanceof SQLite3Database
				|| DB::get_conn() instanceof PostgreSQLDatabase)) {
			$this->markTestIncomplete();
		}

		$query = new SQLQuery();
		$query->setFrom("MyTable");
		$query->setLimit(99);
		$this->assertSQLEquals("SELECT * FROM MyTable LIMIT 99", $query->sql($parameters));

		// array limit with start (MySQL specific)
		$query = new SQLQuery();
		$query->setFrom("MyTable");
		$query->setLimit(99, 97);
		$this->assertSQLEquals("SELECT * FROM MyTable LIMIT 99 OFFSET 97", $query->sql($parameters));
	}

	public function testSelectWithOrderbyClause() {
		$query = new SQLQuery();
		$query->setFrom("MyTable");
		$query->setOrderBy('MyName');
		$this->assertSQLEquals('SELECT * FROM MyTable ORDER BY MyName ASC', $query->sql($parameters));

		$query = new SQLQuery();
		$query->setFrom("MyTable");
		$query->setOrderBy('MyName desc');
		$this->assertSQLEquals('SELECT * FROM MyTable ORDER BY MyName DESC', $query->sql($parameters));

		$query = new SQLQuery();
		$query->setFrom("MyTable");
		$query->setOrderBy('MyName ASC, Color DESC');
		$this->assertSQLEquals('SELECT * FROM MyTable ORDER BY MyName ASC, Color DESC', $query->sql($parameters));

		$query = new SQLQuery();
		$query->setFrom("MyTable");
		$query->setOrderBy('MyName ASC, Color');
		$this->assertSQLEquals('SELECT * FROM MyTable ORDER BY MyName ASC, Color ASC', $query->sql($parameters));

		$query = new SQLQuery();
		$query->setFrom("MyTable");
		$query->setOrderBy(array('MyName' => 'desc'));
		$this->assertSQLEquals('SELECT * FROM MyTable ORDER BY MyName DESC', $query->sql($parameters));

		$query = new SQLQuery();
		$query->setFrom("MyTable");
		$query->setOrderBy(array('MyName' => 'desc', 'Color'));
		$this->assertSQLEquals('SELECT * FROM MyTable ORDER BY MyName DESC, Color ASC', $query->sql($parameters));

		$query = new SQLQuery();
		$query->setFrom("MyTable");
		$query->setOrderBy('implode("MyName","Color")');
		$this->assertSQLEquals(
			'SELECT *, implode("MyName","Color") AS "_SortColumn0" FROM MyTable ORDER BY "_SortColumn0" ASC',
			$query->sql($parameters));

		$query = new SQLQuery();
		$query->setFrom("MyTable");
		$query->setOrderBy('implode("MyName","Color") DESC');
		$this->assertSQLEquals(
			'SELECT *, implode("MyName","Color") AS "_SortColumn0" FROM MyTable ORDER BY "_SortColumn0" DESC',
			$query->sql($parameters));

		$query = new SQLQuery();
		$query->setFrom("MyTable");
		$query->setOrderBy('RAND()');
		$this->assertSQLEquals(
			'SELECT *, RAND() AS "_SortColumn0" FROM MyTable ORDER BY "_SortColumn0" ASC',
			$query->sql($parameters));

		$query = new SQLQuery();
		$query->setFrom("MyTable");
		$query->addFrom('INNER JOIN SecondTable USING (ID)');
		$query->addFrom('INNER JOIN ThirdTable USING (ID)');
		$query->setOrderBy('MyName');
		$this->assertSQLEquals(
			'SELECT * FROM MyTable '
			. 'INNER JOIN SecondTable USING (ID) '
			. 'INNER JOIN ThirdTable USING (ID) '
			. 'ORDER BY MyName ASC',
			$query->sql($parameters));
	}

	public function testNullLimit() {
		$query = new SQLQuery();
		$query->setFrom("MyTable");
		$query->setLimit(null);

		$this->assertSQLEquals(
			'SELECT * FROM MyTable',
			$query->sql($parameters)
		);
	}

	public function testZeroLimit() {
		$query = new SQLQuery();
		$query->setFrom("MyTable");
		$query->setLimit(0);

		$this->assertSQLEquals(
			'SELECT * FROM MyTable',
			$query->sql($parameters)
		);
	}

	public function testZeroLimitWithOffset() {
		if(!(DB::get_conn() instanceof MySQLDatabase || DB::get_conn() instanceof SQLite3Database
				|| DB::get_conn() instanceof PostgreSQLDatabase)) {
			$this->markTestIncomplete();
		}

		$query = new SQLQuery();
		$query->setFrom("MyTable");
		$query->setLimit(0, 99);

		$this->assertSQLEquals(
			'SELECT * FROM MyTable LIMIT 0 OFFSET 99',
			$query->sql($parameters)
		);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testNegativeLimit() {
		$query = new SQLQuery();
		$query->setLimit(-10);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testNegativeOffset() {
		$query = new SQLQuery();
		$query->setLimit(1, -10);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testNegativeOffsetAndLimit() {
		$query = new SQLQuery();
		$query->setLimit(-10, -10);
	}

	public function testReverseOrderBy() {
		$query = new SQLQuery();
		$query->setFrom('MyTable');

		// default is ASC
		$query->setOrderBy("Name");
		$query->reverseOrderBy();

		$this->assertSQLEquals('SELECT * FROM MyTable ORDER BY Name DESC',$query->sql($parameters));

		$query->setOrderBy("Name DESC");
		$query->reverseOrderBy();

		$this->assertSQLEquals('SELECT * FROM MyTable ORDER BY Name ASC',$query->sql($parameters));

		$query->setOrderBy(array("Name" => "ASC"));
		$query->reverseOrderBy();

		$this->assertSQLEquals('SELECT * FROM MyTable ORDER BY Name DESC',$query->sql($parameters));

		$query->setOrderBy(array("Name" => 'DESC', 'Color' => 'asc'));
		$query->reverseOrderBy();

		$this->assertSQLEquals('SELECT * FROM MyTable ORDER BY Name ASC, Color DESC',$query->sql($parameters));

		$query->setOrderBy('implode("MyName","Color") DESC');
		$query->reverseOrderBy();

		$this->assertSQLEquals(
			'SELECT *, implode("MyName","Color") AS "_SortColumn0" FROM MyTable ORDER BY "_SortColumn0" ASC',
			$query->sql($parameters));
	}

	public function testFiltersOnID() {
		$query = new SQLQuery();
		$query->setWhere("ID = 5");
		$this->assertTrue(
			$query->filtersOnID(),
			"filtersOnID() is true with simple unquoted column name"
		);

		$query = new SQLQuery();
		$query->setWhere("ID=5");
		$this->assertTrue(
			$query->filtersOnID(),
			"filtersOnID() is true with simple unquoted column name and no spaces in equals sign"
		);

		$query = new SQLQuery();
		$query->setWhere("Identifier = 5");
		$this->assertFalse(
			$query->filtersOnID(),
			"filtersOnID() is false with custom column name (starting with 'id')"
		);

		$query = new SQLQuery();
		$query->setWhere("ParentID = 5");
		$this->assertFalse(
			$query->filtersOnID(),
			"filtersOnID() is false with column name ending in 'ID'"
		);

		$query = new SQLQuery();
		$query->setWhere("MyTable.ID = 5");
		$this->assertTrue(
			$query->filtersOnID(),
			"filtersOnID() is true with table and column name"
		);

		$query = new SQLQuery();
		$query->setWhere("MyTable.ID = 5");
		$this->assertTrue(
			$query->filtersOnID(),
			"filtersOnID() is true with table and quoted column name "
		);
	}

	public function testFiltersOnFK() {
		$query = new SQLQuery();
		$query->setWhere("ID = 5");
		$this->assertFalse(
			$query->filtersOnFK(),
			"filtersOnFK() is true with simple unquoted column name"
		);

		$query = new SQLQuery();
		$query->setWhere("Identifier = 5");
		$this->assertFalse(
			$query->filtersOnFK(),
			"filtersOnFK() is false with custom column name (starting with 'id')"
		);

		$query = new SQLQuery();
		$query->setWhere("MyTable.ParentID = 5");
		$this->assertTrue(
			$query->filtersOnFK(),
			"filtersOnFK() is true with table and column name"
		);

		$query = new SQLQuery();
		$query->setWhere("MyTable.`ParentID`= 5");
		$this->assertTrue(
			$query->filtersOnFK(),
			"filtersOnFK() is true with table and quoted column name "
		);
	}

	public function testInnerJoin() {
		$query = new SQLQuery();
		$query->setFrom('MyTable');
		$query->addInnerJoin('MyOtherTable', 'MyOtherTable.ID = 2');
		$query->addLeftJoin('MyLastTable', 'MyOtherTable.ID = MyLastTable.ID');

		$this->assertSQLEquals('SELECT * FROM MyTable '.
			'INNER JOIN "MyOtherTable" ON MyOtherTable.ID = 2 '.
			'LEFT JOIN "MyLastTable" ON MyOtherTable.ID = MyLastTable.ID',
			$query->sql($parameters)
		);

		$query = new SQLQuery();
		$query->setFrom('MyTable');
		$query->addInnerJoin('MyOtherTable', 'MyOtherTable.ID = 2', 'table1');
		$query->addLeftJoin('MyLastTable', 'MyOtherTable.ID = MyLastTable.ID', 'table2');

		$this->assertSQLEquals('SELECT * FROM MyTable '.
			'INNER JOIN "MyOtherTable" AS "table1" ON MyOtherTable.ID = 2 '.
			'LEFT JOIN "MyLastTable" AS "table2" ON MyOtherTable.ID = MyLastTable.ID',
			$query->sql($parameters)
		);
	}

	public function testJoinSubSelect() {

		// Test sub-select works
		$query = new SQLQuery();
		$query->setFrom('"MyTable"');
		$query->addInnerJoin('(SELECT * FROM "MyOtherTable")',
			'"Mot"."MyTableID" = "MyTable"."ID"', 'Mot');
		$query->addLeftJoin('(SELECT "MyLastTable"."MyOtherTableID", COUNT(1) as "MyLastTableCount" '
			. 'FROM "MyLastTable" GROUP BY "MyOtherTableID")',
			'"Mlt"."MyOtherTableID" = "Mot"."ID"', 'Mlt');
		$query->setOrderBy('COALESCE("Mlt"."MyLastTableCount", 0) DESC');

		$this->assertSQLEquals('SELECT *, COALESCE("Mlt"."MyLastTableCount", 0) AS "_SortColumn0" FROM "MyTable" '.
			'INNER JOIN (SELECT * FROM "MyOtherTable") AS "Mot" ON "Mot"."MyTableID" = "MyTable"."ID" ' .
			'LEFT JOIN (SELECT "MyLastTable"."MyOtherTableID", COUNT(1) as "MyLastTableCount" FROM "MyLastTable" '
			. 'GROUP BY "MyOtherTableID") AS "Mlt" ON "Mlt"."MyOtherTableID" = "Mot"."ID" ' .
			'ORDER BY "_SortColumn0" DESC',
			$query->sql($parameters)
		);

		// Test that table names do not get mistakenly identified as sub-selects
		$query = new SQLQuery();
		$query->setFrom('"MyTable"');
		$query->addInnerJoin('NewsArticleSelected', '"News"."MyTableID" = "MyTable"."ID"', 'News');
		$this->assertSQLEquals(
			'SELECT * FROM "MyTable" INNER JOIN "NewsArticleSelected" AS "News" ON '.
			'"News"."MyTableID" = "MyTable"."ID"',
			$query->sql()
		);

	}

	public function testSetWhereAny() {
		$query = new SQLQuery();
		$query->setFrom('MyTable');

		$query->setWhereAny(array(
			'Monkey' => 'Chimp',
			'Color' => 'Brown'
		));
		$sql = $query->sql($parameters);
		$this->assertSQLEquals("SELECT * FROM MyTable WHERE ((Monkey = ?) OR (Color = ?))", $sql);
		$this->assertEquals(array('Chimp', 'Brown'), $parameters);
	}

	public function testSelectFirst() {
		// Test first from sequence
		$query = new SQLQuery();
		$query->setFrom('"SQLQueryTest_DO"');
		$query->setOrderBy('"Name"');
		$result = $query->firstRow()->execute();

		$records = array();
		foreach($result as $row) {
			$records[] = $row;
		}

		$this->assertCount(1, $records);
		$this->assertEquals('Object 1', $records[0]['Name']);

		// Test first from empty sequence
		$query = new SQLQuery();
		$query->setFrom('"SQLQueryTest_DO"');
		$query->setOrderBy('"Name"');
		$query->setWhere(array('"Name"' => 'Nonexistent Object'));
		$result = $query->firstRow()->execute();

		$records = array();
		foreach($result as $row) {
			$records[] = $row;
		}

		$this->assertCount(0, $records);

		// Test that given the last item, the 'first' in this list matches the last
		$query = new SQLQuery();
		$query->setFrom('"SQLQueryTest_DO"');
		$query->setOrderBy('"Name"');
		$query->setLimit(1, 1);
		$result = $query->firstRow()->execute();

		$records = array();
		foreach($result as $row) {
			$records[] = $row;
		}

		$this->assertCount(1, $records);
		$this->assertEquals('Object 2', $records[0]['Name']);
	}

	public function testSelectLast() {
		// Test last in sequence
		$query = new SQLQuery();
		$query->setFrom('"SQLQueryTest_DO"');
		$query->setOrderBy('"Name"');
		$result = $query->lastRow()->execute();

		$records = array();
		foreach($result as $row) {
			$records[] = $row;
		}

		$this->assertCount(1, $records);
		$this->assertEquals('Object 2', $records[0]['Name']);

		// Test last from empty sequence
		$query = new SQLQuery();
		$query->setFrom('"SQLQueryTest_DO"');
		$query->setOrderBy('"Name"');
		$query->setWhere(array("\"Name\" = 'Nonexistent Object'"));
		$result = $query->lastRow()->execute();

		$records = array();
		foreach($result as $row) {
			$records[] = $row;
		}

		$this->assertCount(0, $records);

		// Test that given the first item, the 'last' in this list matches the first
		$query = new SQLQuery();
		$query->setFrom('"SQLQueryTest_DO"');
		$query->setOrderBy('"Name"');
		$query->setLimit(1);
		$result = $query->lastRow()->execute();

		$records = array();
		foreach($result as $row) {
			$records[] = $row;
		}

		$this->assertCount(1, $records);
		$this->assertEquals('Object 1', $records[0]['Name']);
	}

	/**
	 * Tests aggregate() function
	 */
	public function testAggregate() {
		$query = new SQLQuery('"Common"');
		$query->setFrom('"SQLQueryTest_DO"');
		$query->setGroupBy('"Common"');

		$queryClone = $query->aggregate('COUNT(*)', 'cnt');
		$result = $queryClone->execute();
		$this->assertEquals(array(2), $result->column('cnt'));
	}

	/**
	 * Tests that an ORDER BY is only added if a LIMIT is set.
	 */
	public function testAggregateNoOrderByIfNoLimit() {
		$query = new SQLQuery();
		$query->setFrom('"SQLQueryTest_DO"');
		$query->setOrderBy('Common');
		$query->setLimit(array());

		$aggregate = $query->aggregate('MAX("ID")');
		$limit = $aggregate->getLimit();
		$this->assertEquals(array(), $aggregate->getOrderBy());
		$this->assertEquals(array(), $limit);

		$query = new SQLQuery();
		$query->setFrom('"SQLQueryTest_DO"');
		$query->setOrderBy('Common');
		$query->setLimit(2);

		$aggregate = $query->aggregate('MAX("ID")');
		$limit = $aggregate->getLimit();
		$this->assertEquals(array('Common' => 'ASC'), $aggregate->getOrderBy());
		$this->assertEquals(array('start' => 0, 'limit' => 2), $limit);
	}

	/**
	 * Test that "_SortColumn0" is added for an aggregate in the ORDER BY
	 * clause, in combination with a LIMIT and GROUP BY clause.
	 * For some databases, like MSSQL, this is a complicated scenario
	 * because a subselect needs to be done to query paginated data.
	 */
	public function testOrderByContainingAggregateAndLimitOffset() {
		$query = new SQLQuery();
		$query->setSelect(array('"Name"', '"Meta"'));
		$query->setFrom('"SQLQueryTest_DO"');
		$query->setOrderBy(array('MAX("Date")'));
		$query->setGroupBy(array('"Name"', '"Meta"'));
		$query->setLimit('1', '1');

		$records = array();
		foreach($query->execute() as $record) {
			$records[] = $record;
		}

		$this->assertCount(1, $records);

		$this->assertEquals('Object 2', $records[0]['Name']);
		$this->assertEquals('2012-05-01 09:00:00', $records['0']['_SortColumn0']);
	}

	/**
	 * Test that multiple order elements are maintained in the given order
	 */
	public function testOrderByMultiple() {
		if(DB::get_conn() instanceof MySQLDatabase) {
			$query = new SQLQuery();
			$query->setSelect(array('"Name"', '"Meta"'));
			$query->setFrom('"SQLQueryTest_DO"');
			$query->setOrderBy(array('MID("Name", 8, 1) DESC', '"Name" ASC'));

			$records = array();
			foreach($query->execute() as $record) {
				$records[] = $record;
			}

			$this->assertCount(2, $records);

			$this->assertEquals('Object 2', $records[0]['Name']);
			$this->assertEquals('2', $records[0]['_SortColumn0']);

			$this->assertEquals('Object 1', $records[1]['Name']);
			$this->assertEquals('1', $records[1]['_SortColumn0']);
		}
	}

	public function testSelect() {
		$query = new SQLQuery('"Title"', '"MyTable"');
		$query->addSelect('"TestField"');
		$this->assertSQLEquals(
			'SELECT "Title", "TestField" FROM "MyTable"',
			$query->sql()
		);

		// Test replacement of select
		$query->setSelect(array(
			'Field' => '"Field"',
			'AnotherAlias' => '"AnotherField"'
		));
		$this->assertSQLEquals(
			'SELECT "Field", "AnotherField" AS "AnotherAlias" FROM "MyTable"',
			$query->sql()
		);

		// Check that ' as ' selects don't get mistaken as aliases
		$query->addSelect(array(
			'Relevance' => "MATCH (Title, MenuTitle) AGAINST ('Two as One')"
		));
		$this->assertSQLEquals(
			'SELECT "Field", "AnotherField" AS "AnotherAlias", MATCH (Title, MenuTitle) AGAINST (' .
			'\'Two as One\') AS "Relevance" FROM "MyTable"',
			$query->sql()
		);
	}

	/**
	 * Test passing in a LIMIT with OFFSET clause string.
	 */
	public function testLimitSetFromClauseString() {
		$query = new SQLQuery();
		$query->setSelect('*');
		$query->setFrom('"SQLQueryTest_DO"');

		$query->setLimit('20 OFFSET 10');
		$limit = $query->getLimit();
		$this->assertEquals(20, $limit['limit']);
		$this->assertEquals(10, $limit['start']);
	}

	public function testParameterisedInnerJoins() {
		$query = new SQLQuery();
		$query->setSelect(array('"SQLQueryTest_DO"."Name"', '"SubSelect"."Count"'));
		$query->setFrom('"SQLQueryTest_DO"');
		$query->addInnerJoin(
			'(SELECT "Title", COUNT(*) AS "Count" FROM "SQLQueryTestBase" GROUP BY "Title" HAVING "Title" NOT LIKE ?)',
			'"SQLQueryTest_DO"."Name" = "SubSelect"."Title"',
			'SubSelect',
			20,
			array('%MyName%')
		);
		$query->addWhere(array('"SQLQueryTest_DO"."Date" > ?' => '2012-08-08 12:00'));

		$this->assertSQLEquals('SELECT "SQLQueryTest_DO"."Name", "SubSelect"."Count"
			FROM "SQLQueryTest_DO" INNER JOIN (SELECT "Title", COUNT(*) AS "Count" FROM "SQLQueryTestBase"
		   GROUP BY "Title" HAVING "Title" NOT LIKE ?) AS "SubSelect" ON "SQLQueryTest_DO"."Name" =
		   "SubSelect"."Title"
			WHERE ("SQLQueryTest_DO"."Date" > ?)', $query->sql($parameters)
		);
		$this->assertEquals(array('%MyName%', '2012-08-08 12:00'), $parameters);
		$query->execute();
	}

	public function testParameterisedLeftJoins() {
		$query = new SQLQuery();
		$query->setSelect(array('"SQLQueryTest_DO"."Name"', '"SubSelect"."Count"'));
		$query->setFrom('"SQLQueryTest_DO"');
		$query->addLeftJoin(
			'(SELECT "Title", COUNT(*) AS "Count" FROM "SQLQueryTestBase" GROUP BY "Title" HAVING "Title" NOT LIKE ?)',
			'"SQLQueryTest_DO"."Name" = "SubSelect"."Title"',
			'SubSelect',
			20,
			array('%MyName%')
		);
		$query->addWhere(array('"SQLQueryTest_DO"."Date" > ?' => '2012-08-08 12:00'));

		$this->assertSQLEquals('SELECT "SQLQueryTest_DO"."Name", "SubSelect"."Count"
			FROM "SQLQueryTest_DO" LEFT JOIN (SELECT "Title", COUNT(*) AS "Count" FROM "SQLQueryTestBase"
		   GROUP BY "Title" HAVING "Title" NOT LIKE ?) AS "SubSelect" ON "SQLQueryTest_DO"."Name" =
		   "SubSelect"."Title"
			WHERE ("SQLQueryTest_DO"."Date" > ?)', $query->sql($parameters)
		);
		$this->assertEquals(array('%MyName%', '2012-08-08 12:00'), $parameters);
		$query->execute();
	}
	
	/**
	 * Test deprecation of SQLQuery::getWhere working appropriately
	 */
	public function testDeprecatedGetWhere() {
		// Temporarily disable deprecation
		Deprecation::notification_version(null);
		
		$query = new SQLQuery();
		$query->setSelect(array('"SQLQueryTest_DO"."Name"'));
		$query->setFrom('"SQLQueryTest_DO"');
		$query->addWhere(array(
			'"SQLQueryTest_DO"."Date" > ?' => '2012-08-08 12:00'
		));
		$query->addWhere('"SQLQueryTest_DO"."Name" = \'Richard\'');
		$query->addWhere(array(
			'"SQLQueryTest_DO"."Meta" IN (?, \'Who?\', ?)' => array('Left', 'Right')
		));
		
		$expectedSQL = <<<EOS
SELECT "SQLQueryTest_DO"."Name"
 FROM "SQLQueryTest_DO"
 WHERE ("SQLQueryTest_DO"."Date" > ?)
 AND ("SQLQueryTest_DO"."Name" = 'Richard')
 AND ("SQLQueryTest_DO"."Meta" IN (?, 'Who?', ?))
EOS
			;
		$expectedParameters = array('2012-08-08 12:00', 'Left', 'Right');
		
		
		// Check sql evaluation of this query maintains the parameters
		$sql = $query->sql($parameters);
		$this->assertSQLEquals($expectedSQL, $sql);
		$this->assertEquals($expectedParameters, $parameters);
		
		// Check that ->toAppropriateExpression()->setWhere doesn't modify the query
		$query->setWhere($query->toAppropriateExpression()->getWhere());
		$sql = $query->sql($parameters);
		$this->assertSQLEquals($expectedSQL, $sql);
		$this->assertEquals($expectedParameters, $parameters);
		
		// Check that getWhere are all flattened queries
		$expectedFlattened = array(
			'"SQLQueryTest_DO"."Date" > \'2012-08-08 12:00\'',
			'"SQLQueryTest_DO"."Name" = \'Richard\'',
			'"SQLQueryTest_DO"."Meta" IN (\'Left\', \'Who?\', \'Right\')'
		);
		$this->assertEquals($expectedFlattened, $query->getWhere());
	}
	
	/**
	 * Test deprecation of SQLQuery::setDelete/getDelete
	 */
	public function testDeprecatedSetDelete() {
		// Temporarily disable deprecation
		Deprecation::notification_version(null);
		
		$query = new SQLQuery();
		$query->setSelect(array('"SQLQueryTest_DO"."Name"'));
		$query->setFrom('"SQLQueryTest_DO"');
		$query->setWhere(array('"SQLQueryTest_DO"."Name"' => 'Andrew'));

		// Check SQL for select
		$this->assertSQLEquals(<<<EOS
SELECT "SQLQueryTest_DO"."Name" FROM "SQLQueryTest_DO"
WHERE ("SQLQueryTest_DO"."Name" = ?)
EOS
			,
			$query->sql($parameters)
		);
		$this->assertEquals(array('Andrew'), $parameters);
		
		// Check setDelete works
		$query->setDelete(true);
	$this->assertSQLEquals(<<<EOS
DELETE FROM "SQLQueryTest_DO"
WHERE ("SQLQueryTest_DO"."Name" = ?)
EOS
			,
			$query->sql($parameters)
		);
		$this->assertEquals(array('Andrew'), $parameters);
		
		// Check that setDelete back to false restores the state
		$query->setDelete(false);
		$this->assertSQLEquals(<<<EOS
SELECT "SQLQueryTest_DO"."Name" FROM "SQLQueryTest_DO"
WHERE ("SQLQueryTest_DO"."Name" = ?)
EOS
			,
			$query->sql($parameters)
		);
		$this->assertEquals(array('Andrew'), $parameters);
	}
}

class SQLQueryTest_DO extends DataObject implements TestOnly {
	private static $db = array(
		"Name" => "Varchar",
		"Meta" => "Varchar",
		"Common" => "Varchar",
		"Date" => "SS_Datetime"
	);
}

class SQLQueryTestBase extends DataObject implements TestOnly {
	private static $db = array(
		"Title" => "Varchar",
	);
}

class SQLQueryTestChild extends SQLQueryTestBase {
	private static $db = array(
		"Name" => "Varchar",
	);

	private static $has_one = array(
	);
}
