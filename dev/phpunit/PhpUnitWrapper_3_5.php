<?php

class PhpUnitWrapper_3_5 extends PhpUnitWrapper {

	protected $version = 'PhpUnit V3.5';
	
	protected $coverage = null;

	protected static $test_name = 'SapphireTest';

	protected static $generate_clover = false;

	protected static $clover_filename = 'clover.xml';
	
	static function get_test_name() {
		return self::$test_name;
	}

	static function get_generate_clover() {
		return self::$generate_clover;
	}

	static function set_generate_clover($value) {
		self::$generate_clover = $value;
	}
	
	static function get_clover_filename() {
		return self::$clover_filename;
	}
	
	static function set_clover_filename($value) {
		self::$clover_filename = $value;
	}

	public function init() {
		require_once 'PHP/CodeCoverage.php';
		require_once 'PHP/CodeCoverage/Report/Clover.php';
		require_once 'PHP/CodeCoverage/Report/HTML.php';

		require_once 'PHPUnit/Autoload.php';

		require_once 'PHP/CodeCoverage/Filter.php';
		PHP_CodeCoverage_Filter::getInstance()->addFileToBlacklist(__FILE__, 'PHPUNIT');

	}
	
	protected function beforeRunTests() {
		
		if($this->getCoverageStatus()) {			
            $this->coverage = new PHP_CodeCoverage;
			$coverage = $this->coverage;

            $filter = $coverage->filter();

			foreach(TestRunner::$coverage_filter_dirs as $dir) {
				$filter->addDirectoryToBlacklist(BASE_PATH . '/' . $dir);
			}
			
			$coverage->start(self::get_test_name());
		}
	}

	protected function aferRunTests() {

		if($this->getCoverageStatus()) {
			$coverage = $this->coverage;
			$coverage->stop();
		
			if (self::get_generate_clover() == true) {
				
				$filename = self::get_clover_filename();
				$writer = new PHP_CodeCoverage_Report_Clover;
				$writer->process($coverage, ASSETS_PATH."/".$filename);
			}

			$writer = new PHP_CodeCoverage_Report_HTML;
			$writer->process($coverage, ASSETS_PATH.'/code-coverage-report');
		}
	}
	
	public function runTests() {
		return parent::runTests();
	}
}