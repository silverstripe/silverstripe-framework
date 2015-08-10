<?php
/**
 * @package framework
 * @subpackage tests
 */
class ArrayListTest extends SapphireTest {

	public function testPushOperator() {
		$list = new ArrayList([
			['Num' => 1]
		]);

		$list[] = ['Num' => 2];
		$this->assertEquals(2, count($list));
		$this->assertEquals(['Num' => 2], $list->last());

		$list[] = ['Num' => 3];
		$this->assertEquals(3, count($list));
		$this->assertEquals(['Num' => 3], $list->last());
	}

	public function testArrayAccessExists() {
		$list = new ArrayList([
			$one = new DataObject(['Title' => 'one']),
			$two = new DataObject(['Title' => 'two']),
			$three = new DataObject(['Title' => 'three'])
		]);
		$this->assertEquals(count($list), 3);
		$this->assertTrue(isset($list[0]), 'First item in the set is set');
		$this->assertEquals($one, $list[0], 'First item in the set is accessible by array notation');
	}

	public function testArrayAccessUnset() {
		$list = new ArrayList([
			$one = new DataObject(['Title' => 'one']),
			$two = new DataObject(['Title' => 'two']),
			$three = new DataObject(['Title' => 'three'])
		]);
		unset($list[0]);
		$this->assertEquals(count($list), 2);
	}

	public function testArrayAccessSet() {
		$list = new ArrayList();
		$this->assertEquals(0, count($list));
		$list['testing!'] = $test = new DataObject(['Title' => 'I\'m testing!']);
		$this->assertEquals($test, $list['testing!'], 'Set item is accessible by the key we set it as');
	}

	public function testCount() {
		$list = new ArrayList();
		$this->assertEquals(0, $list->count());
		$list = new ArrayList([1, 2, 3]);
		$this->assertEquals(3, $list->count());
	}

	public function testExists() {
		$list = new ArrayList();
		$this->assertFalse($list->exists());
		$list = new ArrayList([1, 2, 3]);
		$this->assertTrue($list->exists());
	}

	public function testToNestedArray() {
		$list = new ArrayList([
			['First' => 'FirstFirst', 'Second' => 'FirstSecond'],
			(object) ['First' => 'SecondFirst', 'Second' => 'SecondSecond'],
			new ArrayListTest_Object('ThirdFirst', 'ThirdSecond')
		]);

		$this->assertEquals($list->toNestedArray(), [
			['First' => 'FirstFirst', 'Second' => 'FirstSecond'],
			['First' => 'SecondFirst', 'Second' => 'SecondSecond'],
			['First' => 'ThirdFirst', 'Second' => 'ThirdSecond']
		]);
	}

	public function testEach() {
		$list = new ArrayList([1, 2, 3]);

		$count = 0;
		$test = $this;

		$list->each(function($item) use (&$count, $test) {
			$count++;

			$test->assertTrue(is_int($item));
		});

		$this->assertEquals($list->Count(), $count);
	}

	public function testLimit() {
		$list = new ArrayList([
			['Key' => 1], ['Key' => 2], ['Key' => 3]
		]);
		$this->assertEquals($list->limit(2,1)->toArray(), [
			['Key' => 2], ['Key' => 3]
		]);
	}

	public function testAddRemove() {
		$list = new ArrayList([
			['Key' => 1], ['Key' => 2]
		]);

		$list->add(['Key' => 3]);
		$this->assertEquals($list->toArray(), [
			['Key' => 1], ['Key' => 2], ['Key' => 3]
		]);

		$list->remove(['Key' => 2]);
		$this->assertEquals(array_values($list->toArray()), [
			['Key' => 1], ['Key' => 3]
		]);
	}

