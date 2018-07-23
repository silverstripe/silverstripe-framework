<?php

namespace SilverStripe\Dev;

use Exception;
use LogicException;
use PHPUnit_Framework_Constraint_Not;
use PHPUnit_Framework_TestCase;
use PHPUnit_Util_InvalidArgumentHelper;
use SilverStripe\CMS\Controllers\RootURLController;
use SilverStripe\Control\CLIRequestBuilder;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Cookie;
use SilverStripe\Control\Director;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\Email\Mailer;
use SilverStripe\Control\HTTPApplication;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Injector\InjectorLoader;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\Dev\Constraint\SSListContains;
use SilverStripe\Dev\Constraint\SSListContainsOnly;
use SilverStripe\Dev\Constraint\SSListContainsOnlyMatchingItems;
use SilverStripe\Dev\State\FixtureTestState;
use SilverStripe\Dev\State\SapphireTestState;
use SilverStripe\Dev\State\TestState;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\Connect\TempDatabase;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\SS_List;
use SilverStripe\Security\Group;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\View\SSViewer;
use SilverStripe\Core\Manifest\ModuleResourceLoader;

if (!class_exists(PHPUnit_Framework_TestCase::class)) {
    return;
}

/**
 * Test case class for the Sapphire framework.
 * Sapphire unit testing is based on PHPUnit, but provides a number of hooks into our data model that make it easier
 * to work with.
 *
 * This class should not be used anywhere outside of unit tests, as phpunit may not be installed
 * in production sites.
 */
class SapphireTest extends PHPUnit_Framework_TestCase implements TestOnly
{
    /**
     * Path to fixture data for this test run.
     * If passed as an array, multiple fixture files will be loaded.
     * Please note that you won't be able to refer with "=>" notation
     * between the fixtures, they act independent of each other.
     *
     * @var string|array
     */
    protected static $fixture_file = null;

    /**
     * @var FixtureFactory
     */
    protected $fixtureFactory;

    /**
     * @var Boolean If set to TRUE, this will force a test database to be generated
     * in {@link setUp()}. Note that this flag is overruled by the presence of a
     * {@link $fixture_file}, which always forces a database build.
     *
     * @var bool
     */
    protected $usesDatabase = null;

    /**
     * This test will cleanup its state via transactions.
     * If set to false a full schema is forced between tests, but at a performance cost.
     *
     * @var bool
     */
    protected $usesTransactions = true;

    /**
     * @var bool
     */
    protected static $is_running_test = false;

    /**
     * By default, setUp() does not require default records. Pass
     * class names in here, and the require/augment default records
     * function will be called on them.
     *
     * @var array
     */
    protected $requireDefaultRecordsFrom = array();

    /**
     * A list of extensions that can't be applied during the execution of this run.  If they are
     * applied, they will be temporarily removed and a database migration called.
     *
     * The keys of the are the classes that the extensions can't be applied the extensions to, and
     * the values are an array of illegal extensions on that class.
     *
     * Set a class to `*` to remove all extensions (unadvised)
     *
     * @var array
     */
    protected static $illegal_extensions = [];

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
     *
     * @var array
     */
    protected static $required_extensions = [];

    /**
     * By default, the test database won't contain any DataObjects that have the interface TestOnly.
     * This variable lets you define additional TestOnly DataObjects to set up for this test.
     * Set it to an array of DataObject subclass names.
     *
     * @var array
     */
    protected static $extra_dataobjects = [];

    /**
     * List of class names of {@see Controller} objects to register routes for
     * Controllers must implement Link() method
     *
     * @var array
     */
    protected static $extra_controllers = [];

    /**
     * We need to disabling backing up of globals to avoid overriding
     * the few globals SilverStripe relies on, like $lang for the i18n subsystem.
     *
     * @see http://sebastian-bergmann.de/archives/797-Global-Variables-and-PHPUnit.html
     */
    protected $backupGlobals = false;

    /**
     * State management container for SapphireTest
     *
     * @var SapphireTestState
     */
    protected static $state = null;

    /**
     * Temp database helper
     *
     * @var TempDatabase
     */
    protected static $tempDB = null;

