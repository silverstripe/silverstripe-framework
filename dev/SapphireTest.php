<?php
require_once 'TestRunner.php';

/**
 * Test case class for the Sapphire framework.
 * Sapphire unit testing is based on PHPUnit, but provides a number of hooks into our data model that make it easier to work with.
 * 
 * @package framework
 * @subpackage testing
 */
class SapphireTest extends PHPUnit_Framework_TestCase {
	/**
	 * Path to fixture data for this test run.
	 * If passed as an array, multiple fixture files will be loaded.
	 * Please note that you won't be able to refer with "=>" notation
	 * between the fixtures, they act independent of each other.
	 * 
	 * @var string|array
	 */
	static $fixture_file = null;
	
	/**
	 * Set whether to include this test in the TestRunner or to skip this.
	 *
	 * @var bool
	 */
	protected $skipTest = false;
	
	/**
	 * @var Boolean If set to TRUE, this will force a test database to be generated
	 * in {@link setUp()}. Note that this flag is overruled by the presence of a 
	 * {@link $fixture_file}, which always forces a database build.
	 */
	protected $usesDatabase = null;
	
	protected $originalMailer;
	protected $originalMemberPasswordValidator;
	protected $originalRequirements;
	protected $originalIsRunningTest;
	protected $originalTheme;
	protected $originalNestedURLsState;
	protected $originalMemoryLimit;
	
	protected $mailer;
	
	/**
	 * Pointer to the manifest that isn't a test manifest
	 */
	protected static $regular_manifest;
	
	/**
	 * @var boolean
	 */
	protected static $is_running_test = false;
	
	protected static $test_class_manifest;
	
	/**
	 * By default, setUp() does not require default records. Pass
	 * class names in here, and the require/augment default records
	 * function will be called on them.
	 */
	protected $requireDefaultRecordsFrom = array();
	
	
	/**
	 * A list of extensions that can't be applied during the execution of this run.  If they are
	 * applied, they will be temporarily removed and a database migration called.
	 * 
	 * The keys of the are the classes that the extensions can't be applied the extensions to, and
	 * the values are an array of illegal extensions on that class.
	 */
	protected $illegalExtensions = array(
	);

	/**
	 * A list of extensions that must be applied during the execution of this run.  If they are
	 * not applied, they will be temporarily added and a database migration called.
	 * 
	 * The keys of the are the classes to apply the extensions to, and the values are an array
	 * of required extensions on that class.
	 * 
	 * Example:
	 * <code>
	 * array("MyTreeDataObject" => array("Versioned", "Hierarchy"))
	 * </code>
	 */
	protected $requiredExtensions = array(
	);
	
	/**
	 * By default, the test database won't contain any DataObjects that have the interface TestOnly.
	 * This variable lets you define additional TestOnly DataObjects to set up for this test.
	 * Set it to an array of DataObject subclass names.
	 */
	protected $extraDataObjects = array();
	
	/**
	 * We need to disabling backing up of globals to avoid overriding
	 * the few globals SilverStripe relies on, like $lang for the i18n subsystem.
	 * 
	 * @see http://sebastian-bergmann.de/archives/797-Global-Variables-and-PHPUnit.html
	 */
	protected $backupGlobals = FALSE;

	/** 
	 * Helper arrays for illegalExtensions/requiredExtensions code
	 */
	private $extensionsToReapply = array(), $extensionsToRemove = array();

	
	/**
	 * Determines if unit tests are currently run (via {@link TestRunner}).
	 * This is used as a cheap replacement for fully mockable state
	 * in certain contiditions (e.g. access checks).
	 * Caution: When set to FALSE, certain controllers might bypass
	 * access checks, so this is a very security sensitive setting.
	 * 
	 * @return boolean
	 */
	public static function is_running_test() {
		return self::$is_running_test;
	}


	/**
	 * Set the manifest to be used to look up test classes by helper functions
	 */
	public static function set_test_class_manifest($manifest) {
		self::$test_class_manifest = $manifest;
	}

	/**
	 * Return the manifest being used to look up test classes by helper functions
	 */
	public static function get_test_class_manifest() {
		return self::$test_class_manifest;
	}
	
	/**
	 * @var array $fixtures Array of {@link YamlFixture} instances
	 */
	protected $fixtures; 
	
