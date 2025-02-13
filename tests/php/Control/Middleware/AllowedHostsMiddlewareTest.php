<?php

namespace SilverStripe\Control\Tests\Middleware;

use InvalidArgumentException;
use ReflectionClass;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\Middleware\AllowedHostsMiddleware;

class AllowedHostsMiddlewareTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function provideProcess(): array
    {
        return [
            'cli allow all' => [
                'allowedHosts' => [],
                'isCli' => true,
                'allowed' => true,
            ],
            'cli ignores config' => [
                'allowedHosts' => ['example.org'],
                'isCli' => true,
                'allowed' => true,
            ],
            'HTTP allow all' => [
                'allowedHosts' => [],
                'isCli' => false,
                'allowed' => true,
            ],
            'HTTP allow all explicit' => [
                'allowedHosts' => ['*'],
                'isCli' => false,
                'allowed' => true,
            ],
            'HTTP allow explicit host' => [
                'allowedHosts' => ['www.example.com'],
                'isCli' => false,
                'allowed' => true,
            ],
            'HTTP allow explicit host multiple values' => [
                'allowedHosts' => ['example.com', 'example.org', 'ww.example.org', 'www.example.com'],
                'isCli' => false,
                'allowed' => true,
            ],
            'HTTP allow explicit host string' => [
                'allowedHosts' => 'example.com,example.org,ww.example.org,www.example.com',
                'isCli' => false,
                'allowed' => true,
            ],
            'HTTP host mismatch (missing subdomain)' => [
                'allowedHosts' => ['example.com'],
                'isCli' => false,
                'allowed' => false,
            ],
            'HTTP host mismatch (different tld)' => [
                'allowedHosts' => ['example.org'],
                'isCli' => false,
                'allowed' => false,
            ],
            'HTTP host mismatch multiple' => [
                'allowedHosts' => ['example.org', 'www.example.org', 'example.com'],
                'isCli' => false,
                'allowed' => false,
            ],
            'HTTP host mismatch string' => [
                'allowedHosts' => 'example.org,www.example.org,example.com',
                'isCli' => false,
                'allowed' => false,
            ],
        ];
    }

    /**
     * @dataProvider provideProcess
     */
    public function testProcess(string|array $allowedHosts, bool $isCli, bool $allowed): void
    {
        $reflectionEnvironment = new ReflectionClass(Environment::class);
        $origIsCli = $reflectionEnvironment->getStaticPropertyValue('isCliOverride');
        $reflectionEnvironment->setStaticPropertyValue('isCliOverride', $isCli);

        try {
            $middleware = new AllowedHostsMiddleware();
            $middleware->setAllowedHosts($allowedHosts);
            $request = new HTTPRequest('GET', '/');
            $request->addHeader('host', 'www.example.com');
            $defaultResponse = new HTTPResponse();

            $result = $middleware->process($request, function () use ($defaultResponse) {
                return $defaultResponse;
            });

            if ($allowed) {
                $this->assertSame(200, $result->getStatusCode());
                $this->assertSame($defaultResponse, $result);
            } else {
                $this->assertSame(400, $result->getStatusCode());
                $this->assertNotSame($defaultResponse, $result);
            }
        } finally {
            $reflectionEnvironment->setStaticPropertyValue('isCliOverride', $origIsCli);
        }
    }

    public function testProcessInvalidConfig(): void
    {
        $middleware = new AllowedHostsMiddleware();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The wildcard "*" cannot be used in conjunction with actual hosts.');

        $middleware->setAllowedHosts(['*', 'www.example.com']);
    }
}