    /**
     * @return TempDatabase
     */
    public static function tempDB()
    {
        if (!static::$tempDB) {
            static::$tempDB = TempDatabase::create();
        }
        return static::$tempDB;
    }

    /**
     * Gets illegal extensions for this class
     *
     * @return array
     */
    public static function getIllegalExtensions()
    {
        return static::$illegal_extensions;
    }

    /**
     * Gets required extensions for this class
     *
     * @return array
     */
    public static function getRequiredExtensions()
    {
        return static::$required_extensions;
    }

    /**
     * Check if test bootstrapping has been performed. Must not be relied on
     * outside of unit tests.
     *
     * @return bool
     */
    protected static function is_running_test()
    {
        return self::$is_running_test;
    }

    /**
     * Set test running state
     *
     * @param bool $bool
     */
    protected static function set_is_running_test($bool)
    {
        self::$is_running_test = $bool;
    }

    /**
     * @return String
     */
    public static function get_fixture_file()
    {
        return static::$fixture_file;
    }

    /**
     * @return bool
     */
    public function getUsesDatabase()
    {
        return $this->usesDatabase;
    }

    /**
     * @return bool
     */
    public function getUsesTransactions()
    {
        return $this->usesTransactions;
    }

    /**
     * @return array
     */
    public function getRequireDefaultRecordsFrom()
    {
        return $this->requireDefaultRecordsFrom;
    }

    /**
     * Setup  the test.
     * Always sets up in order:
     *  - Reset php state
     *  - Nest
     *  - Custom state helpers
     *
     * User code should call parent::setUp() before custom setup code
     */
    protected function setUp()
    {
        if (!defined('FRAMEWORK_PATH')) {
            trigger_error(
                'Missing constants, did you remember to include the test bootstrap in your phpunit.xml file?',
                E_USER_WARNING
            );
        }

        // Call state helpers
        static::$state->setUp($this);

        // We cannot run the tests on this abstract class.
        if (static::class == __CLASS__) {
            $this->markTestSkipped(sprintf('Skipping %s ', static::class));
            return;
        }

        // i18n needs to be set to the defaults or tests fail
        i18n::set_locale(i18n::config()->uninherited('default_locale'));

        // Set default timezone consistently to avoid NZ-specific dependencies
        date_default_timezone_set('UTC');

        Member::set_password_validator(null);
        Cookie::config()->update('report_errors', false);
        if (class_exists(RootURLController::class)) {
            RootURLController::reset();
        }

        Security::clear_database_is_ready();

        // Set up test routes
        $this->setUpRoutes();

        $fixtureFiles = $this->getFixturePaths();

        if ($this->shouldSetupDatabaseForCurrentTest($fixtureFiles)) {
            /** @var FixtureTestState $fixtureState */
            $fixtureState = static::$state->getStateByName('fixtures');
            $this->setFixtureFactory($fixtureState->getFixtureFactory(static::class));
            $this->logInWithPermission('ADMIN');
        }

        // turn off template debugging
        SSViewer::config()->update('source_file_comments', false);

        // Set up the test mailer
        Injector::inst()->registerService(new TestMailer(), Mailer::class);
        Email::config()->remove('send_all_emails_to');
        Email::config()->remove('send_all_emails_from');
        Email::config()->remove('cc_all_emails_to');
        Email::config()->remove('bcc_all_emails_to');
    }



    /**
     * Helper method to determine if the current test should enable a test database
     *
     * @param $fixtureFiles
     * @return bool
     */
    protected function shouldSetupDatabaseForCurrentTest($fixtureFiles)
    {
        $databaseEnabledByDefault = $fixtureFiles || $this->usesDatabase;

        return ($databaseEnabledByDefault && !$this->currentTestDisablesDatabase())
            || $this->currentTestEnablesDatabase();
    }

    /**
     * Helper method to check, if the current test uses the database.
     * This can be switched on with the annotation "@useDatabase"
     *
     * @return bool
     */
    protected function currentTestEnablesDatabase()
    {
        $annotations = $this->getAnnotations();

        return array_key_exists('useDatabase', $annotations['method'])
            && $annotations['method']['useDatabase'][0] !== 'false';
    }

