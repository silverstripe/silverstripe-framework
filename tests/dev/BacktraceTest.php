<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class BacktraceTest extends SapphireTest {
	
	function testIgnoredFunctionArgs() {
		$orig = SS_Backtrace::$ignore_function_args;
		
		$bt = array(
			array(
				'type' => '->',
				'file' => 'MyFile.php',
				'line' => 99,
				'function' => 'myIgnoredGlobalFunction',
				'args' => array('password' => 'secred',)
			),
			array(
				'class' => 'MyClass',
				'type' => '->',
				'file' => 'MyFile.php',
				'line' => 99,
				'function' => 'myIgnoredClassFunction',
				'args' => array('password' => 'secred',)
			),
			array(
				'class' => 'MyClass',
				'type' => '->',
				'file' => 'MyFile.php',
				'line' => 99,
				'function' => 'myFunction',
				'args' => array('myarg' => 'myval')
			)
		);
		SS_Backtrace::$ignore_function_args[] = array('MyClass', 'myIgnoredClassFunction');
		SS_Backtrace::$ignore_function_args[] = 'myIgnoredGlobalFunction';

		$filtered = SS_Backtrace::filter_backtrace($bt);

		$this->assertEquals('<filtered>', $filtered[0]['args']['password'], 'Filters global functions');
		$this->assertEquals('<filtered>', $filtered[1]['args']['password'], 'Filters class functions');
		$this->assertEquals('myval', $filtered[2]['args']['myarg'], 'Doesnt filter other functions');
		
		SS_Backtrace::$ignore_function_args = $orig;
	}
	
}