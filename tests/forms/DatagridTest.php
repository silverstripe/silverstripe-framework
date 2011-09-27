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
		$grid->setDatasource( $source );
		$this->assertEquals($source, $grid->getDatasource());
	}

	public function testGetDefaultHeadersFromEmptyArrayList() {
		$grid = new Datagrid('Testgrid');
		$source = new ArrayList();
		$grid->setDatasource($source);
		$this->assertEquals(array(), $grid->getHeaders());
	}

	public function testGetDefaultHeadersFromArrayList() {
		$grid = new Datagrid('Testgrid');
		$source = new ArrayList(array(array('ID'=>1,'Name'=>'Aaron Aardwark')));
		$grid->setDatasource($source);
		$this->assertEquals(array('ID'=>'ID','Name'=>'Name'), $grid->getHeaders());
	}

	public function testGetDefaultHeadersFromDataList() {
		$grid = new Datagrid('Testgrid');
		$source = new DataList('DatagridTest_Person');
		$grid->setDatasource($source);
		$this->assertEquals(array('Name'=>'Name','ID'=>'ID'), $grid->getHeaders());
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