<?php

namespace SilverStripe\Control\Tests\Middleware;

use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\ConfirmationMiddleware;
use SilverStripe\Control\Middleware\ConfirmationMiddleware\Url;
use SilverStripe\Control\Tests\HttpRequestMockBuilder;
use SilverStripe\Dev\SapphireTest;

class ConfirmationMiddlewareTest extends SapphireTest
{
    use HttpRequestMockBuilder;

    public function testBypass()
    {
        $request = $this->buildRequestMock('dev/build', ['flush' => 'all']);

        $middleware = new ConfirmationMiddleware(new Url('dev/build'));
        $this->assertFalse($middleware->canBypass($request));

        $middleware->setBypasses([new Url('no-match')]);
        $this->assertFalse($middleware->canBypass($request));

        $middleware->setBypasses([new Url('dev/build')]);
        $this->assertTrue($middleware->canBypass($request));
    }

    public function testConfirmationItems()
    {
        $request = $this->buildRequestMock('dev/build', ['flush' => 'all']);

        $middleware = new ConfirmationMiddleware(
            new Url('dev/build'),
            new Url('dev/build', null, ['flush' => null])
        );

        $items = $middleware->getConfirmationItems($request);

        $this->assertCount(2, $items);
    }

    public function testProcess()
    {
        $request = $this->buildRequestMock('dev/build', ['flush' => 'all']);

        // Testing the middleware does not do anything if rules do not apply
        $middleware = new ConfirmationMiddleware(new Url('no-match'));
        $next = false;
        $middleware->process(
            $request,
            static function () use (&$next) {
                $next = true;
            }
        );
        $this->assertTrue($next);

        // Test for a redirection when rules hit the request
        $middleware = new ConfirmationMiddleware(new Url('dev/build'));
        $next = false;
        $response = $middleware->process(
            $request,
            static function () use (&$next) {
                $next = true;
            }
        );
        $this->assertFalse($next);
        $this->assertInstanceOf(HTTPResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/dev/confirm/middleware', $response->getHeader('location'));

        // Test bypasses have more priority than rules
        $middleware->setBypasses([new Url('dev/build')]);
        $next = false;
        $response = $middleware->process(
            $request,
            static function () use (&$next) {
                $next = true;
            }
        );
    }
}
