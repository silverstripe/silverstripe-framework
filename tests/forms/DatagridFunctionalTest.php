<?php

/**
 * This is a functional test for DatagridFunctionalTest
 * 
 */
class DatagridFunctionalTest extends FunctionalTest {

	public function testGetInstance() {
		$this->assertTrue(new Datagrid('testgrid') instanceof Datagrid, 'Trying to find an instance of Datagrid.');
	}

	public function testAddToForm() {
		$response = $this->get("DatagridFunctionalTest_Controller/");
		$this->assertContains("form", $response->getBody());
	}
}

class DatagridFunctionalTest_Controller extends ContentController {

	public function index() {
		$grid = new Datagrid('testgrid');
		$form = new Form($this, 'gridform', new FieldList($grid), new FieldList(new FormAction('rerender', 'rerender')));
		return array('Form'=>$form);
	}
}