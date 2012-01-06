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
		$this->assertTrue(new GridField('Testgrid', 'Test grid', new DataList('GridFieldTest_Person')) instanceof CompositeField, 'Testing constructor of GridField');
	}
	
	public function testSetDataSource() {
		$source = new DataList('GridFieldTest_Person');
		$grid = new GridField('Testgrid', 'Test grid', $source);
		$this->assertEquals($source, $grid->getList());
	}
	
	function testSetDataclass() {
		$source = new DataList('GridFieldTest_Person');
		$grid = new GridField('Testgrid', 'Test grid', $source);
		$grid->setModelClass('SiteTree');
		$this->assertEquals('SiteTree', $grid->getModelClass());
	}
	
	public function testGetDisplayFields() {
		$grid = new GridField('Testgrid', 'Test grid', new DataList('GridFieldTest_Person'));
		$expected = array( 'Name' => 'Name', 'ID' => 'ID' );
		$this->assertEquals($expected, $grid->getDisplayFields());
	}
	
	public function testGetState() {
		$grid = new GridField('Testgrid', 'Test grid', new DataList('GridFieldTest_Person'));
		$this->assertTrue($grid->getState() instanceof GridState);
	}
	
	/**
	 * Will test that the default state changers are rendered
	 */
	public function testFieldHolder() {
		$grid = new GridField('Testgrid', 'Test grid', new DataList('GridFieldTest_Person'));
		$html = $grid->FieldHolder();
		$this->assertContains('id="GridState" name="GridState"',$html);
		$this->assertContains('id="action_SetOrderID" type="submit"',$html);
		$this->assertContains('id="SetFilterID" name="SetFilterID" value=""',$html);
		$this->assertContains('id="action_SetPage1" type="submit"',$html);
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
