<?php

/**
 * @package framework
 * @subpackage tests
 */
class VersionedTest extends SapphireTest {

	protected static $fixture_file = 'VersionedTest.yml';

	protected $extraDataObjects = array(
		'VersionedTest_DataObject',
		'VersionedTest_Subclass',
		'VersionedTest_AnotherSubclass',
		'VersionedTest_RelatedWithoutVersion',
		'VersionedTest_SingleStage',
		'VersionedTest_WithIndexes',
	);

	protected $requiredExtensions = array(
		"VersionedTest_DataObject" => array('Versioned'),
		"VersionedTest_WithIndexes" => array('Versioned'),
	);

	public function testUniqueIndexes() {
		$tableExpectations = array(
			'VersionedTest_WithIndexes' =>
				array('value' => true, 'message' => 'Unique indexes are unique in main table'),
			'VersionedTest_WithIndexes_versions' =>
				array('value' => false, 'message' => 'Unique indexes are no longer unique in _versions table'),
			'VersionedTest_WithIndexes_Live' =>
				array('value' => false, 'message' => 'Unique indexes are no longer unique in _Live table'),
		);
	
		// Test each table's performance
		foreach ($tableExpectations as $tableName => $expectation) {
			$indexes = DB::get_schema()->indexList($tableName);

			// Check for presence of all unique indexes
			$indexColumns = array_map(function($index) {
				return $index['value'];
			}, $indexes);
			sort($indexColumns);
			$expectedColumns = array('"UniqA"', '"UniqS"');
			$this->assertEquals(
					array_values($expectedColumns),
					array_values(array_intersect($indexColumns, $expectedColumns)),
					"$tableName has both indexes");

			// Check unique -> non-unique conversion
			foreach ($indexes as $indexKey => $indexSpec) {
				if (in_array($indexSpec['value'], $expectedColumns)) {
					$isUnique = $indexSpec['type'] === 'unique';
					$this->assertEquals($isUnique, $expectation['value'], $expectation['message']);
}
			}
		}
	}

	public function testDeletingOrphanedVersions() {
		$obj = new VersionedTest_Subclass();
		$obj->ExtraField = 'Foo'; // ensure that child version table gets written
		$obj->write();
		$obj->publish('Stage', 'Live');

		$obj->ExtraField = 'Bar'; // ensure that child version table gets written
		$obj->write();
		$obj->publish('Stage', 'Live');

		$versions = DB::query("SELECT COUNT(*) FROM \"VersionedTest_Subclass_versions\""
			. " WHERE \"RecordID\" = '$obj->ID'")->value();

		$this->assertGreaterThan(0, $versions, 'At least 1 version exists in the history of the page');

		// Force orphaning of all versions created earlier, only on parent record.
		// The child versiones table should still have the correct relationship
		DB::query("DELETE FROM \"VersionedTest_DataObject_versions\" WHERE \"RecordID\" = $obj->ID");

		// insert a record with no primary key (ID)
		DB::query("INSERT INTO \"VersionedTest_DataObject_versions\" (\"RecordID\") VALUES ($obj->ID)");

		// run the script which should clean that up
		$obj->augmentDatabase();

		$versions = DB::query("SELECT COUNT(*) FROM \"VersionedTest_Subclass_versions\""
			. " WHERE \"RecordID\" = '$obj->ID'")->value();
		$this->assertEquals(0, $versions, 'Orphaned versions on child tables are removed');

		// test that it doesn't delete records that we need
		$obj->write();
		$obj->publish('Stage', 'Live');

		$count = DB::query("SELECT COUNT(*) FROM \"VersionedTest_Subclass_versions\""
			. " WHERE \"RecordID\" = '$obj->ID'")->value();
		$obj->augmentDatabase();

		$count2 = DB::query("SELECT COUNT(*) FROM \"VersionedTest_Subclass_versions\""
			. " WHERE \"RecordID\" = '$obj->ID'")->value();

		$this->assertEquals($count, $count2);
	}

