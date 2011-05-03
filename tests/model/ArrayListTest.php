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
		$this->assertEquals($list->getRange(1, 2), array(
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