    /**
     * Helper method to check, if the current test uses the database.
     * This can be switched on with the annotation "@useDatabase false"
     *
     * @return bool
     */
    protected function currentTestDisablesDatabase()
    {
        $annotations = $this->getAnnotations();

        return array_key_exists('useDatabase', $annotations['method'])
            && $annotations['method']['useDatabase'][0] === 'false';
    }

    /**
     * Called once per test case ({@link SapphireTest} subclass).
     * This is different to {@link setUp()}, which gets called once
     * per method. Useful to initialize expensive operations which
     * don't change state for any called method inside the test,
     * e.g. dynamically adding an extension. See {@link teardownAfterClass()}
     * for tearing down the state again.
     *
     * Always sets up in order:
     *  - Reset php state
     *  - Nest
     *  - Custom state helpers
     *
     * User code should call parent::setUpBeforeClass() before custom setup code
     *
     * @throws Exception
     */
    public static function setUpBeforeClass()
    {
        // Start tests
        static::start();

        if (!static::$state) {
            throw new Exception('SapphireTest failed to bootstrap!');
        }

        // Call state helpers
        static::$state->setUpOnce(static::class);

        // Build DB if we have objects
        if (static::getExtraDataObjects()) {
            DataObject::reset();
            static::resetDBSchema(true, true);
        }
    }

    /**
     * tearDown method that's called once per test class rather once per test method.
     *
     * Always sets up in order:
     *  - Custom state helpers
     *  - Unnest
     *  - Reset php state
     *
     * User code should call parent::tearDownAfterClass() after custom tear down code
     */
    public static function tearDownAfterClass()
    {
        // Call state helpers
        static::$state->tearDownOnce(static::class);

        // Reset DB schema
        static::resetDBSchema();
    }

    /**
     * @deprecated 4.0..5.0
     * @return FixtureFactory|false
     */
    public function getFixtureFactory()
    {
        Deprecation::notice('5.0', __FUNCTION__ . ' is deprecated, use ' . FixtureTestState::class . ' instead');
        /** @var FixtureTestState $state */
        $state = static::$state->getStateByName('fixtures');
        return $state->getFixtureFactory(static::class);
    }

    /**
     * Sets a new fixture factory
     * @deprecated 4.0..5.0
     * @param FixtureFactory $factory
     * @return $this
     */
    public function setFixtureFactory(FixtureFactory $factory)
    {
        Deprecation::notice('5.0', __FUNCTION__ . ' is deprecated, use ' . FixtureTestState::class . ' instead');
        /** @var FixtureTestState $state */
        $state = static::$state->getStateByName('fixtures');
        $state->setFixtureFactory($factory, static::class);
        $this->fixtureFactory = $factory;
        return $this;
    }

    /**
     * Get the ID of an object from the fixture.
     *
     * @param string $className The data class or table name, as specified in your fixture file.  Parent classes won't work
     * @param string $identifier The identifier string, as provided in your fixture file
     * @return int
     */
    protected function idFromFixture($className, $identifier)
    {
        $id = $this->getFixtureFactory()->getId($className, $identifier);

        if (!$id) {
            user_error(sprintf(
                "Couldn't find object '%s' (class: %s)",
                $identifier,
                $className
            ), E_USER_ERROR);
        }

        return $id;
    }

    /**
     * Return all of the IDs in the fixture of a particular class name.
     * Will collate all IDs form all fixtures if multiple fixtures are provided.
     *
     * @param string $className The data class or table name, as specified in your fixture file
     * @return array A map of fixture-identifier => object-id
     */
    protected function allFixtureIDs($className)
    {
        return $this->getFixtureFactory()->getIds($className);
    }

    /**
     * Get an object from the fixture.
     *
     * @param string $className The data class or table name, as specified in your fixture file. Parent classes won't work
     * @param string $identifier The identifier string, as provided in your fixture file
     *
     * @return DataObject
     */
    protected function objFromFixture($className, $identifier)
    {
        $obj = $this->getFixtureFactory()->get($className, $identifier);

        if (!$obj) {
            user_error(sprintf(
                "Couldn't find object '%s' (class: %s)",
                $identifier,
                $className
            ), E_USER_ERROR);
        }

        return $obj;
    }

