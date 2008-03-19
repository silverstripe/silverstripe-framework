<?php

/**
 * @package sapphire
 * @subpackage testing
 */

// Check that PHPUnit is installed
function hasPhpUnit() {
	$paths = explode(PATH_SEPARATOR, ini_get('include_path'));
	foreach($paths as $path) {
		if(@file_exists("$path/PHPUnit/Framework.php")) return true;
	}
	return false;
}

/**
 */
if(hasPhpUnit()) {
require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';
}

/**
 * Controller that executes PHPUnit tests
 * @package sapphire
 * @subpackage testing
 */
class TestRunner extends Controller {
	/**
	 * Run all test classes
	 */
	function index() {
		if(hasPhpUnit()) {
			$tests = ClassInfo::subclassesFor('SapphireTest');
			array_shift($tests);
		
			$this->runTests($tests);
		} else {
			echo "Please install PHPUnit using pear";
		}
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

// This class is here to help with documentation.
if(!hasPhpUnit()) {
/**
 * PHPUnit is a testing framework that can be installed using PEAR.
 * It's not bundled with Sapphire, you will need to install it yourself.
 * 
 * @package sapphire
 * @subpackage testing
 */
class PHPUnit_Framework_TestCase {
	
}
}