<?php declare(strict_types = 1);

namespace SilverStripe\Control\Tests\Middleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\CanonicalURLMiddleware;
use SilverStripe\Dev\SapphireTest;

class CanonicalURLMiddlewareTest extends SapphireTest
{
    /**
     * Stub middleware class, partially mocked
     *
     * @var CanonicalURLMiddleware
     */
    protected $middleware;

    protected function setUp()
    {
        parent::setUp();

        /** @var CanonicalURLMiddleware $middleware */
        $this->middleware = $this->getMockBuilder(CanonicalURLMiddleware::class)
            ->setMethods(['getRedirect'])
            ->getMock();

        $this->middleware->expects($this->any())->method('getRedirect')->willReturn(false);

        $this->middleware->setForceBasicAuthToSSL(true);
    }

    public function testHttpsIsForcedForBasicAuth()
    {
        $this->middleware->expects($this->once())->method('getRedirect');

        $request = new HTTPRequest('GET', '/');
        $mockResponse = (new HTTPResponse)
            ->addHeader('WWW-Authenticate', 'basic')
            ->setStatusCode(401);

        $result = $this->middleware->process($request, function () use ($mockResponse) {
            return $mockResponse;
        });

        $this->assertNotSame($mockResponse, $result, 'New response is created and returned');
        $this->assertEquals(301, $result->getStatusCode(), 'Basic auth responses are redirected');
        $this->assertContains('https://', $result->getHeader('Location'), 'HTTPS is in the redirect location');
    }

    public function testMiddlewareDelegateIsReturnedWhenBasicAuthRedirectIsDisabled()
    {
        $this->middleware->expects($this->once())->method('getRedirect');
        $this->middleware->setForceBasicAuthToSSL(false);

        $request = new HTTPRequest('GET', '/');
        $mockResponse = (new HTTPResponse)
            ->addHeader('WWW-Authenticate', 'basic')
            ->setStatusCode(401);

        $result = $this->middleware->process($request, function () use ($mockResponse) {
            return $mockResponse;
        });
        $this->assertSame($mockResponse, $result, 'Response returned verbatim with auto redirect disabled');
    }

    public function testMiddlewareDelegateIsReturnedWhenNoBasicAuthIsPresent()
    {
        $this->middleware->expects($this->once())->method('getRedirect');

        $request = new HTTPRequest('GET', '/');
        $mockResponse = (new HTTPResponse)->addHeader('Foo', 'bar');

        $result = $this->middleware->process($request, function () use ($mockResponse) {
            return $mockResponse;
        });

        $this->assertSame($mockResponse, $result, 'Non basic-auth responses are returned verbatim');
    }

    public function testGetForceBasicAuthToSSL()
    {
        $this->middleware->setForceBasicAuthToSSL(null);

        $this->middleware->setForceSSL(true);
        $this->assertTrue($this->middleware->getForceBasicAuthToSSL(), 'Default falls over to forceSSL');

        $this->middleware->setForceSSL(false);
        $this->assertFalse($this->middleware->getForceBasicAuthToSSL(), 'Default falls over to forceSSL');

        $this->middleware->setForceBasicAuthToSSL(true);
        $this->assertTrue($this->middleware->getForceBasicAuthToSSL(), 'Explicitly set is returned');

        $this->middleware->setForceBasicAuthToSSL(false);
        $this->middleware->setForceSSL(true);
        $this->assertFalse($this->middleware->getForceBasicAuthToSSL(), 'Explicitly set is returned');
    }
}
