<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class BacktraceTest extends SapphireTest {
	
	function testFullFuncNameWithArgsAndCustomCharLimit() {
		$func = array(
			'class' => 'MyClass',
			'type' => '->',
			'file' => 'MyFile.php',
			'line' => 99,
			'function' => 'myFunction',
			'args' => array(
				'number' => 1,
				'mylongstring' => 'more than 20 characters 1234567890',
			)
		);
		$this->assertEquals(
			'MyClass->myFunction(1,more than 20 charact...)',
			SS_Backtrace::full_func_name($func, true, 20)
		);
	}
	
}