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
	}
}