	public function testDuplicate() {
		$obj1 = new VersionedTest_Subclass();
		$obj1->ExtraField = 'Foo';
		$obj1->write(); // version 1
		$obj1->publish('Stage', 'Live');
		$obj1->ExtraField = 'Foo2';
		$obj1->write(); // version 2

		// Make duplicate
		$obj2 = $obj1->duplicate();

		// Check records differ
		$this->assertNotEquals($obj1->ID, $obj2->ID);
		$this->assertEquals(2, $obj1->Version);
		$this->assertEquals(1, $obj2->Version);
	}

	public function testForceChangeUpdatesVersion() {
		$obj = new VersionedTest_DataObject();
		$obj->Name = "test";
		$obj->write();

		$oldVersion = $obj->Version;
		$obj->forceChange();
		$obj->write();

		$this->assertTrue(
			($obj->Version > $oldVersion),
			"A object Version is increased when just calling forceChange() without any other changes"
		);
	}

	/**
	 * Test Versioned::get_including_deleted()
	 */
	public function testGetIncludingDeleted() {
		// Get all ids of pages
		$allPageIDs = DataObject::get(
			'VersionedTest_DataObject',
			"\"ParentID\" = 0", "\"VersionedTest_DataObject\".\"ID\" ASC"
		)->column('ID');

		// Modify a page, ensuring that the Version ID and Record ID will differ,
		// and then subsequently delete it
		$targetPage = $this->objFromFixture('VersionedTest_DataObject', 'page3');
		$targetPage->Content = 'To be deleted';
		$targetPage->write();
		$targetPage->delete();

		// Get all items, ignoring deleted
		$remainingPages = DataObject::get("VersionedTest_DataObject", "\"ParentID\" = 0",
			"\"VersionedTest_DataObject\".\"ID\" ASC");
		// Check that page 3 has gone
		$this->assertNotNull($remainingPages);
		$this->assertEquals(array("Page 1", "Page 2"), $remainingPages->column('Title'));

		// Get all including deleted
		$allPages = Versioned::get_including_deleted("VersionedTest_DataObject", "\"ParentID\" = 0",
			"\"VersionedTest_DataObject\".\"ID\" ASC");
		// Check that page 3 is still there
		$this->assertEquals(array("Page 1", "Page 2", "Page 3"), $allPages->column('Title'));

		// Check that the returned pages have the correct IDs
		$this->assertEquals($allPageIDs, $allPages->column('ID'));

		// Check that this still works if we switch to reading the other stage
		Versioned::reading_stage("Live");
		$allPages = Versioned::get_including_deleted("VersionedTest_DataObject", "\"ParentID\" = 0",
			"\"VersionedTest_DataObject\".\"ID\" ASC");
		$this->assertEquals(array("Page 1", "Page 2", "Page 3"), $allPages->column('Title'));

		// Check that the returned pages still have the correct IDs
		$this->assertEquals($allPageIDs, $allPages->column('ID'));
	}

	public function testVersionedFieldsAdded() {
		$obj = new VersionedTest_DataObject();
		// Check that the Version column is added as a full-fledged column
		$this->assertInstanceOf('Int', $obj->dbObject('Version'));

		$obj2 = new VersionedTest_Subclass();
		// Check that the Version column is added as a full-fledged column
		$this->assertInstanceOf('Int', $obj2->dbObject('Version'));
	}

	public function testVersionedFieldsNotInCMS() {
		$obj = new VersionedTest_DataObject();

		// the version field in cms causes issues with Versioned::augmentWrite()
		$this->assertNull($obj->getCMSFields()->dataFieldByName('Version'));
	}

	public function testPublishCreateNewVersion() {
		$page1 = $this->objFromFixture('VersionedTest_DataObject', 'page1');
		$page1->Content = 'orig';
		$page1->write();
		$oldVersion = $page1->Version;
		$page1->publish('Stage', 'Live', false);
		$this->assertEquals($oldVersion, $page1->Version, 'publish() with $createNewVersion=FALSE');

		$page1->Content = 'changed';
		$page1->write();
		$oldVersion = $page1->Version;
		$page1->publish('Stage', 'Live', true);
		$this->assertTrue($oldVersion < $page1->Version, 'publish() with $createNewVersion=TRUE');
	}

