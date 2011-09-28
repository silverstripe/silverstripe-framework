<?php

/**
 * This is a Unittest class for DatagridPresenterTest
 * 
 */
class DatagridPresenterTest extends SapphireTest {

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
		$this->assertTrue(new DatagridPresenter instanceof DatagridPresenter, 'Trying to find an instance of DatagridPresenter');
	}
	
	public function testHeaders() {
		$presenter = new DatagridPresenter();
		$grid = new Datagrid('testgrid', 'testgrid', new DataList('DatagridTest_Person'));
		$presenter->setDatagrid($grid);
		$headers = $presenter->Headers()->first();
		
		$this->assertEquals(1, count($headers));
		$this->assertEquals('Name', $headers->Name );
	}
	
	public function testItemsReturnCorrectNumberOfItems() {
		$presenter = new DatagridPresenter();
		$grid = new Datagrid('testgrid', 'testgrid', new DataList('DatagridTest_Person'));
		$presenter->setDatagrid($grid);
		$this->assertEquals(2, $presenter->Items()->count());
	}

}