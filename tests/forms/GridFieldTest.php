<?php

/**
 * This is a Unittest class for GridFieldTest
 * 
 */
class GridFieldTest extends SapphireTest {

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

	public function testGetInstance() {
		$this->assertTrue(new GridField('Testgrid') instanceof FormField, 'GridField should be a FormField');
	}
	
	public function testSetDataSource() {
		$grid = new GridField('Testgrid');
		$source = new ArrayList();
		$grid->setList($source);
		$this->assertEquals($source, $grid->getList());
	}
	
	function testSetEmptyDataPresenter() {
		$this->setExpectedException('InvalidArgumentException');
		$grid = new GridField('Testgrid');
		$grid->setPresenter('');
	}
	
	function testSetNonExistingDataPresenter() {
		$this->setExpectedException('InvalidArgumentException');
		$grid = new GridField('Testgrid');
		$grid->setPresenter('ifThisClassExistsIWouldBeSurprised');
	}
	
	function testSetDataPresenterWithDataObject() {
		$this->setExpectedException('InvalidArgumentException');
		$grid = new GridField('Testgrid');
		$grid->setPresenter('DataObject');
	}
	
	function testSetDataPresenter() {
		$grid = new GridField('Testgrid');
		$grid->setPresenter('GridFieldPresenter');
	}
	
	function testSetDataclass() {
		$grid = new GridField('Testgrid');
		$grid->setModelClass('SiteTree');
		$this->assertEquals('SiteTree', $grid->getModelClass());
	}
	
	/**
	 * 
	 */
	function testFieldHolderWithoutDataSource() {
		$this->setExpectedException('InvalidArgumentException');
		$grid = new GridField('Testgrid');
		$this->assertNotNull($grid->FieldHolder());
	}
	
	/**
	 * This is better tested in the GridFieldFunctionalTest
	 * 
	 * @see GridFieldFunctionalTest
	 */
	function testFieldHolder() {
		$grid = new GridField('Testgrid');
		$grid->setList(new DataList('GridFieldTest_Person'));
		$this->assertNotNull($grid->FieldHolder());
	}
	
	function testGetState() {
		$grid = new GridField('Testgrid');
		$this->assertTrue($grid->getState() instanceof GridState, 'getState() should return a GridState');
	}
}

class GridFieldTest_Person extends Dataobject implements TestOnly {

	public static $db = array(
		'Name' => 'Varchar'
	);

	public static $summary_fields = array(
		'Name',
		'ID'
	);
}