<?php
/**
 * @package framework
 * @subpackage tests
 */
class HTTPResponseTest extends SapphireTest {
	
	public function testStatusDescriptionStripsNewlines() {
		$r = new SS_HTTPResponse('my body', 200, "my description \nwith newlines \rand carriage returns");
		$this->assertEquals(
			"my description with newlines and carriage returns",
			$r->getStatusDescription()
		);
	}
	
}
