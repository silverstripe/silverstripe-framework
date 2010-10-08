<?php
/**
 * @package sapphire
 * @subpackage testing
 */

/**
 * Controller that executes PHPUnit tests.
 *
 * <h2>URL Options</h2>
 * - SkipTests: A comma-separated list of test classes to skip (useful when running dev/tests/all)
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
		'startsession' => 'startsession',
		'endsession' => 'endsession',
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
		ManifestBuilder::load_test_manifest();
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
		self::$default_reporter->writeInfo('Available Tests', false);
		if(Director::is_cli()) {
			$tests = ClassInfo::subclassesFor('SapphireTest');
			$relativeLink = Director::makeRelative($this->Link());
			echo "sake {$relativeLink}all: Run all " . count($tests) . " tests\n";
			echo "sake {$relativeLink}coverage: Runs all tests and make test coverage report\n";
			foreach ($tests as $test) {
				echo "sake {$relativeLink}$test: Run $test\n";
			}
		} else {
			echo '<div class="trace">';
			$tests = ClassInfo::subclassesFor('SapphireTest');
			asort($tests);
			echo "<h3><a href=\"" . $this->Link() . "all\">Run all " . count($tests) . " tests</a></h3>";
			echo "<h3><a href=\"" . $this->Link() . "coverage\">Runs all tests and make test coverage report</a></h3>";
			echo "<hr />";
			foreach ($tests as $test) {
				echo "<h3><a href=\"" . $this->Link() . "$test\">Run $test</a></h3>";
			}
			echo '</div>';
		}
		
		self::$default_reporter->writeFooter();
	}
	
	function coverage() {
		if(!PhpUnitWrapper::hasPhpUnit()) {
			ManifestBuilder::load_all_classes();
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
			if(!is_subclass_of($className, 'SapphireTest')) {
				user_error("TestRunner::only(): Invalid TestCase '$className', cannot find matching class", E_USER_ERROR);
			}
			$this->runTests(array($className));
		} else {
			if ($className == 'all') $this->all();
		}
	}

	/**
	 * @param array $classList
	 * @param boolean $coverage
	 */
	function runTests($classList, $coverage = false) {
		$startTime = microtime(true);
		
		// XDEBUG seem to cause problems with test execution :-(
		if(function_exists('xdebug_disable')) xdebug_disable();
		
		ini_set('max_execution_time', 0);		
		
		$this->setUp();
		
		// Optionally skip certain tests
		$skipTests = array();
		if($this->request->getVar('SkipTests')) {
			$skipTests = explode(',', $this->request->getVar('SkipTests'));
		}
		$classList = array_diff($classList, $skipTests);
		
		// run tests before outputting anything to the client
		$suite = new PHPUnit_Framework_TestSuite();
		natcasesort($classList);
		foreach($classList as $className) {
			// Ensure that the autoloader pulls in the test class, as PHPUnit won't know how to do this.
			class_exists($className);
			$suite->addTest(new SapphireTestSuite($className));
		}

		// Remove the error handler so that PHPUnit can add its own
		restore_error_handler();


		self::$default_reporter->writeHeader("Sapphire Test Runner");
		if (count($classList) > 1) { 
			self::$default_reporter->writeInfo("All Tests", "Running test cases: ",implode(", ", $classList));
		} else
		if (count($classList) == 1) { 
			self::$default_reporter->writeInfo($classList[0], "");
		} else {
			// border case: no tests are available. 
			self::$default_reporter->writeInfo("", "");
		}

		// perform unit tests (use PhpUnitWrapper or derived versions)
		$phpunitwrapper = PhpUnitWrapper::inst();
		$phpunitwrapper->setSuite($suite);
		$phpunitwrapper->setCoverageStatus($coverage);

		$phpunitwrapper->runTests();

		// get results of the PhpUnitWrapper class
		$reporter = $phpunitwrapper->getReporter();
		$results = $phpunitwrapper->getFrameworkTestResults();
		
		if(!Director::is_cli()) echo '<div class="trace">';
		$reporter->writeResults();

		$endTime = microtime(true);
		if(Director::is_cli()) echo "\n\nTotal time: " . round($endTime-$startTime,3) . " seconds\n";
		else echo "<p>Total time: " . round($endTime-$startTime,3) . " seconds</p>\n";
		
		if(!Director::is_cli()) echo '</div>';
		
		// Put the error handlers back
		Debug::loadErrorHandlers();
		
		if(!Director::is_cli()) self::$default_reporter->writeFooter();
		
		$this->tearDown();
		
		// Todo: we should figure out how to pass this data back through Director more cleanly
		if(Director::is_cli() && ($results->failureCount() + $results->errorCount()) > 0) exit(2);
	}
	
	/**
	 * Start a test session.
	 * Usage: visit dev/tests/startsession?fixture=(fixturefile).  A test database will be constructed, and your browser session will be amended
	 * to use this database.  This can only be run on dev and test sites.
	 */
	function startsession() {
		if(!Director::isLive()) {
			if(SapphireTest::using_temp_db()) {
				$endLink = Director::baseURL() . "/dev/tests/endsession";
				return "<p><a id=\"end-session\" href=\"$endLink\">You're in the middle of a test session; click here to end it.</a></p>";
			
			} else if(!isset($_GET['fixture'])) {
				$me = Director::baseURL() . "/dev/tests/startsession";
				return <<<HTML
<form action="$me">				
	<p>Enter a fixture file name to start a new test session.  Don't forget to visit dev/tests/endsession when you're done!</p>
	<p>Fixture file (leave blank to start with default set-up): <input id="fixture-file" name="fixture" /></p>
	<input type="hidden" name="flush" value="1">
	<p><input id="start-session" value="Start test session" type="submit" /></p>
</form>
HTML;
			} else {
				$fixtureFile = $_GET['fixture'];
				
				if($fixtureFile) {
					// Validate fixture file
					$realFile = realpath('../' . $fixtureFile);
					$baseDir = realpath(Director::baseFolder());
					if(!$realFile || !file_exists($realFile)) {
						return "<p>Fixture file doesn't exist</p>";
					} else if(substr($realFile,0,strlen($baseDir)) != $baseDir) {
						return "<p>Fixture file must be inside $baseDir</p>";
					} else if(substr($realFile,-4) != '.yml') {
						return "<p>Fixture file must be a .yml file</p>";
					} else if(!preg_match('/^([^\/.][^\/]+)\/tests\//', $fixtureFile)) {
						return "<p>Fixture file must be inside the tests subfolder of one of your modules.</p>";
					}
				}

				$dbname = SapphireTest::create_temp_db();

				DB::set_alternative_database_name($dbname);
				
				// Fixture
				if($fixtureFile) {
					$fixture = new YamlFixture($fixtureFile);
					$fixture->saveIntoDatabase();
					
				// If no fixture, then use defaults
				} else {
					$dataClasses = ClassInfo::subclassesFor('DataObject');
					array_shift($dataClasses);
					foreach($dataClasses as $dataClass) singleton($dataClass)->requireDefaultRecords();
				}
				
				return "<p>Started testing session with fixture '$fixtureFile'.  Time to start testing; where would you like to start?</p>
					<ul>
						<li><a id=\"home-link\" href=\"" .Director::baseURL() . "\">Homepage - published site</a></li>
						<li><a id=\"draft-link\" href=\"" .Director::baseURL() . "?stage=Stage\">Homepage - draft site</a></li>
						<li><a id=\"admin-link\" href=\"" .Director::baseURL() . "admin/\">CMS Admin</a></li>
						<li><a id=\"endsession-link\" href=\"" .Director::baseURL() . "dev/tests/endsession\">End your test session</a></li>
					</ul>";
			}
						
		} else {
			return "<p>startession can only be used on dev and test sites</p>";
		}
	}
	
	function endsession() {
		SapphireTest::kill_temp_db();
		DB::set_alternative_database_name(null);

		return "<p>Test session ended.</p>
			<ul>
				<li><a id=\"home-link\" href=\"" .Director::baseURL() . "\">Return to your site</a></li>
				<li><a id=\"startsession-link\" href=\"" .Director::baseURL() . "dev/tests/startsession\">Start a new test session</a></li>
			</ul>";
	}
	
	function setUp() {
		SapphireTest::create_temp_db();
		SSViewer::flush_template_cache();
	}
	
	function tearDown() {
		SapphireTest::kill_temp_db();
		DB::set_alternative_database_name(null);
	}
}

}