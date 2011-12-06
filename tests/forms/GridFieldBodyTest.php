<?php

/**
 * This is a Unittest class for GridFieldBody
 * 
 */
class GridFieldBodyTest extends SapphireTest {
	
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
	
	public function testConstructor() {
		$this->assertTrue(new GridFieldBody($this->gridField) instanceof GridFieldBody, 'Trying to find an instance of GridFieldBody');
	}

	/**
	 * This test that the list contains rendered entries from GridFieldTest_Person
	 */
	public function testFieldHolder() {
		$gfb = new GridFieldBody($this->gridField);
		$t = $gfb->FieldHolder();
		$firstPerson= '<td>First Person</td><td>1</td>';
		$this->assertContains($firstPerson,  preg_replace("/(\n*)|(\t*)/", '', $t));
		$secondPerson= '<td>Second Person</td><td>2</td>';
		$this->assertContains($secondPerson,  preg_replace("/(\n*)|(\t*)/", '', $t));
	}
}