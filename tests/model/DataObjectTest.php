<?php
/**
 * @package framework
 * @subpackage tests
 */
class DataObjectTest extends SapphireTest {
	
	static $fixture_file = 'DataObjectTest.yml';

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
	
	function testDataIntegrityWhenTwoSubclassesHaveSameField() {
		// Save data into DataObjectTest_SubTeam.SubclassDatabaseField
		$obj = new DataObjectTest_SubTeam();
		$obj->SubclassDatabaseField = "obj-SubTeam";
		$obj->write();

		// Change the class
		$obj->ClassName = 'OtherSubclassWithSameField';
		$obj->write();
		$obj->flushCache();

		// Re-fetch from the database and confirm that the data is sourced from
		// OtherSubclassWithSameField.SubclassDatabaseField
		$obj = DataObject::get_by_id('DataObjectTest_Team', $obj->ID);
		$this->assertNull($obj->SubclassDatabaseField);

		// Confirm that save the object in the other direction.
		$obj->SubclassDatabaseField = 'obj-Other';
		$obj->write();

		$obj->ClassName = 'DataObjectTest_SubTeam';
		$obj->write();
		$obj->flushCache();

		// If we restore the class, the old value has been lying dormant and will be available again.
		// NOTE: This behaviour is volatile; we may change this in the future to clear fields that
		// are no longer relevant when changing ClassName
		$obj = DataObject::get_by_id('DataObjectTest_Team', $obj->ID);
		$this->assertEquals('obj-SubTeam', $obj->SubclassDatabaseField);
	}

	/**
	 * Test deletion of DataObjects
	 *   - Deleting using delete() on the DataObject
	 *   - Deleting using DataObject::delete_by_id()
	 */
	function testDelete() {
		// Test deleting using delete() on the DataObject
		// Get the first page
		$obj = $this->objFromFixture('DataObjectTest_Player', 'captain1');
		$objID = $obj->ID;
		// Check the page exists before deleting
		$this->assertTrue(is_object($obj) && $obj->exists());
		// Delete the page
		$obj->delete();
		// Check that page does not exist after deleting
		$obj = DataObject::get_by_id('DataObjectTest_Player', $objID);
		$this->assertTrue(!$obj || !$obj->exists());


		// Test deleting using DataObject::delete_by_id()
		// Get the second page
		$obj = $this->objFromFixture('DataObjectTest_Player', 'captain2');
		$objID = $obj->ID;
		// Check the page exists before deleting
		$this->assertTrue(is_object($obj) && $obj->exists());
		// Delete the page
		DataObject::delete_by_id('DataObjectTest_Player', $obj->ID);
		// Check that page does not exist after deleting
		$obj = DataObject::get_by_id('DataObjectTest_Player', $objID);
		$this->assertTrue(!$obj || !$obj->exists());
	}

	/**
	 * Test methods that get DataObjects
	 *   - DataObject::get()
	 *       - All records of a DataObject
	 *       - Filtering
	 *       - Sorting
	 *       - Joins
	 *       - Limit
	 *       - Container class
	 *   - DataObject::get_by_id()
	 *   - DataObject::get_one()
	 *        - With and without caching
	 *        - With and without ordering
	 */
	function testGet() {
		// Test getting all records of a DataObject
		$comments = DataObject::get('DataObjectTest_TeamComment');
		$this->assertEquals(3, $comments->Count());

		// Test WHERE clause
		$comments = DataObject::get('DataObjectTest_TeamComment', "\"Name\"='Bob'");
		$this->assertEquals(1, $comments->Count());
		foreach($comments as $comment) {
			$this->assertEquals('Bob', $comment->Name);
		}

		// Test sorting
		$comments = DataObject::get('DataObjectTest_TeamComment', '', "\"Name\" ASC");
		$this->assertEquals(3, $comments->Count());
		$this->assertEquals('Bob', $comments->First()->Name);
		$comments = DataObject::get('DataObjectTest_TeamComment', '', "\"Name\" DESC");
		$this->assertEquals(3, $comments->Count());
		$this->assertEquals('Phil', $comments->First()->Name);

		// Test join - 2.4 only
		$originalDeprecation = Deprecation::dump_settings();
		Deprecation::notification_version('2.4');

		$comments = DataObject::get(
			'DataObjectTest_TeamComment',
			"\"DataObjectTest_Team\".\"Title\" = 'Team 1'",
			"\"Name\" ASC",
			"INNER JOIN \"DataObjectTest_Team\" ON \"DataObjectTest_TeamComment\".\"TeamID\" = \"DataObjectTest_Team\".\"ID\""
		);

		$this->assertEquals(2, $comments->Count());
		$this->assertEquals('Bob', $comments->First()->Name);
		$this->assertEquals('Joe', $comments->Last()->Name);

		Deprecation::restore_settings($originalDeprecation);

		// Test limit
		$comments = DataObject::get('DataObjectTest_TeamComment', '', "\"Name\" ASC", '', '1,2');
		$this->assertEquals(2, $comments->Count());
		$this->assertEquals('Joe', $comments->First()->Name);
		$this->assertEquals('Phil', $comments->Last()->Name);

		// Test get_by_id()
		$captain1ID = $this->idFromFixture('DataObjectTest_Player', 'captain1');
		$captain1 = DataObject::get_by_id('DataObjectTest_Player', $captain1ID);
		$this->assertEquals('Captain', $captain1->FirstName);

		// Test get_one() without caching
		$comment1 = DataObject::get_one('DataObjectTest_TeamComment', "\"Name\" = 'Joe'", false);
		$comment1->Comment = "Something Else";

		$comment2 = DataObject::get_one('DataObjectTest_TeamComment', "\"Name\" = 'Joe'", false);
		$this->assertNotEquals($comment1->Comment, $comment2->Comment);

		// Test get_one() with caching
		$comment1 = DataObject::get_one('DataObjectTest_TeamComment', "\"Name\" = 'Bob'", true);
		$comment1->Comment = "Something Else";

		$comment2 = DataObject::get_one('DataObjectTest_TeamComment', "\"Name\" = 'Bob'", true);
		$this->assertEquals((string)$comment1->Comment, (string)$comment2->Comment);

		// Test get_one() with order by without caching
		$comment = DataObject::get_one('DataObjectTest_TeamComment', '', false, "\"Name\" ASC");
		$this->assertEquals('Bob', $comment->Name);

		$comment = DataObject::get_one('DataObjectTest_TeamComment', '', false, "\"Name\" DESC");
		$this->assertEquals('Phil', $comment->Name);

		// Test get_one() with order by with caching
		$comment = DataObject::get_one('DataObjectTest_TeamComment', '', true, '"Name" ASC');
		$this->assertEquals('Bob', $comment->Name);
		$comment = DataObject::get_one('DataObjectTest_TeamComment', '', true, '"Name" DESC');
		$this->assertEquals('Phil', $comment->Name);
	}

