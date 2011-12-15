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
	
	public function testFieldHolder() {
		$gfb = new GridFieldBody($this->gridField);
		$t = $gfb->FieldHolder();
		$expected = '<tr class="ss-gridfield-odd first">'.
			'<td class="ss-gridfield-first">First Person</td>'.
			'<td class="ss-gridfield-last">1</td>'.
			'</tr>'.
			'<tr class="ss-gridfield-even last">'.
			'<td class="ss-gridfield-first">Second Person</td>'.
			'<td class="ss-gridfield-last">2</td>'.
			'</tr>';
		$this->assertContains($expected,  preg_replace("/(\n*)|(\t*)/", '', $t));
	}
}