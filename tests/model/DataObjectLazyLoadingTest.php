<?php
/**
 * @package framework
 * @subpackage tests
 */

class DataObjectLazyLoadingTest extends SapphireTest {

	static $fixture_file = 'DataObjectTest.yml';

	// These are all defined in DataObjectTest.php
	protected $extraDataObjects = array(
		'DataObjectTest_Team',
		'DataObjectTest_Fixture',
		'DataObjectTest_SubTeam',
		'OtherSubclassWithSameField',
		'DataObjectTest_FieldlessTable',
		'DataObjectTest_FieldlessSubTable',
		'DataObjectTest_ValidatedObject',
		'DataObjectTest_Player',
		'DataObjectTest_TeamComment'
	);

	function testQueriedColumnsID() {
		$db = DB::getConn();
		$playerList = new DataList('DataObjectTest_SubTeam');
		$playerList = $playerList->setQueriedColumns(array('ID'));
		$expected = 'SELECT DISTINCT "DataObjectTest_Team"."ClassName", "DataObjectTest_Team"."Created", ' .
			'"DataObjectTest_Team"."LastEdited", "DataObjectTest_Team"."ID", CASE WHEN '.
			'"DataObjectTest_Team"."ClassName" IS NOT NULL THEN "DataObjectTest_Team"."ClassName" ELSE ' .
			$db->prepStringForDB('DataObjectTest_Team').' END AS "RecordClassName", "DataObjectTest_Team"."Title" '.
			'FROM "DataObjectTest_Team" ' .
			'WHERE ("DataObjectTest_Team"."ClassName" IN ('.$db->prepStringForDB('DataObjectTest_SubTeam').'))' .
			' ORDER BY "DataObjectTest_Team"."Title" ASC';
		$this->assertEquals($expected, $playerList->sql());
	}

	function testQueriedColumnsFromBaseTableAndSubTable() {
		$db = DB::getConn();
		$playerList = new DataList('DataObjectTest_SubTeam');
		$playerList = $playerList->setQueriedColumns(array('Title', 'SubclassDatabaseField'));
		$expected = 'SELECT DISTINCT "DataObjectTest_Team"."ClassName", "DataObjectTest_Team"."Created", ' .
			'"DataObjectTest_Team"."LastEdited", "DataObjectTest_Team"."Title", ' .
			'"DataObjectTest_SubTeam"."SubclassDatabaseField", "DataObjectTest_Team"."ID", CASE WHEN ' .
			'"DataObjectTest_Team"."ClassName" IS NOT NULL THEN "DataObjectTest_Team"."ClassName" ELSE ' .
			$db->prepStringForDB('DataObjectTest_Team').' END AS "RecordClassName" FROM "DataObjectTest_Team" LEFT JOIN ' .
			'"DataObjectTest_SubTeam" ON "DataObjectTest_SubTeam"."ID" = "DataObjectTest_Team"."ID" WHERE ' .
			'("DataObjectTest_Team"."ClassName" IN ('.$db->prepStringForDB('DataObjectTest_SubTeam').')) ' .
			'ORDER BY "DataObjectTest_Team"."Title" ASC';
		$this->assertEquals($expected, $playerList->sql());
	}

	function testQueriedColumnsFromBaseTable() {
		$db = DB::getConn();
		$playerList = new DataList('DataObjectTest_SubTeam');
		$playerList = $playerList->setQueriedColumns(array('Title'));
		$expected = 'SELECT DISTINCT "DataObjectTest_Team"."ClassName", "DataObjectTest_Team"."Created", ' .
			'"DataObjectTest_Team"."LastEdited", "DataObjectTest_Team"."Title", "DataObjectTest_Team"."ID", ' .
			'CASE WHEN "DataObjectTest_Team"."ClassName" IS NOT NULL THEN "DataObjectTest_Team"."ClassName" ELSE ' .
			$db->prepStringForDB('DataObjectTest_Team').' END AS "RecordClassName" FROM "DataObjectTest_Team" WHERE ' .
			'("DataObjectTest_Team"."ClassName" IN ('.$db->prepStringForDB('DataObjectTest_SubTeam').')) ' .
			'ORDER BY "DataObjectTest_Team"."Title" ASC';
		$this->assertEquals($expected, $playerList->sql());
	}

