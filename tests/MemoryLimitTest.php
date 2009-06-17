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
	}


	///////////////////
	
	private $origLimit;
	
	function setUp() {
		$this->origLimit = ini_get('memory_limit');
	}
	function tearDown() {
		ini_set('memory_limit', $this->origLimit);
	}
}