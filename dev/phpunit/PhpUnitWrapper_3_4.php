<?php
/**
* @package framework
* @subpackage dev
*/

/**
 * PHPUnit Wrapper class. Implements the correct behaviour for PHPUnit V3.4.
 */
class PhpUnitWrapper_3_4 extends PhpUnitWrapper {

	public function getVersion() {
		return 'PhpUnit V3.4';
	}

	/**
	 * Initialise the wrapper class.
	 */
	public function init() {
		parent::init();
		require_once 'PHPUnit/Framework.php';
		require_once 'PHPUnit/Util/Report.php';
		require_once 'PHPUnit/TextUI/TestRunner.php';
	}

	/**
	 * Overwrites beforeRunTests. Initiates coverage-report generation if
	 * $coverage has been set to true (@see setCoverageStatus).
	 */
	protected function beforeRunTests() {

		if($this->getCoverageStatus()) {
			// blacklist selected folders from coverage report
			$modules = $this->moduleDirectories();

			foreach(TestRunner::config()->coverage_filter_dirs as $dir) {
				if($dir[0] == '*') {
					$dir = substr($dir, 1);
					foreach ($modules as $module) {
						PHPUnit_Util_Filter::addDirectoryToFilter(BASE_PATH . '/' . $dir);
					}
				} else {
					PHPUnit_Util_Filter::addDirectoryToFilter(BASE_PATH . '/' . $dir);
				}
			}
			$this->getFrameworkTestResults()->collectCodeCoverageInformation(true);
		}
	}

	/**
	 * Overwrites afterRunTests. Creates coverage report and clover report
	 * if required.
	 */
	protected function afterRunTests() {

		if($this->getCoverageStatus()) {

			if(!file_exists(ASSETS_PATH . '/coverage-report')) {
				mkdir(ASSETS_PATH . '/coverage-report');
			}

			$ret = PHPUnit_Util_Report::render($this->getFrameworkTestResults(), ASSETS_PATH . '/coverage-report/');

			$coverageApp = ASSETS_PATH . '/coverage-report/'
				. preg_replace('/[^A-Za-z0-9]/','_',preg_replace('/(\/$)|(^\/)/','',Director::baseFolder())) . '.html';
			$coverageTemplates = ASSETS_PATH . '/coverage-report/'
				. preg_replace('/[^A-Za-z0-9]/','_',preg_replace('/(\/$)|(^\/)/','',realpath(TEMP_FOLDER))) . '.html';

			echo "<p>Coverage reports available here:<ul>
				<li><a href=\"$coverageApp\">Coverage report of the application</a></li>
				<li><a href=\"$coverageTemplates\">Coverage report of the templates</a></li>
			</ul>";
		}
	}

}
