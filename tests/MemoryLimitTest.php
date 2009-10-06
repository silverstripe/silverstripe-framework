<?php

class MemoryLimitTest extends SapphireTest {
	
	function testIncreaseMemoryLimitTo() {
		ini_set('memory_limit', '64M');
		
		// It can go up
		increase_memory_limit_to('128M');
		$this->assertEquals('128M', ini_get('memory_limit'));

		// But not down
		increase_memory_limit_to('64M');
		$this->assertEquals('128M', ini_get('memory_limit'));
		
		// Test the different kinds of syntaxes
		increase_memory_limit_to(1024*1024*200);
		$this->assertEquals(1024*1024*200, ini_get('memory_limit'));

		increase_memory_limit_to('409600K');
		$this->assertEquals('409600K', ini_get('memory_limit'));

		increase_memory_limit_to('1G');
		$this->assertEquals('1G', ini_get('memory_limit'));

		// No argument means unlimited
		increase_memory_limit_to();
		$this->assertEquals(-1, ini_get('memory_limit'));
	}

	function testIncreaseTimeLimitTo() {
		set_time_limit(6000);
		
		// It can go up
		increase_time_limit_to(7000);
		$this->assertEquals(7000, ini_get('max_execution_time'));

		// But not down
		increase_time_limit_to(5000);
		$this->assertEquals(7000, ini_get('max_execution_time'));
		
		// 0/nothing means infinity
		increase_time_limit_to();
		$this->assertEquals(0, ini_get('max_execution_time'));

		// Can't go down from there
		increase_time_limit_to(10000);
		$this->assertEquals(0, ini_get('max_execution_time'));
		
	}


	///////////////////
	
	private $origMemLimit, $origTimeLimit;
	
	function setUp() {
		$this->origMemLimit = ini_get('memory_limit');
		$this->origTimeLimit = ini_get('max_execution_time');
	}
	function tearDown() {
		ini_set('memory_limit', $this->origMemLimit);
		set_time_limit($this->origTimeLimit);
	}
}