	protected $model;
	
	function setUp() {
		// We cannot run the tests on this abstract class.
		if(get_class($this) == "SapphireTest") $this->skipTest = true;
		
		if($this->skipTest) {
			$this->markTestSkipped(sprintf(
				'Skipping %s ', get_class($this)
			));
			
			return;
		}
		
		// Mark test as being run
		$this->originalIsRunningTest = self::$is_running_test;
		self::$is_running_test = true;
		
		// i18n needs to be set to the defaults or tests fail
		i18n::set_locale(i18n::default_locale());
		i18n::set_date_format(null);
		i18n::set_time_format(null);
		
		// Set default timezone consistently to avoid NZ-specific dependencies
		date_default_timezone_set('UTC');
		
		// Remove password validation
		$this->originalMemberPasswordValidator = Member::password_validator();
		$this->originalRequirements = Requirements::backend();
		Member::set_password_validator(null);
		Cookie::set_report_errors(false);
		
		if(class_exists('RootURLController')) RootURLController::reset();
		if(class_exists('Translatable')) Translatable::reset();
		Versioned::reset();
		DataObject::reset();
		if(class_exists('SiteTree')) SiteTree::reset();
		Hierarchy::reset();
		if(Controller::has_curr()) Controller::curr()->setSession(new Session(array()));
		Security::$database_is_ready = null;
		
		$this->originalTheme = SSViewer::current_theme();
		
		if(class_exists('SiteTree')) {
			// Save nested_urls state, so we can restore it later
			$this->originalNestedURLsState = SiteTree::nested_urls();
		}

		$className = get_class($this);
		$fixtureFile = eval("return {$className}::\$fixture_file;");
		$prefix = defined('SS_DATABASE_PREFIX') ? SS_DATABASE_PREFIX : 'ss_';
		
		// Todo: this could be a special test model
		$this->model = DataModel::inst();

		// Set up fixture
		if($fixtureFile || $this->usesDatabase || !self::using_temp_db()) {
			if(substr(DB::getConn()->currentDatabase(), 0, strlen($prefix) + 5) != strtolower(sprintf('%stmpdb', $prefix))) {
				//echo "Re-creating temp database... ";
				self::create_temp_db();
				//echo "done.\n";
			}

			singleton('DataObject')->flushCache();
			
			self::empty_temp_db();
			
			foreach($this->requireDefaultRecordsFrom as $className) {
				$instance = singleton($className);
				if (method_exists($instance, 'requireDefaultRecords')) $instance->requireDefaultRecords();
				if (method_exists($instance, 'augmentDefaultRecords')) $instance->augmentDefaultRecords();
			}

			if($fixtureFile) {
				$pathForClass = $this->getCurrentAbsolutePath();
				$fixtureFiles = (is_array($fixtureFile)) ? $fixtureFile : array($fixtureFile);

				$i = 0;
				foreach($fixtureFiles as $fixtureFilePath) {
					// Support fixture paths relative to the test class, rather than relative to webroot
					// String checking is faster than file_exists() calls.
					$isRelativeToFile = (strpos('/', $fixtureFilePath) === false || preg_match('/^\.\./', $fixtureFilePath));
					if($isRelativeToFile) {
						$resolvedPath = realpath($pathForClass . '/' . $fixtureFilePath);
						if($resolvedPath) $fixtureFilePath = $resolvedPath;
					}
					
					$fixture = new YamlFixture($fixtureFilePath);
					$fixture->saveIntoDatabase($this->model);
					$this->fixtures[] = $fixture;

					// backwards compatibility: Load first fixture into $this->fixture
					if($i == 0) $this->fixture = $fixture;
					$i++;
				}
			}
			
			$this->logInWithPermission("ADMIN");
		}
		
		// Set up email
		$this->originalMailer = Email::mailer();
		$this->mailer = new TestMailer();
		Email::set_mailer($this->mailer);
		Email::send_all_emails_to(null);
		
		// Preserve memory settings
		$this->originalMemoryLimit = ini_get('memory_limit');
		
		// turn off template debugging
		SSViewer::set_source_file_comments(false);
		
		// Clear requirements
		Requirements::clear();
	}
	
