<?php

class FulltextFilterTest extends SapphireTest {

	protected static $fixture_file = "FulltextFilterTest.yml";


	public function testFilter() {
		if(DB::getConn() instanceof MySQLDatabase) {
			$baseQuery = FulltextDataObject::get();
			$this->assertEquals(3, $baseQuery->count(), "FulltextDataObject count does not match.");

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

			// Edgecase
			$this->setExpectedException("Exception");
			$search = $baseQuery->exclude("Madeup:fulltext", "SilverStripe");
		} else {
			$this->markTestSkipped("FulltextFilter only supports MySQL syntax.");
		}
	}
	
}


class FulltextDataObject extends DataObject {

	private static $db = array(
		"ColumnA" => "Varchar(255)",
		"ColumnB" => "HTMLText",
		"ColumnC" => "Varchar(255)",
		"ColumnD" => "HTMLText",
	);

	private static $indexes = array(
		'SearchFields' => array(
			'type' => 'fulltext',
			'name' => 'SearchFields',
			'value' => '"ColumnA", "ColumnB"',
		),
		'OtherSearchFields' => 'fulltext ("ColumnC", "ColumnD")',
	);
	
	private static $create_table_options = array(
		"MySQLDatabase" => "ENGINE=MyISAM",
	);

}