	public function testReplace() {
		$list = new ArrayList([
			['Key' => 1],
			$two = (object) ['Key' => 2],
			(object) ['Key' => 3]
		]);

		$this->assertEquals(['Key' => 1], $list[0]);
		$list->replace(['Key' => 1], ['Replaced' => 1]);
		$this->assertEquals(3, count($list));
		$this->assertEquals(['Replaced' => 1], $list[0]);

		$this->assertEquals($two, $list[1]);
		$list->replace($two, ['Replaced' => 2]);
		$this->assertEquals(3, count($list));
		$this->assertEquals(['Replaced' => 2], $list[1]);
	}

	public function testMerge() {
		$list = new ArrayList([
			['Num' => 1], ['Num' => 2]
		]);
		$list->merge([
			['Num' => 3], ['Num' => 4]
		]);

		$this->assertEquals(4, count($list));
		$this->assertEquals($list->toArray(), [
			['Num' => 1], ['Num' => 2], ['Num' => 3], ['Num' => 4]
		]);
	}

	public function testRemoveDuplicates() {
		$list = new ArrayList([
			['ID' => 1, 'Field' => 1],
			['ID' => 2, 'Field' => 2],
			['ID' => 3, 'Field' => 3],
			['ID' => 4, 'Field' => 1],
			(object) ['ID' => 5, 'Field' => 2]
		]);

		$this->assertEquals(5, count($list));
		$list->removeDuplicates();
		$this->assertEquals(5, count($list));

		$list->removeDuplicates('Field');
		$this->assertEquals(3, count($list));
		$this->assertEquals([1, 2, 3], $list->column('Field'));
		$this->assertEquals([1, 2, 3], $list->column('ID'));
	}

	public function testPushPop() {
		$list = new ArrayList(['Num' => 1]);
		$this->assertEquals(1, count($list));

		$list->push(['Num' => 2]);
		$this->assertEquals(2, count($list));
		$this->assertEquals(['Num' => 2], $list->last());

		$list->push(['Num' => 3]);
		$this->assertEquals(3, count($list));
		$this->assertEquals(['Num' => 3], $list->last());

		$this->assertEquals(['Num' => 3], $list->pop());
		$this->assertEquals(2, count($list));
		$this->assertEquals(['Num' => 2], $list->last());
	}

	public function testShiftUnshift() {
		$list = new ArrayList(['Num' => 1]);
		$this->assertEquals(1, count($list));

		$list->unshift(['Num' => 2]);
		$this->assertEquals(2, count($list));
		$this->assertEquals(['Num' => 2], $list->first());

		$list->unshift(['Num' => 3]);
		$this->assertEquals(3, count($list));
		$this->assertEquals(['Num' => 3], $list->first());

		$this->assertEquals(['Num' => 3], $list->shift());
		$this->assertEquals(2, count($list));
		$this->assertEquals(['Num' => 2], $list->first());
	}

	public function testFirstLast() {
		$list = new ArrayList([
			['Key' => 1], ['Key' => 2], ['Key' => 3]
		]);
		$this->assertEquals($list->first(), ['Key' => 1]);
		$this->assertEquals($list->last(), ['Key' => 3]);
	}

	public function testMap() {
		$list = new ArrayList([
			['ID' => 1, 'Name' => 'Steve',],
			(object) ['ID' => 3, 'Name' => 'Bob'],
			['ID' => 5, 'Name' => 'John']
		]);
		$this->assertEquals($list->map('ID', 'Name'), [
			1 => 'Steve',
			3 => 'Bob',
			5 => 'John'
		]);
	}

	public function testFind() {
		$list = new ArrayList([
			['Name' => 'Steve'],
			(object) ['Name' => 'Bob'],
			['Name' => 'John']
		]);
		$this->assertEquals($list->find('Name', 'Bob'), (object) [
			'Name' => 'Bob'
		]);
	}

	public function testColumn() {
		$list = new ArrayList([
			['Name' => 'Steve'],
			(object) ['Name' => 'Bob'],
			['Name' => 'John']
		]);
		$this->assertEquals($list->column('Name'), [
			'Steve', 'Bob', 'John'
		]);
	}