	public function testRollbackTo() {
		$page1 = $this->objFromFixture('VersionedTest_DataObject', 'page1');
		$page1->Content = 'orig';
		$page1->write();
		$page1->publish('Stage', 'Live');
		$origVersion = $page1->Version;

		$page1->Content = 'changed';
		$page1->write();
		$page1->publish('Stage', 'Live');
		$changedVersion = $page1->Version;

		$page1->doRollbackTo($origVersion);
		$page1 = Versioned::get_one_by_stage('VersionedTest_DataObject', 'Stage',
			sprintf('"VersionedTest_DataObject"."ID" = %d', $page1->ID));

		$this->assertTrue($page1->Version > $changedVersion, 'Create a new higher version number');
		$this->assertEquals('orig', $page1->Content, 'Copies the content from the old version');
	}

	public function testDeleteFromStage() {
		$page1 = $this->objFromFixture('VersionedTest_DataObject', 'page1');
		$pageID = $page1->ID;

		$page1->Content = 'orig';
		$page1->write();
		$page1->publish('Stage', 'Live');

		$this->assertEquals(1,
			DB::query('SELECT COUNT(*) FROM "VersionedTest_DataObject" WHERE "ID" = '.$pageID)->value());
		$this->assertEquals(1,
			DB::query('SELECT COUNT(*) FROM "VersionedTest_DataObject_Live" WHERE "ID" = '.$pageID)->value());

		$page1->deleteFromStage('Live');

		// Confirm that deleteFromStage() doesn't manipulate the original record
		$this->assertEquals($pageID, $page1->ID);

		$this->assertEquals(1,
			DB::query('SELECT COUNT(*) FROM "VersionedTest_DataObject" WHERE "ID" = '.$pageID)->value());
		$this->assertEquals(0,
			DB::query('SELECT COUNT(*) FROM "VersionedTest_DataObject_Live" WHERE "ID" = '.$pageID)->value());

		$page1->delete();

		$this->assertEquals(0, $page1->ID);
		$this->assertEquals(0,
			DB::query('SELECT COUNT(*) FROM "VersionedTest_DataObject" WHERE "ID" = '.$pageID)->value());
		$this->assertEquals(0,
			DB::query('SELECT COUNT(*) FROM "VersionedTest_DataObject_Live" WHERE "ID" = '.$pageID)->value());
	}

	public function testWritingNewToStage() {
		$origStage = Versioned::current_stage();

		Versioned::reading_stage("Stage");
		$page = new VersionedTest_DataObject();
		$page->Title = "testWritingNewToStage";
		$page->URLSegment = "testWritingNewToStage";
		$page->write();

		$live = Versioned::get_by_stage('VersionedTest_DataObject', 'Live', array(
			'"VersionedTest_DataObject_Live"."ID"' => $page->ID
		));
		$this->assertEquals(0, $live->count());

		$stage = Versioned::get_by_stage('VersionedTest_DataObject', 'Stage',array(
			'"VersionedTest_DataObject"."ID"' => $page->ID
		));
		$this->assertEquals(1, $stage->count());
		$this->assertEquals($stage->First()->Title, 'testWritingNewToStage');

		Versioned::reading_stage($origStage);
	}

	/**
	 * This tests for the situation described in the ticket #5596.
	 * Writing new Page to live first creates a row in VersionedTest_DataObject table (to get the new ID),
	 * then "changes it's mind" in Versioned and writes VersionedTest_DataObject_Live. It does not remove
	 * the VersionedTest_DataObject record though.
	 */
	public function testWritingNewToLive() {
		$origStage = Versioned::current_stage();

		Versioned::reading_stage("Live");
		$page = new VersionedTest_DataObject();
		$page->Title = "testWritingNewToLive";
		$page->URLSegment = "testWritingNewToLive";
		$page->write();

		$live = Versioned::get_by_stage('VersionedTest_DataObject', 'Live',array(
			'"VersionedTest_DataObject_Live"."ID"' => $page->ID
		));
		$this->assertEquals(1, $live->count());
		$this->assertEquals($live->First()->Title, 'testWritingNewToLive');

		$stage = Versioned::get_by_stage('VersionedTest_DataObject', 'Stage',array(
			'"VersionedTest_DataObject"."ID"' => $page->ID
		));
		$this->assertEquals(0, $stage->count());

		Versioned::reading_stage($origStage);
	}

