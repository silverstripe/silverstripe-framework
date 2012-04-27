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
		$query->from[] = "MyTable";
		$this->assertEquals("SELECT * FROM MyTable", $query->sql());
		$query->from[] = "MyJoin";
		$this->assertEquals("SELECT * FROM MyTable MyJoin", $query->sql());
	}
	
	function testSelectFromUserSpecifiedFields() {
		$query = new SQLQuery();
		$query->select = array("Name", "Title", "Description");
		$query->from[] = "MyTable";
		$this->assertEquals("SELECT Name, Title, Description FROM MyTable", $query->sql());
	}
	
	function testSelectWithWhereClauseFilter() {
		$query = new SQLQuery();
		$query->select = array("Name","Meta");
		$query->from[] = "MyTable";
		$query->where[] = "Name = 'Name'";
		$query->where[] = "Meta = 'Test'";
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
		$query->select("Name","Meta")->from("MyTable")->where("Name", "Name")->where("Meta", "Test");
		$this->assertEquals("SELECT Name, Meta FROM MyTable WHERE (Name = 'Name') AND (Meta = 'Test')", $query->sql());
	}
	
	function testCanSortBy() {
		$query = new SQLQuery();
		$query->select("Name","Meta")->from("MyTable")->where("Name", "Name")->where("Meta", "Test");
		$this->assertTrue($query->canSortBy('Name ASC'));
		$this->assertTrue($query->canSortBy('Name'));
	}
	
	function testSelectWithChainedFilterParameters() {
		$query = new SQLQuery();
		$query->select(array("Name","Meta"))->from("MyTable");
		$query->where("Name = 'Name'")->where("Meta","Test")->where("Beta", "!=", "Gamma");
		$this->assertEquals("SELECT Name, Meta FROM MyTable WHERE (Name = 'Name') AND (Meta = 'Test') AND (Beta != 'Gamma')", $query->sql());		
	}
	
	function testSelectWithPredicateFilters() {
	    /* this is no longer part of this
		$query = new SQLQuery();
		$query->select(array("Name"))->from("SQLQueryTest_DO");

		$match = new ExactMatchFilter("Name", "Value");
		$match->setModel('SQLQueryTest_DO');
		$match->apply($query);

		$match = new PartialMatchFilter("Meta", "Value");
		$match->setModel('SQLQueryTest_DO');
		$match->apply($query);

		$this->assertEquals("SELECT Name FROM SQLQueryTest_DO WHERE (\"SQLQueryTest_DO\".\"Name\" = 'Value') AND (\"SQLQueryTest_DO\".\"Meta\" LIKE '%Value%')", $query->sql());
		*/
	}
	
	function testSelectWithLimitClause() {
		// These are MySQL specific :-S
		if(DB::getConn() instanceof MySQLDatabase) {
			// numeric limit
			$query = new SQLQuery();
			$query->from[] = "MyTable";
			$query->limit(99);
			$this->assertEquals("SELECT * FROM MyTable LIMIT 99", $query->sql());
		
			// array limit with start (MySQL specific)
			$query = new SQLQuery();
			$query->from[] = "MyTable";
			$query->limit(99, 97);
			$this->assertEquals("SELECT * FROM MyTable LIMIT 99 OFFSET 97", $query->sql());
		}
	}
	
	function testSelectWithOrderbyClause() {
		$query = new SQLQuery();
		$query->from[] = "MyTable";
		$query->orderby('MyName');
		$this->assertEquals('SELECT * FROM MyTable ORDER BY MyName', $query->sql());
		
		$query = new SQLQuery();
		$query->from[] = "MyTable";
		$query->orderby('MyName desc');
		$this->assertEquals('SELECT * FROM MyTable ORDER BY MyName DESC', $query->sql());
		
		$query = new SQLQuery();
		$query->from[] = "MyTable";
		$query->orderby('MyName ASC, Color DESC');
		$this->assertEquals('SELECT * FROM MyTable ORDER BY MyName ASC, Color DESC', $query->sql());
		
		$query = new SQLQuery();
		$query->from[] = "MyTable";
		$query->orderby('MyName ASC, Color');
		$this->assertEquals('SELECT * FROM MyTable ORDER BY MyName ASC, Color', $query->sql());

		$query = new SQLQuery();
		$query->from[] = "MyTable";
		$query->orderby(array('MyName' => 'desc'));
		$this->assertEquals('SELECT * FROM MyTable ORDER BY MyName DESC', $query->sql());
		
		$query = new SQLQuery();
		$query->from[] = "MyTable";
		$query->orderby(array('MyName' => 'desc', 'Color'));
		$this->assertEquals('SELECT * FROM MyTable ORDER BY MyName DESC, Color', $query->sql());
		
		$query = new SQLQuery();
		$query->from[] = "MyTable";
		$query->orderby('implode("MyName","Color")');
		$this->assertEquals('SELECT implode("MyName","Color") AS "_SortColumn0" FROM MyTable ORDER BY _SortColumn0', $query->sql());
		
		$query = new SQLQuery();
		$query->from[] = "MyTable";
		$query->orderby('implode("MyName","Color") DESC');
		$this->assertEquals('SELECT implode("MyName","Color") AS "_SortColumn0" FROM MyTable ORDER BY _SortColumn0 DESC', $query->sql());
		
		$query = new SQLQuery();
		$query->from[] = "MyTable";
		$query->orderby('RAND()');
		
		$this->assertEquals('SELECT RAND() AS "_SortColumn0" FROM MyTable ORDER BY _SortColumn0', $query->sql());
	}
	
	public function testReverseOrderBy() {
		$query = new SQLQuery();
		$query->from('MyTable');
		
		// default is ASC
		$query->orderby("Name");
		$query->reverseOrderBy();

		$this->assertEquals('SELECT * FROM MyTable ORDER BY Name DESC',$query->sql());	
		
		$query->orderby("Name DESC");
		$query->reverseOrderBy();

		$this->assertEquals('SELECT * FROM MyTable ORDER BY Name ASC',$query->sql());
		
		$query->orderby(array("Name" => "ASC"));
		$query->reverseOrderBy();
		
		$this->assertEquals('SELECT * FROM MyTable ORDER BY Name DESC',$query->sql());
		
		$query->orderby(array("Name" => 'DESC', 'Color' => 'asc'));
		$query->reverseOrderBy();
		
		$this->assertEquals('SELECT * FROM MyTable ORDER BY Name ASC, Color DESC',$query->sql());
		
		$query->orderby('implode("MyName","Color") DESC');
		$query->reverseOrderBy();
		
		$this->assertEquals('SELECT implode("MyName","Color") AS "_SortColumn0" FROM MyTable ORDER BY _SortColumn0 ASC',$query->sql());
	}

	function testFiltersOnID() {
		$query = new SQLQuery();
		$query->where[] = "ID = 5";
		$this->assertTrue(
			$query->filtersOnID(),
			"filtersOnID() is true with simple unquoted column name"
		);
		
		$query = new SQLQuery();
		$query->where[] = "ID=5";
		$this->assertTrue(
			$query->filtersOnID(),
			"filtersOnID() is true with simple unquoted column name and no spaces in equals sign"
		);
		/*
		$query = new SQLQuery();
		$query->where[] = "Foo='Bar' AND ID=5";
		$this->assertTrue(
			$query->filtersOnID(),
			"filtersOnID() is true with combined SQL statements"
		);
		*/
		
		$query = new SQLQuery();
		$query->where[] = "Identifier = 5";
		$this->assertFalse(
			$query->filtersOnID(),
			"filtersOnID() is false with custom column name (starting with 'id')"
		);
		
		$query = new SQLQuery();
		$query->where[] = "ParentID = 5";
		$this->assertFalse(
			$query->filtersOnID(),
			"filtersOnID() is false with column name ending in 'ID'"
		);
		
		$query = new SQLQuery();
		$query->where[] = "MyTable.ID = 5";
		$this->assertTrue(
			$query->filtersOnID(),
			"filtersOnID() is true with table and column name"
		);
		
		$query = new SQLQuery();
		$query->where[] = "MyTable.ID= 5";
		$this->assertTrue(
			$query->filtersOnID(),
			"filtersOnID() is true with table and quoted column name "
		);
	}
	
	function testFiltersOnFK() {
		$query = new SQLQuery();
		$query->where[] = "ID = 5";
		$this->assertFalse(
			$query->filtersOnFK(),
			"filtersOnFK() is true with simple unquoted column name"
		);
		
		$query = new SQLQuery();
		$query->where[] = "Identifier = 5";
		$this->assertFalse(
			$query->filtersOnFK(),
			"filtersOnFK() is false with custom column name (starting with 'id')"
		);
		
		$query = new SQLQuery();
		$query->where[] = "MyTable.ParentID = 5";
		$this->assertTrue(
			$query->filtersOnFK(),
			"filtersOnFK() is true with table and column name"
		);
		
		$query = new SQLQuery();
		$query->where[] = "MyTable.`ParentID`= 5";
		$this->assertTrue(
			$query->filtersOnFK(),
			"filtersOnFK() is true with table and quoted column name "
		);
	}

	public function testInnerJoin() {
		$query = new SQLQuery();
		$query->from( 'MyTable' );
		$query->innerJoin( 'MyOtherTable', 'MyOtherTable.ID = 2' );
		$query->leftJoin( 'MyLastTable', 'MyOtherTable.ID = MyLastTable.ID' );

		$this->assertEquals( 'SELECT * FROM MyTable '.
			'INNER JOIN "MyOtherTable" ON MyOtherTable.ID = 2 '.
			'LEFT JOIN "MyLastTable" ON MyOtherTable.ID = MyLastTable.ID',
			$query->sql()
		);

		$query = new SQLQuery();
		$query->from( 'MyTable' );
		$query->innerJoin( 'MyOtherTable', 'MyOtherTable.ID = 2', 'table1' );
		$query->leftJoin( 'MyLastTable', 'MyOtherTable.ID = MyLastTable.ID', 'table2' );

		$this->assertEquals( 'SELECT * FROM MyTable '.
			'INNER JOIN "MyOtherTable" AS "table1" ON MyOtherTable.ID = 2 '.
			'LEFT JOIN "MyLastTable" AS "table2" ON MyOtherTable.ID = MyLastTable.ID',
			$query->sql()
		);
	}


	public function testWhereAny() {
		$query = new SQLQuery();
		$query->from( 'MyTable' );

		$query->whereAny(array("Monkey = 'Chimp'", "Color = 'Brown'"));
		$this->assertEquals("SELECT * FROM MyTable WHERE (Monkey = 'Chimp' OR Color = 'Brown')",$query->sql());
	}
}

class SQLQueryTest_DO extends DataObject implements TestOnly {
	static $db = array(
		"Name" => "Varchar",
		"Meta" => "Varchar",
	);
}


