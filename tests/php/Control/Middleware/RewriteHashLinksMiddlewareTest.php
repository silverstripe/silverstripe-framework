<?php

namespace SilverStripe\Control\Tests\Middleware;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\RewriteHashLinksMiddleware;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\SapphireTest;

class RewriteHashLinksMiddlewareTest extends SapphireTest
{
    protected $currentHost;
    protected $currentURI;

    /**
     * Setup the test
     */
    protected function setUp()
    {
        parent::setUp();

        $this->currentHost = (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : false);
        $this->currentURI = (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : false);
    }

    /**
     * Clean up the test
     */
    protected function tearDown()
    {
        parent::tearDown();
        
        if ($this->currentHost === false) {
            unset($_SERVER['HTTP_HOST']);
        } else {
            $_SERVER['HTTP_HOST'] = $this->currentHost;
        }

        if ($this->currentURI === false) {
            unset($_SERVER['REQUEST_URI']);
        } else {
            $_SERVER['REQUEST_URI'] = $this->currentURI;
        }
    }

    /**
     * Tests rewriting of HTML content types to ensure that it's properly rewriting anchors when the base tag is present
     */
    public function testRewriteHTMLWithBase()
    {
        $_SERVER['HTTP_HOST'] = 'www.mysite.com';
        $_SERVER['REQUEST_URI'] = '//file.com?foo"onclick="alert(\'xss\')""';

        $base = Convert::raw2att('/file.com?foo"onclick="alert(\'xss\')""');

        $body = '<!DOCTYPE html>
        <html>
            <head><base href="' . Director::absoluteBaseURL() . '"><!--[if lte IE 6]></base><![endif]--></head>
            <body>
                <a class="external-inline" href="http://google.com#anchor">ExternalInlineLink</a>
                <a class="inline" href="#anchor">InlineLink</a>
                <svg><use xlink:href="#sprite"></use></svg>
            </body>
        </html>';

        //Mock a request
        $request = new HTTPRequest('GET', $_SERVER['REQUEST_URI']);

        //Hand through the Middleware to be "processed"
        $middleware = new RewriteHashLinksMiddleware();
        $result = $middleware->process($request, function (HTTPRequest $request) use ($body) {
            return HTTPResponse::create($body);
        })->getBody();

        $this->assertContains(
            '<a class="inline" href="' . $base . '#anchor">InlineLink</a>',
            $result
        );

        $this->assertContains(
            '<a class="external-inline" href="http://google.com#anchor">ExternalInlineLink</a>',
            $result
        );

        $this->assertContains(
            '<svg><use xlink:href="#sprite"></use></svg>',
            $result,
            'RewriteHashLinksMiddleware should only rewrite anchor hrefs'
        );
    }

    /**
     * Tests rewriting of HTML content types to ensure that it's not rewriting anchors when the base tag is not present
     */
    public function testRewriteHTMLWithoutBase()
    {
        $_SERVER['HTTP_HOST'] = 'www.mysite.com';
        $_SERVER['REQUEST_URI'] = '//file.com?foo"onclick="alert(\'xss\')""';

        $base = Convert::raw2att('/file.com?foo"onclick="alert(\'xss\')""');

        $body = '<!DOCTYPE html>
        <html>
            <head></head>
            <body>
                <a class="external-inline" href="http://google.com#anchor">ExternalInlineLink</a>
                <a class="inline" href="#anchor">InlineLink</a>
                <svg><use xlink:href="#sprite"></use></svg>
            </body>
        </html>';

        //Mock a request
        $request = new HTTPRequest('GET', $_SERVER['REQUEST_URI']);

        //Hand through the Middleware to be "processed"
        $middleware = new RewriteHashLinksMiddleware();
        $result = $middleware->process($request, function (HTTPRequest $request) use ($body) {
            return HTTPResponse::create($body);
        })->getBody();

        $this->assertNotContains(
            '<a class="inline" href="' . $base . '#anchor">InlineLink</a>',
            $result
        );

        $this->assertContains(
            '<a class="external-inline" href="http://google.com#anchor">ExternalInlineLink</a>',
            $result
        );

        $this->assertContains(
            '<svg><use xlink:href="#sprite"></use></svg>',
            $result,
            'RewriteHashLinksMiddleware should only rewrite anchor hrefs'
        );
    }

    /**
     * Tests rewriting of JSON content type to ensure that it's not rewriting anchors
     */
    public function testRewriteJSONWithBase()
    {
        $_SERVER['HTTP_HOST'] = 'www.mysite.com';
        $_SERVER['REQUEST_URI'] = '//file.com?foo"onclick="alert(\'xss\')""';

        $base = Convert::raw2att('/file.com?foo"onclick="alert(\'xss\')""');

        $body = json_encode(['test' => '<!DOCTYPE html>
        <html>
            <head><base href="' . Director::absoluteBaseURL() . '"><!--[if lte IE 6]></base><![endif]--></head>
            <body>
                <a class="external-inline" href="http://google.com#anchor">ExternalInlineLink</a>
                <a class="inline" href="#anchor">InlineLink</a>
                <svg><use xlink:href="#sprite"></use></svg>
            </body>
        </html>']);


        //Mock a request
        $request = new HTTPRequest('GET', $_SERVER['REQUEST_URI']);

        //Hand through the Middleware to be "processed"
        $middleware = new RewriteHashLinksMiddleware();
        $result = $middleware->process($request, function (HTTPRequest $request) use ($body) {
            return HTTPResponse::create($body)->addHeader('content-type', 'application/json; charset=utf-8');
        })->getBody();

        $this->assertNotContains(
            '<a class=\\"inline\\" href=\\"' . $base . '#anchor\\">InlineLink<\\/a>',
            $result
        );

        $this->assertContains(
            '<a class=\\"external-inline\\" href=\\"http:\\/\\/google.com#anchor\\">ExternalInlineLink<\\/a>',
            $result
        );

        $this->assertContains(
            '<svg><use xlink:href=\\"#sprite\\"><\\/use><\\/svg>',
            $result,
            'RewriteHashLinksMiddleware should only rewrite anchor hrefs'
        );
    }
}
