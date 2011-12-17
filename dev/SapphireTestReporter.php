<?php
require_once 'PHPUnit/Framework/TestResult.php';
require_once 'PHPUnit/Framework/TestListener.php';

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
 * @package sapphire
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
	    	$this->currentSuite = array(
	    		'suite'      => $suite,  // the test suite
	    		'tests'      => array(), // the tests in the suite
	    		'errors'     => 0,       // number of tests with errors
	    		'failures'   => 0,       // number of tests which failed
	    		'incomplete' => 0);    	 // number of tests that were not completed correctly
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
			$this->currentTest = array(
				'name'        => preg_replace('(\(.*\))', '', $test->toString()), // the name of the test (without the suite name)
				'timeElapsed' => 0,                // execution time of the test
				'status'      => TEST_SUCCESS,     // status of the test execution
				'message'     => '',               // user message of test result
				'exception'   => NULL,             // original caught exception thrown by test upon failure/error
				'uid'         => md5(microtime())  // a unique ID for this test (used for identification purposes in results)
			);
			if($this->hasTimer) $this->timer->start();
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
		$this->currentTest['status'] = TEST_FAILURE;
		$this->currentTest['message'] = $e->toString();
		$this->currentTest['exception'] = $this->getTestException($test, $e);
		$this->currentTest['trace'] = $e->getTrace();
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
		$this->currentTest['status'] = TEST_ERROR;
		$this->currentTest['message'] = $e->getMessage();
		$this->currentTest['exception'] = $this->getTestException($test, $e);
		$this->currentTest['trace'] = $e->getTrace();
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
		$this->currentTest['status'] = TEST_INCOMPLETE;
		$this->currentTest['message'] = $e->toString();
		$this->currentTest['exception'] = $this->getTestException($test, $e);
		$this->currentTest['trace'] = $e->getTrace();
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
	 * Upon completion of a test, records the execution time (if available) and adds the test to 
	 * the tests performed in the current suite.
	 * 
	 * @access public
	 * @param obj PHPUnit_Framework_Test, current test that is being run
	 * @return void
	 */
	public function endTest( PHPUnit_Framework_Test $test, $time) {
		$testDuration = microtime(true) - $this->startTestTime;
		$this->testSpeeds[$this->currentSuite['suite']->getName() . '.' . $this->currentTest['name']] = $testDuration;

		if($this->hasTimer) {
			$this->timer->stop();
			$this->currentTest['timeElapsed'] = $this->timer->timeElapsed();
		}
		array_push($this->currentSuite['tests'], $this->currentTest);
		if(method_exists($test, 'getActualOutput')) {
			$output = $test->getActualOutput();
			if($output) echo "\nOutput:\n$output";
		}
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
			array_push($this->suiteResults['suites'], $this->currentSuite);
		}
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
		$testName = ereg_replace('(.*)\((.*[^)])\)', '\\2', $test->toString());
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
				if ($test['status'] != 1) {
					echo "<div class=\"failure\"><span>&otimes; ". $this->testNameToPhrase($test['name']) ."</span><br>";
					echo "<pre>".htmlentities($test['message'])."</pre><br>";
					echo SS_Backtrace::get_rendered_backtrace($test['trace']);
					echo "</div>";
				}
			}
		}
		$result = ($failCount > 0) ? 'fail' : 'pass';
		echo "<div class=\"$result\">";
		echo "<h2><span>$testCount</span> tests run: <span>$passCount</span> passes, <span>$failCount</span> fails, and <span>0</span> exceptions</h2>";
		echo "</div>";
		
	}
	
	protected function testNameToPhrase($name) {
		return ucfirst(preg_replace("/([a-z])([A-Z])/", "$1 $2", $name));
	}
	
}

?>