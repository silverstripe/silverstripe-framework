<?php
/**
 * Inject SilverStripe 'setUpOnce' and 'tearDownOnce' unittest extension methods into PHPUnit.
 *
 * This is already in later SilverStripe 2.4 versions, but having it here extends compatibility to older versions.
 *
 * @package framework
 * @subpackage testing
 */
class SilverStripeListener implements PHPUnit_Framework_TestListener {

	protected function isValidClass($name) {
		return (class_exists($name) && is_subclass_of($name, 'SapphireTest'));
	}

	public function startTestSuite(PHPUnit_Framework_TestSuite $suite) {
		$name = $suite->getName();
		if(!$this->isValidClass($name)) return;

		$class = new $name();
		$class->setUpOnce();
	}

	public function endTestSuite(PHPUnit_Framework_TestSuite $suite) {
		$name = $suite->getName();
		if(!$this->isValidClass($name)) return;

		$class = new $name();
		$class->tearDownOnce();
	}

	public function startTest(PHPUnit_Framework_Test $test) {
	}

	public function endTest(PHPUnit_Framework_Test $test, $time) {
	}

	public function addError(PHPUnit_Framework_Test $test, Exception $e, $time) {
	}

	public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time) {
	}

	public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
	}

	public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
	}

	/**
	 * Risky test.
	 *
	 * @param PHPUnit_Framework_Test $test
	 * @param Exception              $e
	 * @param float                  $time
	 * @since  Method available since Release 3.8.0
	 */
	public function addRiskyTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
		// Stub out to support PHPUnit 3.8
	}
}
