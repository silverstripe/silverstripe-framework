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
		$this->assertTrue($comments->Count() == 4);
		
		// Test WHERE clause
		$comments = DataObject::get('PageComment', 'Name="Bob"');
		$this->assertTrue($comments->Count() == 2);
		foreach($comments as $comment) {
			$this->assertTrue($comment->Name == 'Bob');
		}
		
		// Test sorting
		$comments = DataObject::get('PageComment', '', 'Name ASC');
		$this->assertTrue($comments->Count() == 4);
		$this->assertTrue($comments->First()->Name == 'Bob');
		$comments = DataObject::get('PageComment', '', 'Name DESC');
		$this->assertTrue($comments->Count() == 4);
		$this->assertTrue($comments->First()->Name == 'Joe');
		
		// Test join
		$comments = DataObject::get('PageComment', '`SiteTree`.Title="First Page"', '', 'INNER JOIN SiteTree ON PageComment.ParentID = SiteTree.ID');
		$this->assertTrue($comments->Count() == 2);
		$this->assertTrue($comments->First()->Name == 'Bob');
		$this->assertTrue($comments->Last()->Name == 'Bob');
		
		// Test limit
		$comments = DataObject::get('PageComment', '', 'Name ASC', '', '1,2');
		$this->assertTrue($comments->Count() == 2);
		$this->assertTrue($comments->First()->Name == 'Bob');
		$this->assertTrue($comments->Last()->Name == 'Jane');
		
		// Test container class
		$comments = DataObject::get('PageComment', '', '', '', '', 'DataObjectSet');
		$this->assertTrue(get_class($comments) == 'DataObjectSet');
		$comments = DataObject::get('PageComment', '', '', '', '', 'ComponentSet');
		$this->assertTrue(get_class($comments) == 'ComponentSet');
		
		
		// Test get_by_id()
		$homepage = $this->fixture->objFromFixture('Page', 'home');
		$page = DataObject::get_by_id('Page', $homepage->ID);
		$this->assertTrue($page->Title == 'Home');
		
		// Test get_by_url()
		$page = DataObject::get_by_url('home');
		$this->assertTrue($page->ID == $homepage->ID);
		
		// Test get_one() without caching
		$comment1 = DataObject::get_one('PageComment', 'Name="Joe"', false);
		$comment1->Comment = "Something Else";
		$comment2 = DataObject::get_one('PageComment', 'Name="Joe"', false);
		$this->assertTrue($comment1->Comment != $comment2->Comment);
		
		// Test get_one() with caching
		$comment1 = DataObject::get_one('PageComment', 'Name="Jane"', true);
		$comment1->Comment = "Something Else";
		$comment2 = DataObject::get_one('PageComment', 'Name="Jane"', true);
		$this->assertTrue((string)$comment1->Comment == (string)$comment2->Comment);
		
		// Test get_one() with order by without caching
		$comment = DataObject::get_one('PageComment', '', false, 'Name ASC');
		$this->assertTrue($comment->Name == 'Bob');
		$comment = DataObject::get_one('PageComment', '', false, 'Name DESC');
		$this->assertTrue($comment->Name == 'Joe');
		
		// Test get_one() with order by with caching
		$comment = DataObject::get_one('PageComment', '', true, 'Name ASC');
		$this->assertTrue($comment->Name == 'Bob');
		$comment = DataObject::get_one('PageComment', '', true, 'Name DESC');
		$this->assertTrue($comment->Name == 'Joe');
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
	}
}

class DataObjectTest_Player extends Member implements TestOnly {
   
   static $belongs_many_many = array(
      'Teams' => 'DataObjectTest_Team'
   );
   
}

class DataObjectTest_Team extends DataObject implements TestOnly {
   
   static $db = array(
      'Title' => 'Text', 
   );
   
   static $many_many = array(
      'Players' => 'DataObjectTest_Player'
   );
   
}

?>