	function testGetSubclassFields() {
		/* Test that fields / has_one relations from the parent table and the subclass tables are extracted */
		$captain1 = $this->objFromFixture("DataObjectTest_Player", "captain1");
		// Base field
		$this->assertEquals('Captain', $captain1->FirstName);
		// Subclass field
		$this->assertEquals('007', $captain1->ShirtNumber);
		// Subclass has_one relation
		$this->assertEquals($this->idFromFixture('DataObjectTest_Team', 'team1'), $captain1->FavouriteTeamID);
	}

	function testGetRelationClass() {
		$obj = new DataObjectTest_Player();
		$this->assertEquals(singleton('DataObjectTest_Player')->getRelationClass('FavouriteTeam'), 'DataObjectTest_Team', 'has_one is properly inspected');
		$this->assertEquals(singleton('DataObjectTest_Company')->getRelationClass('CurrentStaff'), 'DataObjectTest_Staff', 'has_many is properly inspected');
		$this->assertEquals(singleton('DataObjectTest_Team')->getRelationClass('Players'), 'DataObjectTest_Player', 'many_many is properly inspected');
		$this->assertEquals(singleton('DataObjectTest_Player')->getRelationClass('Teams'), 'DataObjectTest_Team', 'belongs_many_many is properly inspected');
		$this->assertEquals(singleton('DataObjectTest_CEO')->getRelationClass('Company'), 'DataObjectTest_Company', 'belongs_to is properly inspected');
	}

	function testGetHasOneRelations() {
		$captain1 = $this->objFromFixture("DataObjectTest_Player", "captain1");
		/* There will be a field called (relname)ID that contains the ID of the object linked to via the has_one relation */
		$this->assertEquals($this->idFromFixture('DataObjectTest_Team', 'team1'), $captain1->FavouriteTeamID);
		/* There will be a method called $obj->relname() that returns the object itself */
		$this->assertEquals($this->idFromFixture('DataObjectTest_Team', 'team1'), $captain1->FavouriteTeam()->ID);
	}

	function testLimitAndCount() {
		$players = DataObject::get("DataObjectTest_Player");

		// There's 4 records in total
		$this->assertEquals(4, $players->count());

		// Testing "##, ##" syntax
		$this->assertEquals(4, $players->limit(20)->count());
		$this->assertEquals(4, $players->limit(20, 0)->count());
		$this->assertEquals(0, $players->limit(20, 20)->count());
		$this->assertEquals(2, $players->limit(2, 0)->count());
		$this->assertEquals(1, $players->limit(5, 3)->count());
	}

	/**
	 * Test writing of database columns which don't correlate to a DBField,
	 * e.g. all relation fields on has_one/has_many like "ParentID".
	 *
	 */
	function testWritePropertyWithoutDBField() {
		$obj = $this->objFromFixture('DataObjectTest_Player', 'captain1');
		$obj->FavouriteTeamID = 99;
		$obj->write();
		// reload the page from the database
		$savedObj = DataObject::get_by_id('DataObjectTest_Player', $obj->ID);
		$this->assertTrue($savedObj->FavouriteTeamID == 99);
	}

	/**
	 * Test has many relationships
	 *   - Test getComponents() gets the ComponentSet of the other side of the relation
	 *   - Test the IDs on the DataObjects are set correctly
	 */
	function testHasManyRelationships() {
		$team = $this->objFromFixture('DataObjectTest_Team', 'team1');

		// Test getComponents() gets the ComponentSet of the other side of the relation
		$this->assertTrue($team->Comments()->Count() == 2);

		// Test the IDs on the DataObjects are set correctly
		foreach($team->Comments() as $comment) {
			$this->assertEquals($team->ID, $comment->TeamID);
		}

		// Test that we can add and remove items that already exist in the database
		$newComment = new DataObjectTest_TeamComment();
		$newComment->Name = "Automated commenter";
		$newComment->Comment = "This is a new comment";
		$newComment->write();
		$team->Comments()->add($newComment);
		$this->assertEquals($team->ID, $newComment->TeamID);

		$comment1 = $this->fixture->objFromFixture('DataObjectTest_TeamComment', 'comment1');
		$comment2 = $this->fixture->objFromFixture('DataObjectTest_TeamComment', 'comment2');
		$team->Comments()->remove($comment2);

		$commentIDs = $team->Comments()->sort('ID')->column('ID');
		$this->assertEquals(array($comment1->ID, $newComment->ID), $commentIDs);
	}

	function testHasOneRelationship() {
		$team1 = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$player1 = $this->objFromFixture('DataObjectTest_Player', 'player1');

		// Add a captain to team 1
		$team1->setField('CaptainID', $player1->ID);
		$team1->write();

		$this->assertEquals($player1->ID, $team1->Captain()->ID, 'The captain exists for team 1');
		$this->assertEquals($player1->ID, $team1->getComponent('Captain')->ID, 'The captain exists through the component getter');

		$this->assertEquals($team1->Captain()->FirstName, 'Player 1', 'Player 1 is the captain');
		$this->assertEquals($team1->getComponent('Captain')->FirstName, 'Player 1', 'Player 1 is the captain');
	}
	
	/**
	 * @todo Extend type change tests (e.g. '0'==NULL)
	 */
	function testChangedFields() {
		$obj = $this->objFromFixture('DataObjectTest_Player', 'captain1');
		$obj->FirstName = 'Captain-changed';
		$obj->IsRetired = true;

		$this->assertEquals(
			$obj->getChangedFields(false, 1),
			array(
				'FirstName' => array(
					'before' => 'Captain',
					'after' => 'Captain-changed',
					'level' => 2
				),
				'IsRetired' => array(
					'before' => 1,
					'after' => true,
					'level' => 1
				)
			),
			'Changed fields are correctly detected with strict type changes (level=1)'
		);
		
		$this->assertEquals(
			$obj->getChangedFields(false, 2),
			array(
				'FirstName' => array(
					'before'=>'Captain',
					'after'=>'Captain-changed',
					'level' => 2
				)
			),
			'Changed fields are correctly detected while ignoring type changes (level=2)'
		);
		
		$newObj = new DataObjectTest_Player();
		$newObj->FirstName = "New Player";
		$this->assertEquals(
			$newObj->getChangedFields(false, 2),
			array(
				'FirstName' => array(
					'before' => null,
					'after' => 'New Player',
					'level' => 2
				)
			),
			'Initialised fields are correctly detected as full changes'
		);
	}
	
