<?php

require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

/**
 * Controller that executes PHPUnit tests
 */
class TestRunner extends Controller {
	function index() {
		ManifestBuilder::includeEverything();
	
		$tests = ClassInfo::subclassesFor('SapphireTest');
		array_shift($tests);
		
		echo "<h1>Sapphire PHPUnit Test Runner</h1>";
		echo "<p>Discovered the following subclasses of SapphireTest for testing: " . implode(", ", $tests) . "</p>";
		
		echo "<pre>";
		$suite = new PHPUnit_Framework_TestSuite();
		foreach($tests as $test) {
			$suite->addTest(new PHPUnit_Framework_TestSuite($test));
		}

		PHPUnit_TextUI_TestRunner::run($suite);
	}
}