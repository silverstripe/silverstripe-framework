<?php

class ErrorControlChainTest extends SapphireTest {

	function testErrorSuppression() {
		$chain = new ErrorControlChain();

		$chain
			->then(function(){
				user_error('This error should be suppressed', E_USER_ERROR);
			})
			->execute();

		$this->assertTrue($chain->hasErrored());
	}

	function testMultipleErrorSuppression() {
		$chain = new ErrorControlChain();

		$chain
			->then(function(){
				user_error('This error should be suppressed', E_USER_ERROR);
			})
			->thenAlways(function(){
				user_error('This error should also be suppressed', E_USER_ERROR);
			})
			->execute();

		$this->assertTrue($chain->hasErrored());
	}

	function testExceptionSuppression() {
		$chain = new ErrorControlChain();

		$chain
			->then(function(){
				throw new Exception('This exception should be suppressed');
			})
			->execute();

		$this->assertTrue($chain->hasErrored());
	}

	function testMultipleExceptionSuppression() {
		$chain = new ErrorControlChain();

		$chain
			->then(function(){
				throw new Exception('This exception should be suppressed');
			})
			->thenAlways(function(){
				throw new Exception('This exception should also be suppressed');
			})
			->execute();

		$this->assertTrue($chain->hasErrored());
	}

	function testErrorControl() {
		$preError = $postError = array('then' => false, 'thenIfErrored' => false, 'thenAlways' => false);

		$chain = new ErrorControlChain();

		$chain
			->then(function() use (&$preError) { $preError['then'] = true; })
			->thenIfErrored(function() use (&$preError) { $preError['thenIfErrored'] = true; })
			->thenAlways(function() use (&$preError) { $preError['thenAlways'] = true; })

			->then(function(){ user_error('An error', E_USER_ERROR); })

			->then(function() use (&$postError) { $postError['then'] = true; })
			->thenIfErrored(function() use (&$postError) { $postError['thenIfErrored'] = true; })
			->thenAlways(function() use (&$postError) { $postError['thenAlways'] = true; })

			->execute();

		$this->assertEquals(
			array('then' => true, 'thenIfErrored' => false, 'thenAlways' => true),
			$preError,
			'Then and thenAlways callbacks called before error, thenIfErrored callback not called'
		);

		$this->assertEquals(
			array('then' => false, 'thenIfErrored' => true, 'thenAlways' => true),
			$postError,
			'thenIfErrored and thenAlways callbacks called after error, then callback not called'
		);
	}

}