	/**
	 * Called once per test case ({@link SapphireTest} subclass).
	 * This is different to {@link setUp()}, which gets called once
	 * per method. Useful to initialize expensive operations which
	 * don't change state for any called method inside the test,
	 * e.g. dynamically adding an extension. See {@link tearDownOnce()}
	 * for tearing down the state again.
	 */
	function setUpOnce() {
		// Remove any illegal extensions that are present
		foreach($this->illegalExtensions as $class => $extensions) {
			foreach($extensions as $extension) {
				if (Object::has_extension($class, $extension)) {
					if(!isset($this->extensionsToReapply[$class])) $this->extensionsToReapply[$class] = array();
					$this->extensionsToReapply[$class][] = $extension;
					Object::remove_extension($class, $extension);
					$isAltered = true;
				}
			}
		}

		// Add any required extensions that aren't present
		foreach($this->requiredExtensions as $class => $extensions) {
			$this->extensionsToRemove[$class] = array();
			foreach($extensions as $extension) {
				if(!Object::has_extension($class, $extension)) {
					if(!isset($this->extensionsToRemove[$class])) $this->extensionsToReapply[$class] = array();
					$this->extensionsToRemove[$class][] = $extension;
					Object::add_extension($class, $extension);
					$isAltered = true;
				}
			}
		}
		
		// If we have made changes to the extensions present, then migrate the database schema.
		if($this->extensionsToReapply || $this->extensionsToRemove || $this->extraDataObjects) {
			if(!self::using_temp_db()) self::create_temp_db();
			$this->resetDBSchema(true);
		}
		// clear singletons, they're caching old extension info 
		// which is used in DatabaseAdmin->doBuild()
		Injector::inst()->unregisterAllObjects();

		// Set default timezone consistently to avoid NZ-specific dependencies
		date_default_timezone_set('UTC');
	}
	
	/**
	 * tearDown method that's called once per test class rather once per test method.
	 */
	function tearDownOnce() {
		// If we have made changes to the extensions present, then migrate the database schema.
		if($this->extensionsToReapply || $this->extensionsToRemove) {
			// Remove extensions added for testing
			foreach($this->extensionsToRemove as $class => $extensions) {
				foreach($extensions as $extension) {
					Object::remove_extension($class, $extension);
				}
			}

			// Reapply ones removed
			foreach($this->extensionsToReapply as $class => $extensions) {
				foreach($extensions as $extension) {
					Object::add_extension($class, $extension);
				}
			}
		}
		
		if($this->extensionsToReapply || $this->extensionsToRemove || $this->extraDataObjects) {
			$this->resetDBSchema();
		}
	}
	
	/**
	 * Array
	 */
	protected $fixtureDictionary;
	
	
	/**
	 * Get the ID of an object from the fixture.
	 * @param $className The data class, as specified in your fixture file.  Parent classes won't work
	 * @param $identifier The identifier string, as provided in your fixture file
	 * @return int
	 */
	protected function idFromFixture($className, $identifier) {
		if(!$this->fixtures) {
			user_error("You've called idFromFixture() but you haven't specified static \$fixture_file.\n", E_USER_WARNING);
			return;
		}
		
		foreach($this->fixtures as $fixture) {
			$match = $fixture->idFromFixture($className, $identifier);
			if($match) return $match;
		}

		$fixtureFiles = Config::inst()->get(get_class($this), 'fixture_file', Config::FIRST_SET);
		user_error(sprintf(
			"Couldn't find object '%s' (class: %s) in files %s",
			$identifier,
			$className,
			(is_array($fixtureFiles)) ? implode(',', $fixtureFiles) : $fixtureFiles
		), E_USER_ERROR);
		
		return false;
	}
	
	/**
	 * Return all of the IDs in the fixture of a particular class name.
	 * Will collate all IDs form all fixtures if multiple fixtures are provided.
	 * 
	 * @param string $className
	 * @return A map of fixture-identifier => object-id
	 */
	protected function allFixtureIDs($className) {
		if(!$this->fixtures) {
			user_error("You've called allFixtureIDs() but you haven't specified static \$fixture_file.\n", E_USER_WARNING);
			return;
		}
		
		$ids = array();
		foreach($this->fixtures as $fixture) {
			$ids += $fixture->allFixtureIDs($className);
		}
		
		return $ids;
	}

