<?php

/**
 * This is a functional test for GridFieldFunctionalTest
 * 
 */
class GridFieldFunctionalTest extends FunctionalTest {
	
	/**
	 *
	 * @var string
	 */
	static $fixture_file = 'sapphire/tests/forms/GridFieldTest.yml';

	/**
	 *
	 * @var array
	 */
	protected $extraDataObjects = array(
		'GridFieldTest_Person',
	);

	public function testAddToForm() {
		$firstPerson = $this->objFromFixture('GridFieldTest_Person', 'first');
		$response = $this->get("GridFieldFunctionalTest_Controller/");
		$this->assertContains($firstPerson->Name, $response->getBody());
	}
	
	public function testGridStateInForm() {
		$firstPerson = $this->objFromFixture('GridFieldTest_Person', 'first');
		$response = $this->get("GridFieldFunctionalTest_Controller/");
		$this->assertContains('<input class="hidden" type="hidden" id="testgrid_GridState" name="testgrid_GridState" value="[]" />', $response->getBody());
	}
}

class GridFieldFunctionalTest_Controller extends Controller {
	
	protected $template = 'BlankPage';
	
	function Link($action = null) {
		return Controller::join_links('GridFieldFunctionalTest_Controller', $action);
	}

	public function index() {
		$grid = new GridField('testgrid');
		$dataSource = DataList::create("GridFieldTest_Person")->sort("Name");
		$grid->setList($dataSource);
		$form = new Form($this, 'gridform', new FieldList($grid), new FieldList(new FormAction('rerender', 'rerender')));
		return array('Form'=>$form);
	}
}