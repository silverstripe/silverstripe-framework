<?php
/**
 * Tests for the {@link PaginatedList} class.
 *
 * @package framework
 * @subpackage tests
 */
class PaginatedListTest extends SapphireTest {

	protected static $fixture_file = 'DataObjectTest.yml';

	protected $extraDataObjects = array(
		'DataObjectTest_Team',
		'DataObjectTest_SubTeam',
		'DataObjectTest_Player',
		'ManyManyListTest_Product',
		'ManyManyListTest_Category',
	);

	public function testPageStart() {
		$list = new PaginatedList(new ArrayList());
		$this->assertEquals(0, $list->getPageStart(), 'The start defaults to 0.');

		$list->setPageStart(10);
		$this->assertEquals(10, $list->getPageStart(), 'You can set the page start.');

		$list = new PaginatedList(new ArrayList(), array('start' => 50));
		$this->assertEquals(50, $list->getPageStart(), 'The page start can be read from the request.');
	}

	public function testGetTotalItems() {
		$list = new PaginatedList(new ArrayList());
		$this->assertEquals(0, $list->getTotalItems());

		$list->setTotalItems(10);
		$this->assertEquals(10, $list->getTotalItems());

		$list = new PaginatedList(new ArrayList(array(
			new ArrayData(array()),
			new ArrayData(array())
		)));
		$this->assertEquals(2, $list->getTotalItems());
	}

	public function testSetPaginationFromQuery() {
		$query = $this->getMockBuilder('SQLQuery')->getMock();
		$query->expects($this->once())
				->method('getLimit')
				->will($this->returnValue(array('limit' => 15, 'start' => 30)));
		$query->expects($this->once())
				->method('unlimitedRowCount')
				->will($this->returnValue(100));

		$list = new PaginatedList(new ArrayList());
		$list->setPaginationFromQuery($query);

		$this->assertEquals(15, $list->getPageLength());
		$this->assertEquals(30, $list->getPageStart());
		$this->assertEquals(100, $list->getTotalItems());
	}

	public function testSetCurrentPage() {
		$list = new PaginatedList(new ArrayList());
		$list->setPageLength(10);
		$list->setCurrentPage(10);

		$this->assertEquals(10, $list->CurrentPage());
		$this->assertEquals(90, $list->getPageStart());

		// Test disabled paging
		$list->setPageLength(0);
		$this->assertEquals(1, $list->CurrentPage());
	}

	public function testGetIterator() {
		$list = new PaginatedList(new ArrayList(array(
			new DataObject(array('Num' => 1)),
			new DataObject(array('Num' => 2)),
			new DataObject(array('Num' => 3)),
			new DataObject(array('Num' => 4)),
			new DataObject(array('Num' => 5)),
		)));
		$list->setPageLength(2);

		$this->assertDOSEquals(
			array(array('Num' => 1), array('Num' => 2)), $list->getIterator()
		);

		$list->setCurrentPage(2);
		$this->assertDOSEquals(
			array(array('Num' => 3), array('Num' => 4)), $list->getIterator()
		);

		$list->setCurrentPage(3);
		$this->assertDOSEquals(
			array(array('Num' => 5)), $list->getIterator()
		);

		$list->setCurrentPage(999);
		$this->assertDOSEquals(array(), $list->getIterator());

		// Test disabled paging
		$list->setPageLength(0);
		$list->setCurrentPage(1);
		$this->assertDOSEquals(
			array(
				array('Num' => 1),
				array('Num' => 2),
				array('Num' => 3),
				array('Num' => 4),
				array('Num' => 5)
			), $list->getIterator()
		);

		// Test with dataobjectset
		$players = DataObjectTest_Player::get();
		$list = new PaginatedList($players);
		$list->setPageLength(1);
		$list->getIterator();
		$this->assertEquals(4, $list->getTotalItems(),
			'Getting an iterator should not trim the list to the page length.');
	}

	public function testPages() {
		$list = new PaginatedList(new ArrayList());
		$list->setPageLength(10);
		$list->setTotalItems(50);

		$this->assertEquals(5, count($list->Pages()));
		$this->assertEquals(3, count($list->Pages(3)));
		$this->assertEquals(5, count($list->Pages(15)));

		$list->setCurrentPage(3);

		$expectAll = array(
			array('PageNum' => 1),
			array('PageNum' => 2),
			array('PageNum' => 3, 'CurrentBool' => true),
			array('PageNum' => 4),
			array('PageNum' => 5),
		);
		$this->assertDOSEquals($expectAll, $list->Pages());

		$expectLimited = array(
			array('PageNum' => 2),
			array('PageNum' => 3, 'CurrentBool' => true),
			array('PageNum' => 4),
		);
		$this->assertDOSEquals($expectLimited, $list->Pages(3));

		// Disable paging
		$list->setPageLength(0);
		$expectAll = array(
			array('PageNum' => 1, 'CurrentBool' => true),
		);
		$this->assertDOSEquals($expectAll, $list->Pages());
	}

