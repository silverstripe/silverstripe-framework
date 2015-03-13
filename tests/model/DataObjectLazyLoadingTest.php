<?php
/**
 * @package framework
 * @subpackage tests
 */

class DataObjectLazyLoadingTest extends SapphireTest {

	protected static $fixture_file = array(
		'DataObjectTest.yml',
		'VersionedTest.yml'
	);

	// These are all defined in DataObjectTest.php and VersionedTest.php
	protected $extraDataObjects = array(
		'DataObjectTest_Team',
		'DataObjectTest_Fixture',
		'DataObjectTest_SubTeam',
		'OtherSubclassWithSameField',
		'DataObjectTest_FieldlessTable',
		'DataObjectTest_FieldlessSubTable',
		'DataObjectTest_ValidatedObject',
		'DataObjectTest_Player',
		'DataObjectTest_TeamComment',
		'DataObjectTest_EquipmentCompany',
		'DataObjectTest_SubEquipmentCompany',
		'VersionedTest_DataObject',
		'VersionedTest_Subclass',
		'VersionedLazy_DataObject',
		'VersionedLazySub_DataObject',
	);

	public function testQueriedColumnsID() {
		$db = DB::get_conn();
		$playerList = new DataList('DataObjectTest_SubTeam');
		$playerList = $playerList->setQueriedColumns(array('ID'));
		$expected = 'SELECT DISTINCT "DataObjectTest_Team"."ClassName", "DataObjectTest_Team"."LastEdited", ' .
			'"DataObjectTest_Team"."Created", "DataObjectTest_Team"."ID", CASE WHEN '.
			'"DataObjectTest_Team"."ClassName" IS NOT NULL THEN "DataObjectTest_Team"."ClassName" ELSE ' .
			$db->quoteString('DataObjectTest_Team').' END AS "RecordClassName", "DataObjectTest_Team"."Title" '.
			'FROM "DataObjectTest_Team" ' .
			'LEFT JOIN "DataObjectTest_SubTeam" ON "DataObjectTest_SubTeam"."ID" = "DataObjectTest_Team"."ID" ' .
			'WHERE ("DataObjectTest_Team"."ClassName" IN (?))' .
			' ORDER BY "DataObjectTest_Team"."Title" ASC';
		$this->assertSQLEquals($expected, $playerList->sql($parameters));
	}

	public function testQueriedColumnsFromBaseTableAndSubTable() {
		$db = DB::get_conn();
		$playerList = new DataList('DataObjectTest_SubTeam');
		$playerList = $playerList->setQueriedColumns(array('Title', 'SubclassDatabaseField'));
		$expected = 'SELECT DISTINCT "DataObjectTest_Team"."ClassName", "DataObjectTest_Team"."LastEdited", ' .
			'"DataObjectTest_Team"."Created", "DataObjectTest_Team"."Title", ' .
			'"DataObjectTest_SubTeam"."SubclassDatabaseField", "DataObjectTest_Team"."ID", CASE WHEN ' .
			'"DataObjectTest_Team"."ClassName" IS NOT NULL THEN "DataObjectTest_Team"."ClassName" ELSE ' .
			$db->quoteString('DataObjectTest_Team').' END AS "RecordClassName" FROM "DataObjectTest_Team" ' .
			'LEFT JOIN "DataObjectTest_SubTeam" ON "DataObjectTest_SubTeam"."ID" = "DataObjectTest_Team"."ID" WHERE ' .
			'("DataObjectTest_Team"."ClassName" IN (?)) ' .
			'ORDER BY "DataObjectTest_Team"."Title" ASC';
		$this->assertSQLEquals($expected, $playerList->sql($parameters));
	}

