<?php

class HasManyListTest extends SapphireTest {
	
	// Borrow the model from DataObjectTest
	public static $fixture_file = 'DataObjectTest.yml';

	protected $extraDataObjects = array(
		'DataObjectTest_Team',
		'DataObjectTest_SubTeam',
		'DataObjectTest_Player',
	);
	
	public function testRelationshipEmptyOnNewRecords() {
		// Relies on the fact that (unrelated) comments exist in the fixture file already
		$newTeam = new DataObjectTest_Team(); // has_many Comments
		$this->assertEquals(array(), $newTeam->Comments()->column('ID'));
	}

}