	function testIsChanged() {
		$obj = $this->objFromFixture('DataObjectTest_Player', 'captain1');
		$obj->FirstName = 'Captain-changed';
		$obj->IsRetired = true; // type change only, database stores "1"

		$this->assertTrue($obj->isChanged('FirstName', 1));
		$this->assertTrue($obj->isChanged('FirstName', 2));
		$this->assertTrue($obj->isChanged('IsRetired', 1));
		$this->assertFalse($obj->isChanged('IsRetired', 2));
		$this->assertFalse($obj->isChanged('Email', 1), 'Doesnt change mark unchanged property');
		$this->assertFalse($obj->isChanged('Email', 2), 'Doesnt change mark unchanged property');
		
		$newObj = new DataObjectTest_Player();
		$newObj->FirstName = "New Player";
		$this->assertTrue($newObj->isChanged('FirstName', 1));
		$this->assertTrue($newObj->isChanged('FirstName', 2));
		$this->assertFalse($newObj->isChanged('Email', 1));
		$this->assertFalse($newObj->isChanged('Email', 2));
		
		$newObj->write();
		$this->assertFalse($newObj->isChanged('FirstName', 1));
		$this->assertFalse($newObj->isChanged('FirstName', 2));
		$this->assertFalse($newObj->isChanged('Email', 1));
		$this->assertFalse($newObj->isChanged('Email', 2));
		
		$obj = $this->objFromFixture('DataObjectTest_Player', 'captain1');
		$obj->FirstName = null;
		$this->assertTrue($obj->isChanged('FirstName', 1));
		$this->assertTrue($obj->isChanged('FirstName', 2));
		
		/* Test when there's not field provided */ 
		$obj = $this->objFromFixture('DataObjectTest_Player', 'captain1');
		$obj->FirstName = "New Player"; 
		$this->assertTrue($obj->isChanged());
		
		$obj->write(); 
		$this->assertFalse($obj->isChanged());
	}
	
	function testRandomSort() {
		/* If we perform the same regularly sorted query twice, it should return the same results */
		$itemsA = DataObject::get("DataObjectTest_TeamComment", "", "ID");
		foreach($itemsA as $item) $keysA[] = $item->ID;

		$itemsB = DataObject::get("DataObjectTest_TeamComment", "", "ID");
		foreach($itemsB as $item) $keysB[] = $item->ID;
		
		/* Test when there's not field provided */ 
		$obj = $this->objFromFixture('DataObjectTest_Player', 'captain1');
		$obj->FirstName = "New Player"; 
		$this->assertTrue($obj->isChanged());
		
		$obj->write(); 
		$this->assertFalse($obj->isChanged());

		/* If we perform the same random query twice, it shouldn't return the same results */
		$itemsA = DataObject::get("DataObjectTest_TeamComment", "", DB::getConn()->random());
		$itemsB = DataObject::get("DataObjectTest_TeamComment", "", DB::getConn()->random());
		$itemsC = DataObject::get("DataObjectTest_TeamComment", "", DB::getConn()->random());
		$itemsD = DataObject::get("DataObjectTest_TeamComment", "", DB::getConn()->random());
		foreach($itemsA as $item) $keysA[] = $item->ID;
		foreach($itemsB as $item) $keysB[] = $item->ID;
		foreach($itemsC as $item) $keysC[] = $item->ID;
		foreach($itemsD as $item) $keysD[] = $item->ID;
		
		// These shouldn't all be the same (run it 4 times to minimise chance of an accidental collision)
		// There's about a 1 in a billion chance of an accidental collision
		$this->assertTrue($keysA != $keysB || $keysB != $keysC || $keysC != $keysD);
	}
	
	function testWriteSavesToHasOneRelations() {
		/* DataObject::write() should save to a has_one relationship if you set a field called (relname)ID */
		$team = new DataObjectTest_Team();
		$captainID = $this->idFromFixture('DataObjectTest_Player', 'player1');
		$team->CaptainID = $captainID;
		$team->write();
		$this->assertEquals($captainID, DB::query("SELECT \"CaptainID\" FROM \"DataObjectTest_Team\" WHERE \"ID\" = $team->ID")->value());
		
		/* After giving it a value, you should also be able to set it back to null */
		$team->CaptainID = '';
		$team->write();
		$this->assertEquals(0, DB::query("SELECT \"CaptainID\" FROM \"DataObjectTest_Team\" WHERE \"ID\" = $team->ID")->value());

		/* You should also be able to save a blank to it when it's first created */
		$team = new DataObjectTest_Team();
		$team->CaptainID = '';
		$team->write();
		$this->assertEquals(0, DB::query("SELECT \"CaptainID\" FROM \"DataObjectTest_Team\" WHERE \"ID\" = $team->ID")->value());
		
		/* Ditto for existing records without a value */
		$existingTeam = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$existingTeam->CaptainID = '';
		$existingTeam->write();
		$this->assertEquals(0, DB::query("SELECT \"CaptainID\" FROM \"DataObjectTest_Team\" WHERE \"ID\" = $existingTeam->ID")->value());
	}
	
	function testCanAccessHasOneObjectsAsMethods() {
		/* If you have a has_one relation 'Captain' on $obj, and you set the $obj->CaptainID = (ID), then the object itself should
		 * be accessible as $obj->Captain() */
		$team = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$captainID = $this->idFromFixture('DataObjectTest_Player', 'captain1');
		
		$team->CaptainID = $captainID;
		$this->assertNotNull($team->Captain());
		$this->assertEquals($captainID, $team->Captain()->ID);
	}
	
