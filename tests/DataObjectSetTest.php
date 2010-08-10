<?php
/**
 * Test the {@link DataObjectSet} class.
 * 
 * @package sapphire
 * @subpackage tests
 */
class DataObjectSetTest extends SapphireTest {
	
	static $fixture_file = 'sapphire/tests/DataObjectTest.yml';

	protected $extraDataObjects = array(
		'DataObjectTest_Team',
		'DataObjectTest_SubTeam',
		'DataObjectTest_Player',
		'DataObjectSetTest_TeamComment'
	);
	
	function testIterator() {
		$set = new DataObjectSet(array(
			$one = new DataObject(array('Title'=>'one')),
			$two = new DataObject(array('Title'=>'two')),
			$three = new DataObject(array('Title'=>'three')),
			$four = new DataObject(array('Title'=>'four'))
		));
		
		// test Pos() with foreach()
		$i = 0;
		foreach($set as $item) {
			$i++;
			$this->assertEquals($i, $item->Pos(), "Iterator position is set correctly on ViewableData when iterated with foreach()");
		}
		
		// test Pos() manually
		$this->assertEquals(1, $one->Pos());
		$this->assertEquals(2, $two->Pos());
		$this->assertEquals(3, $three->Pos());
		$this->assertEquals(4, $four->Pos());
		
		// test DataObjectSet->Count()
		$this->assertEquals(4, $set->Count());
		
		// test DataObjectSet->First()
		$this->assertSame($one, $set->First());
		
		// test DataObjectSet->Last()
		$this->assertSame($four, $set->Last());
		
		// test ViewableData->First()
		$this->assertTrue($one->First());
		$this->assertFalse($two->First());
		$this->assertFalse($three->First());
		$this->assertFalse($four->First());
		
		// test ViewableData->Last()
		$this->assertFalse($one->Last());
		$this->assertFalse($two->Last());
		$this->assertFalse($three->Last());
		$this->assertTrue($four->Last());
		
		// test ViewableData->Middle()
		$this->assertFalse($one->Middle());
		$this->assertTrue($two->Middle());
		$this->assertTrue($three->Middle());
		$this->assertFalse($four->Middle());
		
		// test ViewableData->Even()
		$this->assertFalse($one->Even());
		$this->assertTrue($two->Even());
		$this->assertFalse($three->Even());
		$this->assertTrue($four->Even());
		
		// test ViewableData->Odd()
		$this->assertTrue($one->Odd());
		$this->assertFalse($two->Odd());
		$this->assertTrue($three->Odd());
		$this->assertFalse($four->Odd());
	}

	public function testMultipleOf() {
		$comments = DataObject::get('PageComment', '', "\"ID\" ASC");
		$commArr = $comments->toArray();
		$multiplesOf3 = 1;
		
		foreach($comments as $comment) {
			if($comment->MultipleOf(3)) {
				$comment->IsMultipleOf3 = true;
				$multiplesOf3++;
			} else {
				$comment->IsMultipleOf3 = false;
			}
		}
		
		$this->assertEquals(3, $multiplesOf3);
		
		$this->assertFalse($commArr[0]->IsMultipleOf3);
		$this->assertFalse($commArr[1]->IsMultipleOf3);
		$this->assertTrue($commArr[2]->IsMultipleOf3);
		$this->assertFalse($commArr[3]->IsMultipleOf3);
		$this->assertFalse($commArr[4]->IsMultipleOf3);
		$this->assertTrue($commArr[5]->IsMultipleOf3);
		$this->assertFalse($commArr[6]->IsMultipleOf3);

		foreach($comments as $comment) {
			if($comment->MultipleOf(3, 1)) {
				$comment->IsMultipleOf3 = true;
			} else {
				$comment->IsMultipleOf3 = false;
			}
		}

		$this->assertFalse($commArr[0]->IsMultipleOf3);
		$this->assertFalse($commArr[1]->IsMultipleOf3);
		$this->assertTrue($commArr[2]->IsMultipleOf3);
		$this->assertFalse($commArr[3]->IsMultipleOf3);
		$this->assertFalse($commArr[4]->IsMultipleOf3);
		$this->assertTrue($commArr[5]->IsMultipleOf3);
		$this->assertFalse($commArr[6]->IsMultipleOf3);
	}

	/**
	 * Test {@link DataObjectSet->Count()}
	 */
	function testCount() {
		$comments = DataObject::get('PageComment', '', "\"ID\" ASC");
		
		/* There are a total of 8 items in the set */
		$this->assertEquals($comments->Count(), 8, 'There are a total of 8 items in the set');
	}

	/**
	 * Test {@link DataObjectSet->First()}
	 */
	function testFirst() {
		$comments = DataObject::get('PageComment', '', "\"ID\" ASC");
		
		/* The first object is Joe's comment */
		//Disabled due to Postgres not liking the ID values to be dictated
		//$this->assertEquals($comments->First()->ID, 1, 'The first object has an ID of "1"');
		$this->assertEquals($comments->First()->Name, 'Joe', 'The first object has a Name field value of "Joe"');
	}
	
