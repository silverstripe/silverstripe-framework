<?php
/**
 * Bind TeamCity test listener. Echos messages to stdout that TeamCity interprets into the test results
 *
 * @package framework
 * @subpackage testing
 */
class TeamCityListener implements PHPUnit_Framework_TestListener {
	
	private function escape($str) {
		return strtr($str, array(
			"\n" => '|n',
			"\r" => '|r',
			"[" => '|[',
			"]" => '|]',
			"'" => "|'",
			"|" => '||'
		));
	}
	
	public function startTestSuite(PHPUnit_Framework_TestSuite $suite) {
		echo "##teamcity[testSuiteStarted name='{$suite->getName()}']\n";
	}

	public function endTestSuite(PHPUnit_Framework_TestSuite $suite) {
		echo "##teamcity[testSuiteFinished name='{$suite->getName()}']\n";
	}
	
	public function startTest(PHPUnit_Framework_Test $test) {
		$class = get_class($test);
		echo "##teamcity[testStarted name='{$class}.{$test->getName()}']\n";
	}

	public function endTest(PHPUnit_Framework_Test $test, $time) {
		$class = get_class($test);
		echo "##teamcity[testFinished name='{$class}.{$test->getName()}' duration='$time']\n";
	}

	public function addError(PHPUnit_Framework_Test $test, Exception $e, $time) {
		$class = get_class($test);
		$message = $this->escape("Exception: {$e->getMessage()}");
		$trace = $this->escape($e->getTraceAsString());
		echo "##teamcity[testFailed type='exception' name='{$class}.{$test->getName()}' message='$message'"
			. " details='$trace']\n";
	}

	public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time) {
		$class = get_class($test);
		$message = $this->escape($e->getMessage());
		$trace = $this->escape($e->getTraceAsString());
		echo "##teamcity[testFailed type='failure' name='{$class}.{$test->getName()}' message='$message'"
			. " details='$trace']\n";
	}

	public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
		// NOP
	}

	public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
		$class = get_class($test);
		$message = $this->escape($e->getMessage());
		echo "##teamcity[testIgnored name='{$class}.{$test->getName()}' message='$message']\n";
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
