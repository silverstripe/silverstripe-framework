<?php
/**
 * @package framework
 * @subpackage tests
 */

class GridFieldSortableHeaderTest extends SapphireTest {

	protected static $fixture_file = 'GridFieldSortableHeaderTest.yml';

	protected $extraDataObjects = array(
		'GridFieldSortableHeaderTest_Team',
		'GridFieldSortableHeaderTest_Cheerleader',
		'GridFieldSortableHeaderTest_CheerleaderHat'
	);

	public function testGetManipulatedData() {
		$list = new DataList('GridFieldSortableHeaderTest_Team');
		$config = new GridFieldConfig_RecordEditor();
		$gridField = new GridField('testfield', 'testfield', $list, $config);

		// Test normal sorting
		$state = $gridField->State->GridFieldSortableHeader;
		$state->SortColumn = 'City';
		$state->SortDirection = 'asc';

		$compontent = $gridField->getConfig()->getComponentByType('GridFieldSortableHeader');
		$listA = $compontent->getManipulatedData($gridField, $list);

		$state->SortDirection = 'desc';
		$listB = $compontent->getManipulatedData($gridField, $list);

		$this->assertEquals(
			array('Auckland', 'Cologne', 'Melbourne', 'Wellington'),
			$listA->column('City')
		);
		$this->assertEquals(
			array('Wellington', 'Melbourne', 'Cologne', 'Auckland'),
			$listB->column('City')
		);

		// Test one relation 'deep'
		$state->SortColumn = 'Cheerleader.Name';
		$state->SortDirection = 'asc';
		$relationListA = $compontent->getManipulatedData($gridField, $list);

		$state->SortDirection = 'desc';
		$relationListB = $compontent->getManipulatedData($gridField, $list);

		$this->assertEquals(
			array('Wellington', 'Melbourne', 'Cologne', 'Auckland'),
			$relationListA->column('City')
		);
		$this->assertEquals(
			array('Auckland', 'Cologne', 'Melbourne', 'Wellington'),
			$relationListB->column('City')
		);

		// Test two relations 'deep'
		$state->SortColumn = 'Cheerleader.Hat.Colour';
		$state->SortDirection = 'asc';
		$relationListC = $compontent->getManipulatedData($gridField, $list);

		$state->SortDirection = 'desc';
		$relationListD = $compontent->getManipulatedData($gridField, $list);

		$this->assertEquals(
			array('Cologne', 'Auckland', 'Wellington', 'Melbourne'),
			$relationListC->column('City')
		);
		$this->assertEquals(
			array('Melbourne', 'Wellington', 'Auckland', 'Cologne'),
			$relationListD->column('City')
		);
	}

}

class GridFieldSortableHeaderTest_Team extends DataObject implements TestOnly {

	private static $db = array(
		'Name' => 'Varchar',
		'City' => 'Varchar'
	);

	private static $has_one = array(
		'Cheerleader' => 'GridFieldSortableHeaderTest_Cheerleader'
	);

}

class GridFieldSortableHeaderTest_Cheerleader extends DataObject implements TestOnly {

	private static $db = array(
		'Name' => 'Varchar'
	);

	private static $has_one = array(
		'Team' => 'GridFieldSortableHeaderTest_Team',
		'Hat' => 'GridFieldSortableHeaderTest_CheerleaderHat'
	);

}

class GridFieldSortableHeaderTest_CheerleaderHat extends DataObject implements TestOnly {

	private static $db = array(
		'Colour' => 'Varchar'
	);

	private static $has_one = array(
		'Cheerleader' => 'GridFieldSortableHeaderTest_Cheerleader'
	);

}