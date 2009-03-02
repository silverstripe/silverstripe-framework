<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class DataObjectSetTest extends SapphireTest {

	function testIterator() {
		$set = new DataObjectSet(array(
			$one = new DataObject(array('Title'=>'one')),
			$two = new DataObject(array('Title'=>'two')),
			$three = new DataObject(array('Title'=>'three')),
			$four = new DataObject(array('Title'=>'four'))
		));
		
		// test Pos() with foreach()
		$i = 0;
		foreach($set as $item) {
			$i++;
			$this->assertEquals($i, $item->Pos(), "Iterator position is set correctly on ViewableData when iterated with foreach()");
		}
		
		// test Pos() manually
		$this->assertEquals(1, $one->Pos());
		$this->assertEquals(2, $two->Pos());
		$this->assertEquals(3, $three->Pos());
		$this->assertEquals(4, $four->Pos());
		
		// test DataObjectSet->Count()
		$this->assertEquals(4, $set->Count());
		
		// test DataObjectSet->First()
		$this->assertSame($one, $set->First());
		
		// test DataObjectSet->Last()
		$this->assertSame($four, $set->Last());
		
		// test ViewableData->First()
		$this->assertTrue($one->First());
		$this->assertFalse($two->First());
		$this->assertFalse($three->First());
		$this->assertFalse($four->First());
		
		// test ViewableData->Last()
		$this->assertFalse($one->Last());
		$this->assertFalse($two->Last());
		$this->assertFalse($three->Last());
		$this->assertTrue($four->Last());
		
		// test ViewableData->Middle()
		$this->assertFalse($one->Middle());
		$this->assertTrue($two->Middle());
		$this->assertTrue($three->Middle());
		$this->assertFalse($four->Middle());
		
		// test ViewableData->Even()
		$this->assertFalse($one->Even());
		$this->assertTrue($two->Even());
		$this->assertFalse($three->Even());
		$this->assertTrue($four->Even());
		
		// test ViewableData->Odd()
		$this->assertTrue($one->Odd());
		$this->assertFalse($two->Odd());
		$this->assertTrue($three->Odd());
		$this->assertFalse($four->Odd());
	}
	
}
?>