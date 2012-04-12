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

}
