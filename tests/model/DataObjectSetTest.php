<?php
/**
 * Test the {@link DataObjectSet} class.
 * 
 * @package sapphire
 * @subpackage tests
 */
class DataObjectSetTest extends SapphireTest {
	
	static $fixture_file = 'DataObjectSetTest.yml';

	protected $extraDataObjects = array(
		'DataObjectTest_Team',
		'DataObjectTest_SubTeam',
		'DataObjectTest_Player',
		'DataObjectSetTest_TeamComment',
		'DataObjectSetTest_Base',
		'DataObjectSetTest_ChildClass',
	);
	
	function testArrayAccessExists() {
		$set = new DataObjectSet(array(
			$one = new DataObject(array('Title' => 'one')),
			$two = new DataObject(array('Title' => 'two')),
			$three = new DataObject(array('Title' => 'three'))
		));
		$this->assertEquals(count($set), 3);
		$this->assertTrue(isset($set[0]), 'First item in the set is set');
		$this->assertEquals($one, $set[0], 'First item in the set is accessible by array notation');
	}
	
	function testArrayAccessUnset() {
		$set = new DataObjectSet(array(
			$one = new DataObject(array('Title' => 'one')),
			$two = new DataObject(array('Title' => 'two')),
			$three = new DataObject(array('Title' => 'three'))
		));
		unset($set[0]);
		$this->assertEquals(count($set), 2);
	}
	
	function testArrayAccessSet() {
		$set = new DataObjectSet();
		$this->assertEquals(0, count($set));
		$set['testing!'] = $test = new DataObject(array('Title' => 'I\'m testing!'));
		$this->assertEquals($test, $set['testing!'], 'Set item is accessible by the key we set it as');
	}
	
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
		$comments = DataObject::get('DataObjectSetTest_TeamComment', '', "\"ID\" ASC");
		$commArr = $comments->toArray();
		$multiplesOf3 = 0;
		
		foreach($comments as $comment) {
			if($comment->MultipleOf(3)) {
				$comment->IsMultipleOf3 = true;
				$multiplesOf3++;
			} else {
				$comment->IsMultipleOf3 = false;
			}
		}
		
		$this->assertEquals(1, $multiplesOf3);
		
