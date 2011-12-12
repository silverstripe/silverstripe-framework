<?php

/**
 * This is a Unittest class for GridState
 *
 * @package sapphire
 * @subpackage tests
 */
class GridStateTest extends SapphireTest {

	public function testToString() {
		$gridField = new GridField('GridField');
		$gridState = new GridState($gridField);
		$this->assertContains('"Bananas":{"Color":"yellow"}',$gridState->__toString());
	}
	
	public function testFromString() {
		$gridField = new GridField('GridField');
		$gridState = new GridState($gridField, '{"Bananas":{"Color":"green"}}');
		$this->assertContains('"Bananas":{"Color":"green"}',$gridState->__toString());
	}
	
	public function testIsset() {
		$gridField = new GridField('GridField');
		$gridState = new GridState($gridField, '{"Bananas":{"Color":"green"}}');//Debug::Dump(is_object($gridState->Bananas));die;
		$this->assertTrue(is_object($gridState->Bananas));
		$this->assertFalse(isset($gridState->Monkey));
	}
	
	/*public function testUnset() {
		$gridField = new GridField('GridField');
		$gridState = new GridState($gridField, '{"Bananas":true,"Page":2,"Title":"Tower"}');
		unset($gridState->Bananas);
		$this->assertFalse(isset($gridState->Bananas));
		$this->assertEquals('{"Page":2,"Title":"Tower"}',$gridState->__toString());
	}*/
	
	public function testToFormField() {
		$gridField = new GridField('GridField');
		$gridState = new GridState($gridField, '{"Bananas":{"Color":"green"}}');
		$this->assertContains('name="GridState"', $gridState->Field());
		$this->assertContains('Bananas&quot;:{&quot;Color&quot;:&quot;green&quot;}', $gridState->Field());
	}
	
	public function testSetArray() {
		$gridField = new GridField('GridField');
		$gridState = new GridState($gridField, 'GridState');
		$gridState->setValue('{"Bananas":{"Color":"blue"}}');
		$this->assertContains('"Bananas":{"Color":"blue"}', $gridState->__toString());
	}
}

class GridState_Bananas extends GridState_Affector implements TestOnly{
	static $name = 'Bananas';

	protected $Color = 'yellow';

	function getState(&$state) {
		$state->Bananas = new stdClass();

		$state->Bananas->Color = $this->Color;
	}
	
	function apply(){
		
	}
	function setState($data){
		if ($data && isset($data->Bananas)) {
			$this->Color = $data->Bananas->Color;
		}
	}
}