	public function testQueriedColumnsFromBaseTable() {
		$db = DB::get_conn();
		$playerList = new DataList('DataObjectTest_SubTeam');
		$playerList = $playerList->setQueriedColumns(array('Title'));
		$expected = 'SELECT DISTINCT "DataObjectTest_Team"."ClassName", "DataObjectTest_Team"."LastEdited", ' .
			'"DataObjectTest_Team"."Created", "DataObjectTest_Team"."Title", "DataObjectTest_Team"."ID", ' .
			'CASE WHEN "DataObjectTest_Team"."ClassName" IS NOT NULL THEN "DataObjectTest_Team"."ClassName" ELSE ' .
			$db->quoteString('DataObjectTest_Team').' END AS "RecordClassName" FROM "DataObjectTest_Team" ' .
			'LEFT JOIN "DataObjectTest_SubTeam" ON "DataObjectTest_SubTeam"."ID" = "DataObjectTest_Team"."ID" WHERE ' .
			'("DataObjectTest_Team"."ClassName" IN (?)) ' .
			'ORDER BY "DataObjectTest_Team"."Title" ASC';
		$this->assertSQLEquals($expected, $playerList->sql($parameters));
	}

	public function testQueriedColumnsFromSubTable() {
		$db = DB::get_conn();
		$playerList = new DataList('DataObjectTest_SubTeam');
		$playerList = $playerList->setQueriedColumns(array('SubclassDatabaseField'));
		$expected = 'SELECT DISTINCT "DataObjectTest_Team"."ClassName", "DataObjectTest_Team"."LastEdited", ' .
			'"DataObjectTest_Team"."Created", "DataObjectTest_SubTeam"."SubclassDatabaseField", ' .
			'"DataObjectTest_Team"."ID", CASE WHEN "DataObjectTest_Team"."ClassName" IS NOT NULL THEN ' .
			'"DataObjectTest_Team"."ClassName" ELSE '.$db->quoteString('DataObjectTest_Team').' END ' .
			'AS "RecordClassName", "DataObjectTest_Team"."Title" ' .
			'FROM "DataObjectTest_Team" LEFT JOIN "DataObjectTest_SubTeam" ON "DataObjectTest_SubTeam"."ID" = ' .
			'"DataObjectTest_Team"."ID" WHERE ("DataObjectTest_Team"."ClassName" IN (?)) ' .
			'ORDER BY "DataObjectTest_Team"."Title" ASC';
		$this->assertSQLEquals($expected, $playerList->sql($parameters));
	}

	public function testNoSpecificColumnNamesBaseDataObjectQuery() {
		// This queries all columns from base table
		$playerList = new DataList('DataObjectTest_Team');
		// Shouldn't be a left join in here.
		$this->assertEquals(0,
			preg_match(
				$this->normaliseSQL(
					'/SELECT DISTINCT "DataObjectTest_Team"."ID" .* LEFT JOIN .* FROM "DataObjectTest_Team"/'
				),
				$this->normaliseSQL($playerList->sql($parameters))
			)
		);
	}

	public function testNoSpecificColumnNamesSubclassDataObjectQuery() {
		// This queries all columns from base table and subtable
		$playerList = new DataList('DataObjectTest_SubTeam');
		// Should be a left join.
		$this->assertEquals(1, preg_match(
			$this->normaliseSQL('/SELECT DISTINCT .* LEFT JOIN .* /'),
			$this->normaliseSQL($playerList->sql($parameters))
		));
	}

	public function testLazyLoadedFieldsHasField() {
		$subteam1 = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam1');
		$teams = DataObject::get('DataObjectTest_Team'); // query parent class
		$subteam1Lazy = $teams->find('ID', $subteam1->ID);

		// TODO Fix hasField() to exclude *_Lazy
		// $this->assertFalse($subteam1Lazy->hasField('SubclassDatabaseField_Lazy'));
		$this->assertTrue($subteam1Lazy->hasField('SubclassDatabaseField'));
	}

	public function testLazyLoadedFieldsGetField() {
		$subteam1 = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam1');
		$teams = DataObject::get('DataObjectTest_Team'); // query parent class
		$subteam1Lazy = $teams->find('ID', $subteam1->ID);

		$this->assertEquals(
			$subteam1->getField('SubclassDatabaseField'),
			$subteam1Lazy->getField('SubclassDatabaseField')
		);
	}

