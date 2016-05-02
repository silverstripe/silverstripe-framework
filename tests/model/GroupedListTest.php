<?php
/**
 * Tests for the {@link GroupedList} list decorator.
 *
 * @package framework
 * @subpackage tests
 */
class GroupedListTest extends SapphireTest {

	public function testGroupBy() {
		$list = new GroupedList(new ArrayList(array(
			array('Name' => 'AAA'),
			array('Name' => 'AAA'),
			array('Name' => 'BBB'),
			array('Name' => 'BBB'),
			array('Name' => 'AAA'),
			array('Name' => 'BBB'),
			array('Name' => 'CCC'),
			array('Name' => 'CCC')
		)));

		$grouped = $list->groupBy('Name');

		$this->assertEquals(3, count($grouped));
		$this->assertEquals(3, count($grouped['AAA']));
		$this->assertEquals(3, count($grouped['BBB']));
		$this->assertEquals(2, count($grouped['CCC']));
	}

	public function testGroupedBy() {
		$list = new GroupedList(new ArrayList(array(
			array('Name' => 'AAA'),
			array('Name' => 'AAA'),
			array('Name' => 'BBB'),
			array('Name' => 'BBB'),
			array('Name' => 'AAA'),
			array('Name' => 'BBB'),
			array('Name' => 'CCC'),
			array('Name' => 'CCC')
		)));

		$grouped = $list->GroupedBy('Name');
		$first   = $grouped->first();
		$last    = $grouped->last();

		$this->assertEquals(3, count($first->Children));
		$this->assertEquals('AAA', $first->Name);
		$this->assertEquals(2, count($last->Children));
		$this->assertEquals('CCC', $last->Name);
	}

	public function testGroupedByChildren(){
		$list = GroupedList::create(
			ArrayList::create(
				array(
					ArrayData::create(array(
						'Name' => 'AAA',
						'Number' => '111',
					)),
					ArrayData::create(array(
						'Name' => 'BBB',
						'Number' => '111',
					)),
					ArrayData::create(array(
						'Name'   => 'AAA',
						'Number' => '222',
					)),
					ArrayData::create(array(
						'Name'   => 'BBB',
						'Number' => '111',
					)),
					ArrayData::create(array(
						'Name'   => 'AAA',
						'Number' => '111',
					)),
					ArrayData::create(array(
						'Name'   => 'AAA',
						'Number' => '333',
					)),
					ArrayData::create(array(
						'Name'   => 'BBB',
						'Number' => '222',
					)),
					ArrayData::create(array(
						'Name'   => 'BBB',
						'Number' => '333',
					)),
					ArrayData::create(array(
						'Name'   => 'AAA',
						'Number' => '111',
					)),
					ArrayData::create(array(
						'Name'   => 'AAA',
						'Number' => '333',
					))
				)
			)
		);
		$grouped = $list->GroupedBy('Name');

		foreach($grouped as $group){
			$children = $group->Children;
			$childGroups = $children->GroupedBy('Number');

			$this->assertEquals(3, count($childGroups));

			$first = $childGroups->first();
			$last  = $childGroups->last();

			if($group->Name == 'AAA'){
				$this->assertEquals(3, count($first->Children));
				$this->assertEquals('111', $first->Number);
				$this->assertEquals(2, count($last->Children));
				$this->assertEquals('333', $last->Number);
			}

			if($group->Name == 'BBB'){
				$this->assertEquals(2, count($first->Children));
				$this->assertEquals('111', $first->Number);
				$this->assertEquals(1, count($last->Children));
				$this->assertEquals('333', $last->Number);
			}
		}
	}

	public function testTotalItems() {
		$list = GroupedList::create(
			ArrayList::create(
				array(
					ArrayData::create(array(
						'Name' => 'AAA',
						'Number' => '111',
					)),
					ArrayData::create(array(
						'Name' => 'BBB',
						'Number' => '111',
					)),
					ArrayData::create(array(
						'Name'   => 'AAA',
						'Number' => '222',
					)),
					ArrayData::create(array(
						'Name'   => 'BBB',
						'Number' => '111',
					))
				)
			)
		);

		$this->assertEquals(4, $list->TotalItems());
	}

}
