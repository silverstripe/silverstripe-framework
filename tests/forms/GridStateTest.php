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
		$this->assertContains('"Sorting":{"Order":null}',$gridState->__toString());
	}
	
	public function testFromString() {
		$gridField = new GridField('GridField');
		$gridState = new GridState($gridField);
		$gridState->addAffector(new GridState_Bananas(), '{"Bananas":{"Color":"green"}}');
		$this->assertContains('"Bananas":{"Color":"green"}',$gridState->__toString());
	}
	
	public function testAddAffector() {
		$gridField = new GridField('GridField');
		$gridState = new GridState($gridField);
		$gridState->addAffector(new GridState_Bananas(), '{"Bananas":{"Color":"green"}}');
		$this->assertTrue(is_object($gridState->Bananas));
		$this->assertFalse(isset($gridState->Monkey));
	}
	
	public function testRemoveAffector() {
		$gridField = new GridField('GridField');
		$gridState = new GridState($gridField);
		$gridState->addAffector(new GridState_Bananas(), '{"Bananas":{"Color":"green"}}');
		$this->assertContains('"Bananas":{"Color":"green"}',$gridState->__toString());
		$gridState->removeAffector('Bananas');
		$this->assertNotContains('{"Bananas":{"Color":"green"}}',$gridState->__toString());
	}
	
	public function testToFormField() {
		$gridField = new GridField('GridField');
		$gridState = new GridState($gridField);
		$gridState->addAffector(new GridState_Bananas(), '{"Bananas":{"Color":"green"}}');
		$this->assertContains('name="GridState"', $gridState->Field());
		$this->assertContains('Bananas&quot;:{&quot;Color&quot;:&quot;green&quot;}', $gridState->Field());
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