	public function testSortSimpleDefaultIsSortedASC() {
		$list = new ArrayList([
			['Name' => 'Steve'],
			(object) ['Name' => 'Bob'],
			['Name' => 'John']
		]);

		// Unquoted name
		$list1 = $list->sort('Name');
		$this->assertEquals($list1->toArray(), [
			(object) ['Name' => 'Bob'],
			['Name' => 'John'],
			['Name' => 'Steve']
		]);

		// Quoted name name
		$list2 = $list->sort('"Name"');
		$this->assertEquals($list2->toArray(), [
			(object) ['Name' => 'Bob'],
			['Name' => 'John'],
			['Name' => 'Steve']
		]);

		// Array (non-associative)
		$list3 = $list->sort(['"Name"']);
		$this->assertEquals($list3->toArray(), [
			(object) ['Name' => 'Bob'],
			['Name' => 'John'],
			['Name' => 'Steve']
		]);

		// Check original list isn't altered
		$this->assertEquals($list->toArray(), [
			['Name' => 'Steve'],
			(object) ['Name' => 'Bob'],
			['Name' => 'John']
		]);
	}

	public function testSortSimpleASCOrder() {
		$list = new ArrayList([
			['Name' => 'Steve'],
			(object) ['Name' => 'Bob'],
			['Name' => 'John']
		]);

		// Sort two arguments
		$list1 = $list->sort('Name','ASC');
		$this->assertEquals($list1->toArray(), [
			(object) ['Name' => 'Bob'],
			['Name' => 'John'],
			['Name' => 'Steve']
		]);

		// Sort single string
		$list2 = $list->sort('Name asc');
		$this->assertEquals($list2->toArray(), [
			(object) ['Name' => 'Bob'],
			['Name' => 'John'],
			['Name' => 'Steve']
		]);

		// Sort quoted string
		$list3 = $list->sort('"Name" ASCENDING');
		$this->assertEquals($list3->toArray(), [
			(object) ['Name' => 'Bob'],
			['Name' => 'John'],
			['Name' => 'Steve']
		]);

		// Sort array specifier
		$list4 = $list->sort(['Name' => 'ascending']);
		$this->assertEquals($list4->toArray(), [
			(object) ['Name' => 'Bob'],
			['Name' => 'John'],
			['Name' => 'Steve']
		]);

		// Check original list isn't altered
		$this->assertEquals($list->toArray(), [
			['Name' => 'Steve'],
			(object) ['Name' => 'Bob'],
			['Name' => 'John']
		]);
	}

	public function testSortSimpleDESCOrder() {
		$list = new ArrayList([
			['Name' => 'Steve'],
			(object) ['Name' => 'Bob'],
			['Name' => 'John']
		]);

		// Sort two arguments
		$list1 = $list->sort('Name', 'DESC');
		$this->assertEquals($list1->toArray(), [
			['Name' => 'Steve'],
			['Name' => 'John'],
			(object) ['Name' => 'Bob']
		]);

		// Sort single string
		$list2 = $list->sort('Name desc');
		$this->assertEquals($list2->toArray(), [
			['Name' => 'Steve'],
			['Name' => 'John'],
			(object) ['Name' => 'Bob']
		]);

		// Sort quoted string
		$list3 = $list->sort('"Name" DESCENDING');
		$this->assertEquals($list3->toArray(), [
			['Name' => 'Steve'],
			['Name' => 'John'],
			(object) ['Name' => 'Bob']
		]);

		// Sort array specifier
		$list4 = $list->sort(['Name' => 'descending']);
		$this->assertEquals($list4->toArray(), [
			['Name' => 'Steve'],
			['Name' => 'John'],
			(object) ['Name' => 'Bob']
		]);

		// Check original list isn't altered
		$this->assertEquals($list->toArray(), [
			['Name' => 'Steve'],
			(object) ['Name' => 'Bob'],
			['Name' => 'John']
		]);
	}