	public function testPaginationSummary() {
		$list = new PaginatedList(new ArrayList());

		$list->setPageLength(10);
		$list->setTotalItems(250);
		$list->setCurrentPage(6);

		$expect = array(
			array('PageNum' => 1),
			array('PageNum' => null),
			array('PageNum' => 4),
			array('PageNum' => 5),
			array('PageNum' => 6, 'CurrentBool' => true),
			array('PageNum' => 7),
			array('PageNum' => 8),
			array('PageNum' => null),
			array('PageNum' => 25),
		);
		$this->assertDOSEquals($expect, $list->PaginationSummary(4));

		// Disable paging
		$list->setPageLength(0);
		$expect = array(
			array('PageNum' => 1, 'CurrentBool' => true)
		);
		$this->assertDOSEquals($expect, $list->PaginationSummary(4));
	}

	public function testLimitItems() {
		$list = new ArrayList(range(1, 50));
		$list = new PaginatedList($list);

		$list->setCurrentPage(3);
		$this->assertEquals(10, count($list->getIterator()->getInnerIterator()));

		$list->setLimitItems(false);
		$this->assertEquals(50, count($list->getIterator()->getInnerIterator()));
	}

	public function testCurrentPage() {
		$list = new PaginatedList(new ArrayList());
		$list->setTotalItems(50);

		$this->assertEquals(1, $list->CurrentPage());
		$list->setPageStart(10);
		$this->assertEquals(2, $list->CurrentPage());
		$list->setPageStart(40);
		$this->assertEquals(5, $list->CurrentPage());

		// Disable paging
		$list->setPageLength(0);
		$this->assertEquals(1, $list->CurrentPage());
	}

	public function testTotalPages() {
		$list = new PaginatedList(new ArrayList());

		$list->setPageLength(1);
		$this->assertEquals(0, $list->TotalPages());

		$list->setTotalItems(1);
		$this->assertEquals(1, $list->TotalPages());

		$list->setTotalItems(5);
		$this->assertEquals(5, $list->TotalPages());

		// Disable paging
		$list->setPageLength(0);
		$this->assertEquals(1, $list->TotalPages());

		$list->setTotalItems(0);
		$this->assertEquals(0, $list->TotalPages());
	}

	public function testMoreThanOnePage() {
		$list = new PaginatedList(new ArrayList());

		$list->setPageLength(1);
		$list->setTotalItems(1);
		$this->assertFalse($list->MoreThanOnePage());

		$list->setTotalItems(2);
		$this->assertTrue($list->MoreThanOnePage());

		// Disable paging
		$list->setPageLength(0);
		$this->assertFalse($list->MoreThanOnePage());
	}

	public function testNotFirstPage() {
		$list = new PaginatedList(new ArrayList());
		$this->assertFalse($list->NotFirstPage());
		$list->setCurrentPage(2);
		$this->assertTrue($list->NotFirstPage());
	}

	public function testNotLastPage() {
		$list = new PaginatedList(new ArrayList());
		$list->setTotalItems(50);

		$this->assertTrue($list->NotLastPage());
		$list->setCurrentPage(5);
		$this->assertFalse($list->NotLastPage());
	}

	public function testFirstItem() {
		$list = new PaginatedList(new ArrayList());
		$this->assertEquals(1, $list->FirstItem());
		$list->setPageStart(10);
		$this->assertEquals(11, $list->FirstItem());
	}

	public function testLastItem() {
		$list = new PaginatedList(new ArrayList());
		$list->setPageLength(10);
		$list->setTotalItems(25);

		$list->setCurrentPage(1);
		$this->assertEquals(10, $list->LastItem());
		$list->setCurrentPage(2);
		$this->assertEquals(20, $list->LastItem());
		$list->setCurrentPage(3);
		$this->assertEquals(25, $list->LastItem());

		// Disable paging
		$list->setPageLength(0);
		$this->assertEquals(25, $list->LastItem());
	}

	public function testFirstLink() {
		$list = new PaginatedList(new ArrayList());
		$this->assertContains('start=0', $list->FirstLink());
	}

	public function testLastLink() {
		$list = new PaginatedList(new ArrayList());
		$list->setPageLength(10);
		$list->setTotalItems(100);
		$this->assertContains('start=90', $list->LastLink());

		// Disable paging
		$list->setPageLength(0);
		$this->assertContains('start=0', $list->LastLink());
	}

	public function testNextLink() {
		$list = new PaginatedList(new ArrayList());
		$list->setTotalItems(50);

		$this->assertContains('start=10', $list->NextLink());
		$list->setCurrentPage(2);
		$this->assertContains('start=20', $list->NextLink());
		$list->setCurrentPage(3);
		$this->assertContains('start=30', $list->NextLink());
		$list->setCurrentPage(4);
		$this->assertContains('start=40', $list->NextLink());
		$list->setCurrentPage(5);
		$this->assertNull($list->NextLink());

		// Disable paging
		$list->setCurrentPage(1);
		$list->setPageLength(0);
		$this->assertNull($list->NextLink());
	}

	public function testPrevLink() {
		$list = new PaginatedList(new ArrayList());
		$list->setTotalItems(50);

		$this->assertNull($list->PrevLink());
		$list->setCurrentPage(2);
		$this->assertContains('start=0', $list->PrevLink());
		$list->setCurrentPage(3);
		$this->assertContains('start=10', $list->PrevLink());
		$list->setCurrentPage(5);
		$this->assertContains('start=30', $list->PrevLink());

		// Disable paging
		$list->setPageLength(0);
		$this->assertNull($list->PrevLink());
	}

}
