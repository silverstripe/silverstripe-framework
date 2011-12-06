<?php

/**
 * This is a Unittest class for GridState
 *
 * @package sapphire
 * @subpackage tests
 */
class GridStateTest extends SapphireTest {
	
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
	
	public function testToString() {
		$gridState = new GridState($this->gridField);
		$this->assertContains('"Sorting":{"Order":null}',$gridState->__toString());
	}
	
	public function testFromString() {
		$gridState = new GridState($this->gridField);
		$gridState->addAffector(new GridState_Bananas(), '{"Bananas":{"Color":"green"}}');
		$this->assertContains('"Bananas":{"Color":"green"}',$gridState->__toString());
	}
	
	public function testAddAffector() {
		$gridState = new GridState($this->gridField);
		$gridState->addAffector(new GridState_Bananas(), '{"Bananas":{"Color":"green"}}');
		$this->assertTrue(is_object($gridState->Bananas));
		$this->assertFalse(isset($gridState->Monkey));
	}
	
	public function testRemoveAffector() {
		$gridState = new GridState($this->gridField);
		$gridState->addAffector(new GridState_Bananas(), '{"Bananas":{"Color":"green"}}');
		$this->assertContains('"Bananas":{"Color":"green"}',$gridState->__toString());
		$gridState->removeAffector('Bananas');
		$this->assertNotContains('{"Bananas":{"Color":"green"}}',$gridState->__toString());
	}
	
	public function testToFormField() {
		$gridState = new GridState($this->gridField);
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