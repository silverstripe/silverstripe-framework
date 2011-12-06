<?php

/**
 * This is a Unittest class for GridState
 *
 * @package sapphire
 * @subpackage tests
 */
class GridStateTest extends SapphireTest {

	public function testToString() {
		$gridState = new GridState();
		$gridState->Bananas = true;
		$gridState->Page = 2;
		$gridState->Title = "Tower";
		$this->assertEquals('{"Bananas":true,"Page":2,"Title":"Tower"}',$gridState->__toString());
	}
	
	public function testFromString() {
		$gridState = new GridState('{"Bananas":true,"Page":2,"Title":"Tower"}');
		$this->assertEquals('{"Bananas":true,"Page":2,"Title":"Tower"}',$gridState->__toString());
	}
	
	public function testIsset() {
		$gridState = new GridState('{"Bananas":true,"Page":2,"Title":"Tower"}');
		$this->assertTrue(isset($gridState->Bananas));
		$this->assertFalse(isset($gridState->Monkey));
	}
	
	public function testUnset() {
		$gridState = new GridState('{"Bananas":true,"Page":2,"Title":"Tower"}');
		unset($gridState->Bananas);
		$this->assertFalse(isset($gridState->Bananas));
		$this->assertEquals('{"Page":2,"Title":"Tower"}',$gridState->__toString());
	}
	
	public function testToFormField() {
		$gridState = new GridState('{"Bananas":true,"Page":2,"Title":"Tower"}');
		$this->assertEquals('<input class="hidden" type="hidden" id="Title" name="Title" value="'.
		'{&quot;Bananas&quot;:true,&quot;Page&quot;:2,&quot;Title&quot;:&quot;Tower&quot;}" />',
		$gridState->Field());
	}
}