	/**
	 * Get an object from the fixture.
	 * @param $className The data class, as specified in your fixture file.  Parent classes won't work
	 * @param $identifier The identifier string, as provided in your fixture file
	 */
	protected function objFromFixture($className, $identifier) {
		if(!$this->fixtures) {
			user_error("You've called objFromFixture() but you haven't specified static \$fixture_file.\n", E_USER_WARNING);
			return;
		}
		
		foreach($this->fixtures as $fixture) {
			$match = $fixture->objFromFixture($className, $identifier);
			if($match) return $match;
		}

		$fixtureFiles = Config::inst()->get(get_class($this), 'fixture_file', Config::FIRST_SET);
		user_error(sprintf(
			"Couldn't find object '%s' (class: %s) in files %s",
			$identifier,
			$className,
			(is_array($fixtureFiles)) ? implode(',', $fixtureFiles) : $fixtureFiles
		), E_USER_ERROR);
		
		return false;
	}
	
	/**
	 * Load a YAML fixture file into the database.
	 * Once loaded, you can use idFromFixture() and objFromFixture() to get items from the fixture.
	 * Doesn't clear existing fixtures.
	 *
	 * @param $fixtureFile The location of the .yml fixture file, relative to the site base dir
	 */
	function loadFixture($fixtureFile) {
		$parser = new Spyc();
		$fixtureContent = $parser->load(Director::baseFolder().'/'.$fixtureFile);
		
		$fixture = new YamlFixture($fixtureFile);
		$fixture->saveIntoDatabase();
		$this->fixtures[] = $fixture;
	}
	
	/**
	 * Clear all fixtures which were previously loaded through
	 * {@link loadFixture()}.
	 */
	function clearFixtures() {
		$this->fixtures = array();
	}
	
	/**
	 * Useful for writing unit tests without hardcoding folder structures.
	 * 
	 * @return String Absolute path to current class.
	 */
	protected function getCurrentAbsolutePath() {
		$filename = self::$test_class_manifest->getItemPath(get_class($this));
		if(!$filename) throw new LogicException("getItemPath returned null for " . get_class($this));
		return dirname($filename);
	}
	
	/**
	 * @return String File path relative to webroot
	 */
	protected function getCurrentRelativePath() {
		$base = Director::baseFolder();
		$path = $this->getCurrentAbsolutePath();
		if(substr($path,0,strlen($base)) == $base) $path = preg_replace('/^\/*/', '', substr($path,strlen($base)));
		return $path;
	}
	
	function tearDown() {
		// Preserve memory settings
		ini_set('memory_limit', ($this->originalMemoryLimit) ? $this->originalMemoryLimit : -1);

		// Restore email configuration
		Email::set_mailer($this->originalMailer);
		$this->originalMailer = null;
		$this->mailer = null;

		// Restore password validation
		Member::set_password_validator($this->originalMemberPasswordValidator);
		
		// Restore requirements
		Requirements::set_backend($this->originalRequirements);

		// Mark test as no longer being run - we use originalIsRunningTest to allow for nested SapphireTest calls
		self::$is_running_test = $this->originalIsRunningTest;
		$this->originalIsRunningTest = null;

		// Reset theme setting
		SSViewer::set_theme($this->originalTheme);

		// Reset mocked datetime
		SS_Datetime::clear_mock_now();
		
		if(class_exists('SiteTree')) {
			// Restore nested_urls state
			if ( $this->originalNestedURLsState )
				SiteTree::enable_nested_urls();
			else
				SiteTree::disable_nested_urls();
		}
		
		// Stop the redirection that might have been requested in the test.
		// Note: Ideally a clean Controller should be created for each test. 
		// Now all tests executed in a batch share the same controller.
		$controller = Controller::has_curr() ? Controller::curr() : null;
		if ( $controller && $controller->response && $controller->response->getHeader('Location') ) {
			$controller->response->setStatusCode(200);
			$controller->response->removeHeader('Location');
		}
	}
	/**
	 * Clear the log of emails sent
	 */
	function clearEmails() {
		return $this->mailer->clearEmails();
	}

