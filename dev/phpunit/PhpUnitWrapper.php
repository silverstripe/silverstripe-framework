<?php
/**
* @package framework
* @subpackage dev
*/

/**
 * This method checks if a given filename exists in the include path (defined
 * in php.ini.
 *
 * @return boolean when the file has been found in the include path.
 */
function fileExistsInIncludePath($filename) {
	$paths = explode(PATH_SEPARATOR, ini_get('include_path'));
	foreach($paths as $path) {
		if(substr($path,-1) == DIRECTORY_SEPARATOR) $path = substr($path,0,-1);
		if(@file_exists($path."/".$filename)) return true;
	}
	return false;
}

/**
 * PHPUnit Wrapper class.
 * Base class for PHPUnit wrapper classes to support different PHPUnit versions.
 * The current implementation supports PHPUnit 3.4 and PHPUnit 3.5.
 */
class PhpUnitWrapper implements IPhpUnitWrapper {

	/**
	 * Flag if coverage report shall be generated or not.
	 * @var boolean
	 */
	private $coverage = false;

	/**
	 * PHPUnit-TestSuite class. The tests, added to this suite are performed
	 * in this test-run.
	 * @var PHPUnit_Framework_TestSuite
	 */
	private $suite = null;

	/**
	 * @var PHPUnit_Framework_TestResult
	 */
	private $results = null;

	/**
	 * @var PHPUnit_Framework_TestListener
	 */
	private $reporter = null;

	/**
	 * Shows the version, implemented by the phpunit-wrapper class instance.
	 * This instance implements no phpunit, the version is null.
	 * @var String
	 */
	protected $version = null;

	private static $phpunit_wrapper = null;

	/**
	 * Getter for $coverage (@see $coverage).
	 * @return boolean
	 */
	public function getCoverageStatus() {
		return $this->coverage;
	}

	/**
	 * Setter for $coverage (@see $coverage).
	 * @parameter $value Boolean
	 */
	public function setCoverageStatus($value) {
		$this->coverage = $value;
	}

	/**
	 * Getter for $suite (@see $suite).
	 * @return PHPUnit_Framework_TestSuite
	 */
	public function getSuite() {
		return $this->suite;
	}

	/**
	 * Setter for $suite (@see $suite).
	 * @param $value PHPUnit_Framework_TestSuite
	 */
	public function setSuite($value) {
		$this->suite = $value;
	}

	/**
	 * Getter for $reporter (@see $reporter).
	 * @return PHPUnit_Framework_TestListener
	 */
	public function getReporter() {
		return $this->reporter;
	}

	/**
	 * Setter for $reporter (@see $reporter).
	 * @param $value PHPUnit_Framework_TestListener
	 */
	public function setReporter($value) {
		$this->reporter = $value;
	}

	/**
	 * Getter for $results (@see $results).
	 * @return PHPUnit_Framework_TestResult
	 */
	public function getFrameworkTestResults() {
		return $this->results;
	}

	/**
	 * Setter for $results (@see $results).
	 * @param $value PHPUnit_Framework_TestResult
	 */
	public function setFrameworkTestResults($value) {
		$this->results = $value;
	}

	/**
	 * Getter for $version (@see $version).
	 * @return String
	 */
	public function getVersion() {
		return $this->version;
	}

	/**
	 * Loads and initiates phpunit, based on the available phpunit version.
	 *
	 * @return PhpUnitWrapper Instance of the php-wrapper class
	 */
	public static function inst() {

		if (self::$phpunit_wrapper == null) {
			// Loaded via autoloader, composer or other generic
			if (class_exists('PHPUnit_Runner_Version')) {
				self::$phpunit_wrapper = new PhpUnitWrapper_Generic();
			}
			// 3.5 detection
			else if (fileExistsInIncludePath("/PHPUnit/Autoload.php")) {
				self::$phpunit_wrapper = new PhpUnitWrapper_3_5();
			}
			// 3.4 detection
			else if (fileExistsInIncludePath("/PHPUnit/Framework.php")) {
				self::$phpunit_wrapper = new PhpUnitWrapper_3_4();
			}
			// No version found - will lead to an error
			else {
				self::$phpunit_wrapper = new PhpUnitWrapper();
			}
			self::$phpunit_wrapper->init();

		}
		return self::$phpunit_wrapper;
	}

	/**
	 * Returns true if one of the two supported PHPUNIT versions is installed.
	 *
	 * @return boolean true if PHPUnit has been installed on the environment.
	 */
	public static function has_php_unit() {
		return (Bool) self::inst()->getVersion();
	}

	/**
	 * Implements method, defined in the interface IPhpUnitWrapper:init (@see IPhpUnitWrapper).
	 * This wrapper class doesn't require any initialisation.
	 */
	public function init() {
	}

	/**
	 * This method is called before the unittests are performed.
	 * This wrapper implements the non-PHPUnit version which means that unit tests
	 * can not be performed.
	 * @throws PhpUnitWrapper_Excption
	 */
	protected function beforeRunTests() {
		throw new PhpUnitWrapper_Exception('Method \'beforeRunTests\' not implemented in PhpUnitWrapper.');
	}

	/**
	 * This method is called after the unittests are performed.
	 * This wrapper implements the non-PHPUnit version which means that unit tests
	 * can not be performed.
	 * @throws PhpUnitWrapper_Excption
	 */
	protected function afterRunTests() {
		throw new PhpUnitWrapper_Exception('Method \'afterRunTests\' not implemented in PhpUnitWrapper.');
	}

	/**
	 * Perform all tests, added to the suite and initialises SilverStripe to collect
	 * the results of the unit tests.
	 *
	 * This method calls @see beforeRunTests and @see afterRunTests.
	 */
	public function runTests() {

		if(Director::is_cli()) {
			$this->setReporter( new CliTestReporter() );
		} else {
			$this->setReporter( new SapphireTestReporter() );
		}

		if ($this->getFrameworkTestResults() == null) {
			$this->setFrameworkTestResults(new PHPUnit_Framework_TestResult());
		}
		$this->getFrameworkTestResults()->addListener( $this->getReporter() );

		$this->beforeRunTests();
		$this->getSuite()->run($this->getFrameworkTestResults());
		$this->afterRunTests();
	}

	/**
	 * Returns an array containing all the module folders in the base dir.
	 *
	 * @return array
	 */
	protected function moduleDirectories() {
		$files = scandir(BASE_PATH);
		$modules = array();
		foreach($files as $file) {
			if(is_dir(BASE_PATH . "/$file") && file_exists(BASE_PATH . "/$file/_config.php")) {
				$modules[] = $file;
			}
		}
		return $modules;
	}
}

/**
 * Interface, implementing the general PHPUnit wrapper API.
 */
interface IPhpUnitWrapper {

	public function init();

	public function runTests();
}


/**
 * PHPUnitWrapper Exception class
 */
class PhpUnitWrapper_Exception extends Exception {}


// If PHPUnit is not installed on the local environment, declare the class to
// ensure that missing class declarations are available to avoind any PHP fatal
// errors.
//
if(!PhpUnitWrapper::has_php_unit()) {
	/**
	 * PHPUnit is a testing framework that can be installed using Composer.
	 * It's not bundled with SilverStripe, you will need to install it yourself.
	 *
	 * @package framework
	 * @subpackage testing
	 */
	class PHPUnit_Framework_TestCase {

	}
}
