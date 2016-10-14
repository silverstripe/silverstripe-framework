<?php

namespace SilverStripe\Control\Tests;

use SilverStripe\Dev\FunctionalTest;

class FlushRequestFilterTest extends FunctionalTest {

	/**
	 * Assert that classes that implement flushable are called
	 */
	public function testImplementorsAreCalled() {
		$this->assertFalse(FlushRequestFilterTest\TestFlushable::$flushed);

		$this->get('?flush=1');

		$this->assertTrue(FlushRequestFilterTest\TestFlushable::$flushed);
	}

}
