<?php

class GroupTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/security/GroupTest.yml';
	
	/**
	 * Test the Group::map() function
	 */
	function testGroupMap() {
		/* Group::map() returns an SQLMap object implementing iterator.  You can use foreach to get ID-Title pairs. */
		
		// We will iterate over the map and build mapOuput to more easily call assertions on the result.
		$map = Group::map();
		foreach($map as $k => $v) {
			$mapOutput[$k] = $v;
		}
		
		$group1 = $this->objFromFixture('Group', 'group1');
		$group2 = $this->objFromFixture('Group', 'group2');
		
		/* We have added 2 groups to our fixture.  They should both appear in $mapOutput. */
		$this->assertEquals($mapOutput[$group1->ID], $group1->Title);
		$this->assertEquals($mapOutput[$group2->ID], $group2->Title);
	}
}