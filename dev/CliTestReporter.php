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
		
		echo "\n\n";
		if ($failCount == 0) {
			echo SSCli::text(" ALL TESTS PASS ", "white", "green");
		}  else {
			echo SSCli::text(" AT LEAST ONE FAILURE ", "white", "red");
		}
		echo "\n\n$testCount tests run: " . SSCli::text("$passCount passes", $passCount > 0 ? "green" : null) . ", ". SSCli::text("$failCount fails", $failCount > 0 ? "red" : null) . ", and 0 exceptions\n\n";
	}
	
	public function endTest( PHPUnit_Framework_Test $test, $time) {
		// Status indicator, a la PHPUnit
		switch($this->currentTest['status']) {
			case TEST_FAILURE: echo SSCli::text("F","red", null, true); break;
			case TEST_ERROR: echo SSCli::text("E","red", null, true); break;
			case TEST_INCOMPLETE: echo SSCli::text("I","yellow"); break;
			case TEST_SUCCESS: echo SSCli::text(".","green"); break;
			default: echo SSCli::text("?", "yellow"); break;
		}
		
		static $colCount = 0;
		$colCount++;
		if($colCount % 80 == 0) echo " - $colCount\n";

		parent::endTest($test, $time);
		$this->writeTest($this->currentTest);
	}
	
	
	protected function writeTest($test) {
		if ($test['status'] != 1) {
			
			$filteredTrace = array();
			$ignoredClasses = array('TestRunner');
			foreach($test['trace'] as $item) {
				if(
					isset($item['file'])
					&& strpos($item['file'], 'PHPUnit/Framework') === false 
					&& (!isset($item['class']) || !in_array($item['class'], $ignoredClasses))) {
					
					$filteredTrace[] = $item;
				}
				
				if(isset($item['class']) && isset($item['function']) && $item['class'] == 'PHPUnit_Framework_TestSuite' 
					&& $item['function'] == 'run') break;
				
			}
			
			echo "\n\n" . SSCli::text($this->testNameToPhrase($test['name']) . "\n". $test['message'] . "\n", 'red', null, true);
			echo SSCli::text("In line {$test['exception']['line']} of {$test['exception']['file']}" . "\n\n", 'red');
			echo Debug::get_rendered_backtrace($filteredTrace, true);
			echo "\n--------------------\n";
		}
	}
	
}