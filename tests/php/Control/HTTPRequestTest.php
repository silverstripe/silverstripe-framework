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
        $this->assertEquals(["_matched" => true], $request->match('admin/crm', true));

        /* Becasue we shifted admin/crm off the stack, just "add" should be remaining */
        $this->assertEquals("add", $request->remaining());

        $this->assertEquals(["_matched" => true], $request->match('add', true));
    }

    /**
     * @useDatabase false
     */
    public function testWildCardMatch()
    {
        $request = new HTTPRequest('GET', 'admin/crm/test');
        $this->assertEquals(['$1' => 'crm', '$2' => 'test'], $request->match('admin/$@', true));
        $this->assertTrue($request->allParsed());

        $request = new HTTPRequest('GET', 'admin/crm/test');
        $this->assertEquals(['_matched' => true], $request->match('admin/$*', true));
        $this->assertTrue($request->allParsed());
        $this->assertEquals('crm/test', $request->remaining());

        $request = new HTTPRequest('GET', 'admin/crm/test/part1/part2');
        $this->assertEquals(['Action' => 'crm', '$1' => 'test', '$2' => 'part1', '$3' => 'part2'], $request->match('admin/$Action/$@', true));
        $this->assertTrue($request->allParsed());

        $request = new HTTPRequest('GET', 'admin/crm/test/part1/part2');
        $this->assertEquals(['Action' => 'crm'], $request->match('admin/$Action/$*', true));
        $this->assertTrue($request->allParsed());
        $this->assertEquals('test/part1/part2', $request->remaining());
    }

    /**
     * This test just asserts a warning is given if there is more than one wildcard parameter. Note that this isn't an
     * enforcement of an API and we an add new behaviour in the future to allow many wildcard params if we want to
     *
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testWildCardWithFurtherParams()
    {
        $request = new HTTPRequest('GET', 'admin/crm/test');
        // all parameters after the first wildcard parameter are ignored
        $request->match('admin/$Action/$@/$Other/$*', true);
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
            ['_method' => 'DELETE']
        );
        $this->assertTrue(
            $request->isGET(),
            'GET with invalid POST method override'
        );

        $request = new HTTPRequest(
            'POST',
            'admin/crm',
            [],
            ['_method' => 'DELETE']
        );
        $this->assertTrue(
            $request->isDELETE(),
            'POST with valid method override to DELETE'
        );

        $request = new HTTPRequest(
            'POST',
            'admin/crm',
            [],
            ['_method' => 'put']
        );
        $this->assertTrue(
            $request->isPUT(),
            'POST with valid method override to PUT'
        );

        $request = new HTTPRequest(
            'POST',
            'admin/crm',
            [],
            ['_method' => 'head']
        );
        $this->assertTrue(
            $request->isHEAD(),
            'POST with valid method override to HEAD '
        );

        $request = new HTTPRequest(
            'POST',
            'admin/crm',
            [],
            ['_method' => 'head']
        );
        $this->assertTrue(
            $request->isHEAD(),
            'POST with valid method override to HEAD'
        );

        $request = new HTTPRequest(
            'POST',
            'admin/crm',
            ['_method' => 'head']
        );
        $this->assertTrue(
            $request->isPOST(),
            'POST with invalid method override by GET parameters to HEAD'
        );
    }

    public function testRequestVars()
    {
        $getVars = [
            'first' => 'a',
            'second' => 'b',
        ];
        $postVars = [
            'third' => 'c',
            'fourth' => 'd',
        ];
        $requestVars = [
            'first' => 'a',
            'second' => 'b',
            'third' => 'c',
            'fourth' => 'd',
        ];
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

        $getVars = [
            'first' => 'a',
            'second' => 'b',
        ];
        $postVars = [
            'first' => 'c',
            'third' => 'd',
        ];
        $requestVars = [
            'first' => 'c',
            'second' => 'b',
            'third' => 'd',
        ];
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

        $getVars = [
            'first' => [
                'first' => 'a',
            ],
            'second' => [
                'second' => 'b',
            ],
        ];
        $postVars = [
            'first' => [
                'first' => 'c',
            ],
            'third' => [
                'third' => 'd',
            ],
        ];
        $requestVars = [
            'first' => [
                'first' => 'c',
            ],
            'second' => [
                'second' => 'b',
            ],
            'third' => [
                'third' => 'd',
            ],
        ];
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

        $getVars = [
            'first' => [
                'first' => 'a',
            ],
            'second' => [
                'second' => 'b',
            ],
        ];
        $postVars = [
            'first' => [
                'second' => 'c',
            ],
            'third' => [
                'third' => 'd',
            ],
        ];
        $requestVars = [
            'first' => [
                'first' => 'a',
                'second' => 'c',
            ],
            'second' => [
                'second' => 'b',
            ],
            'third' => [
                'third' => 'd',
            ],
        ];
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
        $req = new HTTPRequest('GET', '/', ['ajax' => 0]);
        $this->assertFalse($req->isAjax());

        $req = new HTTPRequest('GET', '/', ['ajax' => 1]);
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

        $headers = [
            '80.79.208.21, 149.126.76.1, 10.51.0.68' => '80.79.208.21',
            '52.19.19.103, 10.51.0.49' => '52.19.19.103',
            '10.51.0.49, 52.19.19.103' => '52.19.19.103',
            '10.51.0.49' => '10.51.0.49',
            '127.0.0.1, 10.51.0.49' => '127.0.0.1',
        ];

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