	function testFieldNamesThatMatchMethodNamesWork() {
		/* Check that a field name that corresponds to a method on DataObject will still work */
		$obj = new DataObjectTest_Fixture();
		$obj->Data = "value1";
		$obj->DbObject = "value2";
		$obj->Duplicate = "value3";
		$obj->write();

		$this->assertNotNull($obj->ID);
		$this->assertEquals('value1', DB::query("SELECT \"Data\" FROM \"DataObjectTest_Fixture\" WHERE \"ID\" = $obj->ID")->value());
		$this->assertEquals('value2', DB::query("SELECT \"DbObject\" FROM \"DataObjectTest_Fixture\" WHERE \"ID\" = $obj->ID")->value());
		$this->assertEquals('value3', DB::query("SELECT \"Duplicate\" FROM \"DataObjectTest_Fixture\" WHERE \"ID\" = $obj->ID")->value());
	}
	
	/**
	 * @todo Re-enable all test cases for field existence after behaviour has been fixed
	 */
	function testFieldExistence() {
		$teamInstance = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$teamSingleton = singleton('DataObjectTest_Team');
		
		$subteamInstance = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam1');
		$subteamSingleton = singleton('DataObjectTest_SubTeam');
		
		/* hasField() singleton checks */
		$this->assertTrue($teamSingleton->hasField('ID'), 'hasField() finds built-in fields in singletons');
		$this->assertTrue($teamSingleton->hasField('Title'), 'hasField() finds custom fields in singletons');
		
		/* hasField() instance checks */
		$this->assertFalse($teamInstance->hasField('NonExistingField'), 'hasField() doesnt find non-existing fields in instances');
		$this->assertTrue($teamInstance->hasField('ID'), 'hasField() finds built-in fields in instances');
		$this->assertTrue($teamInstance->hasField('Created'), 'hasField() finds built-in fields in instances');
		$this->assertTrue($teamInstance->hasField('DatabaseField'), 'hasField() finds custom fields in instances');
		//$this->assertFalse($teamInstance->hasField('SubclassDatabaseField'), 'hasField() doesnt find subclass fields in parentclass instances');
		$this->assertTrue($teamInstance->hasField('DynamicField'), 'hasField() finds dynamic getters in instances');
		$this->assertTrue($teamInstance->hasField('HasOneRelationshipID'), 'hasField() finds foreign keys in instances');
		$this->assertTrue($teamInstance->hasField('ExtendedDatabaseField'), 'hasField() finds extended fields in instances');
		$this->assertTrue($teamInstance->hasField('ExtendedHasOneRelationshipID'), 'hasField() finds extended foreign keys in instances');
		//$this->assertTrue($teamInstance->hasField('ExtendedDynamicField'), 'hasField() includes extended dynamic getters in instances');
		
		/* hasField() subclass checks */
		$this->assertTrue($subteamInstance->hasField('ID'), 'hasField() finds built-in fields in subclass instances');
		$this->assertTrue($subteamInstance->hasField('Created'), 'hasField() finds built-in fields in subclass instances');
		$this->assertTrue($subteamInstance->hasField('DatabaseField'), 'hasField() finds custom fields in subclass instances');
		$this->assertTrue($subteamInstance->hasField('SubclassDatabaseField'), 'hasField() finds custom fields in subclass instances');
		$this->assertTrue($subteamInstance->hasField('DynamicField'), 'hasField() finds dynamic getters in subclass instances');
		$this->assertTrue($subteamInstance->hasField('HasOneRelationshipID'), 'hasField() finds foreign keys in subclass instances');
		$this->assertTrue($subteamInstance->hasField('ExtendedDatabaseField'), 'hasField() finds extended fields in subclass instances');
		$this->assertTrue($subteamInstance->hasField('ExtendedHasOneRelationshipID'), 'hasField() finds extended foreign keys in subclass instances');
		
		/* hasDatabaseField() singleton checks */
		//$this->assertTrue($teamSingleton->hasDatabaseField('ID'), 'hasDatabaseField() finds built-in fields in singletons');
		$this->assertTrue($teamSingleton->hasDatabaseField('Title'), 'hasDatabaseField() finds custom fields in singletons');
		
		/* hasDatabaseField() instance checks */
		$this->assertFalse($teamInstance->hasDatabaseField('NonExistingField'), 'hasDatabaseField() doesnt find non-existing fields in instances');
		//$this->assertTrue($teamInstance->hasDatabaseField('ID'), 'hasDatabaseField() finds built-in fields in instances');
		$this->assertTrue($teamInstance->hasDatabaseField('Created'), 'hasDatabaseField() finds built-in fields in instances');
		$this->assertTrue($teamInstance->hasDatabaseField('DatabaseField'), 'hasDatabaseField() finds custom fields in instances');
		$this->assertFalse($teamInstance->hasDatabaseField('SubclassDatabaseField'), 'hasDatabaseField() doesnt find subclass fields in parentclass instances');
		//$this->assertFalse($teamInstance->hasDatabaseField('DynamicField'), 'hasDatabaseField() doesnt dynamic getters in instances');
		$this->assertTrue($teamInstance->hasDatabaseField('HasOneRelationshipID'), 'hasDatabaseField() finds foreign keys in instances');
		$this->assertTrue($teamInstance->hasDatabaseField('ExtendedDatabaseField'), 'hasDatabaseField() finds extended fields in instances');
		$this->assertTrue($teamInstance->hasDatabaseField('ExtendedHasOneRelationshipID'), 'hasDatabaseField() finds extended foreign keys in instances');
		$this->assertFalse($teamInstance->hasDatabaseField('ExtendedDynamicField'), 'hasDatabaseField() doesnt include extended dynamic getters in instances');
		
		/* hasDatabaseField() subclass checks */
		$this->assertTrue($subteamInstance->hasField('DatabaseField'), 'hasField() finds custom fields in subclass instances');
		$this->assertTrue($subteamInstance->hasField('SubclassDatabaseField'), 'hasField() finds custom fields in subclass instances');
	
	}
	