    /**
     * Load a YAML fixture file into the database.
     * Once loaded, you can use idFromFixture() and objFromFixture() to get items from the fixture.
     * Doesn't clear existing fixtures.
     * @deprecated 4.0...5.0
     *
     * @param string $fixtureFile The location of the .yml fixture file, relative to the site base dir
     */
    public function loadFixture($fixtureFile)
    {
        Deprecation::notice('5.0', __FUNCTION__ . ' is deprecated, use ' . FixtureTestState::class . ' instead');
        $fixture = Injector::inst()->create(YamlFixture::class, $fixtureFile);
        $fixture->writeInto($this->getFixtureFactory());
    }

    /**
     * Clear all fixtures which were previously loaded through
     * {@link loadFixture()}
     */
    public function clearFixtures()
    {
        $this->getFixtureFactory()->clear();
    }

    /**
     * Useful for writing unit tests without hardcoding folder structures.
     *
     * @return string Absolute path to current class.
     */
    protected function getCurrentAbsolutePath()
    {
        $filename = ClassLoader::inst()->getItemPath(static::class);
        if (!$filename) {
            throw new LogicException('getItemPath returned null for ' . static::class
                . '. Try adding flush=1 to the test run.');
        }
        return dirname($filename);
    }

    /**
     * @return string File path relative to webroot
     */
    protected function getCurrentRelativePath()
    {
        $base = Director::baseFolder();
        $path = $this->getCurrentAbsolutePath();
        if (substr($path, 0, strlen($base)) == $base) {
            $path = preg_replace('/^\/*/', '', substr($path, strlen($base)));
        }
        return $path;
    }

    /**
     * Setup  the test.
     * Always sets up in order:
     *  - Custom state helpers
     *  - Unnest
     *  - Reset php state
     *
     * User code should call parent::tearDown() after custom tear down code
     */
    protected function tearDown()
    {
        // Reset mocked datetime
        DBDatetime::clear_mock_now();

        // Stop the redirection that might have been requested in the test.
        // Note: Ideally a clean Controller should be created for each test.
        // Now all tests executed in a batch share the same controller.
        $controller = Controller::has_curr() ? Controller::curr() : null;
        if ($controller && ($response = $controller->getResponse()) && $response->getHeader('Location')) {
            $response->setStatusCode(200);
            $response->removeHeader('Location');
        }

        // Call state helpers
        static::$state->tearDown($this);
    }

    public static function assertContains(
        $needle,
        $haystack,
        $message = '',
        $ignoreCase = false,
        $checkForObjectIdentity = true,
        $checkForNonObjectIdentity = false
    ) {
        if ($haystack instanceof DBField) {
            $haystack = (string)$haystack;
        }
        parent::assertContains($needle, $haystack, $message, $ignoreCase, $checkForObjectIdentity, $checkForNonObjectIdentity);
    }

    public static function assertNotContains(
        $needle,
        $haystack,
        $message = '',
        $ignoreCase = false,
        $checkForObjectIdentity = true,
        $checkForNonObjectIdentity = false
    ) {
        if ($haystack instanceof DBField) {
            $haystack = (string)$haystack;
        }
        parent::assertNotContains($needle, $haystack, $message, $ignoreCase, $checkForObjectIdentity, $checkForNonObjectIdentity);
    }

    /**
     * Clear the log of emails sent
     *
     * @return bool True if emails cleared
     */
    public function clearEmails()
    {
        /** @var Mailer $mailer */
        $mailer = Injector::inst()->get(Mailer::class);
        if ($mailer instanceof TestMailer) {
            $mailer->clearEmails();
            return true;
        }
        return false;
    }

    /**
     * Search for an email that was sent.
     * All of the parameters can either be a string, or, if they start with "/", a PREG-compatible regular expression.
     * @param string $to
     * @param string $from
     * @param string $subject
     * @param string $content
     * @return array|null Contains keys: 'Type', 'To', 'From', 'Subject', 'Content', 'PlainContent', 'AttachedFiles',
     *               'HtmlContent'
     */
    public static function findEmail($to, $from = null, $subject = null, $content = null)
    {
        /** @var Mailer $mailer */
        $mailer = Injector::inst()->get(Mailer::class);
        if ($mailer instanceof TestMailer) {
            return $mailer->findEmail($to, $from, $subject, $content);
        }
        return null;
    }

