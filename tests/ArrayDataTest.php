<?php

class ArrayDataTest extends SapphireTest {
	
	function testViewabledataItemsInsideArraydataArePreserved() {
		/* ViewableData objects will be preserved, but other objects will be converted */
		$arrayData = new ArrayData(array(
			"A" => new Varchar("A"),
			"B" => new stdClass(),
		));
		$this->assertEquals("Varchar", get_class($arrayData->A));
		$this->assertEquals("ArrayData", get_class($arrayData->B));
	}
}