	public function testReverse() {
		$list = new ArrayList([
			['Name' => 'John'],
			['Name' => 'Bob'],
			['Name' => 'Steve']
		]);

		$list = $list->sort('Name', 'ASC');
		$list = $list->reverse();

		$this->assertEquals($list->toArray(), [
			['Name' => 'Steve'],
			['Name' => 'John'],
			['Name' => 'Bob']
		]);
	}

	public function testSimpleMultiSort() {
		$list = new ArrayList([
			(object) ['Name'=>'Object1', 'F1'=>1, 'F2'=>2, 'F3'=>3],
			(object) ['Name'=>'Object2', 'F1'=>2, 'F2'=>1, 'F3'=>4],
			(object) ['Name'=>'Object3', 'F1'=>5, 'F2'=>2, 'F3'=>2],
		]);

		$list = $list->sort('F3', 'ASC');
		$this->assertEquals($list->first()->Name, 'Object3', 'Object3 should be first in the list');
		$this->assertEquals($list->last()->Name, 'Object2', 'Object2 should be last in the list');

		$list = $list->sort('F3', 'DESC');
		$this->assertEquals($list->first()->Name, 'Object2', 'Object2 should be first in the list');
		$this->assertEquals($list->last()->Name, 'Object3', 'Object3 should be last in the list');
	}

	public function testMultiSort() {
		$list = new ArrayList([
			(object) ['ID'=>3, 'Name'=>'Bert', 'Importance'=>1],
			(object) ['ID'=>1, 'Name'=>'Aron', 'Importance'=>2],
			(object) ['ID'=>2, 'Name'=>'Aron', 'Importance'=>1],
		]);

		$list = $list->sort(['Name'=>'ASC', 'Importance'=>'ASC']);
		$this->assertEquals($list->first()->ID, 2, 'Aron.2 should be first in the list');
		$this->assertEquals($list->last()->ID, 3, 'Bert.3 should be last in the list');

		$list = $list->sort(['Name'=>'ASC', 'Importance'=>'DESC']);
		$this->assertEquals($list->first()->ID, 1, 'Aron.2 should be first in the list');
		$this->assertEquals($list->last()->ID, 3, 'Bert.3 should be last in the list');
	}

	/**
	 * $list->filter('Name', 'bob'); // only bob in the list
	 */
	public function testSimpleFilter() {
		$list = new ArrayList([
			['Name' => 'Steve'],
			(object) ['Name' => 'Bob'],
			['Name' => 'John']
		]);
		$list = $list->filter('Name','Bob');
		$this->assertEquals([(object)['Name'=>'Bob']], $list->toArray(), 'List should only contain Bob');
	}

	/**
	 * $list->filter('Name', array('Steve', 'John'); // Steve and John in list
	 */
	public function testSimpleFilterWithMultiple() {
		$list = new ArrayList([
			['Name' => 'Steve'],
			(object) ['Name' => 'Bob'],
			['Name' => 'John']
		]);

		$expected = [
			['Name' => 'Steve'],
			['Name' => 'John']
		];
		$list = $list->filter('Name',['Steve','John']);
		$this->assertEquals($expected, $list->toArray(), 'List should only contain Steve and John');
	}

	/**
	 * $list->filter('Name', array('Steve', 'John'); // negative version
	 */
	public function testSimpleFilterWithMultipleNoMatch() {
		$list = new ArrayList([
			['Name' => 'Steve', 'ID' => 1],
			(object) ['Name' => 'Steve', 'ID' => 2],
			['Name' => 'John', 'ID' => 2]
		]);
		$list = $list->filter(['Name'=>'Clair']);
		$this->assertEquals([], $list->toArray(), 'List should be empty');
	}

	/**
	 * $list->filter(array('Name'=>'bob, 'Age'=>21)); // bob with the Age 21 in list
	 */
	public function testMultipleFilter() {
		$list = new ArrayList([
			['Name' => 'Steve', 'ID' => 1],
			(object) ['Name' => 'Steve', 'ID' => 2],
			['Name' => 'John', 'ID' => 2]
		]);
		$list = $list->filter(['Name'=>'Steve', 'ID'=>2]);
		$this->assertEquals([(object)['Name'=>'Steve', 'ID'=>2]], $list->toArray(),
			'List should only contain object Steve');
	}

