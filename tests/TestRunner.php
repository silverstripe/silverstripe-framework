<?php

// Check that PHPUnit is installed
$hasPhpUnit = false;
$paths = explode(PATH_SEPARATOR, ini_get('include_path'));
foreach($paths as $path) {
	if(@file_exists("$path/PHPUnit/Framework.php")) $hasPhpUnit = true;
}

if($hasPhpUnit) {

require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

/**
 * Controller that executes PHPUnit tests
 */
class TestRunner extends Controller {
	/**
	 * Run all test classes
	 */
	function index() {
		$tests = ClassInfo::subclassesFor('SapphireTest');
		array_shift($tests);
		
		$this->runTests($tests);
	}
		
	/**
	 * Run only a single test class
	 */
	function only() {
		$className = $this->urlParams['ID'];
		if(class_exists($className)) {
			$this->runTests(array($className));
		} else {
			echo "Class '$className' not found";
			
		}
		
	}

	function runTests($classList) {
		echo "<h1>Sapphire PHPUnit Test Runner</h1>";
		echo "<p>Using the following subclasses of SapphireTest for testing: " . implode(", ", $classList) . "</p>";
		
		echo "<pre>";
		$suite = new PHPUnit_Framework_TestSuite();
		foreach($classList as $className) {
			// Ensure that the autoloader pulls in the test class, as PHPUnit won't know how to do this.
			class_exists($className);
			$suite->addTest(new PHPUnit_Framework_TestSuite($className));
		}

		/*, array("reportDirectory" => "/Users/sminnee/phpunit-report")*/
		PHPUnit_TextUI_TestRunner::run($suite);
	}
}

} else {

class TestRunner extends Controller {
	function index() {
		echo "Please install PHPUnit using pear.";
	}
}

}