	/**
	 * Search for an email that was sent.
	 * All of the parameters can either be a string, or, if they start with "/", a PREG-compatible regular expression.
	 * @param $to
	 * @param $from
	 * @param $subject
	 * @param $content
	 * @return An array containing the keys: 'type','to','from','subject','content', 'plainContent','attachedFiles','customHeaders','htmlContent',inlineImages'
	 */
	function findEmail($to, $from = null, $subject = null, $content = null) {
		return $this->mailer->findEmail($to, $from, $subject, $content);
	}
	
	/**
	 * Assert that the matching email was sent since the last call to clearEmails()
	 * All of the parameters can either be a string, or, if they start with "/", a PREG-compatible regular expression.
	 * @param $to
	 * @param $from
	 * @param $subject
	 * @param $content
	 * @return An array containing the keys: 'type','to','from','subject','content', 'plainContent','attachedFiles','customHeaders','htmlContent',inlineImages'
	 */
	function assertEmailSent($to, $from = null, $subject = null, $content = null) {
		// To do - this needs to be turned into a "real" PHPUnit ass
		if(!$this->findEmail($to, $from, $subject, $content)) {
			
			$infoParts = "";
			$withParts = array();
			if($to) $infoParts .= " to '$to'";
			if($from) $infoParts .= " from '$from'";
			if($subject) $withParts[] = "subject '$subject'";
			if($content) $withParts[] = "content '$content'";
			if($withParts) $infoParts .= " with " . implode(" and ", $withParts);
			
			throw new PHPUnit_Framework_AssertionFailedError(
                "Failed asserting that an email was sent$infoParts."
            );
		}
	}


	/**
	 * Assert that the given {@link SS_List} includes DataObjects matching the given key-value
	 * pairs.  Each match must correspond to 1 distinct record.
	 * 
	 * @param $matches The patterns to match.  Each pattern is a map of key-value pairs.  You can
	 * either pass a single pattern or an array of patterns.
	 * @param $dataObjectSet The {@link SS_List} to test.
	 *
	 * Examples
	 * --------
	 * Check that $members includes an entry with Email = sam@example.com:
	 *      $this->assertDOSContains(array('Email' => '...@example.com'), $members); 
	 * 
	 * Check that $members includes entries with Email = sam@example.com and with 
	 * Email = ingo@example.com:
	 *      $this->assertDOSContains(array( 
	 *         array('Email' => '...@example.com'), 
	 *         array('Email' => 'i...@example.com'), 
	 *      ), $members); 
	 */
	function assertDOSContains($matches, $dataObjectSet) {
		$extracted = array();
		foreach($dataObjectSet as $item) $extracted[] = $item->toMap();
		
		foreach($matches as $match) {
			$matched = false;
			foreach($extracted as $i => $item) {
				if($this->dataObjectArrayMatch($item, $match)) {
					// Remove it from $extracted so that we don't get duplicate mapping.
					unset($extracted[$i]);
					$matched = true;
					break;
				}
			}

			// We couldn't find a match - assertion failed
			if(!$matched) {
				throw new PHPUnit_Framework_AssertionFailedError(
	                "Failed asserting that the SS_List contains an item matching "
						. var_export($match, true) . "\n\nIn the following SS_List:\n" 
						. $this->DOSSummaryForMatch($dataObjectSet, $match)
	            );
			}

		}
	} 
	
