<?php
/**
 * @package framework
 * @subpackage testing
 */

/**
 * Controller that executes PHPUnit tests.
 * 
 * Alternatively, you can also use the "phpunit" binary directly by
 * pointing it to a file or folder containing unit tests.
 * See phpunit.dist.xml in the webroot for configuration details.
 *
 * <h2>URL Options</h2>
 * - SkipTests: A comma-separated list of test classes to skip (useful when running dev/tests/all)
 * 
 * See {@link browse()} output for generic usage instructions.
 * 
 * @package framework
 * @subpackage testing
 */
class TestRunner extends Controller {

	/** @ignore */
	private static $default_reporter;
	
	static $url_handlers = array(
		'' => 'browse',
		'coverage/module/$ModuleName' => 'coverageModule',
		'coverage/$TestCase!' => 'coverageOnly',
		'coverage' => 'coverageAll',
		'sessionloadyml' => 'sessionloadyml',
		'startsession' => 'startsession',
		'endsession' => 'endsession',
		'setdb' => 'setdb',
		'cleanupdb' => 'cleanupdb',
		'emptydb' => 'emptydb',
		'module/$ModuleName' => 'module',
		'all' => 'all',
		'build' => 'build',
		'$TestCase' => 'only'
	);
	
	static $allowed_actions = array(
		'index',
		'browse',
		'coverage',
		'coverageAll',
		'coverageModule',
		'coverageOnly',
		'startsession',
		'endsession',
		'setdb',
		'cleanupdb',
		'module',
		'all',
		'build',
		'only'
	);
	
