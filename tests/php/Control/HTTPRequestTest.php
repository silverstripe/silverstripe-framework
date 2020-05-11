<?php

namespace SilverStripe\Control\Tests;

use ReflectionMethod;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Middleware\TrustedProxyMiddleware;
use SilverStripe\Control\Session;
use SilverStripe\Dev\SapphireTest;

class HTTPRequestTest extends SapphireTest
{
    protected static $fixture_file = null;

    public function testMatch()
    {
        $request = new HTTPRequest("GET", "admin/crm/add");

        /* When a rule matches, but has no variables, array("_matched" => true) is returned. */
        $this->assertEquals(array("_matched" => true), $request->match('admin/crm', true));

        /* Becasue we shifted admin/crm off the stack, just "add" should be remaining */
        $this->assertEquals("add", $request->remaining());

        $this->assertEquals(array("_matched" => true), $request->match('add', true));
    }

    public function testHttpMethodOverrides()
    {
        $request = new HTTPRequest(
            'GET',
            'admin/crm'
        );
        $this->assertTrue(
            $request->isGET(),
            'GET with no method override'
        );

        $request = new HTTPRequest(
            'POST',
            'admin/crm'
        );
        $this->assertTrue(
            $request->isPOST(),
            'POST with no method override'
        );

        $request = new HTTPRequest(
            'GET',
            'admin/crm',
            array('_method' => 'DELETE')
        );
        $this->assertTrue(
            $request->isGET(),
            'GET with invalid POST method override'
        );

        $request = new HTTPRequest(
            'POST',
            'admin/crm',
            array(),
            array('_method' => 'DELETE')
        );

        $this->assertTrue(
            $request->isPOST(),
            '_method override is no longer honored'
        );

        $this->assertFalse(
            $request->isDELETE(),
            'DELETE _method override is not honored'
        );

        $request = new HTTPRequest(
            'POST',
            'admin/crm',
            array(),
            array('_method' => 'put')
        );
        $this->assertFalse(
            $request->isPUT(),
            'PUT _method override is not honored'
        );

        $request = new HTTPRequest(
            'POST',
            'admin/crm',
            array(),
            array('_method' => 'head')
        );
        $this->assertFalse(
            $request->isHEAD(),
            'HEAD _method override is not honored'
        );
    }

    public function detectMethodDataProvider()
    {
        return [
            'Plain POST request' => ['POST', [], 'POST'],
            'Plain GET request' => ['GET', [], 'GET'],
            'Plain DELETE request' => ['DELETE', [], 'DELETE'],
            'Plain PUT request' => ['PUT', [], 'PUT'],
            'Plain HEAD request' => ['HEAD', [], 'HEAD'],

            'Request with GET method override' => ['POST', ['_method' => 'GET'], 'GET'],
            'Request with HEAD method override' => ['POST', ['_method' => 'HEAD'], 'HEAD'],
            'Request with DELETE method override' => ['POST', ['_method' => 'DELETE'], 'DELETE'],
            'Request with PUT method override' => ['POST', ['_method' => 'PUT'], 'PUT'],
            'Request with POST method override' => ['POST', ['_method' => 'POST'], 'POST'],

            'Request with mixed case method override' => ['POST', ['_method' => 'gEt'], 'GET']
        ];
    }

