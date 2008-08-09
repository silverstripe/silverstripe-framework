<?php

class SQLQueryTest extends SapphireTest {
	
	static $fixture_file = null;
	
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
	
	function testSelectWithChainedFilterParameters() {
		$query = new SQLQuery();
		$query->select(array("Name","Meta"))->from("MyTable");
		$query->where("Name = 'Name'")->where("Meta","Test")->where("Beta", "!=", "Gamma");
		$this->assertEquals("SELECT Name, Meta FROM MyTable WHERE (Name = 'Name') AND (Meta = 'Test') AND (Beta != 'Gamma')", $query->sql());		
	}
	
	function testSelectWithPredicateFilters() {
		$query = new SQLQuery();
		$query->select(array("Name"))->from("MyTable");
		$match = new ExactMatchFilter("Name", "Value");
		$match->apply($query);
		$match = new PartialMatchFilter("Meta", "Value");
		$match->apply($query);
		$this->assertEquals("SELECT Name FROM MyTable WHERE (Name = 'Value') AND (Meta LIKE '%Value%')", $query->sql());
	}
	
	function testSelectWithLimitClause() {
		// not implemented
	}
	
}

?>