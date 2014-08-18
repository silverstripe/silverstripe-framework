<?php
/**
 * @package framework
 * @subpackage tests
 */

class MemoryLimitTest extends SapphireTest {

	public function testIncreaseMemoryLimitTo() {
		if(!$this->canChangeMemory()) return;

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

		// If memory limit was left at 409600K, that means that the current testbox doesn't have
		// 1G of memory available.  That's okay; let's not report a failure for that.
		if(ini_get('memory_limit') != '409600K') {
			$this->assertEquals('1G', ini_get('memory_limit'));
		}

		// No argument means unlimited
		increase_memory_limit_to();
		$this->assertEquals(-1, ini_get('memory_limit'));
	}

	public function testIncreaseTimeLimitTo() {
		if(!$this->canChangeMemory()) return;

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

	public function setUp() {
		$this->origMemLimit = ini_get('memory_limit');
		$this->origTimeLimit = ini_get('max_execution_time');
		$this->origMemLimitMax = get_increase_memory_limit_max();
		$this->origTimeLimitMax = get_increase_time_limit_max();
		set_increase_memory_limit_max(-1);
		set_increase_time_limit_max(-1);
	}
	public function tearDown() {
		ini_set('memory_limit', $this->origMemLimit);
		set_time_limit($this->origTimeLimit);
		set_increase_memory_limit_max($this->origMemLimitMax);
		set_increase_time_limit_max($this->origTimeLimitMax);
	}

	/**
	 * Determines wether the environment generally allows
	 * to change the memory limits, which is not always the case.
	 *
	 * @return Boolean
	 */
	protected function canChangeMemory() {
		$exts = get_loaded_extensions();
		// see http://www.hardened-php.net/suhosin/configuration.html#suhosin.memory_limit
		if(in_array('suhosin', $exts)) return false;

		// We can't change memory limit in safe mode
		if(ini_get('safe_mode')) return false;

		return true;
	}
}
