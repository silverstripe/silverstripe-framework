<?php

namespace SilverStripe\Control\Tests\Middleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Middleware\TrustedProxyMiddleware;
use SilverStripe\Dev\SapphireTest;

class TrustedProxyMiddlewareTest extends SapphireTest
{
    public function goodHostnames()
    {
        return [
            ['127.0.0.1'],
            ['test1.com'],
            ['localhost'],
            ['www.test.com'],
            ['www.test.com'],
            ['2001:0db8:85a3:0000:0000:8a2e:0370:7334'],
            ['localhost:8080'],
            ['127.0.0.1:8080'],
            ['vAlId-hOsT'],
            ['www.monkey.intranet.silverstripe.com'],
            ['はじめよう.みんな'],
        ];
    }

    public function badHostnames()
    {
        return [
            ['">invalid-test.com'],
            ['invalid_host'],
            ['invalid host'],
            ['invalid-char.com#$!#^%$&^&'],
            ['www.invalid-char.com/some-path'],
            ['https://www.invalid-char.com'],
            ['-invalid-host.com'],
        ];
    }

    public function testIgnoresHostHeaderOnUntrustedIP()
    {
        $m = new TrustedProxyMiddleware();
        $request = (new HTTPRequest('GET', ''))
            ->addHeader('X-Forwarded-Host', 'test1.com');

        $response = $m->process($request, $this->getIdentityFn());
        $this->assertEquals('', $response->getHeader('Host'));
    }

    public function testSetsFirstHostHeader()
    {
        $m = new TrustedProxyMiddleware();
        $m->setTrustedProxyIPs('*');
        $request = (new HTTPRequest('GET', ''))
            ->addHeader('X-Forwarded-Host', 'test1.com,test2.com');

        $response = $m->process($request, $this->getIdentityFn());
        $this->assertEquals('test1.com', $response->getHeader('Host'));
    }

    /**
     * @dataProvider goodHostnames
     */
    public function testSetSepecificHostheader($host)
    {
        $m = new TrustedProxyMiddleware();
        $m->setTrustedProxyIPs('*');
        $request = (new HTTPRequest('GET', ''))
            ->addHeader('X-Forwarded-Host', $host);

        $response = $m->process($request, $this->getIdentityFn());
        $this->assertEquals($host, $response->getHeader('Host'));
    }

    /**
     * @dataProvider badHostnames
     */
    public function testFiltersHostHeader($host)
    {
        $m = new TrustedProxyMiddleware();
        $m->setTrustedProxyIPs('*');
        $request = (new HTTPRequest('GET', ''))
            ->addHeader('X-Forwarded-Host', '$host');

        $response = $m->process($request, $this->getIdentityFn());
        $this->assertEquals('', $response->getHeader('Host'));
    }

    protected function getIdentityFn()
    {
        return function ($response) {
            return $response;
        };
    }
}
