<?php

namespace SilverStripe\Dev;

use Exception;
use LogicException;
use PHPUnit_Framework_TestCase;
use SilverStripe\CMS\Controllers\RootURLController;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Cookie;
use SilverStripe\Control\Director;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\Email\Mailer;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\HTTPApplication;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Injector\InjectorLoader;
use SilverStripe\Core\Kernel;
use SilverStripe\Core\TestKernel;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\SS_List;
use SilverStripe\Security\Group;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\View\SSViewer;

if (!class_exists(PHPUnit_Framework_TestCase::class)) {
    return;
}

/**
 * Test case class for the Sapphire framework.
 * Sapphire unit testing is based on PHPUnit, but provides a number of hooks into our data model that make it easier
 * to work with.
 */
class SapphireTest extends PHPUnit_Framework_TestCase
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
     */
    protected $usesDatabase = null;

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
     */
    protected static $required_extensions = [];

    /**
     * By default, the test database won't contain any DataObjects that have the interface TestOnly.
     * This variable lets you define additional TestOnly DataObjects to set up for this test.
     * Set it to an array of DataObject subclass names.
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
     * Test application kernel stace.
     *
     * @var TestKernel[]
     */
    protected static $kernels = [];

    /**
     * Get active Kernel instance
     *
     * @return TestKernel
     */
    protected static function kernel()
    {
        return end(static::$kernels);
    }

    /**
     * State management container for SapphireTest
     *
     * @var TestState
     */
    protected static $state = null;

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
        // Reset state
        static::kernel()->reset();

        // Nest
        static::$kernels[] = static::kernel()->nest();

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

        // Set up fixture
        if ($fixtureFiles || $this->usesDatabase) {
            if (!self::using_temp_db()) {
                self::create_temp_db();
            }

            DataObject::singleton()->flushCache();

            self::empty_temp_db();

            foreach ($this->requireDefaultRecordsFrom as $className) {
                $instance = singleton($className);
                if (method_exists($instance, 'requireDefaultRecords')) {
                    $instance->requireDefaultRecords();
                }
                if (method_exists($instance, 'augmentDefaultRecords')) {
                    $instance->augmentDefaultRecords();
                }
            }

            foreach ($fixtureFiles as $fixtureFilePath) {
                $fixture = YamlFixture::create($fixtureFilePath);
                $fixture->writeInto($this->getFixtureFactory());
            }

            $this->logInWithPermission("ADMIN");
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
     */
    public static function setUpBeforeClass()
    {
        // Start tests
        static::start();

        // Reset kernel
        static::kernel()->reset();

        // Nest kernel
        static::$kernels[] = static::kernel()->nest();

        // Call state helpers
        static::$state->setUpOnce(static::class);

        // Build DB if we have objects
        if (static::getExtraDataObjects()) {
            DataObject::reset();
            if (!self::using_temp_db()) {
                self::create_temp_db();
            }
            static::resetDBSchema(true);
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

        // Unnest
        array_pop(static::$kernels);
        static::kernel()->activate();

        // Reset PHP state
        static::kernel()->reset();

        // Reset DB schema
        static::resetDBSchema();
    }

    /**
     * @return FixtureFactory
     */
    public function getFixtureFactory()
    {
        if (!$this->fixtureFactory) {
            $this->fixtureFactory = Injector::inst()->create(FixtureFactory::class);
        }
        return $this->fixtureFactory;
    }

    /**
     * Sets a new fixture factory
     *
     * @param FixtureFactory $factory
     * @return $this
     */
    public function setFixtureFactory(FixtureFactory $factory)
    {
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
     *
     * @param string $fixtureFile The location of the .yml fixture file, relative to the site base dir
     */
    public function loadFixture($fixtureFile)
    {
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
        $filename = static::kernel()->getClassLoader()->getItemPath(static::class);
        if (!$filename) {
            throw new LogicException("getItemPath returned null for " . static::class);
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

        // Unnest
        array_pop(static::$kernels);
        static::kernel()->activate();

        // Reset state
        static::kernel()->reset();
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
     * @return array Contains keys: 'type', 'to', 'from', 'subject','content', 'plainContent', 'attachedFiles',
     *               'customHeaders', 'htmlContent', 'inlineImages'
     */
    public function findEmail($to, $from = null, $subject = null, $content = null)
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
    public function assertEmailSent($to, $from = null, $subject = null, $content = null)
    {
        $found = (bool)$this->findEmail($to, $from, $subject, $content);

        $infoParts = "";
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
            $infoParts .= " with " . implode(" and ", $withParts);
        }

        $this->assertTrue(
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
     * @param SS_List $dataObjectSet The {@link SS_List} to test.
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
    public function assertDOSContains($matches, $dataObjectSet)
    {
        $extracted = array();
        foreach ($dataObjectSet as $object) {
            /** @var DataObject $object */
            $extracted[] = $object->toMap();
        }

        foreach ($matches as $match) {
            $matched = false;
            foreach ($extracted as $i => $item) {
                if ($this->dataObjectArrayMatch($item, $match)) {
                    // Remove it from $extracted so that we don't get duplicate mapping.
                    unset($extracted[$i]);
                    $matched = true;
                    break;
                }
            }

            // We couldn't find a match - assertion failed
            $this->assertTrue(
                $matched,
                "Failed asserting that the SS_List contains an item matching "
                . var_export($match, true) . "\n\nIn the following SS_List:\n"
                . $this->DOSSummaryForMatch($dataObjectSet, $match)
            );
        }
    }
    /**
     * Asserts that no items in a given list appear in the given dataobject list
     *
     * @param SS_List|array $matches The patterns to match.  Each pattern is a map of key-value pairs.  You can
     * either pass a single pattern or an array of patterns.
     * @param SS_List $dataObjectSet The {@link SS_List} to test.
     *
     * Examples
     * --------
     * Check that $members doesn't have an entry with Email = sam@example.com:
     *      $this->assertNotDOSContains(array('Email' => '...@example.com'), $members);
     *
     * Check that $members doesn't have entries with Email = sam@example.com and with
     * Email = ingo@example.com:
     *      $this->assertNotDOSContains(array(
     *         array('Email' => '...@example.com'),
     *         array('Email' => 'i...@example.com'),
     *      ), $members);
     */
    public function assertNotDOSContains($matches, $dataObjectSet)
    {
        $extracted = array();
        foreach ($dataObjectSet as $object) {
            /** @var DataObject $object */
            $extracted[] = $object->toMap();
        }

        $matched = [];
        foreach ($matches as $match) {
            foreach ($extracted as $i => $item) {
                if ($this->dataObjectArrayMatch($item, $match)) {
                    $matched[] = $extracted[$i];
                    break;
                }
            }

            // We couldn't find a match - assertion failed
            $this->assertEmpty(
                $matched,
                "Failed asserting that the SS_List dosn't contain a set of objects. "
                . "Found objects were: " . var_export($matched, true)
            );
        }
    }

    /**
     * Assert that the given {@link SS_List} includes only DataObjects matching the given
     * key-value pairs.  Each match must correspond to 1 distinct record.
     *
     * Example
     * --------
     * Check that *only* the entries Sam Minnee and Ingo Schommer exist in $members.  Order doesn't
     * matter:
     *     $this->assertDOSEquals(array(
     *        array('FirstName' =>'Sam', 'Surname' => 'Minnee'),
     *        array('FirstName' => 'Ingo', 'Surname' => 'Schommer'),
     *      ), $members);
     *
     * @param mixed $matches The patterns to match.  Each pattern is a map of key-value pairs.  You can
     * either pass a single pattern or an array of patterns.
     * @param mixed $dataObjectSet The {@link SS_List} to test.
     */
    public function assertDOSEquals($matches, $dataObjectSet)
    {
        // Extract dataobjects
        $extracted = array();
        if ($dataObjectSet) {
            foreach ($dataObjectSet as $object) {
                /** @var DataObject $object */
                $extracted[] = $object->toMap();
            }
        }

        // Check all matches
        if ($matches) {
            foreach ($matches as $match) {
                $matched = false;
                foreach ($extracted as $i => $item) {
                    if ($this->dataObjectArrayMatch($item, $match)) {
                        // Remove it from $extracted so that we don't get duplicate mapping.
                        unset($extracted[$i]);
                        $matched = true;
                        break;
                    }
                }

                // We couldn't find a match - assertion failed
                $this->assertTrue(
                    $matched,
                    "Failed asserting that the SS_List contains an item matching "
                    . var_export($match, true) . "\n\nIn the following SS_List:\n"
                    . $this->DOSSummaryForMatch($dataObjectSet, $match)
                );
            }
        }

        // If we have leftovers than the DOS has extra data that shouldn't be there
        $this->assertTrue(
            (count($extracted) == 0),
            // If we didn't break by this point then we couldn't find a match
            "Failed asserting that the SS_List contained only the given items, the "
            . "following items were left over:\n" . var_export($extracted, true)
        );
    }

    /**
     * Assert that the every record in the given {@link SS_List} matches the given key-value
     * pairs.
     *
     * Example
     * --------
     * Check that every entry in $members has a Status of 'Active':
     *     $this->assertDOSAllMatch(array('Status' => 'Active'), $members);
     *
     * @param mixed $match The pattern to match.  The pattern is a map of key-value pairs.
     * @param mixed $dataObjectSet The {@link SS_List} to test.
     */
    public function assertDOSAllMatch($match, $dataObjectSet)
    {
        $extracted = array();
        foreach ($dataObjectSet as $object) {
            /** @var DataObject $object */
            $extracted[] = $object->toMap();
        }

        foreach ($extracted as $i => $item) {
            $this->assertTrue(
                $this->dataObjectArrayMatch($item, $match),
                "Failed asserting that the the following item matched "
                . var_export($match, true) . ": " . var_export($item, true)
            );
        }
    }

    /**
     * Removes sequences of repeated whitespace characters from SQL queries
     * making them suitable for string comparison
     *
     * @param string $sql
     * @return string The cleaned and normalised SQL string
     */
    protected function normaliseSQL($sql)
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
    public function assertSQLEquals(
        $expectedSQL,
        $actualSQL,
        $message = '',
        $delta = 0,
        $maxDepth = 10,
        $canonicalize = false,
        $ignoreCase = false
    ) {
        // Normalise SQL queries to remove patterns of repeating whitespace
        $expectedSQL = $this->normaliseSQL($expectedSQL);
        $actualSQL = $this->normaliseSQL($actualSQL);

        $this->assertEquals($expectedSQL, $actualSQL, $message, $delta, $maxDepth, $canonicalize, $ignoreCase);
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
    public function assertSQLContains(
        $needleSQL,
        $haystackSQL,
        $message = '',
        $ignoreCase = false,
        $checkForObjectIdentity = true
    ) {
        $needleSQL = $this->normaliseSQL($needleSQL);
        $haystackSQL = $this->normaliseSQL($haystackSQL);

        $this->assertContains($needleSQL, $haystackSQL, $message, $ignoreCase, $checkForObjectIdentity);
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
    public function assertSQLNotContains(
        $needleSQL,
        $haystackSQL,
        $message = '',
        $ignoreCase = false,
        $checkForObjectIdentity = true
    ) {
        $needleSQL = $this->normaliseSQL($needleSQL);
        $haystackSQL = $this->normaliseSQL($haystackSQL);

        $this->assertNotContains($needleSQL, $haystackSQL, $message, $ignoreCase, $checkForObjectIdentity);
    }

    /**
     * Helper function for the DOS matchers
     *
     * @param array $item
     * @param array $match
     * @return bool
     */
    private function dataObjectArrayMatch($item, $match)
    {
        foreach ($match as $k => $v) {
            if (!array_key_exists($k, $item) || $item[$k] != $v) {
                return false;
            }
        }
        return true;
    }

    /**
     * Helper function for the DOS matchers
     *
     * @param SS_List|array $dataObjectSet
     * @param array $match
     * @return string
     */
    private function DOSSummaryForMatch($dataObjectSet, $match)
    {
        $extracted = array();
        foreach ($dataObjectSet as $item) {
            $extracted[] = array_intersect_key($item->toMap(), $match);
        }
        return var_export($extracted, true);
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
        if (InjectorLoader::inst()->countManifests() || static::kernel()) {
            throw new LogicException("SapphireTest::start() cannot be called within another application");
        }
        static::set_is_running_test(true);

        // Mock request
        $session = new Session(isset($_SESSION) ? $_SESSION : array());
        $request = new HTTPRequest('GET', '/');
        $request->setSession($session);

        // Test application
        static::$kernels[] = new TestKernel();
        $app = new HTTPApplication(static::kernel());

        // Custom application
        $app->execute(function () use ($request) {
            // Invalidate classname spec since the test manifest will now pull out new subclasses for each internal class
            // (e.g. Member will now have various subclasses of DataObjects that implement TestOnly)
            DataObject::reset();

            // Set dummy controller
            $controller = Controller::create();
            $controller->setRequest($request);
            $controller->pushCurrent();
            $controller->doInit();
        });

        // Register state
        static::$state = SapphireTestState::singleton();
    }

    /**
     * Returns true if we are currently using a temporary database
     *
     * @return bool
     */
    public static function using_temp_db()
    {
        $dbConn = DB::get_conn();
        $prefix = getenv('SS_DATABASE_PREFIX') ?: 'ss_';
        return 1 === preg_match(sprintf('/^%stmpdb_[0-9]+_[0-9]+$/i', preg_quote($prefix, '/')), $dbConn->getSelectedDatabase());
    }

    /**
     * Destroy all temp databases
     */
    public static function kill_temp_db()
    {
        // Delete our temporary database
        if (self::using_temp_db()) {
            $dbConn = DB::get_conn();
            $dbName = $dbConn->getSelectedDatabase();
            if ($dbName && DB::get_conn()->databaseExists($dbName)) {
                // Some DataExtensions keep a static cache of information that needs to
                // be reset whenever the database is killed
                foreach (ClassInfo::subclassesFor(DataExtension::class) as $class) {
                    $toCall = array($class, 'on_db_reset');
                    if (is_callable($toCall)) {
                        call_user_func($toCall);
                    }
                }

                // echo "Deleted temp database " . $dbConn->currentDatabase() . "\n";
                $dbConn->dropSelectedDatabase();
            }
        }
    }

    /**
     * Remove all content from the temporary database.
     */
    public static function empty_temp_db()
    {
        if (self::using_temp_db()) {
            DB::get_conn()->clearAllData();

            // Some DataExtensions keep a static cache of information that needs to
            // be reset whenever the database is cleaned out
            $classes = array_merge(ClassInfo::subclassesFor(DataExtension::class), ClassInfo::subclassesFor(DataObject::class));
            foreach ($classes as $class) {
                $toCall = array($class, 'on_db_reset');
                if (is_callable($toCall)) {
                    call_user_func($toCall);
                }
            }
        }
    }

    /**
     * Create temp DB without creating extra objects
     *
     * @return string
     */
    public static function create_temp_db()
    {
        // Disable PHPUnit error handling
        $oldErrorHandler = set_error_handler(null);

        // Create a temporary database, and force the connection to use UTC for time
        global $databaseConfig;
        $databaseConfig['timezone'] = '+0:00';
        DB::connect($databaseConfig);
        $dbConn = DB::get_conn();
        $prefix = getenv('SS_DATABASE_PREFIX') ?: 'ss_';
        do {
            $dbname = strtolower(sprintf('%stmpdb_%s_%s', $prefix, time(), rand(1000000, 9999999)));
        } while ($dbConn->databaseExists($dbname));

        $dbConn->selectDatabase($dbname, true);

        static::resetDBSchema();

        // Reinstate PHPUnit error handling
        set_error_handler($oldErrorHandler);

        // Ensure test db is killed on exit
        register_shutdown_function(function () {
            static::kill_temp_db();
        });

        return $dbname;
    }

    public static function delete_all_temp_dbs()
    {
        $prefix = getenv('SS_DATABASE_PREFIX') ?: 'ss_';
        foreach (DB::get_schema()->databaseList() as $dbName) {
            if (1 === preg_match(sprintf('/^%stmpdb_[0-9]+_[0-9]+$/i', preg_quote($prefix, '/')), $dbName)) {
                DB::get_schema()->dropDatabase($dbName);
                if (Director::is_cli()) {
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
     * @param bool $includeExtraDataObjects If true, the extraDataObjects tables will also be included
     */
    public static function resetDBSchema($includeExtraDataObjects = false)
    {
        if (self::using_temp_db()) {
            DataObject::reset();

            // clear singletons, they're caching old extension info which is used in DatabaseAdmin->doBuild()
            Injector::inst()->unregisterObjects(DataObject::class);

            $dataClasses = ClassInfo::subclassesFor(DataObject::class);
            array_shift($dataClasses);

            DB::quiet();
            $schema = DB::get_schema();
            $extraDataObjects = $includeExtraDataObjects ? static::getExtraDataObjects() : null;
            $schema->schemaUpdate(function () use ($dataClasses, $extraDataObjects) {
                foreach ($dataClasses as $dataClass) {
                    // Check if class exists before trying to instantiate - this sidesteps any manifest weirdness
                    if (class_exists($dataClass)) {
                        $SNG = singleton($dataClass);
                        if (!($SNG instanceof TestOnly)) {
                            $SNG->requireTable();
                        }
                    }
                }

                // If we have additional dataobjects which need schema, do so here:
                if ($extraDataObjects) {
                    foreach ($extraDataObjects as $dataClass) {
                        $SNG = singleton($dataClass);
                        if (singleton($dataClass) instanceof DataObject) {
                            $SNG->requireTable();
                        }
                    }
                }
            });

            ClassInfo::reset_db_cache();
            DataObject::singleton()->flushCache();
        }
    }

    /**
     * Create a member and group with the given permission code, and log in with it.
     * Returns the member ID.
     *
     * @param string|array $permCode Either a permission, or list of permissions
     * @return int Member ID
     */
    public function logInWithPermission($permCode = "ADMIN")
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
            $member->Surname = "User";
            $member->Email = "$permCode@example.org";
            $member->write();
            $group->Members()->add($member);

            $this->cache_generatedMembers[$permCode] = $member;
        }
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
        Injector::inst()->get(IdentityStore::class)->logOut();
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
        SSViewer::set_themes([$themeBaseDir.'/themes/'.$theme, '$default']);

        $e = null;

        try {
            $callback();
        } catch (Exception $e) {
        /* NOP for now, just save $e */
        }

        Config::unnest();

        if ($e) {
            throw $e;
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

        $fixtureFiles = (is_array($fixtureFile)) ? $fixtureFile : [$fixtureFile];

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
        // Support fixture paths relative to the test class, rather than relative to webroot
        // String checking is faster than file_exists() calls.
        $isRelativeToFile
            = (strpos('/', $fixtureFilePath) === false)
            || preg_match('/^(\.){1,2}/', $fixtureFilePath);

        if ($isRelativeToFile) {
            $resolvedPath = realpath($this->getCurrentAbsolutePath() . '/' . $fixtureFilePath);
            if ($resolvedPath) {
                return $resolvedPath;
            }
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
