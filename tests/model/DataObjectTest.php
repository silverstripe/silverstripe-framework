<?php
/**
 * @package framework
 * @subpackage tests
 */
class DataObjectTest extends SapphireTest {

	protected static $fixture_file = 'DataObjectTest.yml';

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
		'DataObjectTest\NamespacedClass',
		'DataObjectTest\RelationClass',
		'DataObjectTest_ExtendedTeamComment',
		'DataObjectTest_Company',
		'DataObjectTest_Staff',
		'DataObjectTest_CEO',
		'DataObjectTest_Fan',
		'DataObjectTest_Play',
		'DataObjectTest_Ploy',
		'DataObjectTest_Bogey',
	);

	public function testDb() {
		$obj = new DataObjectTest_TeamComment();
		$dbFields = $obj->db();

		// Assert fields are included
		$this->assertArrayHasKey('Name', $dbFields);

		// Assert the base fields are excluded
		$this->assertArrayNotHasKey('Created', $dbFields);
		$this->assertArrayNotHasKey('LastEdited', $dbFields);
		$this->assertArrayNotHasKey('ClassName', $dbFields);
		$this->assertArrayNotHasKey('ID', $dbFields);

		// Assert that the correct field type is returned when passing a field
		$this->assertEquals('Varchar', $obj->db('Name'));
		$this->assertEquals('Text', $obj->db('Comment'));

		$obj = new DataObjectTest_ExtendedTeamComment();
		$dbFields = $obj->db();

		// Assert overloaded fields have correct data type
		$this->assertEquals('HTMLText', $obj->db('Comment'));
		$this->assertEquals('HTMLText', $dbFields['Comment'],
			'Calls to DataObject::db without a field specified return correct data types');

		// assertEquals doesn't verify the order of array elements, so access keys manually to check order:
		// expected: array('Name' => 'Varchar', 'Comment' => 'HTMLText')
		reset($dbFields);
		$this->assertEquals('Name', key($dbFields), 'DataObject::db returns fields in correct order');
		next($dbFields);
		$this->assertEquals('Comment', key($dbFields), 'DataObject::db returns fields in correct order');
	}

	public function testConstructAcceptsValues() {
		// Values can be an array...
		$player = new DataObjectTest_Player(array(
			'FirstName' => 'James',
			'Surname' => 'Smith'
		));

		$this->assertEquals('James', $player->FirstName);
		$this->assertEquals('Smith', $player->Surname);

		// ... or a stdClass inst
		$data = new stdClass();
		$data->FirstName = 'John';
		$data->Surname = 'Doe';
		$player = new DataObjectTest_Player($data);

		$this->assertEquals('John', $player->FirstName);
		$this->assertEquals('Doe', $player->Surname);

		// IDs should be stored as integers, not strings
		$player = new DataObjectTest_Player(array('ID' => '5'));
		$this->assertSame(5, $player->ID);
	}

	public function testValidObjectsForBaseFields() {
		$obj = new DataObjectTest_ValidatedObject();

		foreach (array('Created', 'LastEdited', 'ClassName', 'ID') as $field) {
			$helper = $obj->dbObject($field);
			$this->assertTrue(
				($helper instanceof DBField),
				"for {$field} expected helper to be DBField, but was " .
				(is_object($helper) ? get_class($helper) : "null")
			);
		}
	}

	public function testDataIntegrityWhenTwoSubclassesHaveSameField() {
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
	public function testDelete() {
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
	public function testGet() {
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
		$comment1 = DataObject::get_one('DataObjectTest_TeamComment', array(
			'"DataObjectTest_TeamComment"."Name"' => 'Joe'
		), false);
		$comment1->Comment = "Something Else";

		$comment2 = DataObject::get_one('DataObjectTest_TeamComment', array(
			'"DataObjectTest_TeamComment"."Name"' => 'Joe'
		), false);
		$this->assertNotEquals($comment1->Comment, $comment2->Comment);

		// Test get_one() with caching
		$comment1 = DataObject::get_one('DataObjectTest_TeamComment', array(
			'"DataObjectTest_TeamComment"."Name"' => 'Bob'
		), true);
		$comment1->Comment = "Something Else";

		$comment2 = DataObject::get_one('DataObjectTest_TeamComment', array(
			'"DataObjectTest_TeamComment"."Name"' => 'Bob'
		), true);
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

	public function testGetCaseInsensitive() {
		// Test get_one() with bad case on the classname
		// Note: This will succeed only if the underlying DB server supports case-insensitive
		// table names (e.g. such as MySQL, but not SQLite3)
		if(!(DB::get_conn() instanceof MySQLDatabase)) {
			$this->markTestSkipped('MySQL only');
		}

		$subteam1 = DataObject::get_one('dataobjecttest_subteam', array(
			'"DataObjectTest_Team"."Title"' => 'Subteam 1'
		), true);
		$this->assertNotEmpty($subteam1);
		$this->assertEquals($subteam1->Title, "Subteam 1");
	}

	public function testGetSubclassFields() {
		/* Test that fields / has_one relations from the parent table and the subclass tables are extracted */
		$captain1 = $this->objFromFixture("DataObjectTest_Player", "captain1");
		// Base field
		$this->assertEquals('Captain', $captain1->FirstName);
		// Subclass field
		$this->assertEquals('007', $captain1->ShirtNumber);
		// Subclass has_one relation
		$this->assertEquals($this->idFromFixture('DataObjectTest_Team', 'team1'), $captain1->FavouriteTeamID);
	}

	public function testGetRelationClass() {
		$obj = new DataObjectTest_Player();
		$this->assertEquals(singleton('DataObjectTest_Player')->getRelationClass('FavouriteTeam'),
			'DataObjectTest_Team', 'has_one is properly inspected');
		$this->assertEquals(singleton('DataObjectTest_Company')->getRelationClass('CurrentStaff'),
			'DataObjectTest_Staff', 'has_many is properly inspected');
		$this->assertEquals(singleton('DataObjectTest_Team')->getRelationClass('Players'), 'DataObjectTest_Player',
			'many_many is properly inspected');
		$this->assertEquals(singleton('DataObjectTest_Player')->getRelationClass('Teams'), 'DataObjectTest_Team',
			'belongs_many_many is properly inspected');
		$this->assertEquals(singleton('DataObjectTest_CEO')->getRelationClass('Company'), 'DataObjectTest_Company',
			'belongs_to is properly inspected');
		$this->assertEquals(singleton('DataObjectTest_Fan')->getRelationClass('Favourite'), 'DataObject',
			'polymorphic has_one is properly inspected');
	}

	/**
	 * Test that has_one relations can be retrieved
	 */
	public function testGetHasOneRelations() {
		$captain1 = $this->objFromFixture("DataObjectTest_Player", "captain1");
		$team1ID = $this->idFromFixture('DataObjectTest_Team', 'team1');

		// There will be a field called (relname)ID that contains the ID of the
		// object linked to via the has_one relation
		$this->assertEquals($team1ID, $captain1->FavouriteTeamID);

		// There will be a method called $obj->relname() that returns the object itself
		$this->assertEquals($team1ID, $captain1->FavouriteTeam()->ID);

		// Check entity with polymorphic has-one
		$fan1 = $this->objFromFixture("DataObjectTest_Fan", "fan1");
		$this->assertTrue((bool)$fan1->hasValue('Favourite'));

		// There will be fields named (relname)ID and (relname)Class for polymorphic
		// entities
		$this->assertEquals($team1ID, $fan1->FavouriteID);
		$this->assertEquals('DataObjectTest_Team', $fan1->FavouriteClass);

		// There will be a method called $obj->relname() that returns the object itself
		$favourite = $fan1->Favourite();
		$this->assertEquals($team1ID, $favourite->ID);
		$this->assertInstanceOf('DataObjectTest_Team', $favourite);

		// check behaviour of dbObject with polymorphic relations
		$favouriteDBObject = $fan1->dbObject('Favourite');
		$favouriteValue = $favouriteDBObject->getValue();
		$this->assertInstanceOf('PolymorphicForeignKey', $favouriteDBObject);
		$this->assertEquals($favourite->ID, $favouriteValue->ID);
		$this->assertEquals($favourite->ClassName, $favouriteValue->ClassName);
	}

	/**
	 * Simple test to ensure that namespaced classes and polymorphic relations work together
	 */
	public function testPolymorphicNamespacedRelations() {
		$parent = new \DataObjectTest\NamespacedClass();
		$parent->Name = 'New Parent';
		$parent->write();

		$child = new \DataObjectTest\RelationClass();
		$child->Title = 'New Child';
		$child->write();
		$parent->Relations()->add($child);

		$this->assertEquals(1, $parent->Relations()->count());
		$this->assertEquals(array('New Child'), $parent->Relations()->column('Title'));
		$this->assertEquals('New Parent', $child->Parent()->Name);
	}

	public function testLimitAndCount() {
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
	public function testWritePropertyWithoutDBField() {
		$obj = $this->objFromFixture('DataObjectTest_Player', 'captain1');
		$obj->FavouriteTeamID = 99;
		$obj->write();

		// reload the page from the database
		$savedObj = DataObject::get_by_id('DataObjectTest_Player', $obj->ID);
		$this->assertTrue($savedObj->FavouriteTeamID == 99);

		// Test with porymorphic relation
		$obj2 = $this->objFromFixture("DataObjectTest_Fan", "fan1");
		$obj2->FavouriteID = 99;
		$obj2->FavouriteClass = 'DataObjectTest_Player';
		$obj2->write();

		$savedObj2 = DataObject::get_by_id('DataObjectTest_Fan', $obj2->ID);
		$this->assertTrue($savedObj2->FavouriteID == 99);
		$this->assertTrue($savedObj2->FavouriteClass == 'DataObjectTest_Player');
	}

	/**
	 * Test has many relationships
	 *   - Test getComponents() gets the ComponentSet of the other side of the relation
	 *   - Test the IDs on the DataObjects are set correctly
	 */
	public function testHasManyRelationships() {
		$team1 = $this->objFromFixture('DataObjectTest_Team', 'team1');

		// Test getComponents() gets the ComponentSet of the other side of the relation
		$this->assertTrue($team1->Comments()->Count() == 2);

		// Test the IDs on the DataObjects are set correctly
		foreach($team1->Comments() as $comment) {
			$this->assertEquals($team1->ID, $comment->TeamID);
		}

		// Test that we can add and remove items that already exist in the database
		$newComment = new DataObjectTest_TeamComment();
		$newComment->Name = "Automated commenter";
		$newComment->Comment = "This is a new comment";
		$newComment->write();
		$team1->Comments()->add($newComment);
		$this->assertEquals($team1->ID, $newComment->TeamID);

		$comment1 = $this->objFromFixture('DataObjectTest_TeamComment', 'comment1');
		$comment2 = $this->objFromFixture('DataObjectTest_TeamComment', 'comment2');
		$team1->Comments()->remove($comment2);

		$team1CommentIDs = $team1->Comments()->sort('ID')->column('ID');
		$this->assertEquals(array($comment1->ID, $newComment->ID), $team1CommentIDs);

		// Test that removing an item from a list doesn't remove it from the same
		// relation belonging to a different object
		$team1 = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$team2 = $this->objFromFixture('DataObjectTest_Team', 'team2');
		$team2->Comments()->remove($comment1);
		$team1CommentIDs = $team1->Comments()->sort('ID')->column('ID');
		$this->assertEquals(array($comment1->ID, $newComment->ID), $team1CommentIDs);
	}


	/**
	 * Test has many relationships against polymorphic has_one fields
	 *   - Test getComponents() gets the ComponentSet of the other side of the relation
	 *   - Test the IDs on the DataObjects are set correctly
	 */
	public function testHasManyPolymorphicRelationships() {
		$team1 = $this->objFromFixture('DataObjectTest_Team', 'team1');

		// Test getComponents() gets the ComponentSet of the other side of the relation
		$this->assertTrue($team1->Fans()->Count() == 2);

		// Test the IDs/Classes on the DataObjects are set correctly
		foreach($team1->Fans() as $fan) {
			$this->assertEquals($team1->ID, $fan->FavouriteID, 'Fan has the correct FavouriteID');
			$this->assertEquals('DataObjectTest_Team', $fan->FavouriteClass, 'Fan has the correct FavouriteClass');
		}

		// Test that we can add and remove items that already exist in the database
		$newFan = new DataObjectTest_Fan();
		$newFan->Name = "New fan";
		$newFan->write();
		$team1->Fans()->add($newFan);
		$this->assertEquals($team1->ID, $newFan->FavouriteID, 'Newly created fan has the correct FavouriteID');
		$this->assertEquals(
			'DataObjectTest_Team',
			$newFan->FavouriteClass,
			'Newly created fan has the correct FavouriteClass'
		);

		$fan1 = $this->objFromFixture('DataObjectTest_Fan', 'fan1');
		$fan3 = $this->objFromFixture('DataObjectTest_Fan', 'fan3');
		$team1->Fans()->remove($fan3);

		$team1FanIDs = $team1->Fans()->sort('ID')->column('ID');
		$this->assertEquals(array($fan1->ID, $newFan->ID), $team1FanIDs);

		// Test that removing an item from a list doesn't remove it from the same
		// relation belonging to a different object
		$team1 = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$player1 = $this->objFromFixture('DataObjectTest_Player', 'player1');
		$player1->Fans()->remove($fan1);
		$team1FanIDs = $team1->Fans()->sort('ID')->column('ID');
		$this->assertEquals(array($fan1->ID, $newFan->ID), $team1FanIDs);
	}


	public function testHasOneRelationship() {
		$team1 = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$player1 = $this->objFromFixture('DataObjectTest_Player', 'player1');
		$player2 = $this->objFromFixture('DataObjectTest_Player', 'player2');
		$fan1 = $this->objFromFixture('DataObjectTest_Fan', 'fan1');

		// Test relation probing
		$this->assertFalse((bool)$team1->hasValue('Captain', null, false));
		$this->assertFalse((bool)$team1->hasValue('CaptainID', null, false));

		// Add a captain to team 1
		$team1->setField('CaptainID', $player1->ID);
		$team1->write();

		$this->assertTrue((bool)$team1->hasValue('Captain', null, false));
		$this->assertTrue((bool)$team1->hasValue('CaptainID', null, false));

		$this->assertEquals($player1->ID, $team1->Captain()->ID,
			'The captain exists for team 1');
		$this->assertEquals($player1->ID, $team1->getComponent('Captain')->ID,
			'The captain exists through the component getter');

		$this->assertEquals($team1->Captain()->FirstName, 'Player 1',
			'Player 1 is the captain');
		$this->assertEquals($team1->getComponent('Captain')->FirstName, 'Player 1',
			'Player 1 is the captain');

		$team1->CaptainID = $player2->ID;
		$team1->write();

		$this->assertEquals($player2->ID, $team1->Captain()->ID);
		$this->assertEquals($player2->ID, $team1->getComponent('Captain')->ID);
		$this->assertEquals('Player 2', $team1->Captain()->FirstName);
		$this->assertEquals('Player 2', $team1->getComponent('Captain')->FirstName);


		// Set the favourite team for fan1
		$fan1->setField('FavouriteID', $team1->ID);
		$fan1->setField('FavouriteClass', $team1->class);

		$this->assertEquals($team1->ID, $fan1->Favourite()->ID, 'The team is assigned to fan 1');
		$this->assertInstanceOf($team1->class, $fan1->Favourite(), 'The team is assigned to fan 1');
		$this->assertEquals($team1->ID, $fan1->getComponent('Favourite')->ID,
			'The team exists through the component getter'
		);
		$this->assertInstanceOf($team1->class, $fan1->getComponent('Favourite'),
			'The team exists through the component getter'
		);

		$this->assertEquals($fan1->Favourite()->Title, 'Team 1',
			'Team 1 is the favourite');
		$this->assertEquals($fan1->getComponent('Favourite')->Title, 'Team 1',
			'Team 1 is the favourite');
	}

	/**
	 * @todo Extend type change tests (e.g. '0'==NULL)
	 */
	public function testChangedFields() {
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

	public function testIsChanged() {
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

	public function testRandomSort() {
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
		$itemsA = DataObject::get("DataObjectTest_TeamComment", "", DB::get_conn()->random());
		$itemsB = DataObject::get("DataObjectTest_TeamComment", "", DB::get_conn()->random());
		$itemsC = DataObject::get("DataObjectTest_TeamComment", "", DB::get_conn()->random());
		$itemsD = DataObject::get("DataObjectTest_TeamComment", "", DB::get_conn()->random());
		foreach($itemsA as $item) $keysA[] = $item->ID;
		foreach($itemsB as $item) $keysB[] = $item->ID;
		foreach($itemsC as $item) $keysC[] = $item->ID;
		foreach($itemsD as $item) $keysD[] = $item->ID;

		// These shouldn't all be the same (run it 4 times to minimise chance of an accidental collision)
		// There's about a 1 in a billion chance of an accidental collision
		$this->assertTrue($keysA != $keysB || $keysB != $keysC || $keysC != $keysD);
	}

	public function testWriteSavesToHasOneRelations() {
		/* DataObject::write() should save to a has_one relationship if you set a field called (relname)ID */
		$team = new DataObjectTest_Team();
		$captainID = $this->idFromFixture('DataObjectTest_Player', 'player1');
		$team->CaptainID = $captainID;
		$team->write();
		$this->assertEquals($captainID,
			DB::query("SELECT \"CaptainID\" FROM \"DataObjectTest_Team\" WHERE \"ID\" = $team->ID")->value());

		/* After giving it a value, you should also be able to set it back to null */
		$team->CaptainID = '';
		$team->write();
		$this->assertEquals(0,
			DB::query("SELECT \"CaptainID\" FROM \"DataObjectTest_Team\" WHERE \"ID\" = $team->ID")->value());

		/* You should also be able to save a blank to it when it's first created */
		$team = new DataObjectTest_Team();
		$team->CaptainID = '';
		$team->write();
		$this->assertEquals(0,
			DB::query("SELECT \"CaptainID\" FROM \"DataObjectTest_Team\" WHERE \"ID\" = $team->ID")->value());

		/* Ditto for existing records without a value */
		$existingTeam = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$existingTeam->CaptainID = '';
		$existingTeam->write();
		$this->assertEquals(0,
			DB::query("SELECT \"CaptainID\" FROM \"DataObjectTest_Team\" WHERE \"ID\" = $existingTeam->ID")->value());
	}

	public function testCanAccessHasOneObjectsAsMethods() {
		/* If you have a has_one relation 'Captain' on $obj, and you set the $obj->CaptainID = (ID), then the
		 * object itself should be accessible as $obj->Captain() */
		$team = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$captainID = $this->idFromFixture('DataObjectTest_Player', 'captain1');

		$team->CaptainID = $captainID;
		$this->assertNotNull($team->Captain());
		$this->assertEquals($captainID, $team->Captain()->ID);

		// Test for polymorphic has_one relations
		$fan = $this->objFromFixture('DataObjectTest_Fan', 'fan1');
		$fan->FavouriteID = $team->ID;
		$fan->FavouriteClass = $team->class;
		$this->assertNotNull($fan->Favourite());
		$this->assertEquals($team->ID, $fan->Favourite()->ID);
		$this->assertInstanceOf($team->class, $fan->Favourite());
	}

	public function testFieldNamesThatMatchMethodNamesWork() {
		/* Check that a field name that corresponds to a method on DataObject will still work */
		$obj = new DataObjectTest_Fixture();
		$obj->Data = "value1";
		$obj->DbObject = "value2";
		$obj->Duplicate = "value3";
		$obj->write();

		$this->assertNotNull($obj->ID);
		$this->assertEquals('value1',
			DB::query("SELECT \"Data\" FROM \"DataObjectTest_Fixture\" WHERE \"ID\" = $obj->ID")->value());
		$this->assertEquals('value2',
			DB::query("SELECT \"DbObject\" FROM \"DataObjectTest_Fixture\" WHERE \"ID\" = $obj->ID")->value());
		$this->assertEquals('value3',
			DB::query("SELECT \"Duplicate\" FROM \"DataObjectTest_Fixture\" WHERE \"ID\" = $obj->ID")->value());
	}

	/**
	 * @todo Re-enable all test cases for field existence after behaviour has been fixed
	 */
	public function testFieldExistence() {
		$teamInstance = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$teamSingleton = singleton('DataObjectTest_Team');

		$subteamInstance = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam1');
		$subteamSingleton = singleton('DataObjectTest_SubTeam');

		/* hasField() singleton checks */
		$this->assertTrue($teamSingleton->hasField('ID'),
			'hasField() finds built-in fields in singletons');
		$this->assertTrue($teamSingleton->hasField('Title'),
			'hasField() finds custom fields in singletons');

		/* hasField() instance checks */
		$this->assertFalse($teamInstance->hasField('NonExistingField'),
			'hasField() doesnt find non-existing fields in instances');
		$this->assertTrue($teamInstance->hasField('ID'),
			'hasField() finds built-in fields in instances');
		$this->assertTrue($teamInstance->hasField('Created'),
			'hasField() finds built-in fields in instances');
		$this->assertTrue($teamInstance->hasField('DatabaseField'),
			'hasField() finds custom fields in instances');
		//$this->assertFalse($teamInstance->hasField('SubclassDatabaseField'),
		//'hasField() doesnt find subclass fields in parentclass instances');
		$this->assertTrue($teamInstance->hasField('DynamicField'),
			'hasField() finds dynamic getters in instances');
		$this->assertTrue($teamInstance->hasField('HasOneRelationshipID'),
			'hasField() finds foreign keys in instances');
		$this->assertTrue($teamInstance->hasField('ExtendedDatabaseField'),
			'hasField() finds extended fields in instances');
		$this->assertTrue($teamInstance->hasField('ExtendedHasOneRelationshipID'),
			'hasField() finds extended foreign keys in instances');
		//$this->assertTrue($teamInstance->hasField('ExtendedDynamicField'),
		//'hasField() includes extended dynamic getters in instances');

		/* hasField() subclass checks */
		$this->assertTrue($subteamInstance->hasField('ID'),
			'hasField() finds built-in fields in subclass instances');
		$this->assertTrue($subteamInstance->hasField('Created'),
			'hasField() finds built-in fields in subclass instances');
		$this->assertTrue($subteamInstance->hasField('DatabaseField'),
			'hasField() finds custom fields in subclass instances');
		$this->assertTrue($subteamInstance->hasField('SubclassDatabaseField'),
			'hasField() finds custom fields in subclass instances');
		$this->assertTrue($subteamInstance->hasField('DynamicField'),
			'hasField() finds dynamic getters in subclass instances');
		$this->assertTrue($subteamInstance->hasField('HasOneRelationshipID'),
			'hasField() finds foreign keys in subclass instances');
		$this->assertTrue($subteamInstance->hasField('ExtendedDatabaseField'),
			'hasField() finds extended fields in subclass instances');
		$this->assertTrue($subteamInstance->hasField('ExtendedHasOneRelationshipID'),
			'hasField() finds extended foreign keys in subclass instances');

		/* hasDatabaseField() singleton checks */
		//$this->assertTrue($teamSingleton->hasDatabaseField('ID'),
		//'hasDatabaseField() finds built-in fields in singletons');
		$this->assertTrue($teamSingleton->hasDatabaseField('Title'),
			'hasDatabaseField() finds custom fields in singletons');

		/* hasDatabaseField() instance checks */
		$this->assertFalse($teamInstance->hasDatabaseField('NonExistingField'),
			'hasDatabaseField() doesnt find non-existing fields in instances');
		//$this->assertTrue($teamInstance->hasDatabaseField('ID'),
		//'hasDatabaseField() finds built-in fields in instances');
		$this->assertTrue($teamInstance->hasDatabaseField('Created'),
			'hasDatabaseField() finds built-in fields in instances');
		$this->assertTrue($teamInstance->hasDatabaseField('DatabaseField'),
			'hasDatabaseField() finds custom fields in instances');
		$this->assertFalse($teamInstance->hasDatabaseField('SubclassDatabaseField'),
			'hasDatabaseField() doesnt find subclass fields in parentclass instances');
		//$this->assertFalse($teamInstance->hasDatabaseField('DynamicField'),
		//'hasDatabaseField() doesnt dynamic getters in instances');
		$this->assertTrue($teamInstance->hasDatabaseField('HasOneRelationshipID'),
			'hasDatabaseField() finds foreign keys in instances');
		$this->assertTrue($teamInstance->hasDatabaseField('ExtendedDatabaseField'),
			'hasDatabaseField() finds extended fields in instances');
		$this->assertTrue($teamInstance->hasDatabaseField('ExtendedHasOneRelationshipID'),
			'hasDatabaseField() finds extended foreign keys in instances');
		$this->assertFalse($teamInstance->hasDatabaseField('ExtendedDynamicField'),
			'hasDatabaseField() doesnt include extended dynamic getters in instances');

		/* hasDatabaseField() subclass checks */
		$this->assertTrue($subteamInstance->hasDatabaseField('DatabaseField'),
			'hasField() finds custom fields in subclass instances');
		$this->assertTrue($subteamInstance->hasDatabaseField('SubclassDatabaseField'),
			'hasField() finds custom fields in subclass instances');

	}

	/**
	 * @todo Re-enable all test cases for field inheritance aggregation after behaviour has been fixed
	 */
	public function testFieldInheritance() {
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
			'inheritedDatabaseFields() contains all fields defined on instance: base, extended and foreign keys'
		);

		$this->assertEquals(
			array_keys(DataObject::database_fields('DataObjectTest_Team', false)),
			array(
				//'ID',
				'ClassName',
				'LastEdited',
				'Created',
				'Title',
				'DatabaseField',
				'ExtendedDatabaseField',
				'CaptainID',
				'HasOneRelationshipID',
				'ExtendedHasOneRelationshipID'
			),
			'databaseFields() contains only fields defined on instance, including base, extended and foreign keys'
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
			'inheritedDatabaseFields() on subclass contains all fields, including base, extended  and foreign keys'
		);

		$this->assertEquals(
			array_keys(DataObject::database_fields('DataObjectTest_SubTeam', false)),
			array(
				'SubclassDatabaseField',
				'ParentTeamID',
			),
			'databaseFields() on subclass contains only fields defined on instance'
		);
	}

	public function testSearchableFields() {
		$player = $this->objFromFixture('DataObjectTest_Player', 'captain1');
		$fields = $player->searchableFields();
		$this->assertArrayHasKey(
			'IsRetired',
			$fields,
			'Fields defined by $searchable_fields static are correctly detected'
		);
		$this->assertArrayHasKey(
			'ShirtNumber',
			$fields,
			'Fields defined by $searchable_fields static are correctly detected'
		);

		$team = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$fields = $team->searchableFields();
		$this->assertArrayHasKey(
			'Title',
			$fields,
			'Fields can be inherited from the $summary_fields static, including methods called on fields'
		);
		$this->assertArrayHasKey(
			'Captain.ShirtNumber',
			$fields,
			'Fields on related objects can be inherited from the $summary_fields static'
		);
		$this->assertArrayHasKey(
			'Captain.FavouriteTeam.Title',
			$fields,
			'Fields on related objects can be inherited from the $summary_fields static'
		);

		$testObj = new DataObjectTest_Fixture();
		$fields = $testObj->searchableFields();
		$this->assertEmpty($fields);
	}

	public function testSummaryFieldsCustomLabels() {
		$team = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$summaryFields = $team->summaryFields();

		$this->assertEquals(
			'Custom Title',
			$summaryFields['Title'],
			'Custom title is preserved'
		);

		$this->assertEquals(
			'Captain\'s shirt number',
			$summaryFields['Captain.ShirtNumber'],
			'Custom title on relation is preserved'
		);
	}

	public function testDataObjectUpdate() {
		/* update() calls can use the dot syntax to reference has_one relations and other methods that return
		 * objects */
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

		/* Setting Captain.Email and Captain.FirstName will have updated DataObjectTest_Captain.captain1 in
		 * the database.  Although update() doesn't usually write, it does write related records automatically. */
		$captain1 = $this->objFromFixture('DataObjectTest_Player', 'captain1');
		$this->assertEquals('Jim', $captain1->FirstName);
		$this->assertEquals('jim@example.com', $captain1->Email);

		/* Jim's favourite team is team 1; we need to reload the object to the the change that setting Captain.
		 * FavouriteTeam.Title made */
		$reloadedTeam1 = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$this->assertEquals('New and improved team 1', $reloadedTeam1->Title);
	}

	public function testDataObjectUpdateNew() {
		/* update() calls can use the dot syntax to reference has_one relations and other methods that return
		 * objects */
		$team1 = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$team1->CaptainID = 0;

		$team1->update(array(
			'Captain.FirstName' => 'Jim',
			'Captain.FavouriteTeam.Title' => 'New and improved team 1',
		));
		/* Test that the captain ID has been updated */
		$this->assertGreaterThan(0, $team1->CaptainID);

		/* Fetch the newly created captain */
		$captain1 = DataObjectTest_Player::get()->byID($team1->CaptainID);
		$this->assertEquals('Jim', $captain1->FirstName);

		/* Grab the favourite team and make sure it has the correct values */
		$reloadedTeam1 = $captain1->FavouriteTeam();
		$this->assertEquals($reloadedTeam1->ID, $captain1->FavouriteTeamID);
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
		$this->assertEquals("DataObjectTest_SubTeam",
			DB::query("SELECT \"ClassName\" FROM \"DataObjectTest_Team\" WHERE \"ID\" = $obj->ID")->value());
	}

	public function testForceInsert() {
		/* If you set an ID on an object and pass forceInsert = true, then the object should be correctly created */
		$conn = DB::get_conn();
		if(method_exists($conn, 'allowPrimaryKeyEditing')) $conn->allowPrimaryKeyEditing('DataObjectTest_Team', true);
		$obj = new DataObjectTest_SubTeam();
		$obj->ID = 1001;
		$obj->Title = 'asdfasdf';
		$obj->SubclassDatabaseField = 'asdfasdf';
		$obj->write(false, true);
		if(method_exists($conn, 'allowPrimaryKeyEditing')) $conn->allowPrimaryKeyEditing('DataObjectTest_Team', false);

		$this->assertEquals("DataObjectTest_SubTeam",
			DB::query("SELECT \"ClassName\" FROM \"DataObjectTest_Team\" WHERE \"ID\" = $obj->ID")->value());

		/* Check that it actually saves to the database with the correct ID */
		$this->assertEquals("1001", DB::query(
			"SELECT \"ID\" FROM \"DataObjectTest_SubTeam\" WHERE \"SubclassDatabaseField\" = 'asdfasdf'")->value());
		$this->assertEquals("1001",
			DB::query("SELECT \"ID\" FROM \"DataObjectTest_Team\" WHERE \"Title\" = 'asdfasdf'")->value());
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
		// $SubclassDatabaseField is empty on here
		$right = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam2_with_player_relation');
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

	public function testPopulateDefaults() {
		$obj = new DataObjectTest_Fixture();
		$this->assertEquals(
			$obj->MyFieldWithDefault,
			'Default Value',
			'Defaults are populated for in-memory object from $defaults array'
		);

		$this->assertEquals(
			$obj->MyFieldWithAltDefault,
			'Default Value',
			'Defaults are populated from overloaded populateDefaults() method'
		);
	}

	protected function makeAccessible($object, $method) {
		$reflectionMethod = new ReflectionMethod($object, $method);
		$reflectionMethod->setAccessible(true);
		return $reflectionMethod;
	}

	public function testValidateModelDefinitionsFailsWithArray() {
		Config::nest();
		
		$object = new DataObjectTest_Team;
		$method = $this->makeAccessible($object, 'validateModelDefinitions');

		Config::inst()->update('DataObjectTest_Team', 'has_one', array('NotValid' => array('NoArraysAllowed')));
		$this->setExpectedException('LogicException');

		try {
			$method->invoke($object);
		} catch(Exception $e) {
			Config::unnest(); // Catch the exception so we can unnest config before failing the test
			throw $e;
		}
	}

	public function testValidateModelDefinitionsFailsWithIntKey() {
		Config::nest();
		
		$object = new DataObjectTest_Team;
		$method = $this->makeAccessible($object, 'validateModelDefinitions');

		Config::inst()->update('DataObjectTest_Team', 'has_many', array(12 => 'DataObjectTest_Player'));
		$this->setExpectedException('LogicException');

		try {
			$method->invoke($object);
		} catch(Exception $e) {
			Config::unnest(); // Catch the exception so we can unnest config before failing the test
			throw $e;
		}
	}

	public function testValidateModelDefinitionsFailsWithIntValue() {
		Config::nest();
		
		$object = new DataObjectTest_Team;
		$method = $this->makeAccessible($object, 'validateModelDefinitions');

		Config::inst()->update('DataObjectTest_Team', 'many_many', array('Players' => 12));
		$this->setExpectedException('LogicException');

		try {
			$method->invoke($object);
		} catch(Exception $e) {
			Config::unnest(); // Catch the exception so we can unnest config before failing the test
			throw $e;
		}
	}

	/**
	 * many_many_extraFields is allowed to have an array value, so shouldn't throw an exception
	 */
	public function testValidateModelDefinitionsPassesWithExtraFields() {
		Config::nest();
		
		$object = new DataObjectTest_Team;
		$method = $this->makeAccessible($object, 'validateModelDefinitions');

		Config::inst()->update('DataObjectTest_Team', 'many_many_extraFields',
			array('Relations' => array('Price' => 'Int')));

		try {
			$method->invoke($object);
		} catch(Exception $e) {
			Config::unnest();
			$this->fail('Exception should not be thrown');
			throw $e;
		}

		Config::unnest();
	}

	public function testNewClassInstance() {
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

	public function testMultipleManyManyWithSameClass() {
		$team = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$sponsors = $team->Sponsors();
		$equipmentSuppliers = $team->EquipmentSuppliers();

		// Check that DataObject::many_many() works as expected
		list($class, $targetClass, $parentField, $childField, $joinTable) = $team->manyManyComponent('Sponsors');
		$this->assertEquals('DataObjectTest_Team', $class,
			'DataObject::many_many() didn\'t find the correct base class');
		$this->assertEquals('DataObjectTest_EquipmentCompany', $targetClass,
			'DataObject::many_many() didn\'t find the correct target class for the relation');
		$this->assertEquals('DataObjectTest_EquipmentCompany_SponsoredTeams', $joinTable,
			'DataObject::many_many() didn\'t find the correct relation table');

		// Check that ManyManyList still works
		$this->assertEquals(2, $sponsors->count(), 'Rows are missing from relation');
		$this->assertEquals(1, $equipmentSuppliers->count(), 'Rows are missing from relation');

		// Check everything works when no relation is present
		$teamWithoutSponsor = $this->objFromFixture('DataObjectTest_Team', 'team3');
		$this->assertInstanceOf('ManyManyList', $teamWithoutSponsor->Sponsors());
		$this->assertEquals(0, $teamWithoutSponsor->Sponsors()->count());

		// Check many_many_extraFields still works
		$equipmentCompany = $this->objFromFixture('DataObjectTest_EquipmentCompany', 'equipmentcompany1');
		$equipmentCompany->SponsoredTeams()->add($teamWithoutSponsor, array('SponsorFee' => 1000));
		$sponsoredTeams = $equipmentCompany->SponsoredTeams();
		$this->assertEquals(1000, $sponsoredTeams->byID($teamWithoutSponsor->ID)->SponsorFee,
			'Data from many_many_extraFields was not stored/extracted correctly');

		// Check subclasses correctly inherit multiple many_manys
		$subTeam = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam1');
		$this->assertEquals(2, $subTeam->Sponsors()->count(),
			'Child class did not inherit multiple many_manys');
		$this->assertEquals(1, $subTeam->EquipmentSuppliers()->count(),
			'Child class did not inherit multiple many_manys');
		// Team 2 has one EquipmentCompany sponsor and one SubEquipmentCompany
		$team2 = $this->objFromFixture('DataObjectTest_Team', 'team2');
		$this->assertEquals(2, $team2->Sponsors()->count(),
			'Child class did not inherit multiple belongs_many_manys');

		// Check many_many_extraFields also works from the belongs_many_many side
		$sponsors = $team2->Sponsors();
		$sponsors->add($equipmentCompany, array('SponsorFee' => 750));
		$this->assertEquals(750, $sponsors->byID($equipmentCompany->ID)->SponsorFee,
			'Data from many_many_extraFields was not stored/extracted correctly');

		$subEquipmentCompany = $this->objFromFixture('DataObjectTest_SubEquipmentCompany', 'subequipmentcompany1');
		$subTeam->Sponsors()->add($subEquipmentCompany, array('SponsorFee' => 1200));
		$this->assertEquals(1200, $subTeam->Sponsors()->byID($subEquipmentCompany->ID)->SponsorFee,
			'Data from inherited many_many_extraFields was not stored/extracted correctly');
	}

	public function testManyManyExtraFields() {
		$player = $this->objFromFixture('DataObjectTest_Player', 'player1');
		$team = $this->objFromFixture('DataObjectTest_Team', 'team1');

		// Get all extra fields
		$teamExtraFields = $team->manyManyExtraFields();
		$this->assertEquals(array(
			'Players' => array('Position' => 'Varchar(100)')
		), $teamExtraFields);

		// Ensure fields from parent classes are included
		$subTeam = singleton('DataObjectTest_SubTeam');
		$teamExtraFields = $subTeam->manyManyExtraFields();
		$this->assertEquals(array(
			'Players' => array('Position' => 'Varchar(100)'),
			'FormerPlayers' => array('Position' => 'Varchar(100)')
		), $teamExtraFields);

		// Extra fields are immediately available on the Team class (defined in $many_many_extraFields)
		$teamExtraFields = $team->manyManyExtraFieldsForComponent('Players');
		$this->assertEquals($teamExtraFields, array(
			'Position' => 'Varchar(100)'
		));

		// We'll have to go through the relation to get the extra fields on Player
		$playerExtraFields = $player->manyManyExtraFieldsForComponent('Teams');
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
		$player->Teams()->sort("count(DISTINCT \"DataObjectTest_Team_Players\".\"DataObjectTest_PlayerID\") DESC");
	}

	/**
	 * Check that the queries generated for many-many relation queries can have unlimitedRowCount
	 * called on them.
	 */
	public function testManyManyUnlimitedRowCount() {
		$player = $this->objFromFixture('DataObjectTest_Player', 'player2');
		// TODO: What's going on here?
		$this->assertEquals(2, $player->Teams()->dataQuery()->query()->unlimitedRowCount());
	}

	/**
	 * Tests that singular_name() generates sensible defaults.
	 */
	public function testSingularName() {
		$assertions = array(
			'DataObjectTest_Player'       => 'Data Object Test Player',
			'DataObjectTest_Team'         => 'Data Object Test Team',
			'DataObjectTest_Fixture'      => 'Data Object Test Fixture'
		);

		foreach($assertions as $class => $expectedSingularName) {
			$this->assertEquals(
				$expectedSingularName,
				singleton($class)->singular_name(),
				"Assert that the singular_name for '$class' is correct."
			);
		}
	}

	/**
	 * Tests that plural_name() generates sensible defaults.
	 */
	public function testPluralName() {
		$assertions = array(
			'DataObjectTest_Player'       => 'Data Object Test Players',
			'DataObjectTest_Team'         => 'Data Object Test Teams',
			'DataObjectTest_Fixture'      => 'Data Object Test Fixtures',
			'DataObjectTest_Play'         => 'Data Object Test Plays',
			'DataObjectTest_Bogey'        => 'Data Object Test Bogeys',
			'DataObjectTest_Ploy'         => 'Data Object Test Ploys',
		);

		foreach($assertions as $class => $expectedPluralName) {
			$this->assertEquals(
				$expectedPluralName,
				singleton($class)->plural_name(),
				"Assert that the plural_name for '$class' is correct."
			);
		}
	}

	public function testHasDatabaseField() {
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

	public function testFieldTypes() {
		$obj = new DataObjectTest_Fixture();
		$obj->DateField = '1988-01-02';
		$obj->DatetimeField = '1988-03-04 06:30';
		$obj->write();
		$obj->flushCache();

		$obj = DataObject::get_by_id('DataObjectTest_Fixture', $obj->ID);
		$this->assertEquals('1988-01-02', $obj->DateField);
		$this->assertEquals('1988-03-04 06:30:00', $obj->DatetimeField);
	}

	public function testTwoSubclassesWithTheSameFieldNameWork() {
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

	public function testClassNameSetForNewObjects() {
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
			$company->hasMany(),
			'has_many strips field name data by default.'
		);

		$this->assertEquals (
			'DataObjectTest_Staff',
			$company->hasManyComponent('CurrentStaff'),
			'has_many strips field name data by default on single relationships.'
		);

		$this->assertEquals (
			array (
				'CurrentStaff'     => 'DataObjectTest_Staff.CurrentCompany',
				'PreviousStaff'    => 'DataObjectTest_Staff.PreviousCompany'
			),
			$company->hasMany(null, false),
			'has_many returns field name data when $classOnly is false.'
		);

		$this->assertEquals (
			'DataObjectTest_Staff.CurrentCompany',
			$company->hasManyComponent('CurrentStaff', false),
			'has_many returns field name data on single records when $classOnly is false.'
		);
	}

	public function testGetRemoteJoinField() {
		$company = new DataObjectTest_Company();

		$staffJoinField = $company->getRemoteJoinField('CurrentStaff', 'has_many', $polymorphic);
		$this->assertEquals('CurrentCompanyID', $staffJoinField);
		$this->assertFalse($polymorphic, 'DataObjectTest_Company->CurrentStaff is not polymorphic');
		$previousStaffJoinField = $company->getRemoteJoinField('PreviousStaff', 'has_many', $polymorphic);
		$this->assertEquals('PreviousCompanyID', $previousStaffJoinField);
		$this->assertFalse($polymorphic, 'DataObjectTest_Company->PreviousStaff is not polymorphic');

		$ceo = new DataObjectTest_CEO();

		$this->assertEquals('CEOID', $ceo->getRemoteJoinField('Company', 'belongs_to', $polymorphic));
		$this->assertFalse($polymorphic, 'DataObjectTest_CEO->Company is not polymorphic');
		$this->assertEquals('PreviousCEOID', $ceo->getRemoteJoinField('PreviousCompany', 'belongs_to', $polymorphic));
		$this->assertFalse($polymorphic, 'DataObjectTest_CEO->PreviousCompany is not polymorphic');

		$team = new DataObjectTest_Team();

		$this->assertEquals('Favourite', $team->getRemoteJoinField('Fans', 'has_many', $polymorphic));
		$this->assertTrue($polymorphic, 'DataObjectTest_Team->Fans is polymorphic');
		$this->assertEquals('TeamID', $team->getRemoteJoinField('Comments', 'has_many', $polymorphic));
		$this->assertFalse($polymorphic, 'DataObjectTest_Team->Comments is not polymorphic');
	}

	public function testBelongsTo() {
		$company = new DataObjectTest_Company();
		$ceo     = new DataObjectTest_CEO();

		$company->write();
		$ceo->write();

		// Test belongs_to assignment
		$company->CEOID = $ceo->ID;
		$company->write();

		$this->assertEquals($company->ID, $ceo->Company()->ID, 'belongs_to returns the right results.');

		// Test automatic creation of class where no assigment exists
		$ceo = new DataObjectTest_CEO();
		$ceo->write();

		$this->assertTrue (
			$ceo->Company() instanceof DataObjectTest_Company,
			'DataObjects across belongs_to relations are automatically created.'
		);
		$this->assertEquals($ceo->ID, $ceo->Company()->CEOID, 'Remote IDs are automatically set.');

		// Write object with components
		$ceo->Name = 'Edward Scissorhands';
		$ceo->write(false, false, false, true);
		$this->assertTrue($ceo->Company()->isInDB(), 'write() writes belongs_to components to the database.');

		$newCEO = DataObject::get_by_id('DataObjectTest_CEO', $ceo->ID);
		$this->assertEquals (
			$ceo->Company()->ID, $newCEO->Company()->ID, 'belongs_to can be retrieved from the database.'
		);
	}

	public function testBelongsToPolymorphic() {
		$company = new DataObjectTest_Company();
		$ceo     = new DataObjectTest_CEO();

		$company->write();
		$ceo->write();

		// Test belongs_to assignment
		$company->OwnerID = $ceo->ID;
		$company->OwnerClass = $ceo->class;
		$company->write();

		$this->assertEquals($company->ID, $ceo->CompanyOwned()->ID, 'belongs_to returns the right results.');
		$this->assertEquals($company->class, $ceo->CompanyOwned()->class, 'belongs_to returns the right results.');

		// Test automatic creation of class where no assigment exists
		$ceo = new DataObjectTest_CEO();
		$ceo->write();

		$this->assertTrue (
			$ceo->CompanyOwned() instanceof DataObjectTest_Company,
			'DataObjects across polymorphic belongs_to relations are automatically created.'
		);
		$this->assertEquals($ceo->ID, $ceo->CompanyOwned()->OwnerID, 'Remote IDs are automatically set.');
		$this->assertInstanceOf($ceo->CompanyOwned()->OwnerClass, $ceo, 'Remote class is automatically  set');

		// Write object with components
		$ceo->write(false, false, false, true);
		$this->assertTrue($ceo->CompanyOwned()->isInDB(), 'write() writes belongs_to components to the database.');

		$newCEO = DataObject::get_by_id('DataObjectTest_CEO', $ceo->ID);
		$this->assertEquals (
			$ceo->CompanyOwned()->ID,
			$newCEO->CompanyOwned()->ID,
			'polymorphic belongs_to can be retrieved from the database.'
		);
	}

	/**
	 * @expectedException LogicException
	 */
	public function testInvalidate() {
		$do = new DataObjectTest_Fixture();
		$do->write();

		$do->delete();

		$do->delete(); // Prohibit invalid object manipulation
		$do->write();
		$do->duplicate();
	}

	public function testToMap() {
		$obj = $this->objFromFixture('DataObjectTest_SubTeam', 'subteam1');

		$map = $obj->toMap();

		$this->assertArrayHasKey('ID', $map, 'Contains base fields');
		$this->assertArrayHasKey('Title', $map, 'Contains fields from parent class');
		$this->assertArrayHasKey('SubclassDatabaseField', $map, 'Contains fields from concrete class');

		$this->assertEquals($obj->ID, $map['ID'],
			'Contains values from base fields');
		$this->assertEquals($obj->Title, $map['Title'],
			'Contains values from parent class fields');
		$this->assertEquals($obj->SubclassDatabaseField, $map['SubclassDatabaseField'],
			'Contains values from concrete class fields');

		$newObj = new DataObjectTest_SubTeam();
		$this->assertArrayHasKey('Title', $map, 'Contains null fields');
	}

	public function testIsEmpty() {
		$objEmpty = new DataObjectTest_Team();
		$this->assertTrue($objEmpty->isEmpty(), 'New instance without populated defaults is empty');

		$objEmpty->Title = '0'; //
		$this->assertFalse($objEmpty->isEmpty(), 'Zero value in attribute considered non-empty');
	}

	public function testRelField() {
		$captain = $this->objFromFixture('DataObjectTest_Player', 'captain1');
		// Test traversal of a single has_one
		$this->assertEquals("Team 1", $captain->relField('FavouriteTeam.Title'));
		// Test direct field access
		$this->assertEquals("Captain", $captain->relField('FirstName'));

		$player = $this->objFromFixture('DataObjectTest_Player', 'player2');
		// Test that we can traverse more than once, and that arbitrary methods are okay
		$this->assertEquals("Team 1", $player->relField('Teams.First.Title'));

		$newPlayer = new DataObjectTest_Player();
		$this->assertNull($newPlayer->relField('Teams.First.Title'));

		// Test that relField works on db field manipulations
		$comment = $this->objFromFixture('DataObjectTest_TeamComment', 'comment3');
		$this->assertEquals("PHIL IS A UNIQUE GUY, AND COMMENTS ON TEAM2" , $comment->relField('Comment.UpperCase'));
	}

	public function testRelObject() {
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

	public function testLateStaticBindingStyle() {
		// Confirm that DataObjectTest_Player::get() operates as excepted
		$this->assertEquals(4, DataObjectTest_Player::get()->Count());
		$this->assertInstanceOf('DataObjectTest_Player', DataObjectTest_Player::get()->First());

		// You can't pass arguments to LSB syntax - use the DataList methods instead.
		$this->setExpectedException('InvalidArgumentException');
		DataObjectTest_Player::get(null, "\"ID\" = 1");

	}

	public function testBrokenLateStaticBindingStyle() {
		// If you call DataObject::get() you have to pass a first argument
		$this->setExpectedException('InvalidArgumentException');
		DataObject::get();

	}

}

class DataObjectTest_Player extends Member implements TestOnly {
	private static $db = array(
		'IsRetired' => 'Boolean',
		'ShirtNumber' => 'Varchar',
	);

	private static $has_one = array(
		'FavouriteTeam' => 'DataObjectTest_Team',
	);

	private static $belongs_many_many = array(
		'Teams' => 'DataObjectTest_Team'
	);

	private static $has_many = array(
		'Fans' => 'DataObjectTest_Fan.Favourite' // Polymorphic - Player fans
	);

	private static $belongs_to = array (
		'CompanyOwned'    => 'DataObjectTest_Company.Owner'
	);

	private static $searchable_fields = array(
		'IsRetired',
		'ShirtNumber'
	);
}

class DataObjectTest_Team extends DataObject implements TestOnly {

	private static $db = array(
		'Title' => 'Varchar',
		'DatabaseField' => 'HTMLVarchar'
	);

	private static $has_one = array(
		"Captain" => 'DataObjectTest_Player',
		'HasOneRelationship' => 'DataObjectTest_Player',
	);

	private static $has_many = array(
		'SubTeams' => 'DataObjectTest_SubTeam',
		'Comments' => 'DataObjectTest_TeamComment',
		'Fans' => 'DataObjectTest_Fan.Favourite' // Polymorphic - Team fans
	);

	private static $many_many = array(
		'Players' => 'DataObjectTest_Player'
	);

	private static $many_many_extraFields = array(
		'Players' => array(
			'Position' => 'Varchar(100)'
		)
	);

	private static $belongs_many_many = array(
		'Sponsors' => 'DataObjectTest_EquipmentCompany.SponsoredTeams',
		'EquipmentSuppliers' => 'DataObjectTest_EquipmentCompany.EquipmentCustomers'
	);

	private static $summary_fields = array(
		'Title' => 'Custom Title',
		'Title.UpperCase' => 'Title',
		'Captain.ShirtNumber' => 'Captain\'s shirt number',
		'Captain.FavouriteTeam.Title' => 'Captain\'s favourite team'
	);

	private static $default_sort = '"Title"';

	public function MyTitle() {
		return 'Team ' . $this->Title;
	}

	public function getDynamicField() {
		return 'dynamicfield';
	}

}

class DataObjectTest_Fixture extends DataObject implements TestOnly {
	private static $db = array(
		// Funny field names
		'Data' => 'Varchar',
		'Duplicate' => 'Varchar',
		'DbObject' => 'Varchar',

		// Field types
		'DateField' => 'Date',
		'DatetimeField' => 'Datetime',

		'MyFieldWithDefault' => 'Varchar',
		'MyFieldWithAltDefault' => 'Varchar'
	);

	private static $defaults = array(
		'MyFieldWithDefault' => 'Default Value',
	);

	private static $summary_fields = array(
		'Data' => 'Data',
		'DateField.Nice' => 'Date'
	);

	private static $searchable_fields = array();

	public function populateDefaults() {
		parent::populateDefaults();

		$this->MyFieldWithAltDefault = 'Default Value';
	}

}

class DataObjectTest_SubTeam extends DataObjectTest_Team implements TestOnly {
	private static $db = array(
		'SubclassDatabaseField' => 'Varchar'
	);

	private static $has_one = array(
		"ParentTeam" => 'DataObjectTest_Team',
	);

	private static $many_many = array(
		'FormerPlayers' => 'DataObjectTest_Player'
	);
	
	private static $many_many_extraFields = array(
		'FormerPlayers' => array(
			'Position' => 'Varchar(100)'
		)
	);
}
class OtherSubclassWithSameField extends DataObjectTest_Team implements TestOnly {
	private static $db = array(
		'SubclassDatabaseField' => 'Varchar',
	);
}


class DataObjectTest_FieldlessTable extends DataObject implements TestOnly {
}

class DataObjectTest_FieldlessSubTable extends DataObjectTest_Team implements TestOnly {
}


class DataObjectTest_Team_Extension extends DataExtension implements TestOnly {

	private static $db = array(
		'ExtendedDatabaseField' => 'Varchar'
	);

	private static $has_one = array(
		'ExtendedHasOneRelationship' => 'DataObjectTest_Player'
	);

	public function getExtendedDynamicField() {
		return "extended dynamic field";
	}

}

class DataObjectTest_ValidatedObject extends DataObject implements TestOnly {

	private static $db = array(
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

class DataObjectTest_Company extends DataObject implements TestOnly {

	private static $db = array(
		'Name' => 'Varchar'
	);

	private static $has_one = array (
		'CEO'         => 'DataObjectTest_CEO',
		'PreviousCEO' => 'DataObjectTest_CEO',
		'Owner'       => 'DataObject' // polymorphic
	);

	private static $has_many = array (
		'CurrentStaff'     => 'DataObjectTest_Staff.CurrentCompany',
		'PreviousStaff'    => 'DataObjectTest_Staff.PreviousCompany'
	);
}

class DataObjectTest_EquipmentCompany extends DataObjectTest_Company implements TestOnly {
	private static $many_many = array(
		'SponsoredTeams' => 'DataObjectTest_Team',
		'EquipmentCustomers' => 'DataObjectTest_Team'
	);

	private static $many_many_extraFields = array(
		'SponsoredTeams' => array(
			'SponsorFee' => 'Int'
		)
	);
}

class DataObjectTest_SubEquipmentCompany extends DataObjectTest_EquipmentCompany implements TestOnly {
	private static $db = array(
		'SubclassDatabaseField' => 'Varchar'
	);
}

class DataObjectTest_Staff extends DataObject implements TestOnly {
	private static $has_one = array (
		'CurrentCompany'  => 'DataObjectTest_Company',
		'PreviousCompany' => 'DataObjectTest_Company'
	);
}

class DataObjectTest_CEO extends DataObjectTest_Staff {
	private static $belongs_to = array (
		'Company'         => 'DataObjectTest_Company.CEO',
		'PreviousCompany' => 'DataObjectTest_Company.PreviousCEO',
		'CompanyOwned'    => 'DataObjectTest_Company.Owner'
	);
}

class DataObjectTest_TeamComment extends DataObject implements TestOnly {
	private static $db = array(
		'Name' => 'Varchar',
		'Comment' => 'Text'
	);

	private static $has_one = array(
		'Team' => 'DataObjectTest_Team'
	);

	private static $default_sort = '"Name" ASC';
}

class DataObjectTest_Fan extends DataObject implements TestOnly {

	private static $db = array(
		'Name' => 'Varchar(255)'
	);

	private static $has_one = array(
		'Favourite' => 'DataObject', // Polymorphic relation
		'SecondFavourite' => 'DataObject'
	);
}

class DataObjectTest_ExtendedTeamComment extends DataObjectTest_TeamComment {
	private static $db = array(
		'Comment' => 'HTMLText'
	);
}

class DataObjectTest_Play extends DataObject implements TestOnly {}
class DataObjectTest_Ploy extends DataObject implements TestOnly {}
class DataObjectTest_Bogey extends DataObject implements TestOnly {}

DataObjectTest_Team::add_extension('DataObjectTest_Team_Extension');

