<?php

class FulltextFilterTest extends SapphireTest {
	
	protected $extraDataObjects = array(
		'FulltextFilterTest_DataObject'
	);

	protected static $fixture_file = "FulltextFilterTest.yml";

	public function testFilter() {
		if(DB::getConn() instanceof MySQLDatabase) {
			$baseQuery = FulltextFilterTest_DataObject::get();
			$this->assertEquals(3, $baseQuery->count(), "FulltextFilterTest_DataObject count does not match.");

			// First we'll text the 'SearchFields' which has been set using an array
			$search = $baseQuery->filter("SearchFields:fulltext", 'SilverStripe');
			$this->assertEquals(1, $search->count());

			$search = $baseQuery->exclude("SearchFields:fulltext", "SilverStripe");
			$this->assertEquals(2, $search->count());

			// Now we'll run the same tests on 'OtherSearchFields' which should yield the same resutls
			// but has been set using a string.
			$search = $baseQuery->filter("OtherSearchFields:fulltext", 'SilverStripe');
			$this->assertEquals(1, $search->count());

			$search = $baseQuery->exclude("OtherSearchFields:fulltext", "SilverStripe");
			$this->assertEquals(2, $search->count());

			// Search on a single field
			$search = $baseQuery->filter("ColumnE:fulltext", 'Dragons');
			$this->assertEquals(1, $search->count());

			$search = $baseQuery->exclude("ColumnE:fulltext", "Dragons");
			$this->assertEquals(2, $search->count());
		} else {
			$this->markTestSkipped("FulltextFilter only supports MySQL syntax.");
		}
	}

	public function testGenerateQuery() {
		// Test if columns have table identifier
		$filter1 = new FulltextFilter('SearchFields', 'SilverStripe');
		$filter1->setModel('FulltextFilterTest_DataObject');
		$query1 = FulltextFilterTest_DataObject::get()->dataQuery();
		$filter1->apply($query1);
		$this->assertNotEquals('"ColumnA","ColumnB"', $filter1->getDbName());
		$this->assertNotEquals(
			array("MATCH (\"ColumnA\",\"ColumnB\") AGAINST ('SilverStripe')"),
			$query1->query()->getWhere()
		);

		// Test SearchFields
		$filter1 = new FulltextFilter('SearchFields', 'SilverStripe');
		$filter1->setModel('FulltextFilterTest_DataObject');
		$query1 = FulltextFilterTest_DataObject::get()->dataQuery();
		$filter1->apply($query1);
		$this->assertEquals('"FulltextFilterTest_DataObject"."ColumnA","FulltextFilterTest_DataObject"."ColumnB"', $filter1->getDbName());
		$this->assertEquals(
			array("MATCH (\"FulltextFilterTest_DataObject\".\"ColumnA\",\"FulltextFilterTest_DataObject\".\"ColumnB\") AGAINST ('SilverStripe')"),
			$query1->query()->getWhere()
		);

		// Test Other searchfields
		$filter2 = new FulltextFilter('OtherSearchFields', 'SilverStripe');
		$filter2->setModel('FulltextFilterTest_DataObject');
		$query2 = FulltextFilterTest_DataObject::get()->dataQuery();
		$filter2->apply($query2);
		$this->assertEquals('"FulltextFilterTest_DataObject"."ColumnC","FulltextFilterTest_DataObject"."ColumnD"', $filter2->getDbName());
		$this->assertEquals(
			array("MATCH (\"FulltextFilterTest_DataObject\".\"ColumnC\",\"FulltextFilterTest_DataObject\".\"ColumnD\") AGAINST ('SilverStripe')"),
			$query2->query()->getWhere()
		);

		// Test fallback to single field
		$filter3 = new FulltextFilter('ColumnA', 'SilverStripe');
		$filter3->setModel('FulltextFilterTest_DataObject');
		$query3 = FulltextFilterTest_DataObject::get()->dataQuery();
		$filter3->apply($query3);
		$this->assertEquals('"FulltextFilterTest_DataObject"."ColumnA"', $filter3->getDbName());
		$this->assertEquals(
			array("MATCH (\"FulltextFilterTest_DataObject\".\"ColumnA\") AGAINST ('SilverStripe')"),
			$query3->query()->getWhere()
		);
	}

}


class FulltextFilterTest_DataObject extends DataObject implements TestOnly {

	private static $db = array(
		"ColumnA" => "Varchar(255)",
		"ColumnB" => "HTMLText",
		"ColumnC" => "Varchar(255)",
		"ColumnD" => "HTMLText",
		"ColumnE" => 'Varchar(255)'
	);

	private static $indexes = array(
		'SearchFields' => array(
			'type' => 'fulltext',
			'name' => 'SearchFields',
			'value' => '"ColumnA", "ColumnB"',
		),
		'OtherSearchFields' => 'fulltext ("ColumnC", "ColumnD")',
		'SingleIndex' => 'fulltext ("ColumnE")'
	);
	
	private static $create_table_options = array(
		"MySQLDatabase" => "ENGINE=MyISAM",
	);

}