	/**
	 * @todo Re-enable all test cases for field inheritance aggregation after behaviour has been fixed
	 */	
	function testFieldInheritance() {
		$teamInstance = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$subteamInstance = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam1');
		
		$this->assertEquals(
			array_keys($teamInstance->inheritedDatabaseFields()),
			array(
				//'ID',
				//'ClassName',
				//'Created',
				//'LastEdited',
				'Title',
				'DatabaseField',
				'ExtendedDatabaseField',
				'CaptainID',
				'HasOneRelationshipID',
				'ExtendedHasOneRelationshipID'
			),
			'inheritedDatabaseFields() contains all fields defined on instance, including base fields, extended fields and foreign keys'
		);
		
		$this->assertEquals(
			array_keys(DataObject::database_fields('DataObjectTest_Team')),
			array(
				//'ID',
				'ClassName',
				'Created',
				'LastEdited',
				'Title',
				'DatabaseField',
				'ExtendedDatabaseField',
				'CaptainID',
				'HasOneRelationshipID',
				'ExtendedHasOneRelationshipID'
			),
			'databaseFields() contains only fields defined on instance, including base fields, extended fields and foreign keys'
		);
		
		$this->assertEquals(
			array_keys($subteamInstance->inheritedDatabaseFields()),
			array(
				//'ID',
				//'ClassName',
				//'Created',
				//'LastEdited',
				'SubclassDatabaseField',
				'ParentTeamID',
				'Title',
				'DatabaseField',
				'ExtendedDatabaseField',
				'CaptainID',
				'HasOneRelationshipID',
				'ExtendedHasOneRelationshipID',
			),
			'inheritedDatabaseFields() on subclass contains all fields defined on instance, including base fields, extended fields and foreign keys'
		);
		
		$this->assertEquals(
			array_keys(DataObject::database_fields('DataObjectTest_SubTeam')),
			array(
				'SubclassDatabaseField',
				'ParentTeamID',
			),
			'databaseFields() on subclass contains only fields defined on instance'
		);
	}
	
	function testDataObjectUpdate() {
		/* update() calls can use the dot syntax to reference has_one relations and other methods that return objects */
		$team1 = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$team1->CaptainID = $this->idFromFixture('DataObjectTest_Player', 'captain1');
		
		$team1->update(array(
			'DatabaseField' => 'Something',
			'Captain.FirstName' => 'Jim',
			'Captain.Email' => 'jim@example.com',
			'Captain.FavouriteTeam.Title' => 'New and improved team 1',
		));
		
		/* Test the simple case of updating fields on the object itself */
		$this->assertEquals('Something', $team1->DatabaseField);

		/* Setting Captain.Email and Captain.FirstName will have updated DataObjectTest_Captain.captain1 in the database.  Although update()
		 * doesn't usually write, it does write related records automatically. */
		$captain1 = $this->objFromFixture('DataObjectTest_Player', 'captain1');
		$this->assertEquals('Jim', $captain1->FirstName);
		$this->assertEquals('jim@example.com', $captain1->Email);
		
		/* Jim's favourite team is team 1; we need to reload the object to the the change that setting Captain.FavouriteTeam.Title made */
		$reloadedTeam1 = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$this->assertEquals('New and improved team 1', $reloadedTeam1->Title);
	}

	public function testWritingInvalidDataObjectThrowsException() {
		$validatedObject = new DataObjectTest_ValidatedObject();

		$this->setExpectedException('ValidationException');
		$validatedObject->write();
	}
	
	public function testWritingValidDataObjectDoesntThrowException() {
		$validatedObject = new DataObjectTest_ValidatedObject();
		$validatedObject->Name = "Mr. Jones";
		
		$validatedObject->write();
		$this->assertTrue($validatedObject->isInDB(), "Validated object was not saved to database");
	}
	
	public function testSubclassCreation() {
		/* Creating a new object of a subclass should set the ClassName field correctly */
		$obj = new DataObjectTest_SubTeam();
		$obj->write();
		$this->assertEquals("DataObjectTest_SubTeam", DB::query("SELECT \"ClassName\" FROM \"DataObjectTest_Team\" WHERE \"ID\" = $obj->ID")->value());
	}
	
	public function testForceInsert() {	
		/* If you set an ID on an object and pass forceInsert = true, then the object should be correctly created */
		$conn = DB::getConn();
		if(method_exists($conn, 'allowPrimaryKeyEditing')) $conn->allowPrimaryKeyEditing('DataObjectTest_Team', true);
		$obj = new DataObjectTest_SubTeam();
		$obj->ID = 1001;
		$obj->Title = 'asdfasdf';
		$obj->SubclassDatabaseField = 'asdfasdf';
		$obj->write(false, true);
		if(method_exists($conn, 'allowPrimaryKeyEditing')) $conn->allowPrimaryKeyEditing('DataObjectTest_Team', false);

		$this->assertEquals("DataObjectTest_SubTeam", DB::query("SELECT \"ClassName\" FROM \"DataObjectTest_Team\" WHERE \"ID\" = $obj->ID")->value());

		/* Check that it actually saves to the database with the correct ID */
		$this->assertEquals("1001", DB::query("SELECT \"ID\" FROM \"DataObjectTest_SubTeam\" WHERE \"SubclassDatabaseField\" = 'asdfasdf'")->value());
		$this->assertEquals("1001", DB::query("SELECT \"ID\" FROM \"DataObjectTest_Team\" WHERE \"Title\" = 'asdfasdf'")->value());
	}
	
	public function TestHasOwnTable() {
		/* Test DataObject::has_own_table() returns true if the object has $has_one or $db values */
		$this->assertTrue(DataObject::has_own_table("DataObjectTest_Player"));
		$this->assertTrue(DataObject::has_own_table("DataObjectTest_Team"));
		$this->assertTrue(DataObject::has_own_table("DataObjectTest_Fixture"));

		/* Root DataObject that always have a table, even if they lack both $db and $has_one */
		$this->assertTrue(DataObject::has_own_table("DataObjectTest_FieldlessTable"));

		/* Subclasses without $db or $has_one don't have a table */
		$this->assertFalse(DataObject::has_own_table("DataObjectTest_FieldlessSubTable"));

		/* Return false if you don't pass it a subclass of DataObject */
		$this->assertFalse(DataObject::has_own_table("DataObject"));
		$this->assertFalse(DataObject::has_own_table("ViewableData"));
		$this->assertFalse(DataObject::has_own_table("ThisIsntADataObject"));
	}
	
