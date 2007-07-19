<?php

require_once('unit_tester.php');
require_once('reporter.php');

/**
 * UnitTesting is a wrapper around the "Simple Test" unit testing framework.
 */
class UnitTesting extends Controller {
	function index() {
		$test = &new GroupTest("SilverStripe Unit-testing Results for '" . project() . "'");
		$builtins = array('UnitTestCase', 'ShellTestCase', 'WebTestCase','SimpleTestCase');

		foreach(ClassInfo::subclassesFor("SimpleTestCase") as $testCase) {
			if(!in_array($testCase, $builtins)) {
				$test->addTestCase($cases[] = new $testCase());	
				
			}
		}

		$test->run(new HtmlReporter());
		
			echo "<h2>What's being tested?</h2>";
		foreach($cases as $testCase) {
			echo "<li style=\"color: " . ($testCase->testComplete?$testCase->testComplete:'orange') . "\"><b>" . get_class($testCase) . ":</b> ";
			echo ($testCase->whatsBeingTested?$testCase->whatsBeingTested:'unknown');
			echo "</li>";
		}
	}
	
}

?>