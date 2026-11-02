<?php

namespace SilverStripe\Control\Tests\Middleware;

use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\DevelopmentAdminConfirmationMiddleware;
use SilverStripe\Control\Tests\HttpRequestMockBuilder;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Kernel;
use SilverStripe\Dev\DevelopmentAdmin;
use SilverStripe\Dev\SapphireTest;

class DevelopmentAdminConfirmationMiddlewareTest extends SapphireTest
{
    use HttpRequestMockBuilder;

    public static function provideHasAccess(): array
    {
        return [
            'mount point with access' => ['BUILDTASK_CAN_RUN', 'dev/tasks', true],
            'nested task with access' => ['BUILDTASK_CAN_RUN', 'dev/tasks/SomeTask', true],
            'twice nested task with access' => ['BUILDTASK_CAN_RUN', 'dev/tasks/queue/SomeTask', true],
            'unregistered sibling does not inherit' => ['BUILDTASK_CAN_RUN', 'dev/tasksfoo', false],
            'mount point without access' => ['SOME_UNRELATED_PERMISSION', 'dev/tasks', false],
            'nested task without access' => ['SOME_UNRELATED_PERMISSION', 'dev/tasks/SomeTask', false],
        ];
    }

    #[DataProvider('provideHasAccess')]
    public function testHasAccess(string $permission, string $url, bool $expected): void
    {
        $kernel = Injector::inst()->get(Kernel::class);
        $env = $kernel->getEnvironment();
        $kernel->setEnvironment(Kernel::LIVE);
        DevelopmentAdmin::config()->set('allow_all_cli', false);
        try {
            $this->logInWithPermission($permission);
            $middleware = Injector::inst()->get('DevUrlsConfirmationMiddleware');
            $this->assertInstanceOf(DevelopmentAdminConfirmationMiddleware::class, $middleware);
            $request = $this->buildRequestMock($url);
            $request->method('remaining')->willReturn(substr($url, strlen('dev/')));
            $this->assertSame($expected, $middleware->hasAccess($request));
        } finally {
            $kernel->setEnvironment($env);
        }
    }

    public static function provideProcessRedirectsToConfirmation(): array
    {
        return [
            'mount point' => ['dev/tasks'],
            'nested task' => ['dev/tasks/SomeTask'],
        ];
    }

    #[DataProvider('provideProcessRedirectsToConfirmation')]
    public function testProcessRedirectsToConfirmation(string $url): void
    {
        $kernel = Injector::inst()->get(Kernel::class);
        $env = $kernel->getEnvironment();
        $kernel->setEnvironment(Kernel::LIVE);
        DevelopmentAdmin::config()->set('allow_all_cli', false);
        try {
            $this->logInWithPermission('BUILDTASK_CAN_RUN');
            $middleware = Injector::inst()->get('DevUrlsConfirmationMiddleware');
            $request = $this->buildRequestMock($url);
            $request->method('remaining')->willReturn(substr($url, strlen('dev/')));
            $delegateCalled = false;
            $response = $middleware->process($request, function () use (&$delegateCalled) {
                $delegateCalled = true;
                return new HTTPResponse();
            });
            $this->assertFalse($delegateCalled);
            $this->assertInstanceOf(HTTPResponse::class, $response);
            $this->assertSame(302, $response->getStatusCode());
            $this->assertStringContainsString('dev/confirm', $response->getHeader('location'));
        } finally {
            $kernel->setEnvironment($env);
        }
    }
}