	/**
	 * $list->filter(array('Name'=>'bob, 'Age'=>21)); // negative version
	 */
	public function testMultipleFilterNoMatch() {
		$list = new ArrayList([
			['Name' => 'Steve', 'ID' => 1],
			(object) ['Name' => 'Steve', 'ID' => 2],
			['Name' => 'John', 'ID' => 2]
		]);
		$list = $list->filter(['Name'=>'Steve', 'ID'=>4]);
		$this->assertEquals([], $list->toArray(), 'List should be empty');
	}

	/**
	 * $list->filter(array('Name'=>'Steve', 'Age'=>array(21, 43))); // Steve with the Age 21 or 43
	 */
	public function testMultipleWithArrayFilter() {
		$list = new ArrayList([
			['Name' => 'Steve', 'ID' => 1, 'Age'=>21],
			['Name' => 'Steve', 'ID' => 2, 'Age'=>18],
			['Name' => 'Clair', 'ID' => 2, 'Age'=>21],
			['Name' => 'Steve', 'ID' => 3, 'Age'=>43]
		]);

		$list = $list->filter(['Name'=>'Steve','Age'=>[21, 43]]);

		$expected = [
			['Name' => 'Steve', 'ID' => 1, 'Age'=>21],
			['Name' => 'Steve', 'ID' => 3, 'Age'=>43]
		];
		$this->assertEquals(2, $list->count());
		$this->assertEquals($expected, $list->toArray(), 'List should only contain Steve and Steve');
	}

	/**
	 * $list->filter(array('Name'=>array('aziz','bob'), 'Age'=>array(21, 43)));
	 */
	public function testMultipleWithArrayFilterAdvanced() {
		$list = new ArrayList([
			['Name' => 'Steve', 'ID' => 1, 'Age'=>21],
			['Name' => 'Steve', 'ID' => 2, 'Age'=>18],
			['Name' => 'Clair', 'ID' => 2, 'Age'=>21],
			['Name' => 'Clair', 'ID' => 2, 'Age'=>52],
			['Name' => 'Steve', 'ID' => 3, 'Age'=>43]
		]);

		$list = $list->filter(['Name'=>['Steve','Clair'],'Age'=>[21, 43]]);

		$expected = [
			['Name' => 'Steve', 'ID' => 1, 'Age'=>21],
			['Name' => 'Clair', 'ID' => 2, 'Age'=>21],
			['Name' => 'Steve', 'ID' => 3, 'Age'=>43]
		];

		$this->assertEquals(3, $list->count());
		$this->assertEquals($expected, $list->toArray(), 'List should only contain Steve and Steve and Clair');
	}

	/**
	 * $list = $list->filterByCallback(function($item, $list) { return $item->Age == 21; })
	 */
	public function testFilterByCallback() {
		$list = new ArrayList([
			['Name' => 'Steve', 'ID' => 1, 'Age' => 21],
			['Name' => 'Bob', 'ID' => 2, 'Age' => 18],
			['Name' => 'Clair', 'ID' => 2, 'Age' => 21],
			['Name' => 'Oscar', 'ID' => 2, 'Age' => 52],
			['Name' => 'Mike', 'ID' => 3, 'Age' => 43]
		]);

		$list = $list->filterByCallback(function ($item, $list) {
			return $item->Age == 21;
		});

		$expected = [
			new ArrayData(['Name' => 'Steve', 'ID' => 1, 'Age' => 21]),
			new ArrayData(['Name' => 'Clair', 'ID' => 2, 'Age' => 21]),
		];

		$this->assertEquals(2, $list->count());
		$this->assertEquals($expected, $list->toArray(), 'List should only contain Steve and Clair');
		$this->assertTrue($list instanceof SS_Filterable, 'The List should be of type SS_Filterable');
	}