	function testQueriedColumnsFromSubTable() {
		$db = DB::getConn();
		$playerList = new DataList('DataObjectTest_SubTeam');
		$playerList = $playerList->setQueriedColumns(array('SubclassDatabaseField'));
		$expected = 'SELECT DISTINCT "DataObjectTest_Team"."ClassName", "DataObjectTest_Team"."Created", ' .
			'"DataObjectTest_Team"."LastEdited", "DataObjectTest_SubTeam"."SubclassDatabaseField", ' .
			'"DataObjectTest_Team"."ID", CASE WHEN "DataObjectTest_Team"."ClassName" IS NOT NULL THEN ' .
			'"DataObjectTest_Team"."ClassName" ELSE '.$db->prepStringForDB('DataObjectTest_Team').' END AS "RecordClassName", "DataObjectTest_Team"."Title" FROM ' .
			'"DataObjectTest_Team" LEFT JOIN "DataObjectTest_SubTeam" ON "DataObjectTest_SubTeam"."ID" = ' .
			'"DataObjectTest_Team"."ID" WHERE ("DataObjectTest_Team"."ClassName" IN ('.$db->prepStringForDB('DataObjectTest_SubTeam').')) ' . 
			'ORDER BY "DataObjectTest_Team"."Title" ASC';
		$this->assertEquals($expected, $playerList->sql());
	}

	function testNoSpecificColumnNamesBaseDataObjectQuery() {
		// This queries all columns from base table
		$playerList = new DataList('DataObjectTest_Team');
		// Shouldn't be a left join in here.
		$this->assertEquals(0, preg_match('/SELECT DISTINCT "DataObjectTest_Team"."ID" .* LEFT JOIN .* FROM "DataObjectTest_Team"/', $playerList->sql()));
	}

	function testNoSpecificColumnNamesSubclassDataObjectQuery() {
		// This queries all columns from base table and subtable
		$playerList = new DataList('DataObjectTest_SubTeam');
		// Should be a left join.
		$this->assertEquals(1, preg_match('/SELECT DISTINCT .* LEFT JOIN .* /', $playerList->sql()));
	}

	function testLazyLoadedFieldsHasField() {
		$subteam1 = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam1');
		$teams = DataObject::get('DataObjectTest_Team'); // query parent class
		$subteam1Lazy = $teams->find('ID', $subteam1->ID);

		// TODO Fix hasField() to exclude *_Lazy
		// $this->assertFalse($subteam1Lazy->hasField('SubclassDatabaseField_Lazy'));
		$this->assertTrue($subteam1Lazy->hasField('SubclassDatabaseField'));
	}

	function testLazyLoadedFieldsGetField() {
		$subteam1 = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam1');
		$teams = DataObject::get('DataObjectTest_Team'); // query parent class
		$subteam1Lazy = $teams->find('ID', $subteam1->ID);

		$this->assertEquals(
			$subteam1->getField('SubclassDatabaseField'),
			$subteam1Lazy->getField('SubclassDatabaseField')
		);
	}

	function testLazyLoadedFieldsSetField() {
		$subteam1 = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam1');
		$subteam1ID = $subteam1->ID;
		$teams = DataObject::get('DataObjectTest_Team'); // query parent class
		$subteam1Lazy = $teams->find('ID', $subteam1->ID);

		// Updated lazyloaded field
		$subteam1Lazy->SubclassDatabaseField = 'Changed';
		$subteam1Lazy->write();

		// Reload from database
		DataObject::flush_and_destroy_cache();
		$subteam1Reloaded = DataObject::get_by_id('DataObjectTest_SubTeam', $subteam1ID);

		$this->assertEquals(
			'Changed',
			$subteam1Reloaded->getField('SubclassDatabaseField')
		);
	}

	function testLazyLoadedFieldsWriteWithUnloadedFields() {
		$subteam1 = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam1');
		$subteam1ID = $subteam1->ID;
		$teams = DataObject::get('DataObjectTest_Team'); // query parent class
		$subteam1Lazy = $teams->find('ID', $subteam1->ID);

		// Updated lazyloaded field
		$subteam1Lazy->Title = 'Changed';
		$subteam1Lazy->write();

		// Reload from database
		DataObject::flush_and_destroy_cache();
		$subteam1Reloaded = DataObject::get_by_id('DataObjectTest_SubTeam', $subteam1ID);

		$this->assertEquals(
			'Subclassed 1',
			$subteam1Reloaded->getField('SubclassDatabaseField')
		);
	}

