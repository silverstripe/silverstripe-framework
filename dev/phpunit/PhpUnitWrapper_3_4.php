<?php

class PhpUnitWrapper_3_4 extends PhpUnitWrapper {

	protected $version = 'PhpUnit V3.4';

	public function init() {
		require_once 'PHPUnit/Framework.php';
		require_once 'PHPUnit/Util/Report.php';
		require_once 'PHPUnit/TextUI/TestRunner.php';
	}
	
	protected function beforeRunTests() {

		if($this->getCoverageStatus()) {
			// blacklist selected folders from coverage report
			foreach(TestRunner::$coverage_filter_dirs as $dir) {
				PHPUnit_Util_Filter::addDirectoryToFilter(BASE_PATH . '/' . $dir);
			}	
			$this->getFrameworkTestResults()->collectCodeCoverageInformation(true);
		}
	}

	protected function aferRunTests() {

		if($this->getCoverageStatus()) {
	        require_once 'PHPUnit/Util/Log/CodeCoverage/XML/Clover.php';
	        $writer = new PHPUnit_Util_Log_CodeCoverage_XML_Clover('clover.xml');
	        $writer->process($this->getFrameworkTestResults());

			if(!file_exists(ASSETS_PATH . '/coverage-report')) {
				mkdir(ASSETS_PATH . '/coverage-report');
			}

			PHPUnit_Util_Report::render($this->getFrameworkTestResults(), ASSETS_PATH . '/coverage-report/');

			$coverageApp = ASSETS_PATH . '/coverage-report/' . preg_replace('/[^A-Za-z0-9]/','_',preg_replace('/(\/$)|(^\/)/','',Director::baseFolder())) . '.html';
			$coverageTemplates = ASSETS_PATH . '/coverage-report/' . preg_replace('/[^A-Za-z0-9]/','_',preg_replace('/(\/$)|(^\/)/','',realpath(TEMP_FOLDER))) . '.html';

			echo "<p>Coverage reports available here:<ul>
				<li><a href=\"$coverageApp\">Coverage report of the application</a></li>
				<li><a href=\"$coverageTemplates\">Coverage report of the templates</a></li>
			</ul>";
		}
	}
		
	public function runTests() {
		return parent::runTests();
	}
	
}