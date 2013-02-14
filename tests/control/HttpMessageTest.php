<?php
/**
 * Tests for the {@link SS_HttpMessage} class.
 */
class HttpMessageTest extends SapphireTest {

	public function testHeadersCaseInsensitive() {
		$message = $this->getMockForAbstractClass('SS_HttpRequest');
		$message->setHeader('X-HTTP-Header', 'value');

		$this->assertEquals('value', $message->getHeader('X-HTTP-Header'));
		$this->assertEquals('value', $message->getHeader('X-Http-Header'));

		$message->unsetHeader('x-http-header');
		$this->assertNull($message->getHeader('X-HTTP-Header'));
	}

}
