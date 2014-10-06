<?php

/**
 * An extension of ErrorControlChain that runs the chain in a subprocess.
 *
 * We need this because ErrorControlChain only suppresses uncaught fatal errors, and
 * that would kill PHPUnit execution
 */
class ErrorControlChainTest_Chain extends ErrorControlChain {

	// Change function visibility to be testable directly
	public function translateMemstring($memstring) {
		return parent::translateMemstring($memstring);
	}

	function executeInSubprocess($includeStderr = false) {
		// Get the path to the ErrorControlChain class
		$classpath = SS_ClassLoader::instance()->getItemPath('ErrorControlChain');
		$suppression = $this->suppression ? 'true' : 'false';

		// Start building a PHP file that will execute the chain
		$src = '<'."?php
require_once '$classpath';

\$chain = new ErrorControlChain();

\$chain->setSuppression($suppression);

\$chain
";

		// For each step, use reflection to pull out the call, stick in the the PHP source we're building
		foreach ($this->steps as $step) {
			$func = new ReflectionFunction($step['callback']);
			$source = file($func->getFileName());

			$start_line = $func->getStartLine() - 1;
			$end_line = $func->getEndLine();
			$length = $end_line - $start_line;

			$src .= implode("", array_slice($source, $start_line, $length)) . "\n";
		}

		// Finally add a line to execute the chain
		$src .= "->execute();";

		// Now stick it in a temporary file & run it
		$codepath = TEMP_FOLDER.'/ErrorControlChainTest_'.sha1($src).'.php';

		if($includeStderr) {
			$null = '&1';
		} else {
			$null = is_writeable('/dev/null') ? '/dev/null' : 'NUL';
		}

		file_put_contents($codepath, $src);
		exec("php $codepath 2>$null", $stdout, $errcode);
		unlink($codepath);

		return array(implode("\n", $stdout), $errcode);
	}
}

class ErrorControlChainTest extends SapphireTest {

	function setUp() {
		// Check we can run PHP at all
		$null = is_writeable('/dev/null') ? '/dev/null' : 'NUL';
		exec("php -v 2> $null", $out, $rv);

		if ($rv != 0) {
			$this->markTestSkipped("Can't run PHP from the command line - is it in your path?");
			$this->skipTest = true;
		}

		parent::setUp();
	}

	function testErrorSuppression() {

		// Fatal error

		$chain = new ErrorControlChainTest_Chain();

		list($out, $code) = $chain
			->then(function(){
				Foo::bar(); // Non-existant class causes fatal error
			})
			->thenIfErrored(function(){
				echo "Done";
			})
			->executeInSubprocess();

		$this->assertEquals('Done', $out);

		// User error

		$chain = new ErrorControlChainTest_Chain();

		list($out, $code) = $chain
			->then(function(){
				user_error('Error', E_USER_ERROR);
			})
			->thenIfErrored(function(){
				echo "Done";
			})
			->executeInSubprocess();

		$this->assertEquals('Done', $out);

		// Recoverable error

		$chain = new ErrorControlChainTest_Chain();

		list($out, $code) = $chain
			->then(function(){
				$x = function(ErrorControlChain $foo){ };
				$x(1); // Calling against type
			})
			->thenIfErrored(function(){
				echo "Done";
			})
			->executeInSubprocess();

		$this->assertEquals('Done', $out);

		// Memory exhaustion

		$chain = new ErrorControlChainTest_Chain();

		list($out, $code) = $chain
			->then(function(){
				ini_set('memory_limit', '10M');
				$a = array();
				while(1) $a[] = 1;
			})
			->thenIfErrored(function(){
				echo "Done";
			})
			->executeInSubprocess();

		$this->assertEquals('Done', $out);

		// Exceptions

		$chain = new ErrorControlChainTest_Chain();

		list($out, $code) = $chain
			->then(function(){
				throw new Exception("bob");
			})
			->thenIfErrored(function(){
				echo "Done";
			})
			->executeInSubprocess();

		$this->assertEquals('Done', $out);
	}

