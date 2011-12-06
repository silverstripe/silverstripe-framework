<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class GridFieldPaginatorTest extends SapphireTest {

	/**
	 *
	 * @var string
	 */
	public static $fixture_file = 'sapphire/tests/forms/GridFieldTest.yml';
	
	/**
	 *
	 * @var array
	 */
	protected $extraDataObjects = array(
		'GridFieldTest_Person',
	);
	
	public function testGetInstance() {
		
		$this->assertTrue(new GridFieldPaginator(new GridField('PaginatedGridField'),10,1) instanceof GridFieldPaginator, 'Trying to find an instance of GridFieldPaginator');
		$this->assertTrue(new GridFieldPaginator_Extension() instanceof GridFieldPaginator_Extension, 'Trying to find an instance of GridFieldPaginator_Extension');
	}
	
	public function testFlowThroughGridFieldExtension() {
		$gridField = new GridField('PaginatedGridField', 'PaginatedGridField', new DataList('GridFieldTest_Person'));
		$t = new GridFieldPaginator_Extension();
		$t->paginationLimit(5);
		$t->filterList($gridField);
		$this->assertTrue($t->Footer() instanceof GridFieldPaginator);
	}
}