<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class GridFieldPaginatorTest extends SapphireTest {

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
		$this->assertTrue(new GridFieldPaginator($this->gridField) instanceof GridFieldPaginator, 'Testing constructor of GridFieldFilter ');
	}
	
	public function testFieldHolder() {
		$gfb = new GridFieldPaginator($this->gridField);
		$t = $gfb->FieldHolder();
		$this->assertContains('<button class="action  ss-gridfield-button nolabel" id="action_SetPage1" type="submit" name="action_gridFieldAlterAction?StateID=', $t);
		$this->assertContains('">1</button></td></tr>', $t);
	}
	
	public function testFieldHolderTwoPages() {
		$this->gridField->getState()->Pagination->Page = 1;
		$this->gridField->getState()->Pagination->ItemsPerPage = 1;
		$gfb = new GridFieldPaginator($this->gridField);
		$html = $gfb->FieldHolder();
		$this->assertContains('<button class="action  ss-gridfield-button nolabel" id="action_SetPage1" type="submit" name="action_gridFieldAlterAction?StateID=', $html);
		$this->assertContains('">1</button>', $html);
		$this->assertContains('<button class="action  ss-gridfield-button nolabel" id="action_SetPage2" type="submit" name="action_gridFieldAlterAction?StateID=', $html);
		$this->assertContains('">2</button>', $html);
	}
	
	public function testFieldHolderTwoPagesCheckFirstPage() {
		$this->gridField->getState()->Pagination->ItemsPerPage = 1;
		$this->gridField->getState()->Pagination->Page = 1;
		$gfb = new GridFieldPaginator($this->gridField);
		$html = $this->gridField->FieldHolder();
		$this->assertContains('First Person', $html);
		$this->assertNotContains('Second Person', $html);
	}
	
	public function testFieldHolderTwoPagesCheckSecondPage() {
		$this->gridField->getState()->Pagination->ItemsPerPage = 1;
		$this->gridField->getState()->Pagination->Page = 2;
		$html = $this->gridField->FieldHolder();
		$gfb = new GridFieldPaginator($this->gridField);
		$this->assertContains('Second Person', $html);
		$this->assertNotContains('First Person', $html);
	}
}