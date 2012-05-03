<?php
/**
* @package framework
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
            $modules = $this->moduleDirectories();

			foreach(TestRunner::$coverage_filter_dirs as $dir) {
				if($dir[0] == '*') {
					$dir = substr($dir, 1);
					foreach ($modules as $module) {
						$filter->addDirectoryToBlacklist(BASE_PATH . "/$module/$dir");
					}
				} else {
					$filter->addDirectoryToBlacklist(BASE_PATH . '/' . $dir);
				}
			}

			$filter->addFileToBlacklist(__FILE__, 'PHPUNIT');
			
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
