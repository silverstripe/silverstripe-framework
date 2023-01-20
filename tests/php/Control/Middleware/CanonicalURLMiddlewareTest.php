<?php

namespace SilverStripe\Control\Tests\Middleware;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\CanonicalURLMiddleware;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\SapphireTest;

class CanonicalURLMiddlewareTest extends SapphireTest
{

    public function testHttpsIsForcedForBasicAuth()
    {
        $middleware = $this->getMockedMiddleware();
        $middleware->expects($this->once())->method('getRedirect');

        $request = new HTTPRequest('GET', '/');
        $request->addHeader('host', 'www.example.com');
        $mockResponse = (new HTTPResponse)
            ->addHeader('WWW-Authenticate', 'basic')
            ->setStatusCode(401);

        $result = $middleware->process($request, function () use ($mockResponse) {
            return $mockResponse;
        });

        $this->assertNotSame($mockResponse, $result, 'New response is created and returned');
        $this->assertEquals(301, $result->getStatusCode(), 'Basic auth responses are redirected');
        $this->assertStringContainsString('https://', $result->getHeader('Location'), 'HTTPS is in the redirect location');
    }

    public function testMiddlewareDelegateIsReturnedWhenBasicAuthRedirectIsDisabled()
    {
        $middleware = $this->getMockedMiddleware();
        $middleware->expects($this->once())->method('getRedirect');
        $middleware->setForceBasicAuthToSSL(false);

        $request = new HTTPRequest('GET', '/');
        $request->addHeader('host', 'www.example.com');
        $mockResponse = (new HTTPResponse)
            ->addHeader('WWW-Authenticate', 'basic')
            ->setStatusCode(401);

        $result = $middleware->process($request, function () use ($mockResponse) {
            return $mockResponse;
        });
        $this->assertSame($mockResponse, $result, 'Response returned verbatim with auto redirect disabled');
    }

    public function testMiddlewareDelegateIsReturnedWhenNoBasicAuthIsPresent()
    {
        $middleware = $this->getMockedMiddleware();
        $middleware->expects($this->once())->method('getRedirect');

        $request = new HTTPRequest('GET', '/');
        $request->addHeader('host', 'www.example.com');
        $mockResponse = (new HTTPResponse)->addHeader('Foo', 'bar');

        $result = $middleware->process($request, function () use ($mockResponse) {
            return $mockResponse;
        });

        $this->assertSame($mockResponse, $result, 'Non basic-auth responses are returned verbatim');
    }

    public function testGetForceBasicAuthToSSL()
    {
        $middleware = $this->getMockedMiddleware();
        $middleware->setForceBasicAuthToSSL(null);

        $middleware->setForceSSL(true);
        $this->assertTrue($middleware->getForceBasicAuthToSSL(), 'Default falls over to forceSSL');

        $middleware->setForceSSL(false);
        $this->assertFalse($middleware->getForceBasicAuthToSSL(), 'Default falls over to forceSSL');

        $middleware->setForceBasicAuthToSSL(true);
        $this->assertTrue($middleware->getForceBasicAuthToSSL(), 'Explicitly set is returned');

        $middleware->setForceBasicAuthToSSL(false);
        $middleware->setForceSSL(true);
        $this->assertFalse($middleware->getForceBasicAuthToSSL(), 'Explicitly set is returned');
    }

    public function testRedirectTrailingSlash()
    {
        $testScenarios = [
            [
                'forceRedirect' => true,
                'addTrailingSlash' => true,
                'requestHasSlash' => true,
            ],
            [
                'forceRedirect' => true,
                'addTrailingSlash' => true,
                'requestHasSlash' => false,
            ],
            [
                'forceRedirect' => true,
                'addTrailingSlash' => false,
                'requestHasSlash' => true,
            ],
            [
                'forceRedirect' => true,
                'addTrailingSlash' => false,
                'requestHasSlash' => false,
            ],
            [
                'forceRedirect' => false,
                'addTrailingSlash' => true,
                'requestHasSlash' => true,
            ],
            [
                'forceRedirect' => false,
                'addTrailingSlash' => true,
                'requestHasSlash' => false,
            ],
            [
                'forceRedirect' => false,
                'addTrailingSlash' => false,
                'requestHasSlash' => true,
            ],
            [
                'forceRedirect' => false,
                'addTrailingSlash' => false,
                'requestHasSlash' => false,
            ],
        ];
        foreach ($testScenarios as $scenario) {
            $forceRedirect = $scenario['forceRedirect'];
            $addTrailingSlash = $scenario['addTrailingSlash'];
            $requestHasSlash = $scenario['requestHasSlash'];

            $middleware = $this->getMockedMiddleware(false);

            $middleware->setEnforceTrailingSlashConfig($forceRedirect);
            Controller::config()->set('add_trailing_slash', $addTrailingSlash);

            $requestSlash = $requestHasSlash ? '/' : '';
            $requestURL = "/about-us{$requestSlash}";

            Environment::setEnv('REQUEST_URI', $requestURL);
            $request = new HTTPRequest('GET', $requestURL);
            $request->setScheme('https');
            $request->addHeader('host', 'www.example.com');
            $mockResponse = (new HTTPResponse)
                ->setStatusCode(200);

            $result = $middleware->process($request, function () use ($mockResponse) {
                return $mockResponse;
            });

            $noRedirect = !$forceRedirect || ($addTrailingSlash && $requestHasSlash) || (!$addTrailingSlash && !$requestHasSlash);
            if ($noRedirect) {
                $this->assertNull($result->getHeader('Location'), 'No location header should be added');
                $this->assertEquals(200, $result->getStatusCode(), 'No redirection should be made');
            } else {
                $this->assertEquals(301, $result->getStatusCode(), 'Responses should be redirected to include/omit trailing slash');
                if ($addTrailingSlash) {
                    $this->assertStringEndsWith('/', $result->getHeader('Location'), 'Trailing slash should be added');
                } else {
                    $this->assertStringEndsNotWith('/', $result->getHeader('Location'), 'Trailing slash should be removed');
                }
            }
        }
    }

    private function getMockedMiddleware($mockGetRedirect = true): CanonicalURLMiddleware
    {
        $mockedMethods = ['isEnabled'];
        if ($mockGetRedirect) {
            $mockedMethods[] = 'getRedirect';
        }

        /** @var CanonicalURLMiddleware $middleware */
        $middleware = $this->getMockBuilder(CanonicalURLMiddleware::class)
            ->setMethods($mockedMethods)
            ->getMock();

        $middleware->expects($this->any())->method('isEnabled')->willReturn(true);
        if ($mockGetRedirect) {
            $middleware->expects($this->any())->method('getRedirect')->willReturn(false);
        }

        $middleware->setForceBasicAuthToSSL(true);

        return $middleware;
    }
}