	/**
	 * Assert that the given {@link SS_List} includes only DataObjects matching the given 
	 * key-value pairs.  Each match must correspond to 1 distinct record.
	 * 
	 * @param $matches The patterns to match.  Each pattern is a map of key-value pairs.  You can
	 * either pass a single pattern or an array of patterns.
	 * @param $dataObjectSet The {@link SS_List} to test.
	 *
	 * Example
	 * --------
	 * Check that *only* the entries Sam Minnee and Ingo Schommer exist in $members.  Order doesn't 
	 * matter:
	 *     $this->assertDOSEquals(array( 
	 *        array('FirstName' =>'Sam', 'Surname' => 'Minnee'), 
	 *        array('FirstName' => 'Ingo', 'Surname' => 'Schommer'), 
	 *      ), $members); 
	 */
	function assertDOSEquals($matches, $dataObjectSet) {
		if(!$dataObjectSet) return false;
		
		$extracted = array();
		foreach($dataObjectSet as $item) $extracted[] = $item->toMap();
		
		foreach($matches as $match) {
			$matched = false;
			foreach($extracted as $i => $item) {
				if($this->dataObjectArrayMatch($item, $match)) {
					// Remove it from $extracted so that we don't get duplicate mapping.
					unset($extracted[$i]);
					$matched = true;
					break;
				}
			}

			// We couldn't find a match - assertion failed
			if(!$matched) {
				throw new PHPUnit_Framework_AssertionFailedError(
	                "Failed asserting that the SS_List contains an item matching "
						. var_export($match, true) . "\n\nIn the following SS_List:\n" 
						. $this->DOSSummaryForMatch($dataObjectSet, $match)
	            );
			}
		}
		
		// If we have leftovers than the DOS has extra data that shouldn't be there
		if($extracted) {
			// If we didn't break by this point then we couldn't find a match
			throw new PHPUnit_Framework_AssertionFailedError(
	            "Failed asserting that the SS_List contained only the given items, the "
					. "following items were left over:\n" . var_export($extracted, true)
	        );
		}
	} 

	/**
	 * Assert that the every record in the given {@link SS_List} matches the given key-value
	 * pairs.
	 * 
	 * @param $match The pattern to match.  The pattern is a map of key-value pairs.
	 * @param $dataObjectSet The {@link SS_List} to test.
	 *
	 * Example
	 * --------
	 * Check that every entry in $members has a Status of 'Active':
	 *     $this->assertDOSAllMatch(array('Status' => 'Active'), $members); 
	 */
	function assertDOSAllMatch($match, $dataObjectSet) {
		$extracted = array();
		foreach($dataObjectSet as $item) $extracted[] = $item->toMap();

		foreach($extracted as $i => $item) {
			if(!$this->dataObjectArrayMatch($item, $match)) {
				throw new PHPUnit_Framework_AssertionFailedError(
		            "Failed asserting that the the following item matched " 
					. var_export($match, true) . ": " . var_export($item, true)
		        );
			}
		}
	} 
	
	/**
	 * Helper function for the DOS matchers
	 */
	private function dataObjectArrayMatch($item, $match) {
		foreach($match as $k => $v) {
			if(!array_key_exists($k, $item) || $item[$k] != $v) return false;
		}
		return true;
	}

	/**
	 * Helper function for the DOS matchers
	 */
	private function DOSSummaryForMatch($dataObjectSet, $match) {
		$extracted = array();
		foreach($dataObjectSet as $item) $extracted[] = array_intersect_key($item->toMap(), $match);
		return var_export($extracted, true);
	}

	/**
	 * Returns true if we are currently using a temporary database
	 */
	static function using_temp_db() {
		$dbConn = DB::getConn();
		$prefix = defined('SS_DATABASE_PREFIX') ? SS_DATABASE_PREFIX : 'ss_';
		return $dbConn && (substr($dbConn->currentDatabase(), 0, strlen($prefix) + 5) == strtolower(sprintf('%stmpdb', $prefix)));
	}
	
	static function kill_temp_db() {
		// Delete our temporary database
		if(self::using_temp_db()) {
			$dbConn = DB::getConn();
			$dbName = $dbConn->currentDatabase();
			if($dbName && DB::getConn()->databaseExists($dbName)) {
				// Some DataExtensions keep a static cache of information that needs to 
				// be reset whenever the database is killed
				foreach(ClassInfo::subclassesFor('DataExtension') as $class) {
					$toCall = array($class, 'on_db_reset');
					if(is_callable($toCall)) call_user_func($toCall);
				}

				// echo "Deleted temp database " . $dbConn->currentDatabase() . "\n";
				$dbConn->dropDatabase();
			}
		}
	}
	
	/**
	 * Remove all content from the temporary database.
	 */
	static function empty_temp_db() {
		if(self::using_temp_db()) {
			$dbadmin = new DatabaseAdmin();
			$dbadmin->clearAllData();
			
			// Some DataExtensions keep a static cache of information that needs to 
			// be reset whenever the database is cleaned out
			foreach(array_merge(ClassInfo::subclassesFor('DataExtension'), ClassInfo::subclassesFor('DataObject')) as $class) {
				$toCall = array($class, 'on_db_reset');
				if(is_callable($toCall)) call_user_func($toCall);
			}
		}
	}
	
