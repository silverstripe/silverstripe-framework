<?php

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

class PhpUnitWrapper implements IPhpUnitWrapper {
	
	private $coverage = false;

	private $suite = null;

	private $results = null;
	
	protected $version = 'none';
	
	private $reporter = null;
	
	public function getVersion() {
		return $this->version;
	}

	public function getFrameworkTestResults() {
		return $this->results;
	}
	
	public function setFrameworkTestResults($value) {
		$this->results = $value;
	}	
	
	public function getCoverageStatus() {
		return $this->coverage;
	}
	
	public function setCoverageStatus($value) {
		$this->coverage = $value;
	}

	public function getSuite() {
		return $this->suite;
	}

	public function setReporter($value) {
		$this->reporter = $value;
	}
	
	public function getReporter() {
		return $this->reporter;
	}

	public function setSuite($value) {
		$this->suite = $value;
	}
	

	/**
	 *
	 */
	static function getPhpUnit_Version() {
		$result = 'none';

		if (TestRunner::get_phpunit_wrapper() == null) {
			 if (fileExistsInIncludePath("/PHPUnit/Autoload.php")) {
			 	TestRunner::set_phpunit_wrapper(new PhpUnitWrapper_3_5());
			 } else 
			 if (fileExistsInIncludePath("/PHPUnit/Framework.php")) {
			 	TestRunner::set_phpunit_wrapper(new PhpUnitWrapper_3_4());
			 } else {
			 	TestRunner::set_phpunit_wrapper(new PhpUnitWrapper());
			 } 
			TestRunner::get_phpunit_wrapper()->init();

		}
		$result = TestRunner::get_phpunit_wrapper()->getVersion();
		return $result;
	}

	/**
	 * Returns true if one of the two supported PHPUNIT versions is installed.
	 */
	static function hasPhpUnit() {
		return (self::getPhpUnit_Version() != 'none');
	}
	
	public function init() {
	}

	protected function beforeRunTests() {
		// throw new PhpUnitWrapper_Excption('Method \'beforeRunTests\' not implemented in PhpUnitWrapper.');
	}
	
	protected function afterRunTests() {
		// throw new PhpUnitWrapper_Excption('Method \'afterRunTests\' not implemented in PhpUnitWrapper.');
	}

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
		$this->aferRunTests();
	}

}

interface IPhpUnitWrapper {

	public function init();

	public function runTests();
	
}

// This class is here to help with documentation.
if(!PhpUnitWrapper::hasPhpUnit()) {
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
