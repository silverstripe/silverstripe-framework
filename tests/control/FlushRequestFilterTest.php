<?php

class FlushRequestFilterTest extends FunctionalTest {

	/**
	 * Assert that classes that implement flushable are called
	 */
	public function testImplementorsAreCalled() {
		$this->assertFalse(FlushRequestFilterTest_Flushable::$flushed);

		$this->get('?flush=1');

		$this->assertTrue(FlushRequestFilterTest_Flushable::$flushed);
	}

}

class FlushRequestFilterTest_Flushable implements Flushable, TestOnly {

	public static $flushed = false;

	public static function flush() {
		self::$flushed = true;
	}

}
