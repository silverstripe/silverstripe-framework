<?php
/**
* @package framework
* @subpackage dev
*/

class PhpUnitWrapper_3_5 extends PhpUnitWrapper_Generic {

	public function getVersion() {
		return 'PhpUnit V3.5';
	}

	/**
	 * Initialise the wrapper class.
	 */
	public function init() {
		if(!class_exists('PHPUnit_Framework_TestCase')) {
			require_once 'PHP/CodeCoverage.php';
			require_once 'PHP/CodeCoverage/Report/HTML.php';
			require_once 'PHPUnit/Autoload.php';
			require_once 'PHP/CodeCoverage/Filter.php';
		}
	}

}
