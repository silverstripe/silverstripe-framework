<?php
/**
 * Light wrapper around {@link PHPUnit_Framework_TestSuite}
 * which allows to have {@link setUp()} and {@link tearDown()}
 * methods which are called just once per suite, not once per
 * test method in each suite/case.
 * 
 * @package sapphire
 * @subpackage testing
 */
class SapphireTestSuite extends PHPUnit_Framework_TestSuite {
	function setUp() {
		foreach($this->groups as $group) {
			// Assumption: All testcases in the group are the same, as defined in TestRunner->runTests()
			$class = get_class($group[0]);
			if(class_exists($class) && is_subclass_of($class, 'SapphireTest')) {
				eval("$class::set_up_once();");
			}
		}
	}
	
	function tearDown() {
		foreach($this->groups as $group) {
			$class = get_class($group[0]);
			// Assumption: All testcases in the group are the same, as defined in TestRunner->runTests()
			if(class_exists($class) && is_subclass_of($class, 'SapphireTest')) {
				eval("$class::tear_down_once();");
			}
		}
	}
}
?>