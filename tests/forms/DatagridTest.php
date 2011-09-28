<?php

/**
 * This is a Unittest class for DatagridTest
 * 
 */
class DatagridTest extends SapphireTest {

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
		$this->assertTrue(new Datagrid('Testgrid') instanceof FormField, 'Datagrid should be a FormField');
	}
	
	public function testSetDataSource() {
		$grid = new Datagrid('Testgrid');
		$source = new ArrayList();
		$grid->setDatasource($source);
		$this->assertEquals($source, $grid->getDatasource());
	}
	
	function testSetEmptyDataPresenter() {
		$this->setExpectedException('Exception');
		$grid = new Datagrid('Testgrid');
		$grid->setDataPresenter('');
	}
	
	function testSetNonExistingDataPresenter() {
		$this->setExpectedException('Exception');
		$grid = new Datagrid('Testgrid');
		$grid->setDataPresenter('ifThisClassExistsIWouldBeSurprised');
	}
	
	function testSetDataPresenterWithDataObject() {
		$this->setExpectedException('Exception');
		$grid = new Datagrid('Testgrid');
		$grid->setDataPresenter('DataObject');
	}
	
	function testSetDataPresenter() {
		$grid = new Datagrid('Testgrid');
		$grid->setDataPresenter('DatagridPresenter');
	}
	
	function testFieldListIsNullWithoutDataSource() {
		$grid = new Datagrid('Testgrid');
		$this->assertNull($grid->FieldList());
	}
	
	function testFieldList() {
		$grid = new Datagrid('Testgrid');
		$grid->setDatasource(new DataList('DatagridTest_Person'));
		$this->assertNotNull($grid->FieldList());
		$this->assertEquals(array('Name'=>'Name','ID'=>'ID'), $grid->FieldList());
	}
	
	/**
	 * This is better tested in the DatagridFunctionalTest
	 * 
	 * @see DatagridFunctionalTest
	 */
	function testFieldHolder() {
		$grid = new Datagrid('Testgrid');
		$this->assertNotNull($grid->FieldHolder());
	}
}

class DatagridTest_Person extends Dataobject implements TestOnly {

	public static $db = array(
		'Name' => 'Varchar'
	);

	public static $summary_fields = array(
		'Name',
		'ID'
	);
}