<?php

/**
 * Generic PhpUnitWrapper.
 * Originally intended for use with Composer based installations, but will work
 * with any fully functional autoloader.
 *
 * @package framework
 * @subpackage dev
 */
class PhpUnitWrapper_Generic extends PhpUnitWrapper {

	/**
	 * Returns a version string, like 3.7.34 or 4.2-dev.
	 * @return string
	 */
	public function getVersion() {
		return PHPUnit_Runner_Version::id();
	}

	protected $coverage = null;

	protected static $test_name = 'SapphireTest';

	public static function get_test_name() {
		return static::$test_name;
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

			foreach(TestRunner::config()->coverage_filter_dirs as $dir) {
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
	 * Overwrites afterRunTests. Creates coverage report and clover report
	 * if required.
	 */
	protected function afterRunTests() {

		if($this->getCoverageStatus()) {
			$coverage = $this->coverage;
			$coverage->stop();

			$writer = new PHP_CodeCoverage_Report_HTML();
			$writer->process($coverage, ASSETS_PATH.'/code-coverage-report');
		}
	}

}
