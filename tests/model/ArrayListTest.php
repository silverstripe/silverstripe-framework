<?php
/**
 * @package    sapphire
 * @subpackage tests
 */
class ArrayListTest extends SapphireTest {

	public function testCount() {
		$list = new ArrayList();
		$this->assertEquals(0, $list->count());
		$list = new ArrayList(array(1, 2, 3));
		$this->assertEquals(3, $list->count());
	}

	public function testExists() {
		$list = new ArrayList();
		$this->assertFalse($list->exists());
		$list = new ArrayList(array(1, 2, 3));
		$this->assertTrue($list->exists());
	}

	public function testToNestedArray() {
		$list = new ArrayList(array(
			array('First' => 'FirstFirst', 'Second' => 'FirstSecond'),
			(object) array('First' => 'SecondFirst', 'Second' => 'SecondSecond'),
			new ArrayListTest_Object('ThirdFirst', 'ThirdSecond')
		));
		
		$this->assertEquals($list->toNestedArray(), array(
			array('First' => 'FirstFirst', 'Second' => 'FirstSecond'),
			array('First' => 'SecondFirst', 'Second' => 'SecondSecond'),
			array('First' => 'ThirdFirst', 'Second' => 'ThirdSecond')
		));
	}

	public function testGetRange() {
		$list = new ArrayList(array(
			array('Key' => 1), array('Key' => 2), array('Key' => 3)
		));
		$this->assertEquals($list->getRange(1, 2)->toArray(), array(
			array('Key' => 2), array('Key' => 3)
		));
	}

	public function testAddRemove() {
		$list = new ArrayList(array(
			array('Key' => 1), array('Key' => 2)
		));

		$list->add(array('Key' => 3));
		$this->assertEquals($list->toArray(), array(
			array('Key' => 1), array('Key' => 2), array('Key' => 3)
		));

		$list->remove(array('Key' => 2));
		$this->assertEquals(array_values($list->toArray()), array(
			array('Key' => 1), array('Key' => 3)
		));
	}

	public function testReplace() {
		$list = new ArrayList(array(
			array('Key' => 1),
			$two = (object) array('Key' => 2),
			(object) array('Key' => 3)
		));

		$this->assertEquals(array('Key' => 1), $list[0]);
		$list->replace(array('Key' => 1), array('Replaced' => 1));
		$this->assertEquals(3, count($list));
		$this->assertEquals(array('Replaced' => 1), $list[0]);

		$this->assertEquals($two, $list[1]);
		$list->replace($two, array('Replaced' => 2));
		$this->assertEquals(3, count($list));
		$this->assertEquals(array('Replaced' => 2), $list[1]);
	}

	public function testMerge() {
		$list = new ArrayList(array(
			array('Num' => 1), array('Num' => 2)
		));
		$list->merge(array(
			array('Num' => 3), array('Num' => 4)
		));

		$this->assertEquals(4, count($list));
		$this->assertEquals($list->toArray(), array(
			array('Num' => 1), array('Num' => 2), array('Num' => 3), array('Num' => 4)
		));
	}

	public function testRemoveDuplicates() {
		$list = new ArrayList(array(
			array('ID' => 1, 'Field' => 1),
			array('ID' => 2, 'Field' => 2),
			array('ID' => 3, 'Field' => 3),
			array('ID' => 4, 'Field' => 1),
			(object) array('ID' => 5, 'Field' => 2)
		));

		$this->assertEquals(5, count($list));
		$list->removeDuplicates();
		$this->assertEquals(5, count($list));

		$list->removeDuplicates('Field');
		$this->assertEquals(3, count($list));
		$this->assertEquals(array(1, 2, 3), $list->column('Field'));
		$this->assertEquals(array(1, 2, 3), $list->column('ID'));
	}

	public function testPushPop() {
		$list = new ArrayList(array('Num' => 1));
		$this->assertEquals(1, count($list));

		$list->push(array('Num' => 2));
		$this->assertEquals(2, count($list));
		$this->assertEquals(array('Num' => 2), $list->last());

		$list->push(array('Num' => 3));
		$this->assertEquals(3, count($list));
		$this->assertEquals(array('Num' => 3), $list->last());

		$this->assertEquals(array('Num' => 3), $list->pop());
		$this->assertEquals(2, count($list));
		$this->assertEquals(array('Num' => 2), $list->last());
	}

	public function testShiftUnshift() {
		$list = new ArrayList(array('Num' => 1));
		$this->assertEquals(1, count($list));

		$list->unshift(array('Num' => 2));
		$this->assertEquals(2, count($list));
		$this->assertEquals(array('Num' => 2), $list->first());

		$list->unshift(array('Num' => 3));
		$this->assertEquals(3, count($list));
		$this->assertEquals(array('Num' => 3), $list->first());

		$this->assertEquals(array('Num' => 3), $list->shift());
		$this->assertEquals(2, count($list));
		$this->assertEquals(array('Num' => 2), $list->first());
	}

	public function testFirstLast() {
		$list = new ArrayList(array(
			array('Key' => 1), array('Key' => 2), array('Key' => 3)
		));
		$this->assertEquals($list->first(), array('Key' => 1));
		$this->assertEquals($list->last(), array('Key' => 3));
	}

	public function testMap() {
		$list = new ArrayList(array(
			array('ID' => 1, 'Name' => 'Steve',),
			(object) array('ID' => 3, 'Name' => 'Bob'),
			array('ID' => 5, 'Name' => 'John')
		));
		$this->assertEquals($list->map('ID', 'Name'), array(
			1 => 'Steve',
			3 => 'Bob',
			5 => 'John'
		));
	}

	public function testFind() {
		$list = new ArrayList(array(
			array('Name' => 'Steve'),
			(object) array('Name' => 'Bob'),
			array('Name' => 'John')
		));
		$this->assertEquals($list->find('Name', 'Bob'), (object) array(
			'Name' => 'Bob'
		));
	}

	public function testColumn() {
		$list = new ArrayList(array(
			array('Name' => 'Steve'),
			(object) array('Name' => 'Bob'),
			array('Name' => 'John')
		));
		$this->assertEquals($list->column('Name'), array(
			'Steve', 'Bob', 'John'
		));
	}

	public function testSort() {
		$list = new ArrayList(array(
			array('Name' => 'Steve'),
			(object) array('Name' => 'Bob'),
			array('Name' => 'John')
		));

		$list->sort('Name');
		$this->assertEquals($list->toArray(), array(
			(object) array('Name' => 'Bob'),
			array('Name' => 'John'),
			array('Name' => 'Steve')
		));

		$list->sort('Name', 'DESC');
		$this->assertEquals($list->toArray(), array(
			array('Name' => 'Steve'),
			array('Name' => 'John'),
			(object) array('Name' => 'Bob')
		));
	}

}

/**
 * @ignore
 */
class ArrayListTest_Object {

	public $First;
	public $Second;

	public function __construct($first, $second) {
		$this->First  = $first;
		$this->Second = $second;
	}

	public function toMap() {
		return array('First' => $this->First, 'Second' => $this->Second);
	}

}