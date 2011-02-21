<?php
require_once 'TestRunner.php';

PhpUnitWrapper::inst()->init();

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
	 * 
	 * @var string
	 */
	static $fixture_file = null;
	
	protected $originalMailer;
	protected $originalMemberPasswordValidator;
	protected $originalRequirements;
	protected $originalIsRunningTest;
	
	protected $mailer;
	
	protected static $is_running_test = false;
	
	public static function is_running_test() {
		return self::$is_running_test;
	}
	
	/**
	 * @var YamlFixture
	 */
	protected $fixture; 
	
	function setUp() {
		// Mark test as being run
		$this->originalIsRunningTest = self::$is_running_test;
		self::$is_running_test = true;
		
		// Remove password validation
		$this->originalMemberPasswordValidator = Member::password_validator();
		$this->originalRequirements = Requirements::backend();
		Member::set_password_validator(null);
		Cookie::set_report_errors(false);

		$className = get_class($this);
		$fixtureFile = eval("return {$className}::\$fixture_file;");
		
		// Set up fixture
		if($fixtureFile) {
			if(substr(DB::getConn()->currentDatabase(),0,5) != 'tmpdb') {
				//echo "Re-creating temp database... ";
				self::create_temp_db();
				//echo "done.\n";
			}

			// This code is a bit misplaced; we want some way of the whole session being reinitialised...
			Versioned::reading_stage(null);

			singleton('DataObject')->flushCache();

			$dbadmin = new DatabaseAdmin();
			$dbadmin->clearAllData();
			
			// We have to disable validation while we import the fixtures, as the order in
			// which they are imported doesnt guarantee valid relations until after the
			// import is complete.
			$validationenabled = DataObject::get_validation_enabled();
			DataObject::set_validation_enabled(false);
			$this->fixture = new YamlFixture($fixtureFile);
			$this->fixture->saveIntoDatabase();
			DataObject::set_validation_enabled($validationenabled);
		}
		
		// Set up email
		$this->originalMailer = Email::mailer();
		$this->mailer = new TestMailer();
		Email::set_mailer($this->mailer);
		Email::send_all_emails_to(null);
	}
	
	/**
	 * Called once per test case ({@link SapphireTest} subclass).
	 * This is different to {@link setUp()}, which gets called once
	 * per method. Useful to initialize expensive operations which
	 * don't change state for any called method inside the test,
	 * e.g. dynamically adding an extension. See {@link tear_down_once()}
	 * for tearing down the state again.
	 */
	static function set_up_once() {	
	}
	
	/**
	 * Array
	 */
	protected $fixtureDictionary;
	
	
	/**
	 * Get the ID of an object from the fixture.
	 * @param $className The data class, as specified in your fixture file.  Parent classes won't work
	 * @param $identifier The identifier string, as provided in your fixture file
	 */
	protected function idFromFixture($className, $identifier) {
		if($this->fixture) return $this->fixture->idFromFixture($className, $identifier);
		else user_error("You've called \$this->objFromFixture() but you haven't specified static \$fixture_file.\n" . 
			"Ensure that static \"\$fixture_file = 'module/tests/fixturefile.yml';\" is specified in your " .get_class($this). " class.", E_USER_WARNING);
	}
	
	/**
	 * Return all of the IDs in the fixture of a particular class name.
	 * @return A map of fixture-identifier => object-id
	 */
	protected function allFixtureIDs($className) {
		if($this->fixture) return $this->fixture->allFixtureIDs($className);
		else user_error("You've called \$this->objFromFixture() but you haven't specified static \$fixture_file.\n" . 
			"Ensure that static \"\$fixture_file = 'module/tests/fixturefile.yml';\" is specified in your " .get_class($this). " class.", E_USER_WARNING);
	}

	/**
	 * Get an object from the fixture.
	 * @param $className The data class, as specified in your fixture file.  Parent classes won't work
	 * @param $identifier The identifier string, as provided in your fixture file
	 */
	protected function objFromFixture($className, $identifier) {
		if($this->fixture) return $this->fixture->objFromFixture($className, $identifier);
		else user_error("You've called \$this->objFromFixture() but you haven't specified static \$fixture_file.\n" . 
			"Ensure that static \"\$fixture_file = 'module/tests/fixturefile.yml';\" is specified in your " .get_class($this). " class.", E_USER_WARNING);
	}
	
	/**
	 * Load a YAML fixture file into the database.
	 * Once loaded, you can use idFromFixture() and objFromFixture() to get items from the fixture
	 * @param $fixtureFile The location of the .yml fixture file, relative to the site base dir
	 */
	function loadFixture($fixtureFile) {
		$parser = new Spyc();
		$fixtureContent = $parser->load(Director::baseFolder().'/'.$fixtureFile);
		
		$this->fixture = new YamlFixture($fixtureFile);
		$this->fixture->saveIntoDatabase();
	}
	
	function tearDown() {
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
	}
	
	static function tear_down_once() {
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
	 * Backported from PHPUnit 3.4 in order to maintain backwards
	 * compatibility: assertType() is deprecated in PHPUnit 3.5 (with PHP 5.2.7+),
	 * but as SilverStripe 2.3 and 2.4 support PHP 5.1 we can't require it.
	 */
	public static function assertType($expected, $actual, $message = '') {
      // PHPUnit_Util_DeprecatedFeature_Logger::log(
      //   'assertType() will be removed in PHPUnit 3.6 and should no longer ' .
      //   'be used. assertInternalType() should be used for asserting ' .
      //   'internal types such as "integer" or "string" whereas ' .
      //   'assertInstanceOf() should be used for asserting that an object is ' .
      //   'an instance of a specified class or interface.'
      // );

      if (is_string($expected)) {
          if (PHPUnit_Util_Type::isType($expected)) {
              $constraint = new PHPUnit_Framework_Constraint_IsType(
                $expected
              );
          }

          else if (class_exists($expected) || interface_exists($expected)) {
              $constraint = new PHPUnit_Framework_Constraint_IsInstanceOf(
                $expected
              );
          }

          else {
              throw PHPUnit_Util_InvalidArgumentHelper::factory(
                1, 'class or interface name'
              );
          }
      } else {
          throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
      }

      self::assertThat($actual, $constraint, $message);
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
	
	static function kill_temp_db() {
		// Delete our temporary database
		if(self::using_temp_db()) {
			$dbConn = DB::getConn();
			$dbName = $dbConn->currentDatabase();
			if($dbName && DB::query("SHOW DATABASES LIKE '$dbName'")->value()) {
				// echo "Deleted temp database " . $dbConn->currentDatabase() . "\n";
				$dbConn->dropDatabase();
			}
		}
	}
	
	static function create_temp_db() {
		// Create a temporary database
		$dbConn = DB::getConn();
		$dbname = 'tmpdb' . rand(1000000,9999999);
		while(!$dbname || $dbConn->databaseExists($dbname)) {
			$dbname = 'tmpdb' . rand(1000000,9999999);
		}
		
		$dbConn->selectDatabase($dbname);
		$dbConn->createDatabase();

		$dbadmin = new DatabaseAdmin();
		$dbadmin->doBuild(true, false, true);
		
		return $dbname;
	}
	
	/**
	 * Create a member and group with the given permission code, and log in with it.
	 * Returns the member ID.
	 */
	function logInWithPermssion($permCode = "ADMIN") {
		if(!isset($this->cache_generatedMembers[$permCode])) {
			$group = new Group();
			$group->Title = "$permCode group";
			$group->write();

			$permission = new Permission();
			$permission->Code = $permCode;
			$permission->write();
			$group->Permissions()->add($permission);
			
			$member = new Member();
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
