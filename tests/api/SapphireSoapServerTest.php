<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class SapphireSoapServerTest extends FunctionalTest {
	
	/**
	 * @see http://open.silverstripe.com/ticket/4570
	 */
	function testWsdl() {
		$response = $this->get('SapphireSoapServerTest_MyServer/wsdl');
		
		$this->assertEquals(
			$response->getHeader('Content-Type'), 
			'text/xml',
			'wsdl request returns with correct XML content type'
		);
	}
}

/**
 * @package sapphire
 * @subpackage tests
 */
class SapphireSoapServerTest_MyServer extends SapphireSoapServer {
	
	function Link($action = null) {
		return Controller::join_links('SapphireSoapServerTest_MyServer', $action);
	}
}