	/**
	 * Tests DataObject::hasOwnTableDatabaseField
	 */
	public function testHasOwnTableDatabaseFieldWithVersioned() {
		$noversion    = new DataObject();
		$versioned    = new VersionedTest_DataObject();
		$versionedSub = new VersionedTest_Subclass();
		$versionField = new VersionedTest_UnversionedWithField();

		$this->assertFalse(
			(bool) $noversion->hasOwnTableDatabaseField('Version'),
			'Plain models have no version field.'
		);
		$this->assertEquals(
			'Int', $versioned->hasOwnTableDatabaseField('Version'),
			'The versioned ext adds an Int version field.'
		);
		$this->assertEquals(
			'Int', $versionedSub->hasOwnTableDatabaseField('Version'),
			'Sub-classes of a versioned model have a Version field.'
		);
		$this->assertEquals(
			'Varchar', $versionField->hasOwnTableDatabaseField('Version'),
			'Models w/o Versioned can have their own Version field.'
		);
	}

	/**
	 * Test that SQLQuery::queriedTables() applies the version-suffixes properly.
	 */
	public function testQueriedTables() {
		Versioned::reading_stage('Live');

		$this->assertEquals(array(
			'VersionedTest_DataObject_Live',
			'VersionedTest_Subclass_Live',
		), DataObject::get('VersionedTest_Subclass')->dataQuery()->query()->queriedTables());
	}

	public function testGetVersionWhenClassnameChanged() {
		$obj = new VersionedTest_DataObject;
		$obj->Name = "test";
		$obj->write();
		$obj->Name = "test2";
		$obj->ClassName = "VersionedTest_Subclass";
		$obj->write();
		$subclassVersion = $obj->Version;

		$obj->Name = "test3";
		$obj->ClassName = "VersionedTest_DataObject";
		$obj->write();

		// We should be able to pass the subclass and still get the correct class back
		$obj2 = Versioned::get_version("VersionedTest_Subclass", $obj->ID, $subclassVersion);
		$this->assertInstanceOf("VersionedTest_Subclass", $obj2);
		$this->assertEquals("test2", $obj2->Name);

		$obj3 = Versioned::get_latest_version("VersionedTest_Subclass", $obj->ID);
		$this->assertEquals("test3", $obj3->Name);
		$this->assertInstanceOf("VersionedTest_DataObject", $obj3);

	}

