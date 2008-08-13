<?php

/**
 * Test reporter optimised for CLI (ie, plain-text) output
 */
class CliTestReporter extends SapphireTestReporter {

	/**
	 * Display error bar if it exists
	 */
	public function writeResults() {
		$passCount = 0;
		$failCount = 0;
		$testCount = 0;
		$errorCount = 0;
		
		foreach($this->suiteResults['suites'] as $suite) {
			foreach($suite['tests'] as $test) {
				$testCount++;
				($test['status'] == 1) ? $passCount++ : $failCount++;
			}
		}
		$result = ($failCount > 0) ? 'fail' : 'pass';
		echo "$testCount tests run: $passCount passes, $failCount fails, and 0 exceptions\n\n";
	}
	
	public function endTest( PHPUnit_Framework_Test $test, $time) {
		parent::endTest($test, $time);
		$this->writeTest($this->currentTest);
	}
	
	
	protected function writeTest($test) {
		if ($test['status'] != 1) {
			echo $this->testNameToPhrase($test['name']) . "\n". $test['message'] . "\n";
			echo "In line {$test['exception']['line']} of {$test['exception']['file']}" . "\n\n";
			echo Debug::get_rendered_backtrace($test['trace'], true);
			echo "\n--------------------\n";
		}
	}
	
}