<?php

class SQLQueryTest extends SapphireTest {
	
	static $fixture_file = null;

	protected $extraDataObjects = array(
		'SQLQueryTest_DO',
	);
	
	function testEmptyQueryReturnsNothing() {
		$query = new SQLQuery();
		$this->assertEquals('', $query->sql());
	}
	
	function testSelectFromBasicTable() {
		$query = new SQLQuery();
		$query->setFrom('MyTable');
		$this->assertEquals("SELECT * FROM MyTable", $query->sql());
		$query->addFrom('MyJoin');
		$this->assertEquals("SELECT * FROM MyTable MyJoin", $query->sql());
	}
	
	function testSelectFromUserSpecifiedFields() {
		$query = new SQLQuery();
		$query->setSelect(array("Name", "Title", "Description"));
		$query->setFrom("MyTable");
		$this->assertEquals("SELECT Name, Title, Description FROM MyTable", $query->sql());
	}
	
	function testSelectWithWhereClauseFilter() {
		$query = new SQLQuery();
		$query->setSelect(array("Name","Meta"));
		$query->setFrom("MyTable");
		$query->setWhere("Name = 'Name'");
		$query->addWhere("Meta = 'Test'");
		$this->assertEquals("SELECT Name, Meta FROM MyTable WHERE (Name = 'Name') AND (Meta = 'Test')", $query->sql());
	}
	
	function testSelectWithConstructorParameters() {
		$query = new SQLQuery(array("Foo", "Bar"), "FooBarTable");
		$this->assertEquals("SELECT Foo, Bar FROM FooBarTable", $query->sql());
		$query = new SQLQuery(array("Foo", "Bar"), "FooBarTable", array("Foo = 'Boo'"));
		$this->assertEquals("SELECT Foo, Bar FROM FooBarTable WHERE (Foo = 'Boo')", $query->sql());
	}
	
	function testSelectWithChainedMethods() {
		$query = new SQLQuery();
		$query->setSelect("Name","Meta")->setFrom("MyTable")->setWhere("Name = 'Name'")->addWhere("Meta = 'Test'");
		$this->assertEquals("SELECT Name, Meta FROM MyTable WHERE (Name = 'Name') AND (Meta = 'Test')", $query->sql());
	}
	
	function testCanSortBy() {
		$query = new SQLQuery();
		$query->setSelect("Name","Meta")->setFrom("MyTable")->setWhere("Name = 'Name'")->addWhere("Meta = 'Test'");
		$this->assertTrue($query->canSortBy('Name ASC'));
		$this->assertTrue($query->canSortBy('Name'));
	}
	
	function testSelectWithChainedFilterParameters() {
		$query = new SQLQuery();
		$query->setSelect(array("Name","Meta"))->setFrom("MyTable");
		$query->setWhere("Name = 'Name'")->addWhere("Meta = 'Test'")->addWhere("Beta != 'Gamma'");
		$this->assertEquals("SELECT Name, Meta FROM MyTable WHERE (Name = 'Name') AND (Meta = 'Test') AND (Beta != 'Gamma')", $query->sql());
	}
	
	function testSelectWithLimitClause() {
		if(!(DB::getConn() instanceof MySQLDatabase || DB::getConn() instanceof SQLite3Database || DB::getConn() instanceof PostgreSQLDatabase)) {
			$this->markTestIncomplete();
		}

		$query = new SQLQuery();
		$query->setFrom("MyTable");
		$query->setLimit(99);
		$this->assertEquals("SELECT * FROM MyTable LIMIT 99", $query->sql());
	
		// array limit with start (MySQL specific)
		$query = new SQLQuery();
		$query->setFrom("MyTable");
		$query->setLimit(99, 97);
		$this->assertEquals("SELECT * FROM MyTable LIMIT 99 OFFSET 97", $query->sql());
	}
	