	public function testArchiveVersion() {

		// In 2005 this file was created
		SS_Datetime::set_mock_now('2005-01-01 00:00:00');
		$testPage = new VersionedTest_Subclass();
		$testPage->Title = 'Archived page';
		$testPage->Content = 'This is the content from 2005';
		$testPage->ExtraField = '2005';
		$testPage->write();

		// In 2007 we updated it
		SS_Datetime::set_mock_now('2007-01-01 00:00:00');
		$testPage->Content = "It's 2007 already!";
		$testPage->ExtraField = '2007';
		$testPage->write();

		// In 2009 we updated it again
		SS_Datetime::set_mock_now('2009-01-01 00:00:00');
		$testPage->Content = "I'm enjoying 2009";
		$testPage->ExtraField = '2009';
		$testPage->write();

		// End mock, back to the present day:)
		SS_Datetime::clear_mock_now();

		// Test 1 - 2006 Content
		singleton('VersionedTest_Subclass')->flushCache(true);
		Versioned::set_reading_mode('Archive.2006-01-01 00:00:00');
		$testPage2006 = DataObject::get('VersionedTest_Subclass')->filter(array('Title' => 'Archived page'))->first();
		$this->assertInstanceOf("VersionedTest_Subclass", $testPage2006);
		$this->assertEquals("2005", $testPage2006->ExtraField);
		$this->assertEquals("This is the content from 2005", $testPage2006->Content);

		// Test 2 - 2008 Content
		singleton('VersionedTest_Subclass')->flushCache(true);
		Versioned::set_reading_mode('Archive.2008-01-01 00:00:00');
		$testPage2008 = DataObject::get('VersionedTest_Subclass')->filter(array('Title' => 'Archived page'))->first();
		$this->assertInstanceOf("VersionedTest_Subclass", $testPage2008);
		$this->assertEquals("2007", $testPage2008->ExtraField);
		$this->assertEquals("It's 2007 already!", $testPage2008->Content);

		// Test 3 - Today
		singleton('VersionedTest_Subclass')->flushCache(true);
		Versioned::set_reading_mode('Stage.Stage');
		$testPageCurrent = DataObject::get('VersionedTest_Subclass')->filter(array('Title' => 'Archived page'))
			->first();
		$this->assertInstanceOf("VersionedTest_Subclass", $testPageCurrent);
		$this->assertEquals("2009", $testPageCurrent->ExtraField);
		$this->assertEquals("I'm enjoying 2009", $testPageCurrent->Content);
	}

	public function testAllVersions()
	{
		// In 2005 this file was created
		SS_Datetime::set_mock_now('2005-01-01 00:00:00');
		$testPage = new VersionedTest_Subclass();
		$testPage->Title = 'Archived page';
		$testPage->Content = 'This is the content from 2005';
		$testPage->ExtraField = '2005';
		$testPage->write();

		// In 2007 we updated it
		SS_Datetime::set_mock_now('2007-01-01 00:00:00');
		$testPage->Content = "It's 2007 already!";
		$testPage->ExtraField = '2007';
		$testPage->write();

		// Check both versions are returned
		$versions = Versioned::get_all_versions('VersionedTest_Subclass', $testPage->ID);
		$content = array();
		$extraFields = array();
		foreach($versions as $version)
		{
			$content[] = $version->Content;
			$extraFields[] = $version->ExtraField;
		}

		$this->assertEquals($versions->Count(), 2, 'All versions returned');
		$this->assertEquals($content, array('This is the content from 2005', "It's 2007 already!"),
			'Version fields returned');
		$this->assertEquals($extraFields, array('2005', '2007'), 'Version fields returned');

		// In 2009 we updated it again
		SS_Datetime::set_mock_now('2009-01-01 00:00:00');
		$testPage->Content = "I'm enjoying 2009";
		$testPage->ExtraField = '2009';
		$testPage->write();

		// End mock, back to the present day:)
		SS_Datetime::clear_mock_now();

		$versions = Versioned::get_all_versions('VersionedTest_Subclass', $testPage->ID);
		$content = array();
		$extraFields = array();
		foreach($versions as $version)
		{
			$content[] = $version->Content;
			$extraFields[] = $version->ExtraField;
		}

		$this->assertEquals($versions->Count(), 3, 'Additional all versions returned');
		$this->assertEquals($content,
			array('This is the content from 2005', "It's 2007 already!", "I'm enjoying 2009"),
			'Additional version fields returned');
		$this->assertEquals($extraFields, array('2005', '2007', '2009'), 'Additional version fields returned');
	}

	public function testArchiveRelatedDataWithoutVersioned() {
		SS_Datetime::set_mock_now('2009-01-01 00:00:00');

		$relatedData = new VersionedTest_RelatedWithoutVersion();
		$relatedData->Name = 'Related Data';
		$relatedDataId = $relatedData->write();

		$testData = new VersionedTest_DataObject();
		$testData->Title = 'Test';
		$testData->Content = 'Before Content';
		$testData->Related()->add($relatedData);
		$id = $testData->write();

		SS_Datetime::set_mock_now('2010-01-01 00:00:00');
		$testData->Content = 'After Content';
		$testData->write();

		Versioned::reading_archived_date('2009-01-01 19:00:00');

		$fetchedData = VersionedTest_DataObject::get()->byId($id);
		$this->assertEquals('Before Content', $fetchedData->Content, 'We see the correct content of the older version');

		$relatedData = VersionedTest_RelatedWithoutVersion::get()->byId($relatedDataId);
		$this->assertEquals(
			1,
			$relatedData->Related()->count(),
			'We have a relation, with no version table, querying it still works'
		);
	}

