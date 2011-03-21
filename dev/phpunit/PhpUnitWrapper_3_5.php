<?php
/**
* @package sapphire
* @subpackage dev
*/

class PhpUnitWrapper_3_5 extends PhpUnitWrapper {

	protected $version = 'PhpUnit V3.5';
	
	protected $coverage = null;

	protected static $test_name = 'SapphireTest';

	static function get_test_name() {
		return self::$test_name;
	}

	/** 
	 * Initialise the wrapper class.
	 */
	public function init() {
		require_once 'PHP/CodeCoverage.php';
		require_once 'PHP/CodeCoverage/Report/HTML.php';

		require_once 'PHPUnit/Autoload.php';

		require_once 'PHP/CodeCoverage/Filter.php';
		PHP_CodeCoverage_Filter::getInstance()->addFileToBlacklist(__FILE__, 'PHPUNIT');
	}
	
	/**
	 * Overwrites beforeRunTests. Initiates coverage-report generation if 
	 * $coverage has been set to true (@see setCoverageStatus).
	 */
	protected function beforeRunTests() {
		
		if($this->getCoverageStatus()) {			
            $this->coverage = new PHP_CodeCoverage();
			$coverage = $this->coverage;

            $filter = $coverage->filter();

			foreach(TestRunner::$coverage_filter_dirs as $dir) {
				$filter->addDirectoryToBlacklist(BASE_PATH . '/' . $dir);
			}
			
			$coverage->start(self::get_test_name());
		}
	}

	/**
	 * Overwrites aferRunTests. Creates coverage report and clover report 
	 * if required.
	 */
	protected function aferRunTests() {

		if($this->getCoverageStatus()) {
			$coverage = $this->coverage;
			$coverage->stop();
				
			$writer = new PHP_CodeCoverage_Report_HTML();
			$writer->process($coverage, ASSETS_PATH.'/code-coverage-report');
		}
	}

}