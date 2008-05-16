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
 * Controller that executes PHPUnit tests.
 * 
 * @package sapphire
 * @subpackage testing
 */
class TestRunner extends Controller {
	/** @ignore */
	private static $default_reporter;
	
	/**
	 * Override the default reporter with a custom configured subclass.
	 *
	 * @param string $reporter
	 */
	static function set_reporter($reporter) {
		if (is_string($reporter)) $reporter = new $reporter;
		self::$default_reporter = $reporter;
	}
	
	function init() {
		parent::init();
		if (!self::$default_reporter) self::set_reporter('DebugReporter');
	}
	
	/**
	 * Run all test classes
	 */
	function index() {
		if(hasPhpUnit()) {
			$tests = ClassInfo::subclassesFor('SapphireTest');
			array_shift($tests);
			unset($tests['FunctionalTest']);
		
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
		if(!Director::is_cli()) {
			self::$default_reporter->writeHeader();
			echo '<div class="info">';
			echo "<h1>Sapphire PHPUnit Test Runner</h1>";
			echo "<p>Using the following subclasses of SapphireTest for testing: " . implode(", ", $classList) . "</p>";
			echo "</div>";
			echo '<div class="trace">';
			echo "<pre>";
		} else {
			echo "Sapphire PHPUnit Test Runner\n";
			echo "Using the following subclasses of SapphireTest for testing: " . implode(", ", $classList) . "\n\n";
		}
		
		// Remove our error handler so that PHP can use its own
		//restore_error_handler();	
		
		$suite = new PHPUnit_Framework_TestSuite();
		foreach($classList as $className) {
			// Ensure that the autoloader pulls in the test class, as PHPUnit won't know how to do this.
			class_exists($className);
			$suite->addTest(new PHPUnit_Framework_TestSuite($className));
		}

		/*, array("reportDirectory" => "/Users/sminnee/phpunit-report")*/
		$testResult = PHPUnit_TextUI_TestRunner::run($suite);
		
		if(!Director::is_cli()) echo '</div>';
		
		// Put the error handlers back
		Debug::loadErrorHandlers();
		
		if(!Director::is_cli()) self::$default_reporter->writeFooter();
		// Todo: we should figure out how to pass this data back through Director more cleanly
		if(Director::is_cli() && ($testResult->failureCount() + $testResult->errorCount()) > 0) exit(2);
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