	public function testVersionedWithSingleStage() {
		$tables = DB::table_list();
		$this->assertContains(
			'versionedtest_singlestage',
			array_keys($tables),
			'Contains base table'
		);
		$this->assertContains(
			'versionedtest_singlestage_versions',
			array_keys($tables),
			'Contains versions table'
		);
		$this->assertNotContains(
			'versionedtest_singlestage_live',
			array_keys($tables),
			'Does not contain separate table with _Live suffix'
		);
		$this->assertNotContains(
			'versionedtest_singlestage_stage',
			array_keys($tables),
			'Does not contain separate table with _Stage suffix'
		);

		Versioned::reading_stage("Stage");
		$obj = new VersionedTest_SingleStage(array('Name' => 'MyObj'));
		$obj->write();
		$this->assertNotNull(
			VersionedTest_SingleStage::get()->byID($obj->ID),
			'Writes to and reads from default stage if its set explicitly'
		);

		Versioned::reading_stage("Live");
		$obj = new VersionedTest_SingleStage(array('Name' => 'MyObj'));
		$obj->write();
		$this->assertNotNull(
			VersionedTest_SingleStage::get()->byID($obj->ID),
			'Writes to and reads from default stage even if a non-matching stage is set'
		);
	}

	/**
	 * Test that publishing processes respects lazy loaded fields
	 */
	public function testLazyLoadFields() {
		$originalMode = Versioned::get_reading_mode();

		// Generate staging record and retrieve it from stage in live mode
		Versioned::reading_stage('Stage');
		$obj = new VersionedTest_Subclass();
		$obj->Name = 'bob';
		$obj->ExtraField = 'Field Value';
		$obj->write();
		$objID = $obj->ID;
		$filter = sprintf('"VersionedTest_DataObject"."ID" = \'%d\'', Convert::raw2sql($objID));
		Versioned::reading_stage('Live');

		// Check fields are unloaded prior to access
		$objLazy = Versioned::get_one_by_stage('VersionedTest_DataObject', 'Stage', $filter, false);
		$lazyFields = $objLazy->getQueriedDatabaseFields();
		$this->assertTrue(isset($lazyFields['ExtraField_Lazy']));
		$this->assertEquals('VersionedTest_Subclass', $lazyFields['ExtraField_Lazy']);

		// Check lazy loading works when viewing a Stage object in Live mode
		$this->assertEquals('Field Value', $objLazy->ExtraField);

		// Test that writeToStage respects lazy loaded fields
		$objLazy = Versioned::get_one_by_stage('VersionedTest_DataObject', 'Stage', $filter, false);
		$objLazy->writeToStage('Live');
		$objLive = Versioned::get_one_by_stage('VersionedTest_DataObject', 'Live', $filter, false);
		$liveLazyFields = $objLive->getQueriedDatabaseFields();

		// Check fields are unloaded prior to access
		$this->assertTrue(isset($liveLazyFields['ExtraField_Lazy']));
		$this->assertEquals('VersionedTest_Subclass', $liveLazyFields['ExtraField_Lazy']);

		// Check that live record has original value
		$this->assertEquals('Field Value', $objLive->ExtraField);

		Versioned::set_reading_mode($originalMode);
	}

