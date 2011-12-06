<?php

/**
 * @package sapphire
 * @subpackage tests
 */
class GridFieldPresenterTest extends SapphireTest {

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
		$this->assertTrue(new GridFieldPresenter instanceof GridFieldPresenter, 'Trying to find an instance of GridFieldPresenter');
	}
	
	public function testHeaders() {
		$presenter = new GridFieldPresenter();
		$grid = new GridField('testgrid', 'testgrid', new DataList('GridFieldTest_Person'));
		$presenter->setGridField($grid);
		$headers = $presenter->Headers()->first();
		
		$this->assertEquals(1, count($headers));
		$this->assertEquals('Name', $headers->Name );
	}
	
	public function testItemsReturnCorrectNumberOfItems() {
		$presenter = new GridFieldPresenter();
		$grid = new GridField('testgrid', 'testgrid', new DataList('GridFieldTest_Person'));
		$presenter->setGridField($grid);
		$this->assertEquals(2, $presenter->Items()->count());
	}
	
	public function testSorting(){
		$presenter = new GridFieldPresenter();
		$GridField = new GridField('testgrid', 'testgrid', new DataList('GridFieldTest_Person'), null, $presenter);
		
		$GridField->getState()->Sort = array('Name' => 'desc');
		$this->assertEquals('Second Person', $presenter->Items()->first()->Name);
		
		// Reverse the sort
		$GridField->getState()->Sort = array('Name' => 'asc');
		$this->assertEquals('First Person', $presenter->Items()->first()->Name);
	}
	
}