    /**
     * @dataProvider detectMethodDataProvider
     */
    public function testDetectMethod($realMethod, $post, $expected)
    {
        $actual = HTTPRequest::detect_method($realMethod, $post);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testBadDetectMethod()
    {
        HTTPRequest::detect_method('POST', ['_method' => 'Boom']);
    }

    public function setHttpMethodDataProvider()
    {
        return [
            'POST request' => ['POST','POST'],
            'GET request' => ['GET', 'GET'],
            'DELETE request' => ['DELETE', 'DELETE'],
            'PUT request' => ['PUT', 'PUT'],
            'HEAD request' => ['HEAD', 'HEAD'],
            'Mixed case POST' => ['gEt', 'GET'],
        ];
    }

    /**
     * @dataProvider setHttpMethodDataProvider
     */
    public function testSetHttpMethod($method, $expected)
    {
        $request = new HTTPRequest('GET', '/hello');
        $returnedRequest  = $request->setHttpMethod($method);
        $this->assertEquals($expected, $request->httpMethod());
        $this->assertEquals($request, $returnedRequest);
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testBadSetHttpMethod()
    {
        $request = new HTTPRequest('GET', '/hello');
        $request->setHttpMethod('boom');
    }

    public function testRequestVars()
    {
        $getVars = array(
            'first' => 'a',
            'second' => 'b',
        );
        $postVars = array(
            'third' => 'c',
            'fourth' => 'd',
        );
        $requestVars = array(
            'first' => 'a',
            'second' => 'b',
            'third' => 'c',
            'fourth' => 'd',
        );
        $request = new HTTPRequest(
            'POST',
            'admin/crm',
            $getVars,
            $postVars
        );
        $this->assertEquals(
            $requestVars,
            $request->requestVars(),
            'GET parameters should supplement POST parameters'
        );

        $getVars = array(
            'first' => 'a',
            'second' => 'b',
        );
        $postVars = array(
            'first' => 'c',
            'third' => 'd',
        );
        $requestVars = array(
            'first' => 'c',
            'second' => 'b',
            'third' => 'd',
        );
        $request = new HTTPRequest(
            'POST',
            'admin/crm',
            $getVars,
            $postVars
        );
        $this->assertEquals(
            $requestVars,
            $request->requestVars(),
            'POST parameters should override GET parameters'
        );

        $getVars = array(
            'first' => array(
                'first' => 'a',
            ),
            'second' => array(
                'second' => 'b',
            ),
        );
        $postVars = array(
            'first' => array(
                'first' => 'c',
            ),
            'third' => array(
                'third' => 'd',
            ),
        );
        $requestVars = array(
            'first' => array(
                'first' => 'c',
            ),
            'second' => array(
                'second' => 'b',
            ),
            'third' => array(
                'third' => 'd',
            ),
        );
        $request = new HTTPRequest(
            'POST',
            'admin/crm',
            $getVars,
            $postVars
        );
        $this->assertEquals(
            $requestVars,
            $request->requestVars(),
            'Nested POST parameters should override GET parameters'
        );

        $getVars = array(
            'first' => array(
                'first' => 'a',
            ),
            'second' => array(
                'second' => 'b',
            ),
        );
        $postVars = array(
            'first' => array(
                'second' => 'c',
            ),
            'third' => array(
                'third' => 'd',
            ),
        );
        $requestVars = array(
            'first' => array(
                'first' => 'a',
                'second' => 'c',
            ),
            'second' => array(
                'second' => 'b',
            ),
            'third' => array(
                'third' => 'd',
            ),
        );
        $request = new HTTPRequest(
            'POST',
            'admin/crm',
            $getVars,
            $postVars
        );
        $this->assertEquals(
            $requestVars,
            $request->requestVars(),
            'Nested GET parameters should supplement POST parameters'
        );
    }

    public function testIsAjax()
    {
        $req = new HTTPRequest('GET', '/', array('ajax' => 0));
        $this->assertFalse($req->isAjax());

        $req = new HTTPRequest('GET', '/', array('ajax' => 1));
        $this->assertTrue($req->isAjax());

        $req = new HTTPRequest('GET', '/');
        $req->addHeader('X-Requested-With', 'XMLHttpRequest');
        $this->assertTrue($req->isAjax());
    }

    public function testGetURL()
    {
        $req = new HTTPRequest('GET', '/');
        $this->assertEquals('', $req->getURL());

        $req = new HTTPRequest('GET', '/assets/somefile.gif');
        $this->assertEquals('assets/somefile.gif', $req->getURL());

        $req = new HTTPRequest('GET', '/home?test=1');
        $this->assertEquals('home?test=1', $req->getURL(true));
        $this->assertEquals('home', $req->getURL());
    }

    public function testSetIPFromHeaderValue()
    {
        $req = new TrustedProxyMiddleware();
        $reflectionMethod = new ReflectionMethod($req, 'getIPFromHeaderValue');
        $reflectionMethod->setAccessible(true);

        $headers = array(
            '80.79.208.21, 149.126.76.1, 10.51.0.68' => '80.79.208.21',
            '52.19.19.103, 10.51.0.49' => '52.19.19.103',
            '10.51.0.49, 52.19.19.103' => '52.19.19.103',
            '10.51.0.49' => '10.51.0.49',
            '127.0.0.1, 10.51.0.49' => '127.0.0.1',
        );

        foreach ($headers as $header => $ip) {
            $this->assertEquals($ip, $reflectionMethod->invoke($req, $header));
        }
    }

    public function testHasSession()
    {
        $request = new HTTPRequest('GET', '/');
        $this->assertFalse($request->hasSession());

        $request->setSession($this->createMock(Session::class));
        $this->assertTrue($request->hasSession());
    }
}