	/**
	 * $list->exclude('Name', 'bob'); // exclude bob from list
	 */
	public function testSimpleExclude() {
		$list = new ArrayList([
			['Name' => 'Steve'],
			['Name' => 'Bob'],
			['Name' => 'John']
		]);

		$list = $list->exclude('Name', 'Bob');
		$expected = [
			['Name' => 'Steve'],
			['Name' => 'John']
		];
		$this->assertEquals(2, $list->count());
		$this->assertEquals($expected, $list->toArray(), 'List should not contain Bob');
	}

	/**
	 * $list->exclude('Name', 'bob'); // No exclusion version
	 */
	public function testSimpleExcludeNoMatch() {
		$list = new ArrayList([
			['Name' => 'Steve'],
			['Name' => 'Bob'],
			['Name' => 'John']
		]);

		$list = $list->exclude('Name', 'Clair');
		$expected = [
			['Name' => 'Steve'],
			['Name' => 'Bob'],
			['Name' => 'John']
		];
		$this->assertEquals($expected, $list->toArray(), 'List should be unchanged');
	}

	/**
	 * $list->exclude('Name', array('Steve','John'));
	 */
	public function testSimpleExcludeWithArray() {
		$list = new ArrayList([
			['Name' => 'Steve'],
			['Name' => 'Bob'],
			['Name' => 'John']
		]);
		$list = $list->exclude('Name', ['Steve','John']);
		$expected = [['Name' => 'Bob']];
		$this->assertEquals(1, $list->count());
		$this->assertEquals($expected, $list->toArray(), 'List should only contain Bob');
	}

	/**
	 * $list->exclude(array('Name'=>'bob, 'Age'=>21)); // exclude all Bob that has Age 21
	 */
	public function testExcludeWithTwoArrays() {
		$list = new ArrayList([
			['Name' => 'Bob' , 'Age' => 21],
			['Name' => 'Bob' , 'Age' => 32],
			['Name' => 'John', 'Age' => 21]
		]);

		$list = $list->exclude(['Name' => 'Bob', 'Age' => 21]);

		$expected = [
			['Name' => 'Bob', 'Age' => 32],
			['Name' => 'John', 'Age' => 21]
		];

		$this->assertEquals(2, $list->count());
		$this->assertEquals($expected, $list->toArray(), 'List should only contain John and Bob');
	}

	/**
	 * $list->exclude(array('Name'=>array('bob','phil'), 'Age'=>array(10, 16)));
	 */
	public function testMultipleExclude() {
		$list = new ArrayList([
			['Name' => 'bob', 'Age' => 10],
			['Name' => 'phil', 'Age' => 11],
			['Name' => 'bob', 'Age' => 12],
			['Name' => 'phil', 'Age' => 12],
			['Name' => 'bob', 'Age' => 14],
			['Name' => 'phil', 'Age' => 14],
			['Name' => 'bob', 'Age' => 16],
			['Name' => 'phil', 'Age' => 16]
		]);

		$list = $list->exclude(['Name'=>['bob','phil'],'Age'=>[10, 16]]);
		$expected = [
			['Name' => 'phil', 'Age' => 11],
			['Name' => 'bob', 'Age' => 12],
			['Name' => 'phil', 'Age' => 12],
			['Name' => 'bob', 'Age' => 14],
			['Name' => 'phil', 'Age' => 14],
		];
		$this->assertEquals($expected, $list->toArray());
	}