	public function testMerge() {
		// test right merge of subclasses
		$left = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam1');
		$right = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam2_with_player_relation');
		$leftOrigID = $left->ID;
		$left->merge($right, 'right', false, false);
		$this->assertEquals(
			$left->Title,
			'Subteam 2',
			'merge() with "right" priority overwrites fields with existing values on subclasses'
		);
		$this->assertEquals(
			$left->ID,
			$leftOrigID,
			'merge() with "right" priority doesnt overwrite database ID'
		);
		
		// test overwriteWithEmpty flag on existing left values
		$left = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam2_with_player_relation');
		$right = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam3_with_empty_fields');
		$left->merge($right, 'right', false, true);
		$this->assertEquals(
			$left->Title,
			'Subteam 3', 
			'merge() with $overwriteWithEmpty overwrites non-empty fields on left object'
		);
		
		// test overwriteWithEmpty flag on empty left values
		$left = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam1');
		$right = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam2_with_player_relation'); // $SubclassDatabaseField is empty on here
		$left->merge($right, 'right', false, true);
		$this->assertEquals(
			$left->SubclassDatabaseField,
			NULL, 
			'merge() with $overwriteWithEmpty overwrites empty fields on left object'
		);
		
		// @todo test "left" priority flag
		// @todo test includeRelations flag
		// @todo test includeRelations in combination with overwriteWithEmpty
		// @todo test has_one relations
		// @todo test has_many and many_many relations
	}
	
	function testPopulateDefaults() {
		$obj = new DataObjectTest_Fixture();
		$this->assertEquals(
			$obj->MyFieldWithDefault,
			"Default Value",
			"Defaults are populated for in-memory object from \$defaults array"
		);
	}
	
	function testNewClassInstance() {
		$dataObject = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$changedDO = $dataObject->newClassInstance('DataObjectTest_SubTeam');
		$changedFields = $changedDO->getChangedFields();
		
		// Don't write the record, it will reset changed fields
		$this->assertInstanceOf('DataObjectTest_SubTeam', $changedDO);
		$this->assertEquals($changedDO->ClassName, 'DataObjectTest_SubTeam');
		$this->assertContains('ClassName', array_keys($changedFields));
		$this->assertEquals($changedFields['ClassName']['before'], 'DataObjectTest_Team');
		$this->assertEquals($changedFields['ClassName']['after'], 'DataObjectTest_SubTeam');

		$changedDO->write();

		$this->assertInstanceOf('DataObjectTest_SubTeam', $changedDO);
		$this->assertEquals($changedDO->ClassName, 'DataObjectTest_SubTeam');
	}
	
	function testManyManyExtraFields() {
		$player = $this->objFromFixture('DataObjectTest_Player', 'player1');
		$team = $this->objFromFixture('DataObjectTest_Team', 'team1');
		
		// Extra fields are immediately available on the Team class (defined in $many_many_extraFields)
		$teamExtraFields = $team->many_many_extraFields('Players');
		$this->assertEquals($teamExtraFields, array(
			'Position' => 'Varchar(100)'
		));
		
		// We'll have to go through the relation to get the extra fields on Player
		$playerExtraFields = $player->many_many_extraFields('Teams');
		$this->assertEquals($playerExtraFields, array(
			'Position' => 'Varchar(100)'
		));
		
		// Iterate through a many-many relationship and confirm that extra fields are included
		$newTeam = new DataObjectTest_Team();
		$newTeam->Title = "New team";
		$newTeam->write();
		$newTeamID = $newTeam->ID;
		
		$newPlayer = new DataObjectTest_Player();
		$newPlayer->FirstName = "Sam";
		$newPlayer->Surname = "Minnee";
		$newPlayer->write();

		// The idea of Sam as a prop is essentially humourous.
		$newTeam->Players()->add($newPlayer, array("Position" => "Prop"));

		// Requery and uncache everything
		$newTeam->flushCache();
		$newTeam = DataObject::get_by_id('DataObjectTest_Team', $newTeamID);
		
		// Check that the Position many_many_extraField is extracted.
		$player = $newTeam->Players()->First();
		$this->assertEquals('Sam', $player->FirstName);
		$this->assertEquals("Prop", $player->Position);
		
		// Check that ordering a many-many relation by an aggregate column doesn't fail
		$player = $this->objFromFixture('DataObjectTest_Player', 'player2');
		$player->Teams("", "count(DISTINCT \"DataObjectTest_Team_Players\".\"DataObjectTest_PlayerID\") DESC");
	}
	
	/**
	 * Check that the queries generated for many-many relation queries can have unlimitedRowCount
	 * called on them.
	 */
	function testManyManyUnlimitedRowCount() {
		$player = $this->objFromFixture('DataObjectTest_Player', 'player2');
		// TODO: What's going on here?
		$this->assertEquals(2, $player->Teams()->dataQuery()->query()->unlimitedRowCount());
	}
	
	/**
	 * Tests that singular_name() generates sensible defaults.
	 */
	public function testSingularName() {
		$assertions = array (
			'DataObjectTest_Player'       => 'Data Object Test Player',
			'DataObjectTest_Team'         => 'Data Object Test Team',
			'DataObjectTest_Fixture'      => 'Data Object Test Fixture'
		);
		
		foreach($assertions as $class => $expectedSingularName) {
			$this->assertEquals (
				$expectedSingularName,
				singleton($class)->singular_name(),
				"Assert that the singular_name for '$class' is correct."
			);
		}
	}
	
	function testHasDatabaseField() {
		$team = singleton('DataObjectTest_Team');
		$subteam = singleton('DataObjectTest_SubTeam');
		
		$this->assertTrue(
			$team->hasDatabaseField('Title'),
			"hasOwnDatabaseField() works with \$db fields"
		);
		$this->assertTrue(
			$team->hasDatabaseField('CaptainID'),
			"hasOwnDatabaseField() works with \$has_one fields"
		);
		$this->assertFalse(
			$team->hasDatabaseField('NonExistentField'),
			"hasOwnDatabaseField() doesn't detect non-existend fields"
		);
		$this->assertTrue(
			$team->hasDatabaseField('ExtendedDatabaseField'),
			"hasOwnDatabaseField() works with extended fields"
		);
		$this->assertFalse(
			$team->hasDatabaseField('SubclassDatabaseField'),
			"hasOwnDatabaseField() doesn't pick up fields in subclasses on parent class"
		);
		
		$this->assertTrue(
			$subteam->hasDatabaseField('SubclassDatabaseField'),
			"hasOwnDatabaseField() picks up fields in subclasses"
		);
		
	}
	
	function testFieldTypes() {
		$obj = new DataObjectTest_Fixture();
		$obj->DateField = '1988-01-02';
		$obj->DatetimeField = '1988-03-04 06:30';
		$obj->write();
		$obj->flushCache();
		
		$obj = DataObject::get_by_id('DataObjectTest_Fixture', $obj->ID);
		$this->assertEquals('1988-01-02', $obj->DateField);
		$this->assertEquals('1988-03-04 06:30:00', $obj->DatetimeField);
	}
	
