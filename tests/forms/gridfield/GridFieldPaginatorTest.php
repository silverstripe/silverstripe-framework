<?php
class GridFieldPaginatorTest extends FunctionalTest {
	/** @var ArrayList */
	protected $list;
	
	/** @var GridField */
	protected $gridField;
	
	/** @var string */
	static $fixture_file = 'GridFieldTest.yml';
	
	/** @var Form */
	protected $form;
	
	/** @var array */
	protected $extraDataObjects = array('GridFieldTest_Team', 'GridFieldTest_Player');
	
	public function setUp() {
		parent::setUp();
		$this->list = new DataList('GridFieldTest_Team');
		$config = GridFieldConfig::create()->addComponent(new GridFieldPaginator(2));
		$this->gridField = new GridField('testfield', 'testfield', $this->list, $config);
		$this->form = new Form(new Controller(), 'mockform', new FieldList(array($this->gridField)), new FieldList());
	}
	
	function testThereIsPaginatorWhenMoreThanOnePage() {
		$fieldHolder = $this->gridField->FieldHolder();
		$content = new CSSContentParser($fieldHolder);
		// Check that there is paginator render into the footer
		$this->assertEquals(1, count($content->getBySelector('.datagrid-pagination')));
	}
	
	function testThereIsNoPaginatorWhenOnlyOnePage() {
		// We set the itemsPerPage to an reasonably big number so as to avoid test broke from small changes on the fixture YML file 
		$total = $this->list->count();
		$this->gridField->getConfig()->getComponentByType("GridFieldPaginator")->setItemsPerPage($total);
		$fieldHolder = $this->gridField->FieldHolder();
		$content = new CSSContentParser($fieldHolder);
		
		// Check that there is no paginator render into the footer
		$this->assertEquals(0, count($content->getBySelector('.datagrid-pagination')));
		
		// Check that there is still 'View 1 - 4 of 4' part on the left of the paginator
		$this->assertEquals(1, count($content->getBySelector('.pagination-records-number')));
	}
}