    /**
     * Assert that the matching email was sent since the last call to clearEmails()
     * All of the parameters can either be a string, or, if they start with "/", a PREG-compatible regular expression.
     *
     * @param string $to
     * @param string $from
     * @param string $subject
     * @param string $content
     */
    public static function assertEmailSent($to, $from = null, $subject = null, $content = null)
    {
        $found = (bool)static::findEmail($to, $from, $subject, $content);

        $infoParts = '';
        $withParts = array();
        if ($to) {
            $infoParts .= " to '$to'";
        }
        if ($from) {
            $infoParts .= " from '$from'";
        }
        if ($subject) {
            $withParts[] = "subject '$subject'";
        }
        if ($content) {
            $withParts[] = "content '$content'";
        }
        if ($withParts) {
            $infoParts .= ' with ' . implode(' and ', $withParts);
        }

        static::assertTrue(
            $found,
            "Failed asserting that an email was sent$infoParts."
        );
    }


    /**
     * Assert that the given {@link SS_List} includes DataObjects matching the given key-value
     * pairs.  Each match must correspond to 1 distinct record.
     *
     * @param SS_List|array $matches The patterns to match.  Each pattern is a map of key-value pairs.  You can
     * either pass a single pattern or an array of patterns.
     * @param SS_List $list The {@link SS_List} to test.
     * @param string $message
     *
     * Examples
     * --------
     * Check that $members includes an entry with Email = sam@example.com:
     *      $this->assertListContains(['Email' => '...@example.com'], $members);
     *
     * Check that $members includes entries with Email = sam@example.com and with
     * Email = ingo@example.com:
     *      $this->assertListContains([
     *         ['Email' => '...@example.com'],
     *         ['Email' => 'i...@example.com'],
     *      ], $members);
     */
    public static function assertListContains($matches, SS_List $list, $message = '')
    {
        if (!is_array($matches)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(
                1,
                'array'
            );
        }

        static::assertThat(
            $list,
            new SSListContains(
                $matches
            ),
            $message
        );
    }

    /**
     * @deprecated 4.0.0:5.0.0 Use assertListContains() instead
     *
     * @param $matches
     * @param $dataObjectSet
     */
    public function assertDOSContains($matches, $dataObjectSet)
    {
        Deprecation::notice('5.0', 'Use assertListContains() instead');
        return static::assertListContains($matches, $dataObjectSet);
    }

    /**
     * Asserts that no items in a given list appear in the given dataobject list
     *
     * @param SS_List|array $matches The patterns to match.  Each pattern is a map of key-value pairs.  You can
     * either pass a single pattern or an array of patterns.
     * @param SS_List $list The {@link SS_List} to test.
     * @param string $message
     *
     * Examples
     * --------
     * Check that $members doesn't have an entry with Email = sam@example.com:
     *      $this->assertListNotContains(['Email' => '...@example.com'], $members);
     *
     * Check that $members doesn't have entries with Email = sam@example.com and with
     * Email = ingo@example.com:
     *      $this->assertListNotContains([
     *          ['Email' => '...@example.com'],
     *          ['Email' => 'i...@example.com'],
     *      ], $members);
     */
    public static function assertListNotContains($matches, SS_List $list, $message = '')
    {
        if (!is_array($matches)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(
                1,
                'array'
            );
        }

        $constraint =  new PHPUnit_Framework_Constraint_Not(
            new SSListContains(
                $matches
            )
        );

        static::assertThat(
            $list,
            $constraint,
            $message
        );
    }

    /**
     * @deprecated 4.0.0:5.0.0 Use assertListNotContains() instead
     *
     * @param $matches
     * @param $dataObjectSet
     */
    public static function assertNotDOSContains($matches, $dataObjectSet)
    {
        Deprecation::notice('5.0', 'Use assertListNotContains() instead');
        return static::assertListNotContains($matches, $dataObjectSet);
    }

