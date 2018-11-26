<?php

namespace SilverStripe\Core\Tests\Startup;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Core\Startup\URLConfirmationToken;
use SilverStripe\Core\Tests\Startup\URLConfirmationTokenTest\StubToken;
use SilverStripe\Core\Tests\Startup\URLConfirmationTokenTest\StubValidToken;
use SilverStripe\Dev\SapphireTest;

class URLConfirmationTokenTest extends SapphireTest
{
    public function testValidToken()
    {
        $request = new HTTPRequest('GET', 'token/test/url', ['tokentesturltoken' => 'value']);
        $validToken = new StubValidToken('token/test/url', $request);
        $this->assertTrue($validToken->urlMatches());
        $this->assertFalse($validToken->urlExistsInBackURL());
        $this->assertTrue($validToken->tokenProvided()); // Actually forced to true for this test
        $this->assertFalse($validToken->reloadRequired());
        $this->assertFalse($validToken->reloadRequiredIfError());
        $this->assertStringStartsWith(
            Controller::join_links(BASE_URL, '/', 'token/test/url'),
            $validToken->redirectURL()
        );
    }

    public function testTokenWithLeadingSlashInUrl()
    {
        $request = new HTTPRequest('GET', '/leading/slash/url', []);
        $leadingSlash = new StubToken('leading/slash/url', $request);
        $this->assertTrue($leadingSlash->urlMatches());
        $this->assertFalse($leadingSlash->urlExistsInBackURL());
        $this->assertFalse($leadingSlash->tokenProvided());
        $this->assertTrue($leadingSlash->reloadRequired());
        $this->assertTrue($leadingSlash->reloadRequiredIfError());
        $this->assertContains('leading/slash/url', $leadingSlash->redirectURL());
        $this->assertContains('leadingslashurltoken', $leadingSlash->redirectURL());
    }

    public function testTokenWithTrailingSlashInUrl()
    {
        $request = new HTTPRequest('GET', 'trailing/slash/url/', []);
        $trailingSlash = new StubToken('trailing/slash/url', $request);
        $this->assertTrue($trailingSlash->urlMatches());
        $this->assertFalse($trailingSlash->urlExistsInBackURL());
        $this->assertFalse($trailingSlash->tokenProvided());
        $this->assertTrue($trailingSlash->reloadRequired());
        $this->assertTrue($trailingSlash->reloadRequiredIfError());
        $this->assertContains('trailing/slash/url', $trailingSlash->redirectURL());
        $this->assertContains('trailingslashurltoken', $trailingSlash->redirectURL());
    }

    public function testTokenWithUrlMatchedInBackUrl()
    {
        $request = new HTTPRequest('GET', '/', ['BackURL' => 'back/url']);
        $backUrl = new StubToken('back/url', $request);
        $this->assertFalse($backUrl->urlMatches());
        $this->assertTrue($backUrl->urlExistsInBackURL());
        $this->assertFalse($backUrl->tokenProvided());
        $this->assertFalse($backUrl->reloadRequired());
        $this->assertTrue($backUrl->reloadRequiredIfError());
        $home = (BASE_URL ?: '/') . '?';
        $this->assertStringStartsWith($home, $backUrl->redirectURL());
        $this->assertContains('backurltoken', $backUrl->redirectURL());
    }

    public function testUrlSuppressionWhenTokenMissing()
    {
        // Check suppression
        $request = new HTTPRequest('GET', 'test/url', []);
        $token = new StubToken('test/url', $request);
        $this->assertEquals('test/url', $request->getURL(false));
        $token->suppress();
        $this->assertEquals('', $request->getURL(false));
    }

    public function testPrepareTokens()
    {
        $request = new HTTPRequest('GET', 'test/url', []);
        $token = URLConfirmationToken::prepare_tokens(
            [
                'test/url',
                'test',
                'url'
            ],
            $request
        );
        // Test no invalid tokens
        $this->assertEquals('test/url', $token->getURLToCheck());
        $this->assertNotEquals(
            'test/url',
            $request->getURL(false),
            'prepare_tokens() did not suppress URL'
        );
    }

    public function testPrepareTokensDoesntSuppressWhenNotMatched()
    {
        $request = new HTTPRequest('GET', 'test/url', []);
        $token = URLConfirmationToken::prepare_tokens(
            ['another/url'],
            $request
        );
        $this->assertEmpty($token);
        $this->assertEquals(
            'test/url',
            $request->getURL(false),
            'prepare_tokens() incorrectly suppressed URL'
        );
    }

    public function testPrepareTokensWithUrlMatchedInBackUrl()
    {
        // Test backurl token
        $request = new HTTPRequest('GET', '/', ['BackURL' => 'back/url']);
        $token = URLConfirmationToken::prepare_tokens(
            [ 'back/url' ],
            $request
        );
        $this->assertNotEmpty($token);
        $this->assertEquals('back/url', $token->getURLToCheck());
        $this->assertNotEquals(
            'back/url',
            $request->getURL(false),
            'prepare_tokens() did not suppress URL'
        );
    }

    public function dataProviderURLs()
    {
        return [
            [''],
            ['/'],
            ['bar'],
            ['bar/'],
            ['/bar'],
            ['/bar/'],
        ];
    }

    /**
     * currentURL needs to handle base or url being missing, or any combination of slashes.
     *
     * There should always be exactly one slash between each part in the result, and any trailing slash
     * should be preserved.
     *
     * @dataProvider dataProviderURLs
     */
    public function testCurrentURLHandlesSlashes($url)
    {
        $request = new HTTPRequest('GET', $url, []);

        $token = new StubToken(
            'another/url',
            $request
        );
        $expected = rtrim(Controller::join_links(BASE_URL, '/', $url), '/') ?: '/';
        $this->assertEquals($expected, $token->currentURL(), "Invalid redirect for request url $url");
    }
}