	/**
	 * Test {@link DataObjectSet->Last()}
	 */
	function testLast() {
		$comments = DataObject::get('PageComment', '', "\"ID\" ASC");
		
		/* The last object is Dean's comment */
		//Disabled due to Postgres not liking the ID values to be dictated
		//$this->assertEquals($comments->Last()->ID, 8, 'The last object has an ID of "8"');
		$this->assertEquals($comments->Last()->Name, 'Dean', 'The last object has a Name field value of "Dean"');
	}
	
	/**
	 * Test {@link DataObjectSet->map()}
	 */
	function testMap() {
		$comments = DataObject::get('PageComment', '', "\"ID\" ASC");

		/* Now we get a map of all the PageComment records */
		$map = $comments->map('ID', 'Title', '(Select one)');
		
		$expectedMap = array(
			'' => '(Select one)',
			1 => 'Joe',
			2 => 'Jane',
			3 => 'Bob',
			4 => 'Bob',
			5 => 'Ernie',
			6 => 'Jimmy',
			7 => 'Dean',
			8 => 'Dean'
		);
		
		/* There are 9 items in the map. 8 are records. 1 is the empty value */
		$this->assertEquals(count($map), 9, 'There are 9 items in the map. 8 are records. 1 is the empty value');
		
		/* We have the same map as our expected map, asserted above */
		//Disabled due to Postgres not liking the ID values to be dictated 
		//$this->assertSame($expectedMap, $map, 'The map we generated is exactly the same as the asserted one');
		
		/* toDropDownMap() is an alias of map() - let's make a map from that */
		$map2 = $comments->toDropDownMap('ID', 'Title', '(Select one)');
		
		/* There are 9 items in the map. 8 are records. 1 is the empty value */
		$this->assertEquals(count($map), 9, 'There are 9 items in the map. 8 are records. 1 is the empty value.');
		
		/* We have the same map as our expected map, asserted above */
		//Disabled due to Postgres not liking the ID values to be dictated
		//We could possibly fix this problem by changing 'assertSame' to not check the keys
		//$this->assertSame($expectedMap, $map2, 'The map we generated is exactly the same as the asserted one');
	}

	function testRemoveDuplicates() {
		// Note that PageComment and DataObjectSetTest_TeamComment are both descendants of DataObject, and don't
		// share an inheritance relationship below that.
		$pageComments = DataObject::get('PageComment');
		$teamComments = DataObject::get('DataObjectSetTest_TeamComment');

		/* Test default functionality (remove by ID). We'd expect to loose all our
		 * team comments as they have the same IDs as the first three page comments */

		$allComments = new DataObjectSet();
		$allComments->merge($pageComments);
		$allComments->merge($teamComments);

		$allComments->removeDuplicates();

		$this->assertEquals($allComments->Count(), 11, 'Standard functionality is to remove duplicate base class/IDs');

		/* Now test removing duplicates based on a common field. In this case we shall
		 * use 'Name', so we can get all the unique commentators */

		$allComments = new DataObjectSet();
		$allComments->merge($pageComments);
		$allComments->merge($teamComments);

		$allComments->removeDuplicates('Name');

		$this->assertEquals($allComments->Count(), 9, 'There are 9 uniquely named commentators');

		// Ensure that duplicates are removed where the base data class is the same.
		$mixedSet = new DataObjectSet();
		$mixedSet->push(new SiteTree(array('ID' => 1)));
		$mixedSet->push(new Page(array('ID' => 1)));		// dup: same base class and ID
		$mixedSet->push(new Page(array('ID' => 1)));		// dup: more than one dup of the same object
		$mixedSet->push(new Page(array('ID' => 2)));		// not dup: same type again, but different ID
		$mixedSet->push(new PageComment(array('ID' => 1))); // not dup: different base type, same ID
		$mixedSet->push(new SiteTree(array('ID' => 1)));	// dup: another dup, not consequetive.

		$mixedSet->removeDuplicates('ID');

		$this->assertEquals($mixedSet->Count(), 3, 'There are 3 unique data objects in a very mixed set');
	}

