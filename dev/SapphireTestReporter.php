<?php
if(!class_exists('PHPUnit_Framework_TestResult', false)) require_once 'PHPUnit/Framework/TestResult.php';
if(!class_exists('PHPUnit_Framework_TestListener', false)) require_once 'PHPUnit/Framework/TestListener.php';

/**#@+
 * @var int
 */
/**
 * Failure test status constant
 */
define('TEST_FAILURE', -1);
/**
 * Error test status constant
 */
define('TEST_ERROR', 0);
/**
 * Success test status constant
 */
define('TEST_SUCCESS', 1);
/**
 * Incomplete test status constant
 */
define('TEST_INCOMPLETE', 2);
/**#@-*/

/**
 * Gathers details about PHPUnit2 test suites as they
 * are been executed. This does not actually format any output
 * but simply gathers extended information about the overall
 * results of all suites & their tests for use elsewhere.
 *
 * Changelog:
 *  0.6 First created [David Spurr]
 *  0.7 Added fix to getTestException provided [Glen Ogilvie]
 *
 * @package framework
 * @subpackage testing
 *
 * @version 0.7 2006-03-12
 * @author David Spurr
 */
class SapphireTestReporter implements PHPUnit_Framework_TestListener {
	/**
	 * Holds array of suites and total number of tests run
	 * @var array
	 */
	protected $suiteResults;
	/**
	 * Holds data of current suite that is been run
	 * @var array
	 */
	protected $currentSuite;
	/**
	 * Holds data of current test that is been run
	 * @var array
	 */
	protected $currentTest;
	/**
	 * Whether PEAR Benchmark_Timer is available for timing
	 * @var boolean
	 */
	protected $hasTimer;
	/**
	 * Holds the PEAR Benchmark_Timer object
	 * @var obj Benchmark_Timer
	 */
	protected $timer;

	protected $startTestTime;

	/**
	 * An array of all the test speeds
	 */
	protected $testSpeeds = array();

	/**
	 * Constructor, checks to see availability of PEAR Benchmark_Timer and
	 * sets up basic properties
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		@include_once 'Benchmark/Timer.php';
		if(class_exists('Benchmark_Timer')) {
			$this->timer = new Benchmark_Timer();
			$this->hasTimer = true;
		} else {
			$this->hasTimer = false;
		}

		$this->suiteResults = array(
			'suites'      => array(),         // array of suites run
			'hasTimer'    => $this->hasTimer, // availability of PEAR Benchmark_Timer
			'totalTests'  => 0                // total number of tests run
		);
	}

	/**
	 * Returns the suite results
	 *
	 * @access public
	 * @return array Suite results
	 */
	public function getSuiteResults() {
		return $this->suiteResults;
	}

	/**
	 * Sets up the container for result details of the current test suite when
	 * each suite is first run
	 *
	 * @access public
	 * @param obj PHPUnit2_Framework_TestSuite, the suite that is been run
	 * @return void
	 */
	public function startTestSuite( PHPUnit_Framework_TestSuite $suite) {
		if(strlen($suite->getName())) {
			$this->endCurrentTestSuite();
		$this->currentSuite = array(
			'suite'      => $suite,  // the test suite
			'tests'      => array(), // the tests in the suite
			'errors'     => 0,       // number of tests with errors (including setup errors)
			'failures'   => 0,       // number of tests which failed
			'incomplete' => 0,       // number of tests that were not completed correctly
				'error'		 => null);	 // Any error encountered during setup of the test suite
	}
	}

	/**
	 * Sets up the container for result details of the current test when each
	 * test is first run
	 *
	 * @access public
	 * @param obj PHPUnit_Framework_Test, the test that is being run
	 * @return void
	 */
	public function startTest(PHPUnit_Framework_Test $test) {
		$this->startTestTime = microtime(true);

		if($test instanceof PHPUnit_Framework_TestCase) {
			$this->endCurrentTest();
			$this->currentTest = array(
				// the name of the test (without the suite name)
				'name'        => preg_replace('(\(.*\))', '', $test->toString()),
				// execution time of the test
				'timeElapsed' => 0,
				// status of the test execution
				'status'      => TEST_SUCCESS,
				// user message of test result
				'message'     => '',
				// original caught exception thrown by test upon failure/error
				'exception'   => NULL,
				// Stacktrace used for exception handling
				'trace'		  => NULL,
				// a unique ID for this test (used for identification purposes in results)
				'uid'         => md5(microtime())
			);
			if($this->hasTimer) $this->timer->start();
		}
	}

