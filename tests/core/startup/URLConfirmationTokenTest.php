<?php

class URLConfirmationTokenTest_StubToken extends URLConfirmationToken implements TestOnly {
	public function urlMatches() {
		return parent::urlMatches();
	}

	public function currentURL() {
		return parent::currentURL();
	}

	public function redirectURL() {
		return parent::redirectURL();
	}
}

class URLConfirmationTokenTest_StubValidToken extends URLConfirmationTokenTest_StubToken {
	protected function checkToken($token) {
		return true;
	}
}

class URLConfirmationTokenTest extends SapphireTest {

	protected $originalURL;

	protected $originalGetVars;

	public function setUp() {
		parent::setUp();
		global $url;
		$this->originalURL = $url;
		$this->originalGetVars = $_GET;
	}

	public function tearDown() {
		parent::tearDown();
		global $url;
		$url = $this->originalURL;
		$_GET = $this->originalGetVars;
	}

	public function testValidToken() {
		global $url;
		$url = Controller::join_links(BASE_URL, '/', 'token/test/url');
		$_GET = array('tokentesturltoken' => 'value', 'url' => $url);

		$validToken = new URLConfirmationTokenTest_StubValidToken('token/test/url');
		$this->assertTrue($validToken->urlMatches());
		$this->assertTrue($validToken->tokenProvided()); // Actually forced to true for this test
		$this->assertFalse($validToken->reloadRequired());
		$this->assertStringStartsWith(Controller::join_links(BASE_URL, '/', 'token/test/url'), $validToken->redirectURL());
	}

	public function testTokenWithTrailingSlashInUrl() {
		global $url;
		$url = Controller::join_links(BASE_URL, '/', 'trailing/slash/url/');
		$_GET = array('url' => $url);

		$trailingSlash = new URLConfirmationTokenTest_StubToken('trailing/slash/url');
		$this->assertTrue($trailingSlash->urlMatches());
		$this->assertFalse($trailingSlash->tokenProvided());
		$this->assertTrue($trailingSlash->reloadRequired());
		$this->assertContains('trailing/slash/url', $trailingSlash->redirectURL());
		$this->assertContains('trailingslashurltoken', $trailingSlash->redirectURL());
	}

	public function testUrlSuppressionWhenTokenMissing()
	{
		global $url;
		$url = Controller::join_links(BASE_URL, '/', 'test/url/');
		$_GET = array('url' => $url);

		$token = new URLConfirmationTokenTest_StubToken('test/url');
		$token->suppress();
		$this->assertEquals('/', $_GET['url']);
	}
}