	function testLazyLoadedFieldsWriteNullFields() {
		$subteam1 = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam1');
		$subteam1ID = $subteam1->ID;
		$teams = DataObject::get('DataObjectTest_Team'); // query parent class
		$subteam1Lazy = $teams->find('ID', $subteam1->ID);

		// Updated lazyloaded field
		$subteam1Lazy->SubclassDatabaseField = null;
		$subteam1Lazy->write();

		// Reload from database
		DataObject::flush_and_destroy_cache();
		$subteam1Reloaded = DataObject::get_by_id('DataObjectTest_SubTeam', $subteam1ID);

		$this->assertEquals(
			null,
			$subteam1Reloaded->getField('SubclassDatabaseField')
		);
	}

	function testLazyLoadedFieldsGetChangedFields() {
		$subteam1 = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam1');
		$teams = DataObject::get('DataObjectTest_Team'); // query parent class
		$subteam1Lazy = $teams->find('ID', $subteam1->ID);

		// Updated lazyloaded field
		$subteam1Lazy->SubclassDatabaseField = 'Changed';
		$this->assertEquals(
			array('SubclassDatabaseField' => array(
				'before' => 'Subclassed 1',
				'after' => 'Changed',
				'level' => 2
			)),
			$subteam1Lazy->getChangedFields()
		);
	}

	function testLazyLoadedFieldsHasOneRelation() {
		$subteam1 = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam1');
		$parentTeam = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$teams = DataObject::get('DataObjectTest_Team'); // query parent class
		$subteam1Lazy = $teams->find('ID', $subteam1->ID);

		$parentTeamLazy = $subteam1Lazy->ParentTeam();
		$this->assertInstanceOf('DataObjectTest_Team', $parentTeamLazy);
		$this->assertEquals($parentTeam->ID, $parentTeamLazy->ID);
	}

	function testLazyLoadedFieldsToMap() {
		$subteam1 = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam1');
		$parentTeam = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$teams = DataObject::get('DataObjectTest_Team'); // query parent class
		$subteam1Lazy = $teams->find('ID', $subteam1->ID);
		$mapLazy = $subteam1Lazy->toMap();
		$this->assertArrayHasKey('SubclassDatabaseField', $mapLazy);
		$this->assertEquals('Subclassed 1', $mapLazy['SubclassDatabaseField']);
	}

	function testLazyLoadedFieldsIsEmpty() {
		$subteam1 = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam1');
		$parentTeam = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$teams = DataObject::get('DataObjectTest_Team'); // query parent class
		$subteam1Lazy = $teams->find('ID', $subteam1->ID);
		$subteam1Lazy->Title = '';
		$subteam1Lazy->DecoratedDatabaseField = '';
		$subteam1Lazy->ParentTeamID = 0;
		// Leave $subteam1Lazy->SubclassDatabaseField intact
		$this->assertFalse($subteam1Lazy->isEmpty());
	}

	function testLazyLoadedFieldsDuplicate() {
		$subteam1 = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam1');
		$parentTeam = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$teams = DataObject::get('DataObjectTest_Team'); // query parent class
		$subteam1Lazy = $teams->find('ID', $subteam1->ID);
		$subteam1LazyDup = $subteam1Lazy->duplicate();

		$this->assertEquals('Subclassed 1', $subteam1LazyDup->SubclassDatabaseField);
	}

	function testLazyLoadedFieldsGetAllFields() {
		$subteam1 = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam1');
		$parentTeam = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$teams = DataObject::get('DataObjectTest_Team'); // query parent class
		$subteam1Lazy = $teams->find('ID', $subteam1->ID);
		$this->assertArrayNotHasKey('SubclassDatabaseField_Lazy', $subteam1Lazy->toMap());
		$this->assertArrayHasKey('SubclassDatabaseField', $subteam1Lazy->toMap());
	}
}
