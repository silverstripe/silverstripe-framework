<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class DataObjectTest extends SapphireTest {

	static $fixture_file = 'sapphire/tests/DataObjectTest.yml';

	protected $extraDataObjects = array(
		'DataObjectTest_Team',
		'DataObjectTest_Fixture',
		'DataObjectTest_SubTeam',
		'OtherSubclassWithSameField',
		'DataObjectTest_FieldlessTable',
		'DataObjectTest_FieldlessSubTable',
		'DataObjectTest_ValidatedObject',
		'DataObjectTest_Player',
		'DataObjectSetTest_TeamComment'
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
		$page = $this->objFromFixture('Page', 'page1');
		$pageID = $page->ID;
		// Check the page exists before deleting
		$this->assertTrue(is_object($page) && $page->exists());
		// Delete the page
		$page->delete();
		// Check that page does not exist after deleting
		$page = DataObject::get_by_id('Page', $pageID);
		$this->assertTrue(!$page || !$page->exists());
		
		
		// Test deleting using DataObject::delete_by_id()
		// Get the second page
		$page2 = $this->objFromFixture('Page', 'page2');
		$page2ID = $page2->ID;
		// Check the page exists before deleting
		$this->assertTrue(is_object($page2) && $page2->exists());
		// Delete the page
		DataObject::delete_by_id('Page', $page2->ID);
		// Check that page does not exist after deleting
		$page2 = DataObject::get_by_id('Page', $page2ID);
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
	 *   - DataObject::get_one()
	 *        - With and without caching
	 *        - With and without ordering
	 */
	function testGet() {
		// Test getting all records of a DataObject
		$comments = DataObject::get('PageComment');
		$this->assertEquals(8, $comments->Count());
		
		// Test WHERE clause
		$comments = DataObject::get('PageComment', "\"Name\"='Bob'");
		$this->assertEquals(2, $comments->Count());
		foreach($comments as $comment) {
			$this->assertEquals('Bob', $comment->Name);
		}
		
		// Test sorting
		$comments = DataObject::get('PageComment', '', '"Name" ASC');
		$this->assertEquals(8, $comments->Count());
		$this->assertEquals('Bob', $comments->First()->Name);
		$comments = DataObject::get('PageComment', '', '"Name" DESC');
		$this->assertEquals(8, $comments->Count());
		$this->assertEquals('Joe', $comments->First()->Name);
		
		// Test join
		$comments = DataObject::get('PageComment', "\"SiteTree\".\"Title\"='First Page'", '', 'INNER JOIN "SiteTree" ON "PageComment"."ParentID" = "SiteTree"."ID"');
		$this->assertEquals(2, $comments->Count());
		$this->assertEquals('Bob', $comments->First()->Name);
		$this->assertEquals('Bob', $comments->Last()->Name);
		
		// Test limit
		$comments = DataObject::get('PageComment', '', '"Name" ASC', '', '1,2');
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
		
		// Test get_one() without caching
		$comment1 = DataObject::get_one('PageComment', "\"Name\"='Joe'", false);
		$comment1->Comment = "Something Else";
		$comment2 = DataObject::get_one('PageComment', "\"Name\"='Joe'", false);
		$this->assertNotEquals($comment1->Comment, $comment2->Comment);
		
		// Test get_one() with caching
		$comment1 = DataObject::get_one('PageComment', "\"Name\"='Jane'", true);
		$comment1->Comment = "Something Else";
		$comment2 = DataObject::get_one('PageComment', "\"Name\"='Jane'", true);
		$this->assertEquals((string)$comment1->Comment, (string)$comment2->Comment);
		
		// Test get_one() with order by without caching
		$comment = DataObject::get_one('PageComment', '', false, '"Name" ASC');
		$this->assertEquals('Bob', $comment->Name);
		$comment = DataObject::get_one('PageComment', '', false, '"Name" DESC');
		$this->assertEquals('Joe', $comment->Name);
		
		// Test get_one() with order by with caching
		$comment = DataObject::get_one('PageComment', '', true, '"Name" ASC');
		$this->assertEquals('Bob', $comment->Name);
		$comment = DataObject::get_one('PageComment', '', true, '"Name" DESC');
		$this->assertEquals('Joe', $comment->Name);
	}

	/**
	 * Test writing of database columns which don't correlate to a DBField,
	 * e.g. all relation fields on has_one/has_many like "ParentID". 
	 *
	 */
	function testWritePropertyWithoutDBField() {
		$page = $this->objFromFixture('Page', 'page1');
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
		$page = $this->objFromFixture('Page', 'home');
		
		// Test getComponents() gets the ComponentSet of the other side of the relation
		$this->assertTrue($page->getComponents('Comments')->Count() == 2);
		
		// Test the IDs on the DataObjects are set correctly
		foreach($page->getComponents('Comments') as $comment) {
			$this->assertTrue($comment->ParentID == $page->ID);
		}
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
	 * @todo Test removeMany() and addMany() on $many_many relationships
	 */
	function testManyManyRelationships() {
	   $player1 = $this->objFromFixture('DataObjectTest_Player', 'player1');
	   $player2 = $this->objFromFixture('DataObjectTest_Player', 'player2');
	   $team1 = $this->objFromFixture('DataObjectTest_Team', 'team1');
	   $team2 = $this->objFromFixture('DataObjectTest_Team', 'team2');
	   
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
		$page = $this->objFromFixture('Page', 'home');
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
	
	function testIsChanged() {
		$page = $this->objFromFixture('Page', 'home');
		$page->Title = 'Home-Changed';
		$page->ShowInMenus = true; // type change only, database stores "1"

		$this->assertTrue($page->isChanged('Title', 1));
		$this->assertTrue($page->isChanged('Title', 2));
		$this->assertTrue($page->isChanged('ShowInMenus', 1));
		$this->assertFalse($page->isChanged('ShowInMenus', 2));
		$this->assertFalse($page->isChanged('Content', 1));
		$this->assertFalse($page->isChanged('Content', 2));
		
		$newPage = new Page();
		$newPage->Title = "New Page Title";
		$this->assertTrue($newPage->isChanged('Title', 1));
		$this->assertTrue($newPage->isChanged('Title', 2));
		$this->assertFalse($newPage->isChanged('Content', 1));
		$this->assertFalse($newPage->isChanged('Content', 2));
		
		$newPage->write();
		$this->assertFalse($newPage->isChanged('Title', 1));
		$this->assertFalse($newPage->isChanged('Title', 2));
		$this->assertFalse($newPage->isChanged('Content', 1));
		$this->assertFalse($newPage->isChanged('Content', 2));
		
		$page = $this->objFromFixture('Page', 'home');
		$page->Title = null;
		$this->assertTrue($page->isChanged('Title', 1));
		$this->assertTrue($page->isChanged('Title', 2));
		
		/* Test when there's not field provided */ 
		$page = $this->objFromFixture('Page', 'home');
		$page->Title = "New Page Title"; 
		$this->assertTrue($page->isChanged());
		
		$page->write(); 
		$this->assertFalse($page->isChanged());
	}
	
	function testRandomSort() {
		/* If we perforn the same regularly sorted query twice, it should return the same results */
		$itemsA = DataObject::get("PageComment", "", "ID");
		foreach($itemsA as $item) $keysA[] = $item->ID;

		$itemsB = DataObject::get("PageComment", "", "ID");
		foreach($itemsB as $item) $keysB[] = $item->ID;
		
		$this->assertEquals($keysA, $keysB);
		
		/* If we perform the same random query twice, it shouldn't return the same results */
		$itemsA = DataObject::get("PageComment", "", DB::getConn()->random());
		foreach($itemsA as $item) $keysA[] = $item->ID;

		$itemsB = DataObject::get("PageComment", "", DB::getConn()->random());
		foreach($itemsB as $item) $keysB[] = $item->ID;
		
		$this->assertNotEquals($keysA, $keysB);
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
			array_keys(DataObject::database_fields('DataObjectTest_Team')),
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
			array_keys(DataObject::database_fields('DataObjectTest_SubTeam')),
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
		$page = $this->objFromFixture('Page', 'page1');
		$changedPage = $page->newClassInstance('RedirectorPage');
		$changedFields = $changedPage->getChangedFields();
		
		// Don't write the record, it will reset changed fields
		$this->assertType('RedirectorPage', $changedPage);
		$this->assertEquals($changedPage->ClassName, 'RedirectorPage');
		$this->assertEquals($changedPage->RedirectionType, 'Internal');
		//$this->assertEquals($changedPage->RecordClassName, 'RedirectorPage');
		$this->assertContains('ClassName', array_keys($changedFields));
		$this->assertEquals($changedFields['ClassName']['before'], 'Page');
		$this->assertEquals($changedFields['ClassName']['after'], 'RedirectorPage');
		
		$changedPage->write();
		$this->assertType('RedirectorPage', $changedPage);
		$this->assertEquals($changedPage->ClassName, 'RedirectorPage');
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
		$query = $player->getManyManyComponentsQuery('Teams');
		$this->assertEquals(2, $query->unlimitedRowCount());
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
			$team->hasDatabaseField('DecoratedDatabaseField'),
			"hasOwnDatabaseField() works with decorated fields"
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
		$this->assertEquals(array('obj1', 'obj2'), $values);
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
		'Title' => 'Varchar', 
		'DatabaseField' => 'HTMLVarchar'
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


class DataObjectTest_Team_Decorator extends DataObjectDecorator implements TestOnly {
	
	function extraStatics() {
		return array(
			'db' => array(
				'DecoratedDatabaseField' => 'Varchar'
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

DataObject::add_extension('DataObjectTest_Team', 'DataObjectTest_Team_Decorator');

?>
