<?php
require_once 'TestRunner.php';

/**
 * Test case class for the Sapphire framework.
 * Sapphire unit testing is based on PHPUnit, but provides a number of hooks into our data model that make it easier to work with.
 * 
 * @package sapphire
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
	
	protected $originalMailer;
	protected $originalMemberPasswordValidator;
	protected $originalRequirements;
	protected $originalIsRunningTest;
	protected $originalTheme;
	protected $originalNestedURLsState;
	protected $originalMemoryLimit;
	
	protected $mailer;
	
	/**
	 * @var boolean
	 */
	protected static $is_running_test = false;
	
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
	 * @var array $fixtures Array of {@link YamlFixture} instances
	 */
	protected $fixtures; 
	
	function setUp() {
		// Mark test as being run
		$this->originalIsRunningTest = self::$is_running_test;
		self::$is_running_test = true;
		
		// i18n needs to be set to the defaults or tests fail
		i18n::set_locale(i18n::default_locale());
		i18n::set_date_format(null);
		i18n::set_time_format(null);
		
		// Remove password validation
		$this->originalMemberPasswordValidator = Member::password_validator();
		$this->originalRequirements = Requirements::backend();
		Member::set_password_validator(null);
		Cookie::set_report_errors(false);
		
		RootURLController::reset();
		Translatable::reset();
		Versioned::reset();
		DataObject::reset();
		SiteTree::reset();
		if(Controller::has_curr()) Controller::curr()->setSession(new Session(array()));
		
		$this->originalTheme = SSViewer::current_theme();
		
		// Save nested_urls state, so we can restore it later
		$this->originalNestedURLsState = SiteTree::nested_urls();

		$className = get_class($this);
		$fixtureFile = eval("return {$className}::\$fixture_file;");
		
		// Set up fixture
		if($fixtureFile || !self::using_temp_db()) {
			if(substr(DB::getConn()->currentDatabase(),0,5) != 'tmpdb') {
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
				$fixtureFiles = (is_array($fixtureFile)) ? $fixtureFile : array($fixtureFile);

				$i = 0;
				foreach($fixtureFiles as $fixtureFilePath) {
					$fixture = new YamlFixture($fixtureFilePath);
					$fixture->saveIntoDatabase();
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
		global $_SINGLETONS;
		$_SINGLETONS = array();
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
		
		$fixtureFiles = Object::get_static(get_class($this), 'fixture_file');
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

		$fixtureFiles = Object::get_static(get_class($this), 'fixture_file');
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
		
		// Restore nested_urls state
		if ( $this->originalNestedURLsState )
			SiteTree::enable_nested_urls();
		else
			SiteTree::disable_nested_urls();
		
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
	 * Assert that the given {@link DataObjectSet} includes DataObjects matching the given key-value
	 * pairs.  Each match must correspond to 1 distinct record.
	 * 
	 * @param $matches The patterns to match.  Each pattern is a map of key-value pairs.  You can
	 * either pass a single pattern or an array of patterns.
	 * @param $dataObjectSet The {@link DataObjectSet} to test.
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
	                "Failed asserting that the DataObjectSet contains an item matching "
						. var_export($match, true) . "\n\nIn the following DataObjectSet:\n" 
						. $this->DOSSummaryForMatch($dataObjectSet, $match)
	            );
			}

		}
	} 
	
	/**
	 * Assert that the given {@link DataObjectSet} includes only DataObjects matching the given 
	 * key-value pairs.  Each match must correspond to 1 distinct record.
	 * 
	 * @param $matches The patterns to match.  Each pattern is a map of key-value pairs.  You can
	 * either pass a single pattern or an array of patterns.
	 * @param $dataObjectSet The {@link DataObjectSet} to test.
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
	                "Failed asserting that the DataObjectSet contains an item matching "
						. var_export($match, true) . "\n\nIn the following DataObjectSet:\n" 
						. $this->DOSSummaryForMatch($dataObjectSet, $match)
	            );
			}
		}
		
		// If we have leftovers than the DOS has extra data that shouldn't be there
		if($extracted) {
			// If we didn't break by this point then we couldn't find a match
			throw new PHPUnit_Framework_AssertionFailedError(
	            "Failed asserting that the DataObjectSet contained only the given items, the "
					. "following items were left over:\n" . var_export($extracted, true)
	        );
		}
	} 

	/**
	 * Assert that the every record in the given {@link DataObjectSet} matches the given key-value
	 * pairs.
	 * 
	 * @param $match The pattern to match.  The pattern is a map of key-value pairs.
	 * @param $dataObjectSet The {@link DataObjectSet} to test.
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
			if(!isset($item[$k]) || $item[$k] != $v) return false;
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
		return $dbConn && (substr($dbConn->currentDatabase(),0,5) == 'tmpdb');
	}
	
	/**
	 * @todo Make this db agnostic
	 */
	static function kill_temp_db() {
		// Delete our temporary database
		if(self::using_temp_db()) {
			$dbConn = DB::getConn();
			$dbName = $dbConn->currentDatabase();
			if($dbName && DB::getConn()->databaseExists($dbName)) {
				// Some DataObjectsDecorators keep a static cache of information that needs to 
				// be reset whenever the database is killed
				foreach(ClassInfo::subclassesFor('DataObjectDecorator') as $class) {
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
			
			// Some DataObjectsDecorators keep a static cache of information that needs to 
			// be reset whenever the database is cleaned out
			foreach(array_merge(ClassInfo::subclassesFor('DataObjectDecorator'), ClassInfo::subclassesFor('DataObject')) as $class) {
				$toCall = array($class, 'on_db_reset');
				if(is_callable($toCall)) call_user_func($toCall);
			}
		}
	}
	
	/**
	 * @todo Make this db agnostic
	 */
	static function create_temp_db() {
		// Disable PHPUnit error handling
		restore_error_handler();
		
		// Create a temporary database
		$dbConn = DB::getConn();
		$dbname = 'tmpdb' . rand(1000000,9999999);
		while(!$dbname || $dbConn->databaseExists($dbname)) {
			$dbname = 'tmpdb' . rand(1000000,9999999);
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
		foreach(DB::getConn()->allDatabaseNames() as $dbName) {
			if(preg_match('/^tmpdb[0-9]+$/', $dbName)) {
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
			global $_SINGLETONS;
			$_SINGLETONS = array();

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
			$group = new Group();
			$group->Title = "$permCode group";
			$group->write();

			$permission = new Permission();
			$permission->Code = $permCode;
			$permission->write();
			$group->Permissions()->add($permission);
			
			$member = DataObject::get_one('Member', sprintf('"Email" = \'%s\'', "$permCode@example.org"));
			if(!$member) $member = new Member();
			
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

?>