	function testSelectWithOrderbyClause() {
		$query = new SQLQuery();
		$query->setFrom("MyTable");
		$query->setOrderBy('MyName');
		$this->assertEquals('SELECT * FROM MyTable ORDER BY MyName ASC', $query->sql());
		
		$query = new SQLQuery();
		$query->setFrom("MyTable");
		$query->setOrderBy('MyName desc');
		$this->assertEquals('SELECT * FROM MyTable ORDER BY MyName DESC', $query->sql());
		
		$query = new SQLQuery();
		$query->setFrom("MyTable");
		$query->setOrderBy('MyName ASC, Color DESC');
		$this->assertEquals('SELECT * FROM MyTable ORDER BY MyName ASC, Color DESC', $query->sql());
		
		$query = new SQLQuery();
		$query->setFrom("MyTable");
		$query->setOrderBy('MyName ASC, Color');
		$this->assertEquals('SELECT * FROM MyTable ORDER BY MyName ASC, Color ASC', $query->sql());

		$query = new SQLQuery();
		$query->setFrom("MyTable");
		$query->setOrderBy(array('MyName' => 'desc'));
		$this->assertEquals('SELECT * FROM MyTable ORDER BY MyName DESC', $query->sql());
		
		$query = new SQLQuery();
		$query->setFrom("MyTable");
		$query->setOrderBy(array('MyName' => 'desc', 'Color'));
		$this->assertEquals('SELECT * FROM MyTable ORDER BY MyName DESC, Color ASC', $query->sql());
		
		$query = new SQLQuery();
		$query->setFrom("MyTable");
		$query->setOrderBy('implode("MyName","Color")');
		$this->assertEquals('SELECT *, implode("MyName","Color") AS "_SortColumn0" FROM MyTable ORDER BY "_SortColumn0" ASC', $query->sql());
		
		$query = new SQLQuery();
		$query->setFrom("MyTable");
		$query->setOrderBy('implode("MyName","Color") DESC');
		$this->assertEquals('SELECT *, implode("MyName","Color") AS "_SortColumn0" FROM MyTable ORDER BY "_SortColumn0" DESC', $query->sql());
		
		$query = new SQLQuery();
		$query->setFrom("MyTable");
		$query->setOrderBy('RAND()');
		
		$this->assertEquals('SELECT *, RAND() AS "_SortColumn0" FROM MyTable ORDER BY "_SortColumn0" ASC', $query->sql());
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

		$this->assertEquals('SELECT * FROM MyTable ORDER BY Name DESC',$query->sql());	
		
		$query->setOrderBy("Name DESC");
		$query->reverseOrderBy();

		$this->assertEquals('SELECT * FROM MyTable ORDER BY Name ASC',$query->sql());
		
		$query->setOrderBy(array("Name" => "ASC"));
		$query->reverseOrderBy();
		
		$this->assertEquals('SELECT * FROM MyTable ORDER BY Name DESC',$query->sql());
		
		$query->setOrderBy(array("Name" => 'DESC', 'Color' => 'asc'));
		$query->reverseOrderBy();
		
		$this->assertEquals('SELECT * FROM MyTable ORDER BY Name ASC, Color DESC',$query->sql());
		
		$query->setOrderBy('implode("MyName","Color") DESC');
		$query->reverseOrderBy();
		
		$this->assertEquals('SELECT *, implode("MyName","Color") AS "_SortColumn0" FROM MyTable ORDER BY "_SortColumn0" ASC',$query->sql());
	}

	function testFiltersOnID() {
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
	
	function testFiltersOnFK() {
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

		$this->assertEquals('SELECT * FROM MyTable '.
			'INNER JOIN "MyOtherTable" ON MyOtherTable.ID = 2 '.
			'LEFT JOIN "MyLastTable" ON MyOtherTable.ID = MyLastTable.ID',
			$query->sql()
		);

		$query = new SQLQuery();
		$query->setFrom('MyTable');
		$query->addInnerJoin('MyOtherTable', 'MyOtherTable.ID = 2', 'table1');
		$query->addLeftJoin('MyLastTable', 'MyOtherTable.ID = MyLastTable.ID', 'table2');

		$this->assertEquals('SELECT * FROM MyTable '.
			'INNER JOIN "MyOtherTable" AS "table1" ON MyOtherTable.ID = 2 '.
			'LEFT JOIN "MyLastTable" AS "table2" ON MyOtherTable.ID = MyLastTable.ID',
			$query->sql()
		);
	}

	public function testSetWhereAny() {
		$query = new SQLQuery();
		$query->setFrom('MyTable');

		$query->setWhereAny(array("Monkey = 'Chimp'", "Color = 'Brown'"));
		$this->assertEquals("SELECT * FROM MyTable WHERE (Monkey = 'Chimp' OR Color = 'Brown')",$query->sql());
	}

}

class SQLQueryTest_DO extends DataObject implements TestOnly {
	static $db = array(
		"Name" => "Varchar",
		"Meta" => "Varchar",
	);
}


