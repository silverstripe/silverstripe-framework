<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class DataObjectTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/DataObjectTest.yml';

	/**
	 * Test deletion of DataObjects
	 *   - Deleting using delete() on the DataObject
	 *   - Deleting using DataObject::delete_by_id()
	 */
	function testDelete() {
		// Test deleting using delete() on the DataObject
		// Get the first page
		$page = $this->fixture->objFromFixture('Page', 'page1');
		// Check the page exists before deleting
		$this->assertTrue(is_object($page) && $page->exists());
		// Delete the page
		$page->delete();
		// Check that page does not exist after deleting
		$page = $this->fixture->objFromFixture('Page', 'page1');
		$this->assertTrue(!$page || !$page->exists());
		
		
		// Test deleting using DataObject::delete_by_id()
		// Get the second page
		$page2 = $this->fixture->objFromFixture('Page', 'page2');
		// Check the page exists before deleting
		$this->assertTrue(is_object($page2) && $page2->exists());
		// Delete the page
		DataObject::delete_by_id('Page', $page2->ID);
		// Check that page does not exist after deleting
		$page2 = $this->fixture->objFromFixture('Page', 'page2');
		$this->assertTrue(!$page2 || !$page2->exists());
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
	 *   - DataObject::get_by_url()
	 *   - DataObject::get_one()
	 *        - With and without caching
	 *        - With and without ordering
	 */
	function testGet() {
		// Test getting all records of a DataObject
		$comments = DataObject::get('PageComment');
		$this->assertEquals(8, $comments->Count());
		
		// Test WHERE clause
		$comments = DataObject::get('PageComment', 'Name="Bob"');
		$this->assertEquals(2, $comments->Count());
		foreach($comments as $comment) {
			$this->assertEquals('Bob', $comment->Name);
		}
		
		// Test sorting
		$comments = DataObject::get('PageComment', '', 'Name ASC');
		$this->assertEquals(8, $comments->Count());
		$this->assertEquals('Bob', $comments->First()->Name);
		$comments = DataObject::get('PageComment', '', 'Name DESC');
		$this->assertEquals(8, $comments->Count());
		$this->assertEquals('Joe', $comments->First()->Name);
		
		// Test join
		$comments = DataObject::get('PageComment', '`SiteTree`.Title="First Page"', '', 'INNER JOIN SiteTree ON PageComment.ParentID = SiteTree.ID');
		$this->assertEquals(2, $comments->Count());
		$this->assertEquals('Bob', $comments->First()->Name);
		$this->assertEquals('Bob', $comments->Last()->Name);
		
		// Test limit
		$comments = DataObject::get('PageComment', '', 'Name ASC', '', '1,2');
		$this->assertEquals(2, $comments->Count());
		$this->assertEquals('Bob', $comments->First()->Name);
		$this->assertEquals('Dean', $comments->Last()->Name);
		
		// Test container class
		$comments = DataObject::get('PageComment', '', '', '', '', 'DataObjectSet');
		$this->assertEquals('DataObjectSet', get_class($comments));
		$comments = DataObject::get('PageComment', '', '', '', '', 'ComponentSet');
		$this->assertEquals('ComponentSet', get_class($comments));
		
		
		// Test get_by_id()
		$homepageID = $this->idFromFixture('Page', 'home');
		$page = DataObject::get_by_id('Page', $homepageID);
		$this->assertEquals('Home', $page->Title);
		
		// Test get_by_url()
		$page = SiteTree::get_by_url('home');
		$this->assertEquals($homepageID, $page->ID);
		
		// Test get_one() without caching
		$comment1 = DataObject::get_one('PageComment', 'Name="Joe"', false);
		$comment1->Comment = "Something Else";
		$comment2 = DataObject::get_one('PageComment', 'Name="Joe"', false);
		$this->assertNotEquals($comment1->Comment, $comment2->Comment);
		
		// Test get_one() with caching
		$comment1 = DataObject::get_one('PageComment', 'Name="Jane"', true);
		$comment1->Comment = "Something Else";
		$comment2 = DataObject::get_one('PageComment', 'Name="Jane"', true);
		$this->assertEquals((string)$comment1->Comment, (string)$comment2->Comment);
		
		// Test get_one() with order by without caching
		$comment = DataObject::get_one('PageComment', '', false, 'Name ASC');
		$this->assertEquals('Bob', $comment->Name);
		$comment = DataObject::get_one('PageComment', '', false, 'Name DESC');
		$this->assertEquals('Joe', $comment->Name);
		
		// Test get_one() with order by with caching
		$comment = DataObject::get_one('PageComment', '', true, 'Name ASC');
		$this->assertEquals('Bob', $comment->Name);
		$comment = DataObject::get_one('PageComment', '', true, 'Name DESC');
		$this->assertEquals('Joe', $comment->Name);
	}

	/**
	 * Test writing of database columns which don't correlate to a DBField,
	 * e.g. all relation fields on has_one/has_many like "ParentID". 
	 *
	 */
	function testWritePropertyWithoutDBField() {
		$page = $this->fixture->objFromFixture('Page', 'page1');
		$page->ParentID = 99;
		$page->write();
		// reload the page from the database
		$savedPage = DataObject::get_by_id('Page', $page->ID);
		$this->assertTrue($savedPage->ParentID == 99);
	}
	
	/**
	 * Test has many relationships
	 *   - Test getComponents() gets the ComponentSet of the other side of the relation
	 *   - Test the IDs on the DataObjects are set correctly
	 */
	function testHasManyRelationships() {
		$page = $this->fixture->objFromFixture('Page', 'home');
		
		// Test getComponents() gets the ComponentSet of the other side of the relation
		$this->assertTrue($page->getComponents('Comments')->Count() == 2);
		
		// Test the IDs on the DataObjects are set correctly
		foreach($page->getComponents('Comments') as $comment) {
			$this->assertTrue($comment->ParentID == $page->ID);
		}
	}

	function testHasOneRelationship() {
		$team1 = $this->fixture->objFromFixture('DataObjectTest_Team', 'team1');
		$player1 = $this->fixture->objFromFixture('DataObjectTest_Player', 'player1');
	   
		// Add a captain to team 1
		$team1->setField('CaptainID', $player1->ID);
		$team1->write();
		
		$this->assertEquals($player1->ID, $team1->Captain()->ID, 'The captain exists for team 1');
		$this->assertEquals($player1->ID, $team1->getComponent('Captain')->ID, 'The captain exists through the component getter');

		$this->assertEquals($team1->Captain()->FirstName, 'Player 1', 'Player 1 is the captain');
		$this->assertEquals($team1->getComponent('Captain')->FirstName, 'Player 1', 'Player 1 is the captain');
	}
	
	/**
	 * @todo Test removeMany() and addMany() on $many_many relationships
	 */
	function testManyManyRelationships() {
	   $player1 = $this->fixture->objFromFixture('DataObjectTest_Player', 'player1');
	   $player2 = $this->fixture->objFromFixture('DataObjectTest_Player', 'player2');
	   $team1 = $this->fixture->objFromFixture('DataObjectTest_Team', 'team1');
	   $team2 = $this->fixture->objFromFixture('DataObjectTest_Team', 'team2');
	   
	   // Test adding single DataObject by reference
	   $player1->Teams()->add($team1);
	   $player1->flushCache();
	   $compareTeams = new ComponentSet($team1);
	   $this->assertEquals(
	      $player1->Teams()->column('ID'),
	      $compareTeams->column('ID'),
	      "Adding single record as DataObject to many_many"
	   );
	   
	   // test removing single DataObject by reference
	   $player1->Teams()->remove($team1);
	   $player1->flushCache();
	   $compareTeams = new ComponentSet();
	   $this->assertEquals(
	      $player1->Teams()->column('ID'),
	      $compareTeams->column('ID'),
	      "Removing single record as DataObject from many_many"
	   );
	   
	   // test adding single DataObject by ID
	   $player1->Teams()->add($team1->ID);
	   $player1->flushCache();
	   $compareTeams = new ComponentSet($team1);
	   $this->assertEquals(
	      $player1->Teams()->column('ID'),
	      $compareTeams->column('ID'),
	      "Adding single record as ID to many_many"
	   );
	   
	   // test removing single DataObject by ID
	   $player1->Teams()->remove($team1->ID);
	   $player1->flushCache();
	   $compareTeams = new ComponentSet();
	   $this->assertEquals(
	      $player1->Teams()->column('ID'),
	      $compareTeams->column('ID'),
	      "Removing single record as ID from many_many"
	   );
	}
	
	/**
	 * @todo Extend type change tests (e.g. '0'==NULL)
	 */
	function testChangedFields() {
		$page = $this->fixture->objFromFixture('Page', 'home');
		$page->Title = 'Home-Changed';
		$page->ShowInMenus = true;

		$this->assertEquals(
			$page->getChangedFields(false, 1),
			array(
				'Title' => array(
					'before' => 'Home',
					'after' => 'Home-Changed',
					'level' => 2
				),
				'ShowInMenus' => array(
					'before' => 1,
					'after' => true,
					'level' => 1
				)
			),
			'Changed fields are correctly detected with strict type changes (level=1)'
		);
		
		$this->assertEquals(
			$page->getChangedFields(false, 2),
			array(
				'Title' => array(
					'before'=>'Home',
					'after'=>'Home-Changed',
					'level' => 2
				)
			),
			'Changed fields are correctly detected while ignoring type changes (level=2)'
		);
		
		$newPage = new Page();
		$newPage->Title = "New Page Title";
		$this->assertEquals(
			$newPage->getChangedFields(false, 2),
			array(
				'Title' => array(
					'before' => null,
					'after' => 'New Page Title',
					'level' => 2
				)
			),
			'Initialised fields are correctly detected as full changes'
		);
	}
	
	function testRandomSort() {
		/* If we perforn the same regularly sorted query twice, it should return the same results */
		$itemsA = DataObject::get("PageComment", "", "ID");
		foreach($itemsA as $item) $keysA[] = $item->ID;

		$itemsB = DataObject::get("PageComment", "", "ID");
		foreach($itemsB as $item) $keysB[] = $item->ID;
		
		$this->assertEquals($keysA, $keysB);
		
		/* If we perform the same random query twice, it shouldn't return the same results */
		$itemsA = DataObject::get("PageComment", "", "RAND()");
		foreach($itemsA as $item) $keysA[] = $item->ID;

		$itemsB = DataObject::get("PageComment", "", "RAND()");
		foreach($itemsB as $item) $keysB[] = $item->ID;
		
		$this->assertNotEquals($keysA, $keysB);
	}
	
	function testWriteSavesToHasOneRelations() {
		/* DataObject::write() should save to a has_one relationship if you set a field called (relname)ID */
		$team = new DataObjectTest_Team();
		$captainID = $this->idFromFixture('DataObjectTest_Player', 'player1');
		$team->CaptainID = $captainID;
		$team->write();
		$this->assertEquals($captainID, DB::query("SELECT CaptainID FROM DataObjectTest_Team WHERE ID = $team->ID")->value());
		
		/* After giving it a value, you should also be able to set it back to null */
		$team->CaptainID = '';
		$team->write();
		$this->assertEquals(0, DB::query("SELECT CaptainID FROM DataObjectTest_Team WHERE ID = $team->ID")->value());

		/* You should also be able to save a blank to it when it's first created */
		$team = new DataObjectTest_Team();
		$team->CaptainID = '';
		$team->write();
		$this->assertEquals(0, DB::query("SELECT CaptainID FROM DataObjectTest_Team WHERE ID = $team->ID")->value());
		
		/* Ditto for existing records without a value */
		$existingTeam = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$existingTeam->CaptainID = '';
		$existingTeam->write();
		$this->assertEquals(0, DB::query("SELECT CaptainID FROM DataObjectTest_Team WHERE ID = $existingTeam->ID")->value());
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
		$obj = new DataObjectTest_FunnyFieldNames();
		$obj->Data = "value1";
		$obj->DbObject = "value2";
		$obj->Duplicate = "value3";
		$obj->write();

		$this->assertNotNull($obj->ID);
		$this->assertEquals('value1', DB::query("SELECT Data FROM DataObjectTest_FunnyFieldNames WHERE ID = $obj->ID")->value());
		$this->assertEquals('value2', DB::query("SELECT DbObject FROM DataObjectTest_FunnyFieldNames WHERE ID = $obj->ID")->value());
		$this->assertEquals('value3', DB::query("SELECT Duplicate FROM DataObjectTest_FunnyFieldNames WHERE ID = $obj->ID")->value());
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
		$this->assertTrue($teamInstance->hasField('DecoratedDatabaseField'), 'hasField() finds decorated fields in instances');
		$this->assertTrue($teamInstance->hasField('DecoratedHasOneRelationshipID'), 'hasField() finds decorated foreign keys in instances');
		//$this->assertTrue($teamInstance->hasField('DecoratedDynamicField'), 'hasField() includes decorated dynamic getters in instances');
		
		/* hasField() subclass checks */
		$this->assertTrue($subteamInstance->hasField('ID'), 'hasField() finds built-in fields in subclass instances');
		$this->assertTrue($subteamInstance->hasField('Created'), 'hasField() finds built-in fields in subclass instances');
		$this->assertTrue($subteamInstance->hasField('DatabaseField'), 'hasField() finds custom fields in subclass instances');
		$this->assertTrue($subteamInstance->hasField('SubclassDatabaseField'), 'hasField() finds custom fields in subclass instances');
		$this->assertTrue($subteamInstance->hasField('DynamicField'), 'hasField() finds dynamic getters in subclass instances');
		$this->assertTrue($subteamInstance->hasField('HasOneRelationshipID'), 'hasField() finds foreign keys in subclass instances');
		$this->assertTrue($subteamInstance->hasField('DecoratedDatabaseField'), 'hasField() finds decorated fields in subclass instances');
		$this->assertTrue($subteamInstance->hasField('DecoratedHasOneRelationshipID'), 'hasField() finds decorated foreign keys in subclass instances');
		
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
		$this->assertTrue($teamInstance->hasDatabaseField('DecoratedDatabaseField'), 'hasDatabaseField() finds decorated fields in instances');
		$this->assertTrue($teamInstance->hasDatabaseField('DecoratedHasOneRelationshipID'), 'hasDatabaseField() finds decorated foreign keys in instances');
		$this->assertFalse($teamInstance->hasDatabaseField('DecoratedDynamicField'), 'hasDatabaseField() doesnt include decorated dynamic getters in instances');
		
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
				'DecoratedDatabaseField',
				'CaptainID',
				'HasOneRelationshipID',
				'DecoratedHasOneRelationshipID'
			),
			'inheritedDatabaseFields() contains all fields defined on instance, including base fields, decorated fields and foreign keys'
		);
		
		$this->assertEquals(
			array_keys($teamInstance->databaseFields()),
			array(
				//'ID',
				'ClassName',
				'Created',
				'LastEdited',
				'Title',
				'DatabaseField',
				'DecoratedDatabaseField',
				'CaptainID',
				'HasOneRelationshipID',
				'DecoratedHasOneRelationshipID'
			),
			'databaseFields() contains only fields defined on instance, including base fields, decorated fields and foreign keys'
		);
		
		$this->assertEquals(
			array_keys($subteamInstance->inheritedDatabaseFields()),
			array(
				//'ID',
				//'ClassName',
				//'Created',
				//'LastEdited',
				'SubclassDatabaseField',
				'Title',
				'DatabaseField',
				'DecoratedDatabaseField',
				'CaptainID',
				'HasOneRelationshipID',
				'DecoratedHasOneRelationshipID',
			),
			'inheritedDatabaseFields() on subclass contains all fields defined on instance, including base fields, decorated fields and foreign keys'
		);
		
		$this->assertEquals(
			array_keys($subteamInstance->databaseFields()),
			array(
				'SubclassDatabaseField',
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
		$this->assertEquals("DataObjectTest_SubTeam", DB::query("SELECT ClassName FROM DataObjectTest_Team WHERE ID = $obj->ID")->value());
	}
	
	public function testForceInsert() {	
		/* If you set an ID on an object and pass forceInsert = true, then the object should be correctly created */
		$obj = new DataObjectTest_SubTeam();
		$obj->ID = 1001;
		$obj->Title = 'asdfasdf';
		$obj->SubclassDatabaseField = 'asdfasdf';
		$obj->write(false, true);

		$this->assertEquals("DataObjectTest_SubTeam", DB::query("SELECT ClassName FROM DataObjectTest_Team WHERE ID = $obj->ID")->value());

		/* Check that it actually saves to the database with the correct ID */
		$this->assertEquals("1001", DB::query("SELECT ID FROM DataObjectTest_SubTeam WHERE SubclassDatabaseField = 'asdfasdf'")->value());
		$this->assertEquals("1001", DB::query("SELECT ID FROM DataObjectTest_Team WHERE Title = 'asdfasdf'")->value());
	}
	
	public function TestHasOwnTable() {
		/* Test DataObject::has_own_table() returns true if the object has $has_one or $db values */
		$this->assertTrue(DataObject::has_own_table("DataObjectTest_Player"));
		$this->assertTrue(DataObject::has_own_table("DataObjectTest_Team"));
		$this->assertTrue(DataObject::has_own_table("DataObjectTest_FunnyFieldNames"));

		/* Root DataObject that always have a table, even if they lack both $db and $has_one */
		$this->assertTrue(DataObject::has_own_table("DataObjectTest_FieldlessTable"));

		/* Subclasses without $db or $has_one don't have a table */
		$this->assertFalse(DataObject::has_own_table("DataObjectTest_FieldlessSubTable"));

		/* Return false if you don't pass it a subclass of DataObject */
		$this->assertFalse(DataObject::has_own_table("DataObject"));
		$this->assertFalse(DataObject::has_own_table("ViewableData"));
		$this->assertFalse(DataObject::has_own_table("ThisIsntADataObject"));
	}
	
	function testNewClassInstance() {
		$page = $this->fixture->objFromFixture('Page', 'page1');
		$changedPage = $page->newClassInstance('RedirectorPage');
		$changedFields = $changedPage->getChangedFields();
		
		// Don't write the record, it will reset changed fields
		
		$this->assertType('RedirectorPage', $changedPage);
		$this->assertEquals($changedPage->ClassName, 'RedirectorPage');
		//$this->assertEquals($changedPage->RecordClassName, 'RedirectorPage');
		$this->assertContains('ClassName', array_keys($changedFields));
		$this->assertEquals($changedFields['ClassName']['before'], 'Page');
		$this->assertEquals($changedFields['ClassName']['after'], 'RedirectorPage');
		
		$changedPage->write();
		$this->assertType('RedirectorPage', $changedPage);
		$this->assertEquals($changedPage->ClassName, 'RedirectorPage');
	}
	
	function testManyManyExtraFields() {
		$player = $this->fixture->objFromFixture('DataObjectTest_Player', 'player1');
	   $team = $this->fixture->objFromFixture('DataObjectTest_Team', 'team1');
		
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
	}

}

