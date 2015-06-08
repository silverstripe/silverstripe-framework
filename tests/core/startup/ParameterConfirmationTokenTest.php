<?php

/**
 * Dummy parameter token
 */
class ParameterConfirmationTokenTest_Token extends ParameterConfirmationToken implements TestOnly {

	public function currentAbsoluteURL() {
		return parent::currentAbsoluteURL();
	}
}


/**
 * A token that always validates a given token
 */
class ParameterConfirmationTokenTest_ValidToken extends ParameterConfirmationTokenTest_Token {

	protected function checkToken($token) {
		return true;
	}
}

class ParameterConfirmationTokenTest extends SapphireTest {

	private function addPart($answer, $slash, $part) {
		$bare = str_replace('/', '', $part);

		if ($bare) $answer = array_merge($answer, array($bare));
		if ($part) $slash = (substr($part, -1) == '/') ? '/' : '';

		return array($answer, $slash);
	}

	protected $oldHost = null;
	
	public function setUp() {
		parent::setUp();
		$this->oldHost = $_SERVER['HTTP_HOST'];
		$_GET['parameterconfirmationtokentest_notoken'] = 'value';
		$_GET['parameterconfirmationtokentest_empty'] = '';
		$_GET['parameterconfirmationtokentest_withtoken'] = '1';
		$_GET['parameterconfirmationtokentest_withtokentoken'] = 'dummy';
		$_GET['parameterconfirmationtokentest_nulltoken'] = '1';
		$_GET['parameterconfirmationtokentest_nulltokentoken'] = null;
		$_GET['parameterconfirmationtokentest_emptytoken'] = '1';
		$_GET['parameterconfirmationtokentest_emptytokentoken'] = '';
	}

	public function tearDown() {
		foreach($_GET as $param) {
			if(stripos($param, 'parameterconfirmationtokentest_') === 0) unset($_GET[$param]);
		}
		$_SERVER['HTTP_HOST'] = $this->oldHost;
		parent::tearDown();
	}

	public function testParameterDetectsParameters() {
		$withoutToken = new ParameterConfirmationTokenTest_Token('parameterconfirmationtokentest_notoken');
		$emptyParameter = new ParameterConfirmationTokenTest_Token('parameterconfirmationtokentest_empty');
		$withToken = new ParameterConfirmationTokenTest_ValidToken('parameterconfirmationtokentest_withtoken');
		$withoutParameter = new ParameterConfirmationTokenTest_Token('parameterconfirmationtokentest_noparam');
		$nullToken = new ParameterConfirmationTokenTest_Token('parameterconfirmationtokentest_nulltoken');
		$emptyToken = new ParameterConfirmationTokenTest_Token('parameterconfirmationtokentest_emptytoken');
		
		// Check parameter
		$this->assertTrue($withoutToken->parameterProvided());
		$this->assertTrue($emptyParameter->parameterProvided());  // even if empty, it's still provided
		$this->assertTrue($withToken->parameterProvided());
		$this->assertFalse($withoutParameter->parameterProvided());
		$this->assertTrue($nullToken->parameterProvided());
		$this->assertTrue($emptyToken->parameterProvided());
		
		// Check token
		$this->assertFalse($withoutToken->tokenProvided());
		$this->assertFalse($emptyParameter->tokenProvided());
		$this->assertTrue($withToken->tokenProvided()); // Actually forced to true for this test
		$this->assertFalse($withoutParameter->tokenProvided());
		$this->assertFalse($nullToken->tokenProvided());
		$this->assertFalse($emptyToken->tokenProvided());
		
		// Check if reload is required
		$this->assertTrue($withoutToken->reloadRequired());
		$this->assertTrue($emptyParameter->reloadRequired());
		$this->assertFalse($withToken->reloadRequired());
		$this->assertFalse($withoutParameter->reloadRequired());
		$this->assertTrue($nullToken->reloadRequired());
		$this->assertTrue($emptyToken->reloadRequired());
		
		// Check suppression
		$this->assertTrue(isset($_GET['parameterconfirmationtokentest_notoken']));
		$withoutToken->suppress();
		$this->assertFalse(isset($_GET['parameterconfirmationtokentest_notoken']));
	}

	public function testPrepareTokens() {
		// Test priority ordering
		$token = ParameterConfirmationToken::prepare_tokens(array(
			'parameterconfirmationtokentest_notoken',
			'parameterconfirmationtokentest_empty',
			'parameterconfirmationtokentest_noparam'
		));
		// Test no invalid tokens
		$this->assertEquals('parameterconfirmationtokentest_empty', $token->getName());
		$token = ParameterConfirmationToken::prepare_tokens(array(
			'parameterconfirmationtokentest_noparam'
		));
		$this->assertEmpty($token);
	}

	/**
	 * currentAbsoluteURL needs to handle base or url being missing, or any combination of slashes.
	 *
	 * There should always be exactly one slash between each part in the result, and any trailing slash
	 * should be preserved.
	 */
	public function testCurrentAbsoluteURLHandlesSlashes() {
		global $url;

		$token = new ParameterConfirmationTokenTest_Token('parameterconfirmationtokentest_parameter');

		foreach(array('foo','foo/') as $host) {
			list($hostAnswer, $hostSlash) = $this->addPart(array(), '', $host);

			foreach(array('', '/', 'bar', 'bar/', '/bar', '/bar/') as $base) {
				list($baseAnswer, $baseSlash) = $this->addPart($hostAnswer, $hostSlash, $base);

				foreach(array('', '/', 'baz', 'baz/', '/baz', '/baz/') as $url) {
					list($urlAnswer, $urlSlash) = $this->addPart($baseAnswer, $baseSlash, $url);

					$_SERVER['HTTP_HOST'] = $host;
					ParameterConfirmationToken::$alternateBaseURL = $base;

					$this->assertEquals('http://'.implode('/', $urlAnswer) . $urlSlash, $token->currentAbsoluteURL());
				}
			}
		}
	}

}