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
	
	protected function getGridFieldForm(){
		$grid = new GridField('testgrid');
		$dataSource = DataList::create("GridFieldTest_Person")->sort("Name");
		$grid->setList($dataSource);
		return new Form($this, 'gridform', new FieldList($grid), new FieldList(new FormAction('rerender', 'rerender')));
	}

	public function testAddToForm() {
		$firstPerson = $this->objFromFixture('GridFieldTest_Person', 'first');
		$form = $this->getGridFieldForm();
		
		$fields = $form->Fields();
		$formHTML = ($fields->fieldByName('testgrid')->FieldHolder());
		$this->assertContains($firstPerson->Name, $formHTML);
	}
}