    /**
     * Assert that the given {@link SS_List} includes only DataObjects matching the given
     * key-value pairs.  Each match must correspond to 1 distinct record.
     *
     * Example
     * --------
     * Check that *only* the entries Sam Minnee and Ingo Schommer exist in $members.  Order doesn't
     * matter:
     *     $this->assertListEquals([
     *        ['FirstName' =>'Sam', 'Surname' => 'Minnee'],
     *        ['FirstName' => 'Ingo', 'Surname' => 'Schommer'],
     *      ], $members);
     *
     * @param mixed $matches The patterns to match.  Each pattern is a map of key-value pairs.  You can
     * either pass a single pattern or an array of patterns.
     * @param mixed $list The {@link SS_List} to test.
     * @param string $message
     */
    public static function assertListEquals($matches, SS_List $list, $message = '')
    {
        if (!is_array($matches)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(
                1,
                'array'
            );
        }

        static::assertThat(
            $list,
            new SSListContainsOnly(
                $matches
            ),
            $message
        );
    }

    /**
     * @deprecated 4.0.0:5.0.0 Use assertListEquals() instead
     *
     * @param $matches
     * @param SS_List $dataObjectSet
     */
    public function assertDOSEquals($matches, $dataObjectSet)
    {
        Deprecation::notice('5.0', 'Use assertListEquals() instead');
        return static::assertListEquals($matches, $dataObjectSet);
    }


    /**
     * Assert that the every record in the given {@link SS_List} matches the given key-value
     * pairs.
     *
     * Example
     * --------
     * Check that every entry in $members has a Status of 'Active':
     *     $this->assertListAllMatch(['Status' => 'Active'], $members);
     *
     * @param mixed $match The pattern to match.  The pattern is a map of key-value pairs.
     * @param mixed $list The {@link SS_List} to test.
     * @param string $message
     */
    public static function assertListAllMatch($match, SS_List $list, $message = '')
    {
        if (!is_array($match)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(
                1,
                'array'
            );
        }

        static::assertThat(
            $list,
            new SSListContainsOnlyMatchingItems(
                $match
            ),
            $message
        );
    }

    /**
     * @deprecated 4.0.0:5.0.0 Use assertListAllMatch() instead
     *
     * @param $match
     * @param SS_List $dataObjectSet
     */
    public function assertDOSAllMatch($match, SS_List $dataObjectSet)
    {
        Deprecation::notice('5.0', 'Use assertListAllMatch() instead');
        return static::assertListAllMatch($match, $dataObjectSet);
    }

    /**
     * Removes sequences of repeated whitespace characters from SQL queries
     * making them suitable for string comparison
     *
     * @param string $sql
     * @return string The cleaned and normalised SQL string
     */
    protected static function normaliseSQL($sql)
    {
        return trim(preg_replace('/\s+/m', ' ', $sql));
    }

    /**
     * Asserts that two SQL queries are equivalent
     *
     * @param string $expectedSQL
     * @param string $actualSQL
     * @param string $message
     * @param float|int $delta
     * @param integer $maxDepth
     * @param boolean $canonicalize
     * @param boolean $ignoreCase
     */
    public static function assertSQLEquals(
        $expectedSQL,
        $actualSQL,
        $message = '',
        $delta = 0,
        $maxDepth = 10,
        $canonicalize = false,
        $ignoreCase = false
    ) {
        // Normalise SQL queries to remove patterns of repeating whitespace
        $expectedSQL = static::normaliseSQL($expectedSQL);
        $actualSQL = static::normaliseSQL($actualSQL);

        static::assertEquals($expectedSQL, $actualSQL, $message, $delta, $maxDepth, $canonicalize, $ignoreCase);
    }

    /**
     * Asserts that a SQL query contains a SQL fragment
     *
     * @param string $needleSQL
     * @param string $haystackSQL
     * @param string $message
     * @param boolean $ignoreCase
     * @param boolean $checkForObjectIdentity
     */
    public static function assertSQLContains(
        $needleSQL,
        $haystackSQL,
        $message = '',
        $ignoreCase = false,
        $checkForObjectIdentity = true
    ) {
        $needleSQL = static::normaliseSQL($needleSQL);
        $haystackSQL = static::normaliseSQL($haystackSQL);

        static::assertContains($needleSQL, $haystackSQL, $message, $ignoreCase, $checkForObjectIdentity);
    }