class DataObjectTest_Player extends Member implements TestOnly {
	static $has_one = array(
		'FavouriteTeam' => 'DataObjectTest_Team',
	);
	
	static $belongs_many_many = array(
		'Teams' => 'DataObjectTest_Team'
	);
   
}

class DataObjectTest_Team extends DataObject implements TestOnly {

	static $db = array(
		'Title' => 'Text', 
		'DatabaseField' => 'Text'
	);

	static $has_one = array(
		"Captain" => 'DataObjectTest_Player',
		'HasOneRelationship' => 'DataObjectTest_Player',
	);

	static $many_many = array(
		'Players' => 'DataObjectTest_Player'
	);
	
	static $many_many_extraFields = array(
		'Players' => array(
			'Position' => 'Varchar(100)'
		)
	);
	
	function getDynamicField() {
		return 'dynamicfield';
	}

}

class DataObjectTest_FunnyFieldNames extends DataObject implements TestOnly {
	static $db = array(
		'Data' => 'Text',
		'Duplicate' => 'Text',
		'DbObject' => 'Text',
	);
}

class DataObjectTest_SubTeam extends DataObjectTest_Team implements TestOnly {
	static $db = array(
		'SubclassDatabaseField' => 'Text'
	);
}

class DataObjectTest_FieldlessTable extends DataObject implements TestOnly {
}

class DataObjectTest_FieldlessSubTable extends DataObjectTest_Team implements TestOnly {
}


class DataObjectTest_Team_Decorator extends DataObjectDecorator implements TestOnly {
	
	function extraStatics() {
		return array(
			'db' => array(
				'DecoratedDatabaseField' => 'Text'
			),
			'has_one' => array(
				'DecoratedHasOneRelationship' => 'DataObjectTest_Player'
			)
		);
	}
	
	function getDecoratedDynamicField() {
		return "decorated dynamic field";
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

DataObject::add_extension('DataObjectTest_Team', 'DataObjectTest_Team_Decorator');

?>