	function testTwoSubclassesWithTheSameFieldNameWork() {
		// Create two objects of different subclasses, setting the values of fields that are
		// defined separately in each subclass
		$obj1 = new DataObjectTest_SubTeam();
		$obj1->SubclassDatabaseField = "obj1";
		$obj2 = new OtherSubclassWithSameField();
		$obj2->SubclassDatabaseField = "obj2";

		// Write them to the database
		$obj1->write();
		$obj2->write();
		
		// Check that the values of those fields are properly read from the database
		$values = DataObject::get("DataObjectTest_Team", "\"DataObjectTest_Team\".\"ID\" IN 
			($obj1->ID, $obj2->ID)")->column("SubclassDatabaseField");
		$this->assertEquals(array_intersect($values, array('obj1', 'obj2')), $values);
	}
	
	function testClassNameSetForNewObjects() {
		$d = new DataObjectTest_Player();
		$this->assertEquals('DataObjectTest_Player', $d->ClassName);
	}
	
	public function testHasValue() {
		$team = new DataObjectTest_Team();
		$this->assertFalse($team->hasValue('Title', null, false));
		$this->assertFalse($team->hasValue('DatabaseField', null, false));
		
		$team->Title = 'hasValue';
		$this->assertTrue($team->hasValue('Title', null, false));
		$this->assertFalse($team->hasValue('DatabaseField', null, false));
		
		$team->DatabaseField = '<p></p>';
		$this->assertTrue($team->hasValue('Title', null, false));
		$this->assertFalse (
			$team->hasValue('DatabaseField', null, false),
			'Test that a blank paragraph on a HTML field is not a valid value.'
		);
		
		$team->Title = '<p></p>';
		$this->assertTrue (
			$team->hasValue('Title', null, false),
			'Test that an empty paragraph is a value for non-HTML fields.'
		);
		
		$team->DatabaseField = 'hasValue';
		$this->assertTrue($team->hasValue('Title', null, false));
		$this->assertTrue($team->hasValue('DatabaseField', null, false));
	}
	
	public function testHasMany() {
		$company = new DataObjectTest_Company();
		
		$this->assertEquals (
			array (
				'CurrentStaff'     => 'DataObjectTest_Staff',
				'PreviousStaff'    => 'DataObjectTest_Staff'
			),
			$company->has_many(),
			'has_many strips field name data by default.'
		);
		
		$this->assertEquals (
			'DataObjectTest_Staff',
			$company->has_many('CurrentStaff'),
			'has_many strips field name data by default on single relationships.'
		);
		
		$this->assertEquals (
			array (
				'CurrentStaff'     => 'DataObjectTest_Staff.CurrentCompany',
				'PreviousStaff'    => 'DataObjectTest_Staff.PreviousCompany'
			),
			$company->has_many(null, false),
			'has_many returns field name data when $classOnly is false.'
		);
		
		$this->assertEquals (
			'DataObjectTest_Staff.CurrentCompany',
			$company->has_many('CurrentStaff', false),
			'has_many returns field name data on single records when $classOnly is false.'
		);
	}
	
	public function testGetRemoteJoinField() {
		$company = new DataObjectTest_Company();
		
		$this->assertEquals('CurrentCompanyID', $company->getRemoteJoinField('CurrentStaff'));
		$this->assertEquals('PreviousCompanyID', $company->getRemoteJoinField('PreviousStaff'));
		
		$ceo = new DataObjectTest_CEO();
		
		$this->assertEquals('CEOID', $ceo->getRemoteJoinField('Company', 'belongs_to'));
		$this->assertEquals('PreviousCEOID', $ceo->getRemoteJoinField('PreviousCompany', 'belongs_to'));
	}
	
	public function testBelongsTo() {
		$company = new DataObjectTest_Company();
		$ceo     = new DataObjectTest_CEO();
		
		$company->write();
		$ceo->write();
		
		$company->CEOID = $ceo->ID;
		$company->write();
		
		$this->assertEquals($company->ID, $ceo->Company()->ID, 'belongs_to returns the right results.');
		
		$ceo = new DataObjectTest_CEO();
		$ceo->write();
		
		$this->assertTrue (
			$ceo->Company() instanceof DataObjectTest_Company,
			'DataObjects across belongs_to relations are automatically created.'
		);
		$this->assertEquals($ceo->ID, $ceo->Company()->CEOID, 'Remote IDs are automatically set.');
		
		$ceo->write(false, false, false, true);
		$this->assertTrue($ceo->Company()->isInDB(), 'write() writes belongs_to components to the database.');
		
		$newCEO = DataObject::get_by_id('DataObjectTest_CEO', $ceo->ID);
		$this->assertEquals (
			$ceo->Company()->ID, $newCEO->Company()->ID, 'belongs_to can be retrieved from the database.'
		);
	}
	
	public function testInvalidate() {
		$do = new DataObjectTest_Fixture();
		$do->write();
		
		$do->delete();

		try {
			// Prohibit invalid object manipulation
			$do->delete();
			$do->write();
			$do->duplicate();
		}
		catch(Exception $e) {
			return;
		}
		
		$this->fail('Should throw an exception');
	}
	
	function testToMap() {
		$obj = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam1');
		
		$map = $obj->toMap();
		
		$this->assertArrayHasKey('ID', $map, 'Contains base fields');
		$this->assertArrayHasKey('Title', $map, 'Contains fields from parent class');
		$this->assertArrayHasKey('SubclassDatabaseField', $map, 'Contains fields from concrete class');
		
		$this->assertEquals($obj->ID, $map['ID'], 'Contains values from base fields');
		$this->assertEquals($obj->Title, $map['Title'], 'Contains values from parent class fields');
		$this->assertEquals($obj->SubclassDatabaseField, $map['SubclassDatabaseField'], 'Contains values from concrete class fields');
		
		$newObj = new DataObjectTest_SubTeam();
		$this->assertArrayHasKey('Title', $map, 'Contains null fields');
	}
	
	function testIsEmpty() {
		$objEmpty = new DataObjectTest_Team();
		$this->assertTrue($objEmpty->isEmpty(), 'New instance without populated defaults is empty');
		
		$objEmpty->Title = '0'; // 
		$this->assertFalse($objEmpty->isEmpty(), 'Zero value in attribute considered non-empty');
	}

	function testRelField() {
		$captain = $this->objFromFixture('DataObjectTest_Player', 'captain1');
		// Test traversal of a single has_one
		$this->assertEquals("Team 1", $captain->relField('FavouriteTeam.Title'));
		// Test direct field access
		$this->assertEquals("Captain", $captain->relField('FirstName'));

		$player = $this->objFromFixture('DataObjectTest_Player', 'player2');
		// Test that we can traverse more than once, and that arbitrary methods are okay
		$this->assertEquals("Team 1", $player->relField('Teams.First.Title'));
	}

	function testRelObject() {
		$captain = $this->objFromFixture('DataObjectTest_Player', 'captain1');

		// Test traversal of a single has_one
		$this->assertInstanceOf("Varchar", $captain->relObject('FavouriteTeam.Title'));
		$this->assertEquals("Team 1", $captain->relObject('FavouriteTeam.Title')->getValue());

		// Test direct field access
		$this->assertInstanceOf("Boolean", $captain->relObject('IsRetired'));
		$this->assertEquals(1, $captain->relObject('IsRetired')->getValue());

		$player = $this->objFromFixture('DataObjectTest_Player', 'player2');
		// Test that we can traverse more than once, and that arbitrary methods are okay
		$this->assertInstanceOf("Varchar", $player->relObject('Teams.First.Title'));
		$this->assertEquals("Team 1", $player->relObject('Teams.First.Title')->getValue());
	}
	
	function testLateStaticBindingStyle() {
		// Confirm that DataObjectTest_Player::get() operates as excepted
		$this->assertEquals(4, DataObjectTest_Player::get()->Count());
		$this->assertInstanceOf('DataObjectTest_Player', DataObjectTest_Player::get()->First());
		
		// You can't pass arguments to LSB syntax - use the DataList methods instead.
		$this->setExpectedException('InvalidArgumentException');
		DataObjectTest_Player::get(null, "\"ID\" = 1");
		
	}

	function testBrokenLateStaticBindingStyle() {
		// If you call DataObject::get() you have to pass a first argument
		$this->setExpectedException('InvalidArgumentException');
		DataObject::get();
		
	}
	

}

class DataObjectTest_Player extends Member implements TestOnly {
	static $db = array(
		'IsRetired' => 'Boolean',
		'ShirtNumber' => 'Varchar',
	);
	