	/**
	 * Logs the specified status to the current test, or if no test is currently
	 * run, to the test suite.
	 * @param integer $status Status code
	 * @param string $message Message to log
	 * @param string $exception Exception body related to this message
	 * @param array $trace Stacktrace
	 */
	protected function addStatus($status, $message, $exception, $trace) {
		// Build status body to be saved
		$status = array(
			'status' => $status,
			'message' => $message,
			'exception' => $exception,
			'trace' => $trace
		);

		// Log either to current test or suite record
		if($this->currentTest) {
			$this->currentTest = array_merge($this->currentTest, $status);
		} else {
			$this->currentSuite['error'] = $status;
		}
	}

	/**
	 * Adds the failure detail to the current test and increases the failure
	 * count for the current suite
	 *
	 * @access public
	 * @param obj PHPUnit_Framework_Test, current test that is being run
	 * @param obj PHPUnit_Framework_AssertationFailedError, PHPUnit error
	 * @return void
	 */
	public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time) {
		$this->currentSuite['failures']++;
		$this->addStatus(TEST_FAILURE, $e->toString(), $this->getTestException($test, $e), $e->getTrace());
	}

	/**
	 * Adds the error detail to the current test and increases the error
	 * count for the current suite
	 *
	 * @access public
	 * @param obj PHPUnit_Framework_Test, current test that is being run
	 * @param obj PHPUnit_Framework_AssertationFailedError, PHPUnit error
	 * @return void
	 */
	public function addError(PHPUnit_Framework_Test $test, Exception $e, $time) {
		$this->currentSuite['errors']++;
		$this->addStatus(TEST_ERROR, $e->getMessage(), $this->getTestException($test, $e), $e->getTrace());
	}

	/**
	 * Adds the test incomplete detail to the current test and increases the incomplete
	 * count for the current suite
	 *
	 * @access public
	 * @param obj PHPUnit_Framework_Test, current test that is being run
	 * @param obj PHPUnit_Framework_AssertationFailedError, PHPUnit error
	 * @return void
	 */
	public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
		$this->currentSuite['incomplete']++;
		$this->addStatus(TEST_INCOMPLETE, $e->toString(), $this->getTestException($test, $e), $e->getTrace());
	}

	/**
	 * Not used
	 *
	 * @param PHPUnit_Framework_Test $test
	 * @param unknown_type $time
	 */
	public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
		// not implemented
	}


	/**
	 * Cleanly end the current test
	 */
	protected function endCurrentTest() {
		if(!$this->currentTest) return;

		// Time the current test
		$testDuration = microtime(true) - $this->startTestTime;
		$this->testSpeeds[$this->currentSuite['suite']->getName() . '.' . $this->currentTest['name']] = $testDuration;
		if($this->hasTimer) {
			$this->timer->stop();
			$this->currentTest['timeElapsed'] = $this->timer->timeElapsed();
		}

		// Save and reset current state
		array_push($this->currentSuite['tests'], $this->currentTest);
		$this->currentTest = null;
	}

	/**
	 * Upon completion of a test, records the execution time (if available) and adds the test to
	 * the tests performed in the current suite.
	 *
	 * @access public
	 * @param obj PHPUnit_Framework_Test, current test that is being run
	 * @return void
	 */
	public function endTest( PHPUnit_Framework_Test $test, $time) {
		$this->endCurrentTest();
		if(method_exists($test, 'getActualOutput')) {
			$output = $test->getActualOutput();
			if($output) echo "\nOutput:\n$output";
		}
	}

	/**
	 * Cleanly end the current test suite
	 */
	protected function endCurrentTestSuite() {
		if(!$this->currentSuite) return;

		// Ensure any current test is ended along with the current suite
		$this->endCurrentTest();

		// Save and reset current state
		array_push($this->suiteResults['suites'], $this->currentSuite);
		$this->currentSuite = null;
	}

	/**
	 * Upon completion of a test suite adds the suite to the suties performed
	 *
	 * @access public
	 * @param obj PHPUnit_Framework_TestSuite, current suite that is being run
	 * @return void
	 */
	public function endTestSuite( PHPUnit_Framework_TestSuite $suite) {
		if(strlen($suite->getName())) {
			$this->endCurrentTestSuite();
		}
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
	 * Trys to get the original exception thrown by the test on failure/error
	 * to enable us to give a bit more detail about the failure/error
	 *
	 * @access private
	 * @param obj PHPUnit_Framework_Test, current test that is being run
	 * @param obj PHPUnit_Framework_AssertationFailedError, PHPUnit error
	 * @return array
	 */
	private function getTestException(PHPUnit_Framework_Test $test, Exception $e) {
		// get the name of the testFile from the test
		$testName = preg_replace('/(.*)\((.*[^)])\)/', '\\2', $test->toString());
		$trace = $e->getTrace();
		// loop through the exception trace to find the original exception
		for($i = 0; $i < count($trace); $i++) {

			if(array_key_exists('file', $trace[$i])) {
				if(stristr($trace[$i]['file'], $testName.'.php') != false) return $trace[$i];
			}
			if(array_key_exists('file:protected', $trace[$i])) {
				if(stristr($trace[$i]['file:protected'], $testName.'.php') != false) return $trace[$i];
			}
		}
	}

	/**
	 * Writes a status message to the output stream in a user readable HTML format
	 * @param string $name Name of the object that generated the error
	 * @param string $message Message of the error
	 * @param array $trace Stacktrace
	 */
	protected function writeResultError($name, $message, $trace) {
		echo "<div class=\"failure\"><h2 class=\"test-case\">&otimes; ". $this->testNameToPhrase($name) ."</h2>";
		echo "<pre>".htmlentities($message, ENT_COMPAT, 'UTF-8')."</pre>";
		echo SS_Backtrace::get_rendered_backtrace($trace);
		echo "</div>";
	}

	/**
	 * Display error bar if it exists
	 */
	public function writeResults() {
		$passCount = 0;
		$failCount = 0;
		$testCount = 0;
		$incompleteCount = 0;
		$errorCount = 0; // Includes both suite and test level errors

		// Ensure that the current suite is cleanly ended.
		// A suite may not end correctly if there was an error during setUp
		$this->endCurrentTestSuite();

		foreach($this->suiteResults['suites'] as $suite) {

			// Report suite error. In the case of fatal non-success messages
			// These should be reported as errors. Failure/Success relate only
			// to individual tests directly
			if($suite['error']) {
				$errorCount++;
				$this->writeResultError(
					$suite['suite']->getName(),
					$suite['error']['message'],
					$suite['error']['trace']
				);
			}

			// Run through all tests in this suite
			foreach($suite['tests'] as $test) {
				$testCount++;
				switch($test['status']) {
					case TEST_ERROR: $errorCount++; break;
					case TEST_INCOMPLETE: $incompleteCount++; break;
					case TEST_SUCCESS: $passCount++; break;
					case TEST_FAILURE: $failCount++; break;
				}

				// Report test error
				if ($test['status'] != TEST_SUCCESS) {
					$this->writeResultError(
						$test['name'],
						$test['message'],
						$test['trace']
					);
				}
			}
		}
		$result = ($failCount || $errorCount) ? 'fail' : 'pass';
		echo "<div class=\"status $result\">";
		echo "<h2><span>$testCount</span> tests run: <span>$passCount</span> passes, <span>$failCount</span> failures,"
			. " and <span>$incompleteCount</span> incomplete with <span>$errorCount</span> errors</h2>";
		echo "</div>";

	}

	protected function testNameToPhrase($name) {
		return ucfirst(preg_replace("/([a-z])([A-Z])/", "$1 $2", $name));
	}
}