	/**
	 * Tests that reading mode persists between requests
	 */
	public function testReadingPersistent() {
		$session = Injector::inst()->create('Session', array());

		// Set to stage
		Director::test('/?stage=Stage', null, $session);
		$this->assertEquals(
			'Stage.Stage',
			$session->inst_get('readingMode'),
			'Check querystring changes reading mode to Stage'
		);
		Director::test('/', null, $session);
		$this->assertEquals(
			'Stage.Stage',
			$session->inst_get('readingMode'),
			'Check that subsequent requests in the same session remain in Stage mode'
		);

		// Test live persists
		Director::test('/?stage=Live', null, $session);
		$this->assertEquals(
			'Stage.Live',
			$session->inst_get('readingMode'),
			'Check querystring changes reading mode to Live'
		);
		Director::test('/', null, $session);
		$this->assertEquals(
			'Stage.Live',
			$session->inst_get('readingMode'),
			'Check that subsequent requests in the same session remain in Live mode'
		);

		// Test that session doesn't redundantly store the default stage if it doesn't need to
		$session2 = Injector::inst()->create('Session', array());
		Director::test('/', null, $session2);
		$this->assertEmpty($session2->inst_changedData());
		Director::test('/?stage=Live', null, $session2);
		$this->assertEmpty($session2->inst_changedData());

		// Test choose_site_stage
		Session::set('readingMode', 'Stage.Stage');
		Versioned::choose_site_stage();
		$this->assertEquals('Stage.Stage', Versioned::get_reading_mode());
		Session::set('readingMode', 'Archive.2014-01-01');
		Versioned::choose_site_stage();
		$this->assertEquals('Archive.2014-01-01', Versioned::get_reading_mode());
		Session::clear('readingMode');
		Versioned::choose_site_stage();
		$this->assertEquals('Stage.Live', Versioned::get_reading_mode());
	}

	/**
	 * Ensures that the latest version of a record is the expected value
	 *
	 * @param type $record
	 * @param type $version
	 */
	protected function assertRecordHasLatestVersion($record, $version) {
		foreach(ClassInfo::ancestry(get_class($record), true) as $table) {
			$versionForClass = DB::prepared_query(
				$sql = "SELECT MAX(\"Version\") FROM \"{$table}_versions\" WHERE \"RecordID\" = ?",
				array($record->ID)
			)->value();
			$this->assertEquals($version, $versionForClass, "That the table $table has the latest version $version");
		}
	}

	/**
	 * Tests that multi-table dataobjects are correctly versioned
	 */
	public function testWriteToStage() {
		// Test subclass with versioned extension directly added
		$record = VersionedTest_Subclass::create();
		$record->Title = "Test A";
		$record->ExtraField = "Test A";
		$record->writeToStage("Stage");
		$this->assertRecordHasLatestVersion($record, 1);
		$record->publish("Stage", "Live");
		$this->assertRecordHasLatestVersion($record, 1);
		$record->Title = "Test A2";
		$record->ExtraField = "Test A2";
		$record->writeToStage("Stage");
		$this->assertRecordHasLatestVersion($record, 2);

		// Test subclass without changes to base class
		$record = VersionedTest_Subclass::create();
		$record->ExtraField = "Test B";
		$record->writeToStage("Stage");
		$this->assertRecordHasLatestVersion($record, 1);
		$record->publish("Stage", "Live");
		$this->assertRecordHasLatestVersion($record, 1);
		$record->ExtraField = "Test B2";
		$record->writeToStage("Stage");
		$this->assertRecordHasLatestVersion($record, 2);

		// Test subclass without changes to sub class
		$record = VersionedTest_Subclass::create();
		$record->Title = "Test C";
		$record->writeToStage("Stage");
		$this->assertRecordHasLatestVersion($record, 1);
		$record->publish("Stage", "Live");
		$this->assertRecordHasLatestVersion($record, 1);
		$record->Title = "Test C2";
		$record->writeToStage("Stage");
		$this->assertRecordHasLatestVersion($record, 2);

		// Test subclass with versioned extension only added to the base clases
		$record = VersionedTest_AnotherSubclass::create();
		$record->Title = "Test A";
		$record->AnotherField = "Test A";
		$record->writeToStage("Stage");
		$this->assertRecordHasLatestVersion($record, 1);
		$record->publish("Stage", "Live");
		$this->assertRecordHasLatestVersion($record, 1);
		$record->Title = "Test A2";
		$record->AnotherField = "Test A2";
		$record->writeToStage("Stage");
		$this->assertRecordHasLatestVersion($record, 2);


		// Test subclass without changes to base class
		$record = VersionedTest_AnotherSubclass::create();
		$record->AnotherField = "Test B";
		$record->writeToStage("Stage");
		$this->assertRecordHasLatestVersion($record, 1);
		$record->publish("Stage", "Live");
		$this->assertRecordHasLatestVersion($record, 1);
		$record->AnotherField = "Test B2";
		$record->writeToStage("Stage");
		$this->assertRecordHasLatestVersion($record, 2);

		// Test subclass without changes to sub class
		$record = VersionedTest_AnotherSubclass::create();
		$record->Title = "Test C";
		$record->writeToStage("Stage");
		$this->assertRecordHasLatestVersion($record, 1);
		$record->publish("Stage", "Live");
		$this->assertRecordHasLatestVersion($record, 1);
		$record->Title = "Test C2";
		$record->writeToStage("Stage");
		$this->assertRecordHasLatestVersion($record, 2);
	}