	static $has_one = array(
		'FavouriteTeam' => 'DataObjectTest_Team',
	);
	
	static $belongs_many_many = array(
		'Teams' => 'DataObjectTest_Team'
	);
   
}

class DataObjectTest_Team extends DataObject implements TestOnly {

	static $db = array(
		'Title' => 'Varchar', 
		'DatabaseField' => 'HTMLVarchar'
	);

	static $has_one = array(
		"Captain" => 'DataObjectTest_Player',
		'HasOneRelationship' => 'DataObjectTest_Player',
	);

	static $has_many = array(
		'SubTeams' => 'DataObjectTest_SubTeam',
		'Comments' => 'DataObjectTest_TeamComment'
	);
	
	static $many_many = array(
		'Players' => 'DataObjectTest_Player'
	);
	
	static $many_many_extraFields = array(
		'Players' => array(
			'Position' => 'Varchar(100)'
		)
	);

	static $default_sort = "Title";

	function MyTitle() {
		return 'Team ' . $this->Title;
	}

	function getDynamicField() {
		return 'dynamicfield';
	}

}

class DataObjectTest_Fixture extends DataObject implements TestOnly {
	static $db = array(
		// Funny field names
		'Data' => 'Varchar',
		'Duplicate' => 'Varchar',
		'DbObject' => 'Varchar',
		
		// Field with default
		'MyField' => 'Varchar',
		
		// Field types
		"DateField" => "Date",
		"DatetimeField" => "Datetime",
	);

	static $defaults = array(
		'MyFieldWithDefault' => 'Default Value', 
	);
}

class DataObjectTest_SubTeam extends DataObjectTest_Team implements TestOnly {
	static $db = array(
		'SubclassDatabaseField' => 'Varchar'
	);

	static $has_one = array(
		"ParentTeam" => 'DataObjectTest_Team',
	);
}
class OtherSubclassWithSameField extends DataObjectTest_Team implements TestOnly {
	static $db = array(
		'SubclassDatabaseField' => 'Varchar',
	);
}


class DataObjectTest_FieldlessTable extends DataObject implements TestOnly {
}

class DataObjectTest_FieldlessSubTable extends DataObjectTest_Team implements TestOnly {
}


class DataObjectTest_Team_Extension extends DataExtension implements TestOnly {

	static $db = array(
		'ExtendedDatabaseField' => 'Varchar'
	);

	static $has_one = array(
		'ExtendedHasOneRelationship' => 'DataObjectTest_Player'
	);

	function getExtendedDynamicField() {
		return "extended dynamic field";
	}
	
}

class DataObjectTest_ValidatedObject extends DataObject implements TestOnly {
	
	static $db = array(
		'Name' => 'Varchar(50)'
	);
	
	protected function validate() {
		if(!empty($this->Name)) {
			return new ValidationResult();
		} else {
			return new ValidationResult(false, "This object needs a name. Otherwise it will have an identity crisis!");
		}
	}
}

class DataObjectTest_Company extends DataObject {
	public static $has_one = array (
		'CEO'         => 'DataObjectTest_CEO',
		'PreviousCEO' => 'DataObjectTest_CEO'
	);
	
	public static $has_many = array (
		'CurrentStaff'     => 'DataObjectTest_Staff.CurrentCompany',
		'PreviousStaff'    => 'DataObjectTest_Staff.PreviousCompany'
	);
}

class DataObjectTest_Staff extends DataObject {
	public static $has_one = array (
		'CurrentCompany'  => 'DataObjectTest_Company',
		'PreviousCompany' => 'DataObjectTest_Company'
	);
}

class DataObjectTest_CEO extends DataObjectTest_Staff {
	public static $belongs_to = array (
		'Company'         => 'DataObjectTest_Company.CEO',
		'PreviousCompany' => 'DataObjectTest_Company.PreviousCEO'
	);
}

class DataObjectTest_TeamComment extends DataObject {
	static $db = array(
		'Name' => 'Varchar',
		'Comment' => 'Text'
	);

	static $has_one = array(
		'Team' => 'DataObjectTest_Team'
	);
}

DataObject::add_extension('DataObjectTest_Team', 'DataObjectTest_Team_Extension');

