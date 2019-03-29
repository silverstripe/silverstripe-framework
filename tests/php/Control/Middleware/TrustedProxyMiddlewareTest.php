<?php

namespace SilverStripe\Control\Tests\Middleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Middleware\TrustedProxyMiddleware;
use SilverStripe\Dev\SapphireTest;

class TrustedProxyMiddlewareTest extends SapphireTest
{
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

    public function testFiltersHostHeader()
    {
        $m = new TrustedProxyMiddleware();
        $m->setTrustedProxyIPs('*');
        $request = (new HTTPRequest('GET', ''))
            ->addHeader('X-Forwarded-Host', '">invalid-test.com');

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
