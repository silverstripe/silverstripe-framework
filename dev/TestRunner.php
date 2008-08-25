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
require_once 'PHPUnit/Util/Report.php';
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
	
	static $url_handlers = array(
		'' => 'browse',
		'coverage' => 'coverage',
		'$TestCase' => 'only',
	);
	
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
		if (!self::$default_reporter) self::set_reporter(Director::is_cli() ? 'CliDebugView' : 'DebugView');
	}
	
	public function Link() {
		return Controller::join_links(Director::absoluteBaseURL(), 'dev/tests/');
	}
	
	/**
	 * Run all test classes
	 */
	function all() {
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
	 * Browse all enabled test cases in the environment
	 */
	function browse() {
		self::$default_reporter->writeHeader();
		echo '<div class="info">';
		echo '<h1>Available Tests</h1>';
		echo '</div>';
		echo '<div class="trace">';
		$tests = ClassInfo::subclassesFor('SapphireTest');
		echo "<h3><a href=\"" . $this->Link() . "all\">Run all " . count($tests) . " tests</a></h3>";
		echo "<h3><a href=\"" . $this->Link() . "coverage\">Runs all tests and make test coverage report</a></h3>";
		echo "<hr />";
		foreach ($tests as $test) {
			echo "<h3><a href=\"" . $this->Link() . "$test\">Run $test</a></h3>";
		}
		echo '</div>';
		self::$default_reporter->writeFooter();
	}
	
	function coverage() {
		if(hasPhpUnit()) {
			ManifestBuilder::includeEverything();
			$tests = ClassInfo::subclassesFor('SapphireTest');
			array_shift($tests);
			unset($tests['FunctionalTest']);
		
			$this->runTests($tests, true);
		} else {
			echo "Please install PHPUnit using pear";
		}
	}
		
	/**
	 * Run only a single test class
	 */
	function only($request) {
		$className = $request->param('TestCase');
		if(class_exists($className)) {
			$this->runTests(array($className));
		} else {
			if ($className == 'all') $this->all();
		}
	}

	function runTests($classList, $coverage = false) {
		$this->setUp();
		
		// run tests before outputting anything to the client
		$suite = new PHPUnit_Framework_TestSuite();
		foreach($classList as $className) {
			// Ensure that the autoloader pulls in the test class, as PHPUnit won't know how to do this.
			class_exists($className);
			$suite->addTest(new PHPUnit_Framework_TestSuite($className));
		}

		// Remove the error handler so that PHPUnit can add its own
		restore_error_handler();

		/*, array("reportDirectory" => "/Users/sminnee/phpunit-report")*/
		if(Director::is_cli()) $reporter = new CliTestReporter();
		else $reporter = new SapphireTestReporter();

		self::$default_reporter->writeHeader("Sapphire Test Runner");
		if (count($classList) > 1) { 
			self::$default_reporter->writeInfo("All Tests", "Running test cases: " . implode(", ", $classList));
		} else {
			self::$default_reporter->writeInfo($classList[0], "");
		}
		
		$results = new PHPUnit_Framework_TestResult();		
		$results->addListener($reporter);

		if($coverage) {
			$results->collectCodeCoverageInformation(true);
			$suite->run($results);

			if(!file_exists('../assets/coverage-report')) mkdir('../assets/coverage-report');
			PHPUnit_Util_Report::render($results, '../assets/coverage-report/');
			$coverageApp = Director::baseURL() . 'assets/coverage-report/' . preg_replace('/[^A-Za-z0-9]/','_',preg_replace('/(\/$)|(^\/)/','',Director::baseFolder())) . '.html';
			$coverageTemplates = Director::baseURL() . 'assets/coverage-report/' . preg_replace('/[^A-Za-z0-9]/','_',preg_replace('/(\/$)|(^\/)/','',realpath(TEMP_FOLDER))) . '.html';
			echo "<p>Coverage reports available here:<ul>
				<li><a href=\"$coverageApp\">Coverage report of the application</a></li>
				<li><a href=\"$coverageTemplates\">Coverage report of the templates</a></li>
			</ul>";
		} else {
			$suite->run($results);
		}
		
		echo '<div class="trace">';
		$reporter->writeResults();
		
		if(!Director::is_cli()) echo '</div>';
		
		// Put the error handlers back
		Debug::loadErrorHandlers();
		
		if(!Director::is_cli()) self::$default_reporter->writeFooter();
		
		$this->tearDown();
		
		// Todo: we should figure out how to pass this data back through Director more cleanly
		if(Director::is_cli() && ($results->failureCount() + $results->errorCount()) > 0) exit(2);
	}
	
	function setUp() {
		SapphireTest::create_temp_db();
	}
	
	function tearDown() {
		SapphireTest::kill_temp_db();
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