	/**
	 * $list->exclude(array('Name'=>array('bob','phil'), 'Age'=>array(10, 16), 'Bananas'=>true));
	 */
	public function testMultipleExcludeNoMatch() {
		$list = new ArrayList([
			['Name' => 'bob', 'Age' => 10],
			['Name' => 'phil', 'Age' => 11],
			['Name' => 'bob', 'Age' => 12],
			['Name' => 'phil', 'Age' => 12],
			['Name' => 'bob', 'Age' => 14],
			['Name' => 'phil', 'Age' => 14],
			['Name' => 'bob', 'Age' => 16],
			['Name' => 'phil', 'Age' => 16]
		]);

		$list = $list->exclude(['Name'=>['bob','phil'],'Age'=>[10, 16],'Bananas'=>true]);
		$expected = [
			['Name' => 'bob', 'Age' => 10],
			['Name' => 'phil', 'Age' => 11],
			['Name' => 'bob', 'Age' => 12],
			['Name' => 'phil', 'Age' => 12],
			['Name' => 'bob', 'Age' => 14],
			['Name' => 'phil', 'Age' => 14],
			['Name' => 'bob', 'Age' => 16],
			['Name' => 'phil', 'Age' => 16]
		];
		$this->assertEquals($expected, $list->toArray());
	}

	/**
	 * $list->exclude(array('Name'=>array('bob','phil'), 'Age'=>array(10, 16), 'HasBananas'=>true));
	 */
	public function testMultipleExcludeThreeArguments() {
		$list = new ArrayList([
			['Name' => 'bob', 'Age' => 10, 'HasBananas'=>false],
			['Name' => 'phil','Age' => 11, 'HasBananas'=>true],
			['Name' => 'bob', 'Age' => 12, 'HasBananas'=>true],
			['Name' => 'phil','Age' => 12, 'HasBananas'=>true],
			['Name' => 'bob', 'Age' => 14, 'HasBananas'=>false],
			['Name' => 'ann', 'Age' => 14, 'HasBananas'=>true],
			['Name' => 'phil','Age' => 14, 'HasBananas'=>false],
			['Name' => 'bob', 'Age' => 16, 'HasBananas'=>false],
			['Name' => 'phil','Age' => 16, 'HasBananas'=>true],
			['Name' => 'clair','Age' => 16, 'HasBananas'=>true]
		]);

		$list = $list->exclude(['Name'=>['bob','phil'],'Age'=>[10, 16],'HasBananas'=>true]);
		$expected = [
			['Name' => 'bob', 'Age' => 10, 'HasBananas'=>false],
			['Name' => 'phil','Age' => 11, 'HasBananas'=>true],
			['Name' => 'bob', 'Age' => 12, 'HasBananas'=>true],
			['Name' => 'phil','Age' => 12, 'HasBananas'=>true],
			['Name' => 'bob', 'Age' => 14, 'HasBananas'=>false],
			['Name' => 'ann', 'Age' => 14, 'HasBananas'=>true],
			['Name' => 'phil','Age' => 14, 'HasBananas'=>false],
			['Name' => 'bob', 'Age' => 16, 'HasBananas'=>false],
			['Name' => 'clair','Age' => 16, 'HasBananas'=>true]
		];
		$this->assertEquals($expected, $list->toArray());
	}

	public function testCanFilterBy() {
		$list = new ArrayList([
			['Name' => 'Steve'],
			['Name' => 'Bob'],
			['Name' => 'John']
		]);

		$this->assertTrue($list->canFilterBy('Name'));
		$this->assertFalse($list->canFilterBy('Age'));
	}

	public function testCanFilterByEmpty() {
		$list = new ArrayList();

		$this->assertFalse($list->canFilterBy('Name'));
		$this->assertFalse($list->canFilterBy('Age'));
	}

	public function testByID() {
		$list = new ArrayList([
			['ID' => 1, 'Name' => 'Steve'],
			['ID' => 2, 'Name' => 'Bob'],
			['ID' => 3, 'Name' => 'John']
		]);

		$element = $list->byID(1);
		$this->assertEquals($element['Name'], 'Steve');

		$element = $list->byID(2);
		$this->assertEquals($element['Name'], 'Bob');

		$element = $list->byID(4);
		$this->assertNull($element);
	}

	public function testByIDEmpty() {
		$list = new ArrayList();

		$element = $list->byID(1);
		$this->assertNull($element);
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
		return ['First' => $this->First, 'Second' => $this->Second];
	}

}
