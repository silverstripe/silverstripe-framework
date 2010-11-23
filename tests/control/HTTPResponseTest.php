<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class HTTPResponseTest extends SapphireTest {
	
	function testStatusDescriptionStripsNewlines() {
		$r = new SS_HTTPResponse('my body', 200, "my description \nwith newlines \rand carriage returns");
		$this->assertEquals(
			"my description with newlines and carriage returns",
			$r->getStatusDescription()
		);
	}
	
}