		$this->assertFalse($commArr[0]->IsMultipleOf3);
		$this->assertFalse($commArr[1]->IsMultipleOf3);
		$this->assertTrue($commArr[2]->IsMultipleOf3);

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
	}

	/**
	 * Test {@link DataObjectSet->Count()}
	 */
	function testCount() {
		$comments = DataObject::get('DataObjectSetTest_TeamComment', '', "\"ID\" ASC");
		
		/* There are a total of 8 items in the set */
		$this->assertEquals($comments->Count(), 3, 'There are a total of 3 items in the set');
	}

	/**
	 * Test {@link DataObjectSet->First()}
	 */
	function testFirst() {
		$comments = DataObject::get('DataObjectSetTest_TeamComment', '', "\"ID\" ASC");
		
		/* The first object is Joe's comment */
		$this->assertEquals($comments->First()->Name, 'Joe', 'The first object has a Name field value of "Joe"');
	}
	
	/**
	 * Test {@link DataObjectSet->Last()}
	 */
	function testLast() {
		$comments = DataObject::get('DataObjectSetTest_TeamComment', '', "\"ID\" ASC");
		
		/* The last object is Dean's comment */
		$this->assertEquals($comments->Last()->Name, 'Phil', 'The last object has a Name field value of "Phil"');
	}
	
	/**
	 * Test {@link DataObjectSet->map()}
	 */
	function testMap() {
		$comments = DataObject::get('DataObjectSetTest_TeamComment', '', "\"ID\" ASC");

		/* Now we get a map of all the PageComment records */
		$map = $comments->map('ID', 'Title', '(Select one)');
		
		$expectedMap = array(
			'' => '(Select one)',
			1 => 'Joe',
			2 => 'Bob',
			3 => 'Phil'
		);
		
		/* There are 9 items in the map. 3 are records. 1 is the empty value */
		$this->assertEquals(count($map), 4, 'There are 4 items in the map. 3 are records. 1 is the empty value');
		
		/* We have the same map as our expected map, asserted above */
		
		/* toDropDownMap() is an alias of map() - let's make a map from that */
		$map2 = $comments->toDropDownMap('ID', 'Title', '(Select one)');
		
		/* There are 4 items in the map. 3 are records. 1 is the empty value */
		$this->assertEquals(count($map), 4, 'There are 4 items in the map. 3 are records. 1 is the empty value.');
	}

	function testRemoveDuplicates() {
		// Note that PageComment and DataObjectSetTest_TeamComment are both descendants of DataObject, and don't
		// share an inheritance relationship below that.
		$pageComments = DataObject::get('DataObjectSetTest_TeamComment');
		$teamComments = DataObject::get('DataObjectSetTest_TeamComment');

		/* Test default functionality (remove by ID). We'd expect to loose all our
		 * team comments as they have the same IDs as the first three page comments */

		$allComments = new DataObjectSet();
		$allComments->merge($pageComments);
		$allComments->merge($teamComments);
		
		$this->assertEquals($allComments->Count(), 6);
		
		$allComments->removeDuplicates();

		$this->assertEquals($allComments->Count(), 3, 'Standard functionality is to remove duplicate base class/IDs');

		/* Now test removing duplicates based on a common field. In this case we shall
		 * use 'Name', so we can get all the unique commentators */
	
	
		$comment = new DataObjectSetTest_TeamComment();
		$comment->Name = "Bob";
		
		$allComments->push($comment);
		
		$this->assertEquals($allComments->Count(), 4);

		$allComments->removeDuplicates('Name');
		
		$this->assertEquals($allComments->Count(), 3, 'There are 3 uniquely named commentators');

		// Ensure that duplicates are removed where the base data class is the same.
		$mixedSet = new DataObjectSet();
		$mixedSet->push(new DataObjectSetTest_Base(array('ID' => 1)));
		$mixedSet->push(new DataObjectSetTest_ChildClass(array('ID' => 1)));		// dup: same base class and ID
		$mixedSet->push(new DataObjectSetTest_ChildClass(array('ID' => 1)));		// dup: more than one dup of the same object
		$mixedSet->push(new DataObjectSetTest_ChildClass(array('ID' => 2)));		// not dup: same type again, but different
		$mixedSet->push(new DataObjectSetTest_Base(array('ID' => 1)));	// dup: another dup, not consequetive.

		$mixedSet->removeDuplicates('ID');
		
		$this->assertEquals($mixedSet->Count(), 2, 'There are 3 unique data objects in a very mixed set');
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
		$comment = DataObject::get_one('DataObjectSetTest_TeamComment', "\"Name\" = 'Joe'");
		
		// Get all other comments
		$set = DataObject::get('DataObjectSetTest_TeamComment', '"Name" != \'Joe\'');
		
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
		$comments = DataObject::get('DataObjectSetTest_TeamComment', '', "\"ID\" ASC");
		
		// Make sure we got all 8 comments
		$this->assertEquals($comments->Count(), 3, 'Three comments in the database.');
		
		// Grab a range
		$range = $comments->getRange(1, 2);
		$this->assertEquals($range->Count(), 2, 'Two comment in the range.');
		
		// And now grab a range that shouldn't be full. Remember counting starts at 0.
		$range = $comments->getRange(2, 1);
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
		$set = DataObject::get('DataObjectSetTest_TeamComment', '', "\"ID\" ASC");
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
	
	/**
	 * Test {@link DataObjectSet->sort()}
	 */
	function testSort() {
		$set = new DataObjectSet(array(
			array('Name'=>'Object1', 'F1'=>1, 'F2'=>2, 'F3'=>3),
			array('Name'=>'Object2', 'F1'=>2, 'F2'=>1, 'F3'=>4),
			array('Name'=>'Object3', 'F1'=>5, 'F2'=>2, 'F3'=>2),
		));
		// test a single sort ASC
		$set->sort('F3', 'ASC');
		$this->assertEquals($set->First()->Name, 'Object3', 'Object3 should be first in the set');
		// test a single sort DESC
		$set->sort('F3', 'DESC');
		$this->assertEquals($set->First()->Name, 'Object2', 'Object2 should be first in the set');
		// test a multi sort
		$set->sort(array('F2'=>'ASC', 'F1'=>'ASC'));
		$this->assertEquals($set->Last()->Name, 'Object3', 'Object3 should be last in the set');
		// test a multi sort
		$set->sort(array('F2'=>'ASC', 'F1'=>'DESC'));
		$this->assertEquals($set->Last()->Name, 'Object1', 'Object1 should be last in the set');
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

class DataObjectSetTest_Base extends DataObject implements TestOnly {
	static $db = array(
		'Name' => 'Varchar'
	);
}

class DataObjectSetTest_ChildClass extends DataObjectSetTest_Base implements TestOnly {
}