	static function create_temp_db() {
		// Disable PHPUnit error handling
		restore_error_handler();

		// Create a temporary database, and force the connection to use UTC for time
		global $databaseConfig;
		$databaseConfig['timezone'] = '+0:00';
		DB::connect($databaseConfig);
		$dbConn = DB::getConn();
		$prefix = defined('SS_DATABASE_PREFIX') ? SS_DATABASE_PREFIX : 'ss_';
		$dbname = strtolower(sprintf('%stmpdb', $prefix)) . rand(1000000,9999999);
		while(!$dbname || $dbConn->databaseExists($dbname)) {
			$dbname = strtolower(sprintf('%stmpdb', $prefix)) . rand(1000000,9999999);
		}

		$dbConn->selectDatabase($dbname);
		$dbConn->createDatabase();

		$st = new SapphireTest();
		$st->resetDBSchema();
		
		// Reinstate PHPUnit error handling
		set_error_handler(array('PHPUnit_Util_ErrorHandler', 'handleError'));
		
		return $dbname;
	}
	
	static function delete_all_temp_dbs() {
		$prefix = defined('SS_DATABASE_PREFIX') ? SS_DATABASE_PREFIX : 'ss_';
		foreach(DB::getConn()->allDatabaseNames() as $dbName) {
			if(preg_match(sprintf('/^%stmpdb[0-9]+$/', $prefix), $dbName)) {
				DB::getConn()->dropDatabaseByName($dbName);
				if(Director::is_cli()) {
					echo "Dropped database \"$dbName\"" . PHP_EOL;
				} else {
					echo "<li>Dropped database \"$dbName\"</li>" . PHP_EOL;
				}
				flush();
			}
		}
	}
	
	/**
	 * Reset the testing database's schema.
	 * @param $includeExtraDataObjects If true, the extraDataObjects tables will also be included
	 */
	function resetDBSchema($includeExtraDataObjects = false) {
		if(self::using_temp_db()) {
			// clear singletons, they're caching old extension info which is used in DatabaseAdmin->doBuild()
			Injector::inst()->unregisterAllObjects();

			$dataClasses = ClassInfo::subclassesFor('DataObject');
			array_shift($dataClasses);

			$conn = DB::getConn();
			$conn->beginSchemaUpdate();
			DB::quiet();

			foreach($dataClasses as $dataClass) {
				// Check if class exists before trying to instantiate - this sidesteps any manifest weirdness
				if(class_exists($dataClass)) {
					$SNG = singleton($dataClass);
					if(!($SNG instanceof TestOnly)) $SNG->requireTable();
				}
			}

			// If we have additional dataobjects which need schema, do so here:
			if($includeExtraDataObjects && $this->extraDataObjects) {
				foreach($this->extraDataObjects as $dataClass) {
					$SNG = singleton($dataClass);
					if(singleton($dataClass) instanceof DataObject) $SNG->requireTable();
				}
			}

			$conn->endSchemaUpdate();

			ClassInfo::reset_db_cache();
			singleton('DataObject')->flushCache();
		}
	}
	
	/**
	 * Create a member and group with the given permission code, and log in with it.
	 * Returns the member ID.
	 */
	function logInWithPermission($permCode = "ADMIN") {
		if(!isset($this->cache_generatedMembers[$permCode])) {
			$group = Injector::inst()->create('Group');
			$group->Title = "$permCode group";
			$group->write();

			$permission = Injector::inst()->create('Permission');
			$permission->Code = $permCode;
			$permission->write();
			$group->Permissions()->add($permission);
			
			$member = DataObject::get_one('Member', sprintf('"Email" = \'%s\'', "$permCode@example.org"));
			if(!$member) $member = Injector::inst()->create('Member');
			
			$member->FirstName = $permCode;
			$member->Surname = "User";
			$member->Email = "$permCode@example.org";
			$member->write();
			$group->Members()->add($member);
			
			$this->cache_generatedMembers[$permCode] = $member;
		}
		
		$this->cache_generatedMembers[$permCode]->logIn();
	}
	
	/**
	 * Cache for logInWithPermission()
	 */
	protected $cache_generatedMembers = array();
}


