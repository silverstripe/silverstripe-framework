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
	
	function testSetDataclass() {
		$grid = new Datagrid('Testgrid');
		$grid->setModelClass('SiteTree');
		$this->assertEquals('SiteTree', $grid->getModelClass());
	}
	
	/**
	 * 
	 */
	function testFieldHolderWithoutDataSource() {
		$this->setExpectedException('Exception');
		$grid = new Datagrid('Testgrid');
		$this->assertNotNull($grid->FieldHolder());
	}
	
	/**
	 * This is better tested in the DatagridFunctionalTest
	 * 
	 * @see DatagridFunctionalTest
	 */
	function testFieldHolder() {
		$grid = new Datagrid('Testgrid');
		$grid->setDatasource(new DataList('DatagridTest_Person'));
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