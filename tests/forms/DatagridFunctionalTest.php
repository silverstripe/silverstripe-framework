<?php

/**
 * This is a functional test for DatagridFunctionalTest
 * 
 */
class DatagridFunctionalTest extends FunctionalTest {
	
	/**
	 *
	 * @var string
	 */
	static $fixture_file = 'sapphire/tests/forms/DatagridTest.yml';

	/**
	 *
	 * @var array
	 */
	protected $extraDataObjects = array(
		'DatagridTest_Person',
	);

	public function testGetInstance() {
		$this->assertTrue(new Datagrid('testgrid') instanceof Datagrid, 'Trying to find an instance of Datagrid.');
	}

	public function testAddToForm() {
		$firstPerson = $this->objFromFixture('DatagridTest_Person', 'first');
		$response = $this->get("DatagridFunctionalTest_Controller/");
		$this->assertContains($firstPerson->Name, $response->getBody());
	}
}

class DatagridFunctionalTest_Controller extends ContentController {

	public function index() {
		$grid = new Datagrid('testgrid');
		$dataSource = DataList::create("DatagridTest_Person")->sort("Name");
		$grid->setDataSource($dataSource);
		$form = new Form($this, 'gridform', new FieldList($grid), new FieldList(new FormAction('rerender', 'rerender')));
		return array('Form'=>$form);
	}
}