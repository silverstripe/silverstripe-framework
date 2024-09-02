<?php

namespace SilverStripe\Control\Tests\Middleware;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\CanonicalURLMiddleware;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\Director;

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

    public function provideRedirectTrailingSlash()
    {
        $testScenarios = [];
        foreach ([true, false] as $forceRedirect) {
            foreach ([true, false] as $addTrailingSlash) {
                foreach ([true, false] as $requestHasSlash) {
                    $testScenarios[] = [
                        $forceRedirect,
                        $addTrailingSlash,
                        $requestHasSlash,
                    ];
                }
            }
        }
        return $testScenarios;
    }

    /**
     * @dataProvider provideRedirectTrailingSlash
     */
    public function testRedirectTrailingSlash(bool $forceRedirect, bool $addTrailingSlash, bool $requestHasSlash)
    {
        Controller::config()->set('add_trailing_slash', $addTrailingSlash);

        $noRedirect = !$forceRedirect || ($addTrailingSlash && $requestHasSlash) || (!$addTrailingSlash && !$requestHasSlash);
        $middleware = $this->getMockedMiddleware(false);
        $middleware->setEnforceTrailingSlashConfig($forceRedirect);

        $requestSlash = $requestHasSlash ? '/' : '';
        $requestURL = "/about-us{$requestSlash}";

        $this->performRedirectTest($requestURL, $middleware, !$noRedirect, $addTrailingSlash);
    }

    private function performRedirectTest(string $requestURL, CanonicalURLMiddleware $middleware, bool $shouldRedirect, bool $addTrailingSlash)
    {
        Director::config()->set('alternate_base_url', 'https://www.example.com');
        Environment::setEnv('REQUEST_URI', $requestURL);
        $request = new HTTPRequest('GET', $requestURL);
        $request->setScheme('https');
        $request->addHeader('host', 'www.example.com');
        $mockResponse = (new HTTPResponse)
            ->setStatusCode(200);

        $result = $middleware->process($request, function () use ($mockResponse) {
            return $mockResponse;
        });

        if (!$shouldRedirect) {
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

    public function provideRedirectTrailingSlashIgnorePaths()
    {
        return [
            [
                'addTrailingSlash' => false,
                'requestHasSlash' => false,
            ],
            [
                'addTrailingSlash' => false,
                'requestHasSlash' => true,
            ],
            [
                'addTrailingSlash' => true,
                'requestHasSlash' => true,
            ],
            [
                'addTrailingSlash' => true,
                'requestHasSlash' => false,
            ],
        ];
    }

    /**
     * @dataProvider provideRedirectTrailingSlashIgnorePaths
     */
    public function testRedirectTrailingSlashIgnorePaths(bool $addTrailingSlash, bool $requestHasSlash)
    {
        Controller::config()->set('add_trailing_slash', $addTrailingSlash);

        $middleware = $this->getMockedMiddleware(false);
        $middleware->setEnforceTrailingSlashConfig(true);

        $requestSlash = $requestHasSlash ? '/' : '';
        $noRedirectPaths = [
            "/admin{$requestSlash}",
            "/dev/tasks/my-task{$requestSlash}",
        ];
        $allowRedirectPaths = [
            "/administration{$requestSlash}",
            "/administration/more-path{$requestSlash}",
        ];

        foreach ($noRedirectPaths as $path) {
            $this->performRedirectTest($path, $middleware, false, $addTrailingSlash);
        }
        foreach ($allowRedirectPaths as $path) {
            $this->performRedirectTest($path, $middleware, $addTrailingSlash !== $requestHasSlash, $addTrailingSlash);
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