	function testExceptionSuppression() {
		$chain = new ErrorControlChainTest_Chain();

		list($out, $code) = $chain
			->then(function(){
				throw new Exception('This exception should be suppressed');
			})
			->thenIfErrored(function(){
				echo "Done";
			})
			->executeInSubprocess();

		$this->assertEquals('Done', $out);
	}

	function testErrorControl() {
		$chain = new ErrorControlChainTest_Chain();

		list($out, $code) = $chain
			->then(function() { echo 'preThen,'; })
			->thenIfErrored(function() { echo 'preThenIfErrored,'; })
			->thenAlways(function() { echo 'preThenAlways,'; })

			->then(function(){ user_error('An error', E_USER_ERROR); })

			->then(function() { echo 'postThen,'; })
			->thenIfErrored(function() { echo 'postThenIfErrored,'; })
			->thenAlways(function() { echo 'postThenAlways,'; })

			->executeInSubprocess();

		$this->assertEquals(
			"preThen,preThenAlways,postThenIfErrored,postThenAlways,",
			$out
		);
	}

	function testSuppressionControl() {
		// Turning off suppression before execution

		$chain = new ErrorControlChainTest_Chain();
		$chain->setSuppression(false);

		list($out, $code) = $chain
			->then(function($chain){
				Foo::bar(); // Non-existant class causes fatal error
			})
			->executeInSubprocess(true);

		$this->assertContains('Fatal error', $out);
		$this->assertContains('Foo', $out);

		// Turning off suppression during execution

		$chain = new ErrorControlChainTest_Chain();

		list($out, $code) = $chain
			->then(function($chain){
				$chain->setSuppression(false);
				Foo::bar(); // Non-existent class causes fatal error
			})
			->executeInSubprocess(true);

		$this->assertContains('Fatal error', $out);
		$this->assertContains('Foo', $out);
	}

	function testDoesntAffectNonFatalErrors() {
		$chain = new ErrorControlChainTest_Chain();

		list($out, $code) = $chain
			->then(function(){
				$array = null;
				if (@$array['key'] !== null) user_error('Error', E_USER_ERROR);
			})
			->then(function(){
				echo "Good";
			})
			->thenIfErrored(function(){
				echo "Bad";
			})
			->executeInSubprocess();

		$this->assertContains("Good", $out);
	}

	function testDoesntAffectCaughtExceptions() {
		$chain = new ErrorControlChainTest_Chain();

		list($out, $code) = $chain
			->then(function(){
				try {
					throw new Exception('Error');
				}
				catch (Exception $e) {
					echo "Good";
				}
			})
			->thenIfErrored(function(){
				echo "Bad";
			})
			->executeInSubprocess();

		$this->assertContains("Good", $out);
	}

	function testDoesntAffectHandledErrors() {
		$chain = new ErrorControlChainTest_Chain();

		list($out, $code) = $chain
			->then(function(){
				set_error_handler(function(){ /* NOP */ });
				user_error('Error', E_USER_ERROR);
			})
			->then(function(){
				echo "Good";
			})
			->thenIfErrored(function(){
				echo "Bad";
			})
			->executeInSubprocess();

		$this->assertContains("Good", $out);
	}

	function testMemoryConversion() {
		$chain = new ErrorControlChainTest_Chain();

		$this->assertEquals(200, $chain->translateMemstring('200'));
		$this->assertEquals(300, $chain->translateMemstring('300'));

		$this->assertEquals(2 * 1024, $chain->translateMemstring('2k'));
		$this->assertEquals(3 * 1024, $chain->translateMemstring('3K'));

		$this->assertEquals(2 * 1024 * 1024, $chain->translateMemstring('2m'));
		$this->assertEquals(3 * 1024 * 1024, $chain->translateMemstring('3M'));

		$this->assertEquals(2 * 1024 * 1024 * 1024, $chain->translateMemstring('2g'));
		$this->assertEquals(3 * 1024 * 1024 * 1024, $chain->translateMemstring('3G'));

		$this->assertEquals(200, $chain->translateMemstring('200foo'));
		$this->assertEquals(300, $chain->translateMemstring('300foo'));
	}
}