    /**
     * Asserts that a SQL query contains a SQL fragment
     *
     * @param string $needleSQL
     * @param string $haystackSQL
     * @param string $message
     * @param boolean $ignoreCase
     * @param boolean $checkForObjectIdentity
     */
    public static function assertSQLNotContains(
        $needleSQL,
        $haystackSQL,
        $message = '',
        $ignoreCase = false,
        $checkForObjectIdentity = true
    ) {
        $needleSQL = static::normaliseSQL($needleSQL);
        $haystackSQL = static::normaliseSQL($haystackSQL);

        static::assertNotContains($needleSQL, $haystackSQL, $message, $ignoreCase, $checkForObjectIdentity);
    }

    /**
     * Start test environment
     */
    public static function start()
    {
        if (static::is_running_test()) {
            return;
        }

        // Health check
        if (InjectorLoader::inst()->countManifests()) {
            throw new LogicException('SapphireTest::start() cannot be called within another application');
        }
        static::set_is_running_test(true);

        // Mock request
        $_SERVER['argv'] = ['vendor/bin/phpunit', '/'];
        $request = CLIRequestBuilder::createFromEnvironment();

        // Test application
        $kernel = new TestKernel(BASE_PATH);
        $app = new HTTPApplication($kernel);

        // Custom application
        $app->execute($request, function (HTTPRequest $request) {
            // Start session and execute
            $request->getSession()->init($request);

            // Invalidate classname spec since the test manifest will now pull out new subclasses for each internal class
            // (e.g. Member will now have various subclasses of DataObjects that implement TestOnly)
            DataObject::reset();

            // Set dummy controller;
            $controller = Controller::create();
            $controller->setRequest($request);
            $controller->pushCurrent();
            $controller->doInit();
        }, true);

        // Register state
        static::$state = SapphireTestState::singleton();
        // Register temp DB holder
        static::tempDB();
    }

    /**
     * Reset the testing database's schema, but only if it is active
     * @param bool $includeExtraDataObjects If true, the extraDataObjects tables will also be included
     * @param bool $forceCreate Force DB to be created if it doesn't exist
     */
    public static function resetDBSchema($includeExtraDataObjects = false, $forceCreate = false)
    {
        // Check if DB is active before reset
        if (!static::$tempDB->isUsed()) {
            if (!$forceCreate) {
                return;
            }
            static::$tempDB->build();
        }
        $extraDataObjects = $includeExtraDataObjects ? static::getExtraDataObjects() : [];
        static::$tempDB->resetDBSchema((array)$extraDataObjects);
    }

    /**
     * A wrapper for automatically performing callbacks as a user with a specific permission
     *
     * @param string|array $permCode
     * @param callable $callback
     * @return mixed
     */
    public function actWithPermission($permCode, $callback)
    {
        return Member::actAs($this->createMemberWithPermission($permCode), $callback);
    }

    /**
     * Create Member and Group objects on demand with specific permission code
     *
     * @param string|array $permCode
     * @return Member
     */
    protected function createMemberWithPermission($permCode)
    {
        if (is_array($permCode)) {
            $permArray = $permCode;
            $permCode = implode('.', $permCode);
        } else {
            $permArray = array($permCode);
        }

        // Check cached member
        if (isset($this->cache_generatedMembers[$permCode])) {
            $member = $this->cache_generatedMembers[$permCode];
        } else {
            // Generate group with these permissions
            $group = Group::create();
            $group->Title = "$permCode group";
            $group->write();

            // Create each individual permission
            foreach ($permArray as $permArrayItem) {
                $permission = Permission::create();
                $permission->Code = $permArrayItem;
                $permission->write();
                $group->Permissions()->add($permission);
            }

            $member = Member::get()->filter([
                'Email' => "$permCode@example.org",
            ])->first();
            if (!$member) {
                $member = Member::create();
            }

            $member->FirstName = $permCode;
            $member->Surname = 'User';
            $member->Email = "$permCode@example.org";
            $member->write();
            $group->Members()->add($member);

            $this->cache_generatedMembers[$permCode] = $member;
        }
        return $member;
    }

    /**
     * Create a member and group with the given permission code, and log in with it.
     * Returns the member ID.
     *
     * @param string|array $permCode Either a permission, or list of permissions
     * @return int Member ID
     */
    public function logInWithPermission($permCode = 'ADMIN')
    {
        $member = $this->createMemberWithPermission($permCode);
        $this->logInAs($member);
        return $member->ID;
    }

