<?php
/**
 * Necessary to call setUpOnce() and tearDownOnce() on {@link SapphireTest}
 * classes. This feature doesn't exist in PHPUnit in the same way
 * (setUpBeforeClass() and tearDownAfterClass() are just called statically).
 *
 * @see http://www.phpunit.de/manual/3.5/en/extending-phpunit.html#extending-phpunit.PHPUnit_Framework_TestListener
 *
 * @package framework
 * @subpackage testing
 */
class SS_TestListener implements PHPUnit_Framework_TestListener {

	public function addError(PHPUnit_Framework_Test $test, Exception $e, $time) {}

	public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time) {}

	public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time) {}

	public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time) {}

	public function startTest(PHPUnit_Framework_Test $test) {}

	public function endTest(PHPUnit_Framework_Test $test, $time) {}

	public function startTestSuite(PHPUnit_Framework_TestSuite $suite) {
		$name = $suite->getName();
		if(!$this->isValidClass($name)) return;

		$this->class = new $name();
		$this->class->setUpOnce();
	}

	public function endTestSuite(PHPUnit_Framework_TestSuite $suite) {
		$name = $suite->getName();
		if(!$this->isValidClass($name)) return;

		$this->class->tearDownOnce();
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

	/**
	 * @param String Classname
	 * @return boolean
	 */
	protected function isValidClass($name) {
		return (class_exists($name) && is_subclass_of($name, 'SapphireTest'));
	}
}