	/**
	 * @var Array Blacklist certain directories for the coverage report.
	 * Filepaths are relative to the webroot, without leading slash.
	 * 
	 * @see http://www.phpunit.de/manual/current/en/appendixes.configuration.html#appendixes.configuration.blacklist-whitelist
	 */
	static $coverage_filter_dirs = array(
		'*/thirdparty',
		'*/tests',
		'*/lang',
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

	/**
	 * Pushes a class and template manifest instance that include tests onto the
	 * top of the loader stacks.
	 */
	public static function use_test_manifest() {
		$classManifest = new SS_ClassManifest(
			BASE_PATH, true, isset($_GET['flush'])
		);
		
		SS_ClassLoader::instance()->pushManifest($classManifest);
		SapphireTest::set_test_class_manifest($classManifest);

		SS_TemplateLoader::instance()->pushManifest(new SS_TemplateManifest(
			BASE_PATH, true, isset($_GET['flush'])
		));
	}

	function init() {
		parent::init();
		
		$canAccess = (Director::isDev() || Director::is_cli() || Permission::check("ADMIN"));
		if(!$canAccess) return Security::permissionFailure($this);
		
		if (!self::$default_reporter) self::set_reporter(Director::is_cli() ? 'CliDebugView' : 'DebugView');
		
		if(!PhpUnitWrapper::has_php_unit()) {
			die("Please install PHPUnit using pear");
		}

		if(!isset($_GET['flush']) || !$_GET['flush']) {
			Debug::message(
				"WARNING: Manifest not flushed. " .
				"Add flush=1 as an argument to discover new classes or files.\n",
				false
			);
		}
	}
	
	public function Link() {
		return Controller::join_links(Director::absoluteBaseURL(), 'dev/tests/');
	}
	
	/**
	 * Run test classes that should be run with every commit.
	 * Currently excludes PhpSyntaxTest
	 */
	function all($request, $coverage = false) {
		self::use_test_manifest();
		$tests = ClassInfo::subclassesFor('SapphireTest');
		array_shift($tests);
		unset($tests['FunctionalTest']);
		
		// Remove tests that don't need to be executed every time
		unset($tests['PhpSyntaxTest']);
		
		foreach($tests as $class => $v) {
			$reflection = new ReflectionClass($class);
			if(!$reflection->isInstantiable()) unset($tests[$class]);
		}

		$this->runTests($tests, $coverage);
	}
	
	/**
	 * Run test classes that should be run before build - i.e., everything possible.
	 */
	function build() {
		self::use_test_manifest();
		$tests = ClassInfo::subclassesFor('SapphireTest');
		array_shift($tests);
		unset($tests['FunctionalTest']);
		foreach($tests as $class => $v) {
			$reflection = new ReflectionClass($class);
			if(!$reflection->isInstantiable()) unset($tests[$class]);
		}
	
		$this->runTests($tests);
	}
	
	/**
	 * Browse all enabled test cases in the environment
	 */
	function browse() {
		self::use_test_manifest();
		self::$default_reporter->writeHeader();
		self::$default_reporter->writeInfo('Available Tests', false);
		if(Director::is_cli()) {
			$tests = ClassInfo::subclassesFor('SapphireTest');
			$relativeLink = Director::makeRelative($this->Link());
			echo "sake {$relativeLink}all: Run all " . count($tests) . " tests\n";
			echo "sake {$relativeLink}coverage: Runs all tests and make test coverage report\n";
			echo "sake {$relativeLink}module/<modulename>: Runs all tests in a module folder\n";
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
	
	/**
	 * Run a coverage test across all modules
	 */
	function coverageAll($request) {
		self::use_test_manifest();
		$this->all($request, true);
	}

	/**
	 * Run only a single coverage test class or a comma-separated list of tests
	 */
	function coverageOnly($request) {
		$this->only($request, true);
	}
	
	/**
	 * Run coverage tests for one or more "modules".
	 * A module is generally a toplevel folder, e.g. "mysite" or "framework".
	 */
	function coverageModule($request) {
		$this->module($request, true);
	}
	
	function cleanupdb() {
		SapphireTest::delete_all_temp_dbs();
	}
		
	/**
	 * Run only a single test class or a comma-separated list of tests
	 */
	function only($request, $coverage = false) {
		self::use_test_manifest();
		if($request->param('TestCase') == 'all') {
			$this->all();
		} else {
			$classNames = explode(',', $request->param('TestCase'));
			foreach($classNames as $className) {
				if(!class_exists($className) || !is_subclass_of($className, 'SapphireTest')) {
					user_error("TestRunner::only(): Invalid TestCase '$className', cannot find matching class", E_USER_ERROR);
				}
			}
			
			$this->runTests($classNames, $coverage);
		}
	}
	
	/**
	 * Run tests for one or more "modules".
	 * A module is generally a toplevel folder, e.g. "mysite" or "framework".
	 */
	function module($request, $coverage = false) {
		self::use_test_manifest();
		$classNames = array();
		$moduleNames = explode(',', $request->param('ModuleName'));
		
		$ignored = array('functionaltest', 'phpsyntaxtest');

		foreach($moduleNames as $moduleName) {
			$classesForModule = ClassInfo::classes_for_folder($moduleName);
			
			if($classesForModule) {
				foreach($classesForModule as $className) {
					if(class_exists($className) && is_subclass_of($className, 'SapphireTest')) {
						if(!in_array($className, $ignored))
							$classNames[] = $className;
					}
				}
			}
		}

		$this->runTests($classNames, $coverage);
	}

	/**
	 * @param array $classList
	 * @param boolean $coverage
	 */
	function runTests($classList, $coverage = false) {
		$startTime = microtime(true);

		// disable xdebug, as it messes up test execution
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

		self::$default_reporter->writeHeader("SilverStripe Test Runner");
		if (count($classList) > 1) {
			self::$default_reporter->writeInfo("All Tests", "Running test cases: ",implode(", ", $classList));
		} elseif (count($classList) == 1) {
			self::$default_reporter->writeInfo($classList[0], '');
		} else {
			// border case: no tests are available.
			self::$default_reporter->writeInfo('', '');
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
		else echo "<p class=\"total-time\">Total time: " . round($endTime-$startTime,3) . " seconds</p>\n";
		
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
	 *
	 * See {@link setdb()} for an alternative approach which just sets a database
	 * name, and is used for more advanced use cases like interacting with test databases
	 * directly during functional tests.
	 */
	function startsession() {
		if(!Director::isLive()) {
			if(SapphireTest::using_temp_db()) {
				$endLink = Director::baseURL() . "dev/tests/endsession";
				return "<p><a id=\"end-session\" href=\"$endLink\">You're in the middle of a test session; click here to end it.</a></p>";
			
			} else if(!isset($_GET['fixture'])) {
				$me = Director::baseURL() . "dev/tests/startsession";
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
					$realFile = realpath(BASE_PATH.'/'.$fixtureFile);
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

	/**
	 * Set an alternative database name in the current browser session.
	 * Useful for functional testing libraries like behat to create a "clean slate". 
	 * Does not actually create the database, that's usually handled
	 * by {@link SapphireTest::create_temp_db()}.
	 *
	 * The database names are limited to a specific naming convention as a security measure:
	 * The "tmpdb" prefix and a random sequence of seven digits.
	 * This avoids the user gaining access to other production databases 
	 * available on the same connection.
	 *
	 * See {@link startsession()} for a different approach which actually creates
	 * the DB and loads a fixture file instead.
	 */
	function setdb() {
		if(Director::isLive()) {
			return $this->permissionFailure("dev/tests/setdb can only be used on dev and test sites");
		}
		
		if(!isset($_GET['database'])) {
			return $this->permissionFailure("dev/tests/setdb must be used with a 'database' parameter");
		}
		
		$database_name = $_GET['database'];
		$prefix = defined('SS_DATABASE_PREFIX') ? SS_DATABASE_PREFIX : 'ss_';
		$pattern = strtolower(sprintf('#^%stmpdb\d{7}#', $prefix));
		if(!preg_match($pattern, $database_name)) {
			return $this->permissionFailure("Invalid database name format");
		}

		DB::set_alternative_database_name($database_name);

		return "<p>Set database session to '$database_name'.  Time to start testing; where would you like to start?</p>
			<ul>
				<li><a id=\"home-link\" href=\"" .Director::baseURL() . "\">Homepage - published site</a></li>
				<li><a id=\"draft-link\" href=\"" .Director::baseURL() . "?stage=Stage\">Homepage - draft site</a></li>
				<li><a id=\"admin-link\" href=\"" .Director::baseURL() . "admin/\">CMS Admin</a></li>
				<li><a id=\"endsession-link\" href=\"" .Director::baseURL() . "dev/tests/endsession\">End your test session</a></li>
			</ul>";
	}
	
	function emptydb() {
		if(SapphireTest::using_temp_db()) {
			SapphireTest::empty_temp_db();

			if(isset($_GET['fixture']) && ($fixtureFile = $_GET['fixture'])) {
				$fixture = new YamlFixture($fixtureFile);
				$fixture->saveIntoDatabase();
				return "<p>Re-test the test database with fixture '$fixtureFile'.  Time to start testing; where would you like to start?</p>";

			} else {
				return "<p>Re-test the test database.  Time to start testing; where would you like to start?</p>";
			}
			
		} else {
			return "<p>dev/tests/emptydb can only be used with a temporary database. Perhaps you should use dev/tests/startsession first?</p>";
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

	function sessionloadyml() {
		// Load incremental YAML fixtures
		// TODO: We will probably have to filter out the admin member here,
		// as it is supplied by Bare.yml
		if(Director::isLive()) {
			return "<p>sessionloadyml can only be used on dev and test sites</p>";
		}
		if (!SapphireTest::using_temp_db()) {
			return "<p>Please load /dev/tests/startsession first</p>";
		}

		$fixtureFile = isset($_GET['fixture']) ? $_GET['fixture'] : null;
		if (empty($fixtureFile)) {
			$me = Director::baseURL() . "/dev/tests/sessionloadyml";
			return <<<HTML
				<form action="$me">
					<p>Enter a fixture file name to load a new YAML fixture into the session.</p>
					<p>Fixture file <input id="fixture-file" name="fixture" /></p>
					<input type="hidden" name="flush" value="1">
					<p><input id="session-load-yaml" value="Load yml fixture" type="submit" /></p>
				</form>
HTML;
		}
		// Validate fixture file
		$realFile = realpath(BASE_PATH.'/'.$fixtureFile);
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

		// Fixture
		$fixture = new YamlFixture($fixtureFile);
		$fixture->saveIntoDatabase();

		return "<p>Loaded fixture '$fixtureFile' into session</p>";
	}

	function setUp() {
		// The first DB test will sort out the DB, we don't have to
		SSViewer::flush_template_cache();
	}
	
	function tearDown() {
		SapphireTest::kill_temp_db();
		DB::set_alternative_database_name(null);
	}
}