	/**
	 * Test {@link DataObjectSet->parseQueryLimit()}
	 */
	function testParseQueryLimit() {
		// Create empty objects, because they don't need to have contents
		$sql = new SQLQuery('*', '"Member"');
		$max = $sql->unlimitedRowCount();
		$set = new DataObjectSet();
		
		// Test handling an array
		$set->parseQueryLimit($sql->limit(array('limit'=>5, 'start'=>2)));
		$expected = array(
			'pageStart' => 2,
			'pageLength' => 5,
			'totalSize' => $max,
		);
		$this->assertEquals($expected, $set->getPageLimits(), 'The page limits match expected values.');
		
		// Test handling OFFSET string
		// uppercase
		$set->parseQueryLimit($sql->limit('3 OFFSET   1'));
		$expected = array(
			'pageStart' => 1,
			'pageLength' => 3,
			'totalSize' => $max,
		);
		$this->assertEquals($expected, $set->getPageLimits(), 'The page limits match expected values.');
		// and lowercase
		$set->parseQueryLimit($sql->limit('32   offset   3'));
		$expected = array(
			'pageStart' => 3,
			'pageLength' => 32,
			'totalSize' => $max,
		);
		$this->assertEquals($expected, $set->getPageLimits(), 'The page limits match expected values.');
		
		// Finally check MySQL LIMIT syntax
		$set->parseQueryLimit($sql->limit('7, 7'));
		$expected = array(
			'pageStart' => 7,
			'pageLength' => 7,
			'totalSize' => $max,
		);
		$this->assertEquals($expected, $set->getPageLimits(), 'The page limits match expected values.');
	}

	/**
	 * Test {@link DataObjectSet->insertFirst()}
	 */
	function testInsertFirst() {
		// Get one comment
		$comment = DataObject::get_one('PageComment', '"Name" = \'Joe\'');
		// Get all other comments
		$set = DataObject::get('PageComment', '"Name" != \'Joe\'');
		
		// Duplicate so we can use it later without another lookup
		$otherSet = clone $set;
		// insert without a key
		$otherSet->insertFirst($comment);
		$this->assertEquals($comment, $otherSet->First(), 'Comment should be first');
		
		// Give us another copy
		$otherSet = clone $set;
		// insert with a numeric key
		$otherSet->insertFirst($comment, 2);
		$this->assertEquals($comment, $otherSet->First(), 'Comment should be first');
		
		// insert with a non-numeric key
		$set->insertFirst($comment, 'SomeRandomKey');
		$this->assertEquals($comment, $set->First(), 'Comment should be first');
	}

	/**
	 * Test {@link DataObjectSet->getRange()}
	 */
	function testGetRange() {
		$comments = DataObject::get('PageComment', '', "\"ID\" ASC");
		
		// Make sure we got all 8 comments
		$this->assertEquals($comments->Count(), 8, 'Eight comments in the database.');
		
		// Grab a range
		$range = $comments->getRange(1, 5);
		$this->assertEquals($range->Count(), 5, 'Five comments in the range.');
		
		// And now grab a range that shouldn't be full. Remember counting starts at 0.
		$range = $comments->getRange(7, 5);
		$this->assertEquals($range->Count(), 1, 'One comment in the range.');
		// Make sure it's the last one
		$this->assertEquals($range->First(), $comments->Last(), 'The only item in the range should be the last one.');
	}

	/**
	 * Test {@link DataObjectSet->exists()}
	 */
	function testExists() {
		// Test an empty set
		$set = new DataObjectSet();
		$this->assertFalse($set->exists(), 'Empty set doesn\'t exist.');
		// Test a non-empty set
		$set = DataObject::get('PageComment', '', "\"ID\" ASC");
		$this->assertTrue($set->exists(), 'Non-empty set does exist.');
	}

	/**
	 * Test {@link DataObjectSet->shift()}
	 */
	function testShift() {
		$set = new DataObjectSet();
		$set->push(new ArrayData(array('Name' => 'Joe')));
		$set->push(new ArrayData(array('Name' => 'Bob')));
		$set->push(new ArrayData(array('Name' => 'Ted')));
		$this->assertEquals('Joe', $set->shift()->Name);
	}

	/**
	 * Test {@link DataObjectSet->unshift()}
	 */
	function testUnshift() {
		$set = new DataObjectSet();
		$set->push(new ArrayData(array('Name' => 'Joe')));
		$set->push(new ArrayData(array('Name' => 'Bob')));
		$set->push(new ArrayData(array('Name' => 'Ted')));
		$set->unshift(new ArrayData(array('Name' => 'Steve')));
		$this->assertEquals('Steve', $set->First()->Name);
	}

	/**
	 * Test {@link DataObjectSet->pop()}
	 */
	function testPop() {
		$set = new DataObjectSet();
		$set->push(new ArrayData(array('Name' => 'Joe')));
		$set->push(new ArrayData(array('Name' => 'Bob')));
		$set->push(new ArrayData(array('Name' => 'Ted')));
		$this->assertEquals('Ted', $set->pop()->Name);
	}

}

/**
 * @package sapphire
 * @subpackage tests
 */
class DataObjectSetTest_TeamComment extends DataObject implements TestOnly {
	static $db = array(
		'Name' => 'Varchar',
		'Comment' => 'Text',
		);
	static $has_one = array(
		'Team' => 'DataObjectTest_Team',
	);
}
?>