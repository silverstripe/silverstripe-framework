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

	/**
	 * Tests that the appropriate sortable headers are generated
	 */
	public function testRenderHeaders() {

		// Generate sortable header and extract HTML
		$list = new DataList('GridFieldSortableHeaderTest_Team');
		$config = new GridFieldConfig_RecordEditor();
		$form = new Form(Controller::curr(), 'Form', new FieldList(), new FieldList());
		$gridField = new GridField('testfield', 'testfield', $list, $config);
		$gridField->setForm($form);
		$compontent = $gridField->getConfig()->getComponentByType('GridFieldSortableHeader');
		$htmlFragment = $compontent->getHTMLFragments($gridField);

		// Check that the output shows name and hat as sortable fields, but not city
		$this->assertContains('<span class="non-sortable">City</span>', $htmlFragment['header']);
		$this->assertContains('value="Name" class="action ss-gridfield-sort" id="action_SetOrderName"',
			$htmlFragment['header']);
		$this->assertContains(
			'value="Cheerleader Hat" class="action ss-gridfield-sort" id="action_SetOrderCheerleader-Hat-Colour"',
			$htmlFragment['header']);

		// Check inverse of above
		$this->assertNotContains('value="City" class="action ss-gridfield-sort" id="action_SetOrderCity"',
			$htmlFragment['header']);
		$this->assertNotContains('<span class="non-sortable">Name</span>', $htmlFragment['header']);
		$this->assertNotContains('<span class="non-sortable">Cheerleader Hat</span>', $htmlFragment['header']);
	}

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

	private static $summary_fields = array(
		'Name' => 'Name',
		'City.Initial' => 'City',
		'Cheerleader.Hat.Colour' => 'Cheerleader Hat'
	);

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
