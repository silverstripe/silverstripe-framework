<?php

/**
 * This is a Unittest class for GridFieldFilter
 * 
 */
class GridFieldFilterTest extends SapphireTest {
	
	/**
	 *
	 * @var type 
	 */
	public static $fixture_file = 'sapphire/tests/forms/GridFieldTest.yml';
	
	/**
	 *
	 * @var GridField
	 */
	private $gridField = null;
	
	/**
	 *
	 * @var array
	 */
	protected $extraDataObjects = array(
		'GridFieldTest_Person',
	);
	
	
	public function setUp() {
		$this->gridField = new GridField('TestGrid', 'Test grid', new DataList('GridFieldTest_Person'));
		parent::setUp();
	}
	
	public function testContructor() {
		$this->assertTrue(new GridFieldFilter($this->gridField) instanceof GridFieldFilter, 'Testing constructor of GridFieldFilter ');
	}
	
	public function testFieldHolder() {
		$gfb = new GridFieldFilter($this->gridField);
		$html = $gfb->FieldHolder();
		$this->assertContains('<input type="text" class="text ss-gridfield-filter" id="SetFilterName" name="SetFilterName" value="" />', $html);
		$this->assertContains('<input type="text" class="text ss-gridfield-filter" id="SetFilterID" name="SetFilterID" value="" />', $html);
		$this->assertContains('<button class="action  ss-gridfield-button nolabel" id="action_SetFilter" type="submit" name="action_gridFieldAlterAction?StateID=', $html);
		$this->assertContains('<button class="action  ss-gridfield-button nolabel" id="action_ResetFilter" type="submit" name="action_gridFieldAlterAction?StateID=', $html);
	}
}