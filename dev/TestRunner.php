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
	
	private static $url_handlers = array(
		'' => 'browse',
		'coverage/module/$ModuleName' => 'coverageModule',
		'coverage/$TestCase!' => 'coverageOnly',
		'coverage' => 'coverageAll',
		'cleanupdb' => 'cleanupdb',
		'module/$ModuleName' => 'module',
		'all' => 'all',
		'build' => 'build',
		'$TestCase' => 'only'
	);
	
	private static $allowed_actions = array(
		'index',
		'browse',
		'coverage',
		'coverageAll',
		'coverageModule',
		'coverageOnly',
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
	 * @see http://www.phpunit.de/manual/current/en/appendixes.configuration.html
	 *      #appendixes.configuration.blacklist-whitelist
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
	public static function set_reporter($reporter) {
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
			BASE_PATH, project(), true, isset($_GET['flush'])
		));

		Config::inst()->pushConfigStaticManifest(new SS_ConfigStaticManifest(
			BASE_PATH, true, isset($_GET['flush'])
		));
		
		// Invalidate classname spec since the test manifest will now pull out new subclasses for each internal class
		// (e.g. Member will now have various subclasses of DataObjects that implement TestOnly)
		DataObject::clear_classname_spec_cache();
	}

	public function init() {
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
	public function all($request, $coverage = false) {
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
	public function build() {
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
	public function browse() {
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
	public function coverageAll($request) {
		self::use_test_manifest();
		$this->all($request, true);
	}

	/**
	 * Run only a single coverage test class or a comma-separated list of tests
	 */
	public function coverageOnly($request) {
		$this->only($request, true);
	}
	
	/**
	 * Run coverage tests for one or more "modules".
	 * A module is generally a toplevel folder, e.g. "mysite" or "framework".
	 */
	public function coverageModule($request) {
		$this->module($request, true);
	}
	
	public function cleanupdb() {
		SapphireTest::delete_all_temp_dbs();
	}
		
	/**
	 * Run only a single test class or a comma-separated list of tests
	 */
	public function only($request, $coverage = false) {
		self::use_test_manifest();
		if($request->param('TestCase') == 'all') {
			$this->all();
		} else {
			$classNames = explode(',', $request->param('TestCase'));
			foreach($classNames as $className) {
				if(!class_exists($className) || !is_subclass_of($className, 'SapphireTest')) {
					user_error("TestRunner::only(): Invalid TestCase '$className', cannot find matching class",
						E_USER_ERROR);
				}
			}
			
			$this->runTests($classNames, $coverage);
		}
	}
	
	/**
	 * Run tests for one or more "modules".
	 * A module is generally a toplevel folder, e.g. "mysite" or "framework".
	 */
	public function module($request, $coverage = false) {
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
	public function runTests($classList, $coverage = false) {
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

		$abstractClasses = array();
		foreach($classList as $className) {
			// Ensure that the autoloader pulls in the test class, as PHPUnit won't know how to do this.
			class_exists($className);
			$reflection = new ReflectionClass($className);
			if ($reflection->isAbstract()) {
				array_push($abstractClasses, $className);
			}
		}
		
		$classList = array_diff($classList, $skipTests, $abstractClasses);

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

		// Make sure TearDown is called (even in the case of a fatal error)
		$self = $this;
		register_shutdown_function(function() use ($self) {
			$self->tearDown();
		});

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
	
	public function setUp() {
		// The first DB test will sort out the DB, we don't have to
		SSViewer::flush_template_cache();
	}
	
	public function tearDown() {
		SapphireTest::kill_temp_db();
		DB::set_alternative_database_name(null);
	}
}
