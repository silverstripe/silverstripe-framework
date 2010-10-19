<?php
/**
 * Test reporter optimised for CLI (ie, plain-text) output
 * 
 * @package sapphire
 * @subpackage testing
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
			echo SS_Cli::text(" ALL TESTS PASS ", "white", "green");
		}  else {
			echo SS_Cli::text(" AT LEAST ONE FAILURE ", "white", "red");
		}
		echo "\n\n$testCount tests run: " . SS_Cli::text("$passCount passes", null) . ", ". SS_Cli::text("$failCount fails", null) . ", and 0 exceptions\n";
		
		if(function_exists('memory_get_peak_usage')) {
			echo "Maximum memory usage: " . number_format(memory_get_peak_usage()/(1024*1024), 1) . "M\n\n";
		}
		
		// Use sake dev/tests/all --showslow to show slow tests
		if((isset($_GET['args']) && is_array($_GET['args']) && in_array('--showslow', $_GET['args'])) || isset($_GET['showslow'])) {
			$avgSpeed = round(array_sum($this->testSpeeds) / count($this->testSpeeds), 3);
			echo "Slow tests (more than the average $avgSpeed seconds):\n";

			arsort($this->testSpeeds);
			foreach($this->testSpeeds as $k => $v) {
				// Ignore below-average speeds
				if($v < $avgSpeed) break;

				echo " - $k: " . round($v,3) . "\n";
			}
		}
		echo "\n";
	}
	
	public function endTest( PHPUnit_Framework_Test $test, $time) {
		// Status indicator, a la PHPUnit
		switch($this->currentTest['status']) {
			case TEST_FAILURE: echo SS_Cli::text("F","red", null, true); break;
			case TEST_ERROR: echo SS_Cli::text("E","red", null, true); break;
			case TEST_INCOMPLETE: echo SS_Cli::text("I","yellow"); break;
			case TEST_SUCCESS: echo SS_Cli::text(".","green"); break;
			default: echo SS_Cli::text("?", "yellow"); break;
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
			
			echo "\n\n" . SS_Cli::text($this->testNameToPhrase($test['name']) . "\n". $test['message'] . "\n", 'red', null, true);
			echo SS_Backtrace::get_rendered_backtrace($filteredTrace, true);
			echo "\n--------------------\n";
		}
	}
	
}