	public function testLazyLoadedFieldsSetField() {
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

	public function testLazyLoadedFieldsWriteWithUnloadedFields() {
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

	public function testLazyLoadedFieldsWriteNullFields() {
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

	public function testLazyLoadedFieldsGetChangedFields() {
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

	public function testLazyLoadedFieldsHasOneRelation() {
		$subteam1 = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam1');
		$parentTeam = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$teams = DataObject::get('DataObjectTest_Team'); // query parent class
		$subteam1Lazy = $teams->find('ID', $subteam1->ID);

		$parentTeamLazy = $subteam1Lazy->ParentTeam();
		$this->assertInstanceOf('DataObjectTest_Team', $parentTeamLazy);
		$this->assertEquals($parentTeam->ID, $parentTeamLazy->ID);
	}

	public function testLazyLoadedFieldsToMap() {
		$subteam1 = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam1');
		$parentTeam = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$teams = DataObject::get('DataObjectTest_Team'); // query parent class
		$subteam1Lazy = $teams->find('ID', $subteam1->ID);
		$mapLazy = $subteam1Lazy->toMap();
		$this->assertArrayHasKey('SubclassDatabaseField', $mapLazy);
		$this->assertEquals('Subclassed 1', $mapLazy['SubclassDatabaseField']);
	}

	public function testLazyLoadedFieldsIsEmpty() {
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

	public function testLazyLoadedFieldsDuplicate() {
		$subteam1 = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam1');
		$parentTeam = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$teams = DataObject::get('DataObjectTest_Team'); // query parent class
		$subteam1Lazy = $teams->find('ID', $subteam1->ID);
		$subteam1LazyDup = $subteam1Lazy->duplicate();

		$this->assertEquals('Subclassed 1', $subteam1LazyDup->SubclassDatabaseField);
	}

	public function testLazyLoadedFieldsGetAllFields() {
		$subteam1 = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam1');
		$parentTeam = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$teams = DataObject::get('DataObjectTest_Team'); // query parent class
		$subteam1Lazy = $teams->find('ID', $subteam1->ID);
		$this->assertArrayNotHasKey('SubclassDatabaseField_Lazy', $subteam1Lazy->toMap());
		$this->assertArrayHasKey('SubclassDatabaseField', $subteam1Lazy->toMap());
	}

	public function testLazyLoadedFieldsOnVersionedRecords() {
		// Save another record, sanity check that we're getting the right one
		$obj2 = new VersionedTest_Subclass();
		$obj2->Name = "test2";
		$obj2->ExtraField = "foo2";
		$obj2->write();

		// Save the actual inspected record
		$obj1 = new VersionedTest_Subclass();
		$obj1->Name = "test";
		$obj1->ExtraField = "foo";
		$obj1->write();
		$version1 = $obj1->Version;
		$obj1->Name = "test2";
		$obj1->ExtraField = "baz";
		$obj1->write();
		$version2 = $obj1->Version;


		$reloaded = Versioned::get_version('VersionedTest_Subclass', $obj1->ID, $version1);
		$this->assertEquals($reloaded->Name, 'test');
		$this->assertEquals($reloaded->ExtraField, 'foo');

		$reloaded = Versioned::get_version('VersionedTest_Subclass', $obj1->ID, $version2);
		$this->assertEquals($reloaded->Name, 'test2');
		$this->assertEquals($reloaded->ExtraField, 'baz');

		$reloaded = Versioned::get_latest_version('VersionedTest_Subclass', $obj1->ID);
		$this->assertEquals($reloaded->Version, $version2);
		$this->assertEquals($reloaded->Name, 'test2');
		$this->assertEquals($reloaded->ExtraField, 'baz');

		$allVersions = Versioned::get_all_versions('VersionedTest_Subclass', $obj1->ID);
		$this->assertEquals(2, $allVersions->Count());
		$this->assertEquals($allVersions->First()->Version, $version1);
		$this->assertEquals($allVersions->First()->Name, 'test');
		$this->assertEquals($allVersions->First()->ExtraField, 'foo');
		$this->assertEquals($allVersions->Last()->Version, $version2);
		$this->assertEquals($allVersions->Last()->Name, 'test2');
		$this->assertEquals($allVersions->Last()->ExtraField, 'baz');

		$obj1->delete();
	}

	public function testLazyLoadedFieldsDoNotReferenceVersionsTable() {
		// Save another record, sanity check that we're getting the right one
		$obj2 = new VersionedTest_Subclass();
		$obj2->Name = "test2";
		$obj2->ExtraField = "foo2";
		$obj2->write();

		$obj1 = new VersionedLazySub_DataObject();
		$obj1->PageName = "old-value";
		$obj1->ExtraField = "old-value";
		$obj1ID = $obj1->write();
		$obj1->publish('Stage', 'Live');

		$obj1 = VersionedLazySub_DataObject::get()->byID($obj1ID);
		$this->assertEquals(
			'old-value',
			$obj1->PageName,
			"Correct value on base table when fetching base class"
		);
		$this->assertEquals(
			'old-value',
			$obj1->ExtraField,
			"Correct value on sub table when fetching base class"
		);

		$obj1 = VersionedLazy_DataObject::get()->byID($obj1ID);
		$this->assertEquals(
			'old-value',
			$obj1->PageName,
			"Correct value on base table when fetching sub class"
		);
		$this->assertEquals(
			'old-value',
			$obj1->ExtraField,
			"Correct value on sub table when fetching sub class"
		);

		// Force inconsistent state to test behaviour (shouldn't select from *_versions)
		DB::query(sprintf(
			"UPDATE \"VersionedLazy_DataObject_versions\" SET \"PageName\" = 'versioned-value' " .
			"WHERE \"RecordID\" = %d",
			$obj1ID
		));
		DB::query(sprintf(
			"UPDATE \"VersionedLazySub_DataObject_versions\" SET \"ExtraField\" = 'versioned-value' " .
			"WHERE \"RecordID\" = %d",
			$obj1ID
		));

		$obj1 = VersionedLazySub_DataObject::get()->byID($obj1ID);
		$this->assertEquals(
			'old-value',
			$obj1->PageName,
			"Correct value on base table when fetching base class"
		);
		$this->assertEquals(
			'old-value',
			$obj1->ExtraField,
			"Correct value on sub table when fetching base class"
		);
		$obj1 = VersionedLazy_DataObject::get()->byID($obj1ID);
		$this->assertEquals(
			'old-value',
			$obj1->PageName,
			"Correct value on base table when fetching sub class"
		);
		$this->assertEquals(
			'old-value',
			$obj1->ExtraField,
			"Correct value on sub table when fetching sub class"
		);

		// Update live table only to test behaviour (shouldn't select from *_versions or stage)
		DB::query(sprintf(
			'UPDATE "VersionedLazy_DataObject_Live" SET "PageName" = \'live-value\' WHERE "ID" = %d',
			$obj1ID
		));
		DB::query(sprintf(
			'UPDATE "VersionedLazySub_DataObject_Live" SET "ExtraField" = \'live-value\' WHERE "ID" = %d',
			$obj1ID
		));

		Versioned::reading_stage('Live');
		$obj1 = VersionedLazy_DataObject::get()->byID($obj1ID);
		$this->assertEquals(
			'live-value',
			$obj1->PageName,
			"Correct value from base table when fetching base class on live stage"
		);
		$this->assertEquals(
			'live-value',
			$obj1->ExtraField,
			"Correct value from sub table when fetching base class on live stage"
		);
	}

}


/** Additional classes for versioned lazy loading testing */
class VersionedLazy_DataObject extends DataObject implements TestOnly {
	private static $db = array(
		"PageName" => "Varchar"
	);
	private static $extensions = array(
		"Versioned('Stage', 'Live')"
	);
}

class VersionedLazySub_DataObject extends VersionedLazy_DataObject {
	private static $db = array(
		"ExtraField" => "Varchar",
	);
	private static $extensions = array(
		"Versioned('Stage', 'Live')"
	);
}