	public function testVersionedHandlesRenamedDataObjectFields(){
		Config::inst()->remove('VersionedTest_RelatedWithoutVersion','db','Name','Varchar');

		Config::inst()->update('VersionedTest_RelatedWithoutVersion','db',array(
			"NewField" => "Varchar",
		));

		VersionedTest_RelatedWithoutVersion::add_extension("Versioned('Stage', 'Live')");
		$this->resetDBSchema(true);
		$testData = new VersionedTest_RelatedWithoutVersion();
		$testData->NewField = 'Test';
		$testData->write();
	}
}


/**
 * @package framework
 * @subpackage tests
 */
class VersionedTest_DataObject extends DataObject implements TestOnly {
	private static $db = array(
		"Name" => "Varchar",
		'Title' => 'Varchar',
		'Content' => 'HTMLText'
	);

	private static $extensions = array(
		"Versioned('Stage', 'Live')"
	);

	private static $has_one = array(
		'Parent' => 'VersionedTest_DataObject'
	);

	private static $many_many = array(
		'Related' => 'VersionedTest_RelatedWithoutVersion'
	);

}

class VersionedTest_WithIndexes extends DataObject implements TestOnly {

	private static $db = array(
		'UniqA' => 'Int',
		'UniqS' => 'Int',
	);
	private static $extensions = array(
		"Versioned('Stage', 'Live')"
	);
	private static $indexes = array(
		'UniqS_idx' => 'unique ("UniqS")',
		'UniqA_idx' => array('type' => 'unique', 'name' => 'UniqA_idx', 'value' => '"UniqA"',),
	);

}

/**
 * @package framework
 * @subpackage tests
 */
class VersionedTest_RelatedWithoutVersion extends DataObject implements TestOnly {

	private static $db = array(
		'Name' => 'Varchar'
	);

	private static $belongs_many_many = array(
		'Related' => 'VersionedTest_DataObject'
	);

}

/**
 * @package framework
 * @subpackage tests
 */
class VersionedTest_Subclass extends VersionedTest_DataObject implements TestOnly {
	private static $db = array(
		"ExtraField" => "Varchar",
	);

	private static $extensions = array(
		"Versioned('Stage', 'Live')"
	);
}

/**
 * @package framework
 * @subpackage tests
 */
class VersionedTest_AnotherSubclass extends VersionedTest_DataObject implements TestOnly {
	private static $db = array(
		"AnotherField" => "Varchar"
	);
}

/**
 * @package framework
 * @subpackage tests
 */
class VersionedTest_UnversionedWithField extends DataObject implements TestOnly {
	private static $db = array('Version' => 'Varchar(255)');
}

class VersionedTest_SingleStage extends DataObject implements TestOnly {
	private static $db = array(
		'Name' => 'Varchar'
	);

	private static $extensions = array(
		'Versioned("Stage")'
	);
}