    /**
     * Log in as the given member
     *
     * @param Member|int|string $member The ID, fixture codename, or Member object of the member that you want to log in
     */
    public function logInAs($member)
    {
        if (is_numeric($member)) {
            $member = DataObject::get_by_id(Member::class, $member);
        } elseif (!is_object($member)) {
            $member = $this->objFromFixture(Member::class, $member);
        }
        Injector::inst()->get(IdentityStore::class)->logIn($member);
    }

    /**
     * Log out the current user
     */
    public function logOut()
    {
        /** @var IdentityStore $store */
        $store = Injector::inst()->get(IdentityStore::class);
        $store->logOut();
    }

    /**
     * Cache for logInWithPermission()
     */
    protected $cache_generatedMembers = array();

    /**
     * Test against a theme.
     *
     * @param string $themeBaseDir themes directory
     * @param string $theme Theme name
     * @param callable $callback
     * @throws Exception
     */
    protected function useTestTheme($themeBaseDir, $theme, $callback)
    {
        Config::nest();
        if (strpos($themeBaseDir, BASE_PATH) === 0) {
            $themeBaseDir = substr($themeBaseDir, strlen(BASE_PATH));
        }
        SSViewer::config()->update('theme_enabled', true);
        SSViewer::set_themes([$themeBaseDir . '/themes/' . $theme, '$default']);

        try {
            $callback();
        } finally {
            Config::unnest();
        }
    }

    /**
     * Get fixture paths for this test
     *
     * @return array List of paths
     */
    protected function getFixturePaths()
    {
        $fixtureFile = static::get_fixture_file();
        if (empty($fixtureFile)) {
            return [];
        }

        $fixtureFiles = is_array($fixtureFile) ? $fixtureFile : [$fixtureFile];

        return array_map(function ($fixtureFilePath) {
            return $this->resolveFixturePath($fixtureFilePath);
        }, $fixtureFiles);
    }

    /**
     * Return all extra objects to scaffold for this test
     * @return array
     */
    public static function getExtraDataObjects()
    {
        return static::$extra_dataobjects;
    }

    /**
     * Get additional controller classes to register routes for
     *
     * @return array
     */
    public static function getExtraControllers()
    {
        return static::$extra_controllers;
    }

    /**
     * Map a fixture path to a physical file
     *
     * @param string $fixtureFilePath
     * @return string
     */
    protected function resolveFixturePath($fixtureFilePath)
    {
        // support loading via composer name path.
        if (strpos($fixtureFilePath, ':') !== false) {
            return ModuleResourceLoader::singleton()->resolvePath($fixtureFilePath);
        }

        // Support fixture paths relative to the test class, rather than relative to webroot
        // String checking is faster than file_exists() calls.
        $resolvedPath = realpath($this->getCurrentAbsolutePath() . '/' . $fixtureFilePath);
        if ($resolvedPath) {
            return $resolvedPath;
        }

        // Check if file exists relative to base dir
        $resolvedPath = realpath(Director::baseFolder() . '/' . $fixtureFilePath);
        if ($resolvedPath) {
            return $resolvedPath;
        }

        return $fixtureFilePath;
    }

    protected function setUpRoutes()
    {
        // Get overridden routes
        $rules = $this->getExtraRoutes();

        // Add all other routes
        foreach (Director::config()->uninherited('rules') as $route => $rule) {
            if (!isset($rules[$route])) {
                $rules[$route] = $rule;
            }
        }

        // Add default catch-all rule
        $rules['$Controller//$Action/$ID/$OtherID'] = '*';

        // Add controller-name auto-routing
        Director::config()->set('rules', $rules);
    }

    /**
     * Get extra routes to merge into Director.rules
     *
     * @return array
     */
    protected function getExtraRoutes()
    {
        $rules = [];
        foreach ($this->getExtraControllers() as $class) {
            $controllerInst = Controller::singleton($class);
            $link = Director::makeRelative($controllerInst->Link());
            $route = rtrim($link, '/') . '//$Action/$ID/$OtherID';
            $rules[$route] = $class